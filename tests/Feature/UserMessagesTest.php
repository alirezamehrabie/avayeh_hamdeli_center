<?php

namespace Tests\Feature;

use App\Livewire\ChildSupporters\ContactManagement as SupporterContactManagement;
use App\Livewire\Members\ContactManagement as MemberContactManagement;
use App\Models\Conversation;
use App\Models\ManagerNotificationPreference;
use App\Models\Message;
use App\Models\Person;
use App\Models\SponsorProfile;
use App\Models\User;
use App\Support\Notifications\NotificationEventRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class UserMessagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::clear('member-conversations:1');
    }

    public function test_supporter_can_create_thread_and_manager_gets_notified(): void
    {
        $manager = $this->manager();
        $supporter = $this->supporter();

        ManagerNotificationPreference::query()->create([
            'manager_id' => $manager->id,
            'event_key' => NotificationEventRegistry::EVENT_MESSAGE_RECEIVED,
            'enabled' => true,
            'target_type' => NotificationEventRegistry::TARGET_ALL,
        ]);

        Livewire::actingAs($supporter)
            ->test(SupporterContactManagement::class)
            ->set('newSubject', 'درخواست هماهنگی ملاقات')
            ->set('newBody', 'سلام، لطفا برای ملاقات با کودک تحت حمایت هماهنگی کنید.')
            ->call('createThread')
            ->assertHasNoErrors()
            ->assertSet('selectedThreadId', fn ($value) => $value !== null);

        $this->assertDatabaseHas('conversations', [
            'subject' => 'درخواست هماهنگی ملاقات',
            'status' => Conversation::STATUS_PENDING,
            'sender_type' => 'user',
            'sender_id' => $supporter->id,
            'sender_role' => Conversation::SENDER_ROLE_CHILD_SUPPORTER,
            'sender_code' => 'CS-0001',
        ]);

        $this->assertDatabaseHas('messages', [
            'is_from_staff' => false,
            'body' => 'سلام، لطفا برای ملاقات با کودک تحت حمایت هماهنگی کنید.',
        ]);

        $this->assertDatabaseHas('manager_notifications', [
            'manager_id' => $manager->id,
            'event_key' => NotificationEventRegistry::EVENT_MESSAGE_RECEIVED,
            'actor_id' => $supporter->id,
            'title' => 'پیام جدید: درخواست هماهنگی ملاقات',
        ]);
    }

    public function test_member_can_create_thread_without_user_actor(): void
    {
        $this->manager();
        $person = $this->memberPerson();

        Livewire::actingAs($person, 'member')
            ->test(MemberContactManagement::class)
            ->set('newSubject', 'پیام آزمایشی عضو')
            ->set('newBody', 'متن پیام عضو برای مدیریت.')
            ->call('createThread')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('conversations', [
            'subject' => 'پیام آزمایشی عضو',
            'sender_type' => 'person',
            'sender_id' => $person->id,
            'sender_role' => Conversation::SENDER_ROLE_MEMBER,
            'sender_code' => $person->person_code,
        ]);

        $this->assertDatabaseCount('manager_notifications', 0);
    }

    public function test_member_cannot_open_other_members_thread(): void
    {
        $person = $this->memberPerson();
        $otherPerson = $this->memberPerson('نرگس', 'احمدی');
        $conversation = $this->createConversation($otherPerson);

        Livewire::actingAs($person, 'member')
            ->test(MemberContactManagement::class)
            ->call('openThread', $conversation->id)
            ->assertSet('selectedThreadId', null);
    }

    public function test_member_reply_updates_thread(): void
    {
        $person = $this->memberPerson();
        $conversation = $this->createConversation($person);

        Message::query()->create([
            'conversation_id' => $conversation->id,
            'sender_type' => 'user',
            'sender_id' => $this->manager()->id,
            'is_from_staff' => true,
            'body' => 'پاسخ مدیریت مرکز',
        ]);

        Livewire::actingAs($person, 'member')
            ->test(MemberContactManagement::class)
            ->call('openThread', $conversation->id)
            ->set('replyBody', 'ممنون از پاسخ شما.')
            ->call('sendReply')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'is_from_staff' => false,
            'body' => 'ممنون از پاسخ شما.',
        ]);

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'is_from_staff' => true,
            'body' => 'پاسخ مدیریت مرکز',
        ]);

        $this->assertDatabaseMissing('messages', [
            'conversation_id' => $conversation->id,
            'is_from_staff' => true,
            'member_read_at' => null,
        ]);
    }

    public function test_thread_creation_is_rate_limited(): void
    {
        $person = $this->memberPerson();

        for ($i = 1; $i <= 3; $i++) {
            Livewire::actingAs($person, 'member')
                ->test(MemberContactManagement::class)
                ->set('newSubject', 'موضوع شماره '.$i)
                ->set('newBody', 'متن پیام شماره '.$i)
                ->call('createThread')
                ->assertHasNoErrors();
        }

        $this->assertDatabaseCount('conversations', 3);

        Livewire::actingAs($person, 'member')
            ->test(MemberContactManagement::class)
            ->set('newSubject', 'موضوع چهارم')
            ->set('newBody', 'متن پیام چهارم')
            ->call('createThread')
            ->assertHasErrors('newSubject');

        $this->assertDatabaseCount('conversations', 3);
    }

    public function test_validation_rejects_empty_subject(): void
    {
        $person = $this->memberPerson();

        Livewire::actingAs($person, 'member')
            ->test(MemberContactManagement::class)
            ->set('newBody', 'متن بدون موضوع')
            ->call('createThread')
            ->assertHasErrors('newSubject');

        $this->assertDatabaseCount('conversations', 0);
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

    private function supporter(): User
    {
        $supporter = User::factory()->create([
            'first_name' => 'حامد',
            'last_name' => 'حامی',
            'access_level' => User::ACCESS_LEVEL_CHILD_SUPPORTER,
        ]);

        SponsorProfile::query()->create([
            'user_id' => $supporter->id,
            'supporter_code' => 'CS-0001',
            'monthly_donation_amount' => 500000,
            'monthly_payment_reminder_methods' => ['phone'],
            'is_social_media_active' => false,
        ]);

        return $supporter;
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
