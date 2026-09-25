<?php

namespace Tests\Feature;

use App\Livewire\Admin\Messages\MessageBadge;
use App\Livewire\Admin\Messages\MessageInbox;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AdminMessageInboxTest extends TestCase
{
    use RefreshDatabase;

    public function test_inbox_section_requires_manage_messages(): void
    {
        $regular = User::factory()->create([
            'access_level' => User::ACCESS_LEVEL_REGULAR,
        ]);

        Livewire::actingAs($regular)
            ->test(MessageInbox::class)
            ->assertForbidden();
    }

    public function test_manager_sees_threads_and_selection_marks_them_read(): void
    {
        $manager = $this->manager();
        $person = $this->memberPerson();
        $conversation = $this->createConversation($person);

        Livewire::actingAs($manager)
            ->test(MessageInbox::class)
            ->assertSee('موضوع آزمایشی')
            ->call('selectThread', $conversation->id)
            ->assertSet('selectedThreadId', $conversation->id);

        $this->assertDatabaseMissing('messages', [
            'conversation_id' => $conversation->id,
            'is_from_staff' => false,
            'staff_read_at' => null,
        ]);
    }

    public function test_manager_reply_sets_answered_status(): void
    {
        $manager = $this->manager();
        $person = $this->memberPerson();
        $conversation = $this->createConversation($person);

        Livewire::actingAs($manager)
            ->test(MessageInbox::class)
            ->call('selectThread', $conversation->id)
            ->set('replyBody', 'پاسخ مدیریت مرکز')
            ->call('sendReply')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'is_from_staff' => true,
            'body' => 'پاسخ مدیریت مرکز',
        ]);

        $this->assertDatabaseHas('conversations', [
            'id' => $conversation->id,
            'status' => Conversation::STATUS_ANSWERED,
        ]);
    }

    public function test_reply_is_rejected_for_closed_thread(): void
    {
        $manager = $this->manager();
        $person = $this->memberPerson();
        $conversation = $this->createConversation($person);

        $conversation->update(['status' => Conversation::STATUS_CLOSED]);

        Livewire::actingAs($manager)
            ->test(MessageInbox::class)
            ->call('selectThread', $conversation->id)
            ->set('replyBody', 'پاسخ پس از بستن')
            ->call('sendReply')
            ->assertHasErrors('replyBody');

        $this->assertDatabaseCount('messages', 1);
    }

    public function test_close_reopen_and_delete_thread(): void
    {
        $manager = $this->manager();
        $person = $this->memberPerson();
        $conversation = $this->createConversation($person);

        Livewire::actingAs($manager)
            ->test(MessageInbox::class)
            ->call('closeThread', $conversation->id);

        $this->assertDatabaseHas('conversations', [
            'id' => $conversation->id,
            'status' => Conversation::STATUS_CLOSED,
        ]);

        Livewire::actingAs($manager)
            ->test(MessageInbox::class)
            ->call('reopenThread', $conversation->id);

        $this->assertDatabaseHas('conversations', [
            'id' => $conversation->id,
            'status' => Conversation::STATUS_PENDING,
        ]);

        Livewire::actingAs($manager)
            ->test(MessageInbox::class)
            ->call('deleteThread', $conversation->id);

        $this->assertDatabaseMissing('conversations', ['id' => $conversation->id]);
        $this->assertDatabaseCount('messages', 0);
    }

    public function test_badge_counts_unread_staff_messages(): void
    {
        $this->manager();
        $person = $this->memberPerson();
        $conversation = $this->createConversation($person);

        Livewire::test(MessageBadge::class)
            ->assertSee('۱');

        Message::query()->where('conversation_id', $conversation->id)->update(['staff_read_at' => now()]);

        Livewire::test(MessageBadge::class)
            ->assertDontSee('۱');
    }

    public function test_attachment_download_is_restricted_to_owner_and_staff(): void
    {
        $manager = $this->manager();
        $person = $this->memberPerson();
        $stranger = $this->memberPerson('سارا', 'موسوی');
        $conversation = $this->createConversation($person);

        Storage::disk('local')->put('message-attachments/'.$conversation->id.'/test-image.png', 'fake-png-content');

        $attachment = MessageAttachment::query()->create([
            'message_id' => Message::query()->where('conversation_id', $conversation->id)->first()->id,
            'original_name' => 'test-image.png',
            'path' => 'message-attachments/'.$conversation->id.'/test-image.png',
            'mime' => 'image/png',
            'size' => 17,
        ]);

        $url = route('messages.attachments.download', $attachment);

        // کاربر ناشناس دسترسی ندارد
        $this->get($url)->assertForbidden();

        // عضو غریبه دسترسی ندارد
        $this->actingAs($stranger, 'member')->get($url)->assertForbidden();

        // فرستندهٔ خود گفتگو از گارد member
        $this->actingAs($person, 'member')->get($url)->assertOk();

        // مدیریت مجاز است
        $this->actingAs($manager, 'web')->get($url)->assertOk();

        // فایل واقعی روی دیسک با rollback تراکنش پاک نمی‌شود.
        Storage::disk('local')->deleteDirectory('message-attachments/'.$conversation->id);
    }

    private function manager(): User
    {
        return User::factory()->create([
            'first_name' => 'مدیر',
            'last_name' => 'مرکز',
            'access_level' => User::ACCESS_LEVEL_MANAGER,
            'is_admin' => true,
        ]);
    }

    private function memberPerson(string $firstName = 'زهرا', string $lastName = 'کریمی'): Person
    {
        return Person::query()->create([
            'person_code' => Person::generateUniqueCode(),
            'national_id' => (string) random_int(1000000000, 9999999999),
            'first_name' => $firstName,
            'last_name' => $lastName,
        ]);
    }

    private function createConversation(Person $sender, string $subject = 'موضوع آزمایشی'): Conversation
    {
        $conversation = Conversation::query()->create([
            'subject' => $subject,
            'status' => Conversation::STATUS_PENDING,
            'sender_type' => $sender->getMorphClass(),
            'sender_id' => $sender->getKey(),
            'sender_name' => trim($sender->first_name.' '.$sender->last_name),
            'sender_role' => Conversation::SENDER_ROLE_MEMBER,
            'sender_code' => $sender->person_code,
            'last_message_at' => now(),
        ]);

        Message::query()->create([
            'conversation_id' => $conversation->id,
            'sender_type' => $sender->getMorphClass(),
            'sender_id' => $sender->getKey(),
            'sender_name' => trim($sender->first_name.' '.$sender->last_name),
            'is_from_staff' => false,
            'body' => 'متن اولین پیام',
        ]);

        return $conversation;
    }
}
