<div
    dir="rtl"
    class="relative min-h-[100svh] overflow-hidden bg-[#f8fbff]"
    style="margin-inline: calc(50% - 50vw); width: 100vw;"
>
    <div class="pointer-events-none absolute -right-28 top-[-7rem] h-72 w-72 rounded-full bg-[#5964AE]/22 blur-3xl sm:h-96 sm:w-96"></div>
    <div class="pointer-events-none absolute -left-24 bottom-[-6rem] h-64 w-64 rounded-full bg-[#7C6BD8]/18 blur-3xl sm:h-80 sm:w-80"></div>

    <main class="relative z-10 mx-auto flex min-h-[100svh] w-full max-w-3xl flex-col px-4 py-6 sm:px-6 sm:py-10">
        {{-- هدر --}}
        <header class="relative overflow-hidden rounded-2xl bg-[linear-gradient(140deg,#3f4a8f_0%,#5964AE_55%,#7C6BD8_125%)] px-4 py-4 text-white shadow-xl shadow-[#5964AE]/25 sm:rounded-3xl sm:px-8 sm:py-6">
            <div class="pointer-events-none absolute inset-0 opacity-[0.14]" style="background-image: radial-gradient(rgba(255,255,255,0.9) 1px, transparent 1px); background-size: 20px 20px;"></div>
            <div class="relative flex items-center justify-between gap-3">
                <div class="flex items-center gap-2.5 sm:gap-4">
                    <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-white/15 ring-1 ring-white/25 backdrop-blur sm:h-14 sm:w-14">
                        <i class="bi bi-chat-dots text-xl sm:text-2xl" aria-hidden="true"></i>
                    </span>
                    <div>
                        <p class="text-[10px] font-medium text-white/85 sm:text-xs">پنل اعضای آوای همدلی</p>
                        <h1 class="mt-0.5 text-base font-black leading-tight sm:text-2xl">ارتباط با مدیریت</h1>
                    </div>
                </div>
                <a href="{{ route('member.dashboard') }}"
                   class="inline-flex items-center gap-1.5 rounded-xl bg-white/15 px-3 py-2 text-[11px] font-bold text-white ring-1 ring-white/25 backdrop-blur transition hover:bg-white/25 sm:text-xs">
                    <i class="bi bi-arrow-right" aria-hidden="true"></i>
                    پیشخوان
                </a>
            </div>
        </header>

        @if($successMessage)
            <div class="mt-3 flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs font-bold text-emerald-700 sm:text-sm">
                <i class="bi bi-check-circle-fill shrink-0" aria-hidden="true"></i>
                {{ $successMessage }}
            </div>
        @endif

        @if($selectedThread)
            {{-- نمایش گفتگو --}}
            <section class="mt-3 rounded-2xl border border-slate-200/80 bg-white shadow-sm sm:mt-4">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-4 py-3 sm:px-6">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-sm font-black text-slate-900 sm:text-base">{{ $selectedThread->subject }}</h2>
                            <span class="rounded-full px-2 py-0.5 text-[10px] font-bold {{ $selectedThread->senderStatusClasses() }}">{{ $selectedThread->statusLabel() }}</span>
                        </div>
                    </div>
                    <button type="button" wire:click="backToList"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 px-3 py-1.5 text-[11px] font-bold text-slate-600 transition hover:bg-slate-50 sm:text-xs">
                        <i class="bi bi-list-ul" aria-hidden="true"></i>
                        فهرست گفتگوها
                    </button>
                </div>

                <div class="max-h-[26rem] space-y-3 overflow-y-auto px-4 py-4 sm:px-6">
                    @foreach($selectedThread->messages as $message)
                        <div class="flex {{ $message->is_from_staff ? 'justify-start' : 'justify-end' }}">
                            <div class="max-w-[85%] rounded-2xl px-4 py-3 shadow-sm {{ $message->is_from_staff ? 'rounded-bl-sm bg-[#5964AE] text-white' : 'rounded-br-sm bg-slate-100 text-slate-800' }}">
                                <div class="mb-1 flex items-center gap-2 text-[10px] {{ $message->is_from_staff ? 'text-indigo-100' : 'text-slate-500' }} sm:text-[11px]">
                                    <span class="font-bold">{{ $message->is_from_staff ? 'پاسخ مدیریت' : 'شما' }}</span>
                                    <span dir="ltr">{{ \App\Helpers\Morilog\Jalalian::fromDateTime($message->created_at)->format('Y/m/d - H:i') }}</span>
                                </div>
                                <p class="whitespace-pre-line text-xs leading-6 sm:text-sm">{{ $message->body }}</p>

                                @if($message->attachments->isNotEmpty())
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        @foreach($message->attachments as $attachment)
                                            <a href="{{ route('messages.attachments.download', $attachment) }}" target="_blank"
                                               class="block overflow-hidden rounded-lg ring-2 ring-white/40 transition hover:ring-white/80"
                                               title="{{ $attachment->original_name }}">
                                                <img src="{{ route('messages.attachments.download', $attachment) }}" alt="{{ $attachment->original_name }}" class="h-16 w-16 object-cover sm:h-20 sm:w-20">
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="border-t border-slate-100 px-4 py-4 sm:px-6">
                    @if($selectedThread->status === \App\Models\Conversation::STATUS_CLOSED)
                        <p class="rounded-xl bg-slate-50 px-4 py-3 text-center text-[11px] font-bold text-slate-500 sm:text-xs">
                            این گفتگو بسته شده است. برای پیگیری جدید، از فهرست گفتگوها پیام تازه‌ای ارسال کنید.
                        </p>
                    @else
                        <form wire:submit="sendReply" class="space-y-2">
                            <label for="member-reply-body" class="block text-xs font-bold text-slate-600">پاسخ شما</label>
                            <textarea id="member-reply-body" wire:model="replyBody" rows="3"
                                      placeholder="متن پاسخ خود را بنویسید..."
                                      class="w-full rounded-xl border-slate-200 text-xs focus:border-[#5964AE] focus:ring-[#5964AE]/30 sm:text-sm"></textarea>
                            @error('replyBody')
                                <p class="text-[11px] font-bold text-rose-600">{{ $message }}</p>
                            @enderror

                            <div>
                                <label class="mb-1 block text-xs font-bold text-slate-600">پیوست تصویر (حداکثر ۳ عکس)</label>
                                <input type="file" wire:model="replyPhotos" accept=".jpg,.jpeg,.png,.webp" multiple
                                       class="block w-full text-xs text-slate-600 file:ml-3 file:rounded-lg file:border-0 file:bg-[#f1f1fb] file:px-3 file:py-2 file:text-xs file:font-bold file:text-[#5964AE]">
                                @error('replyPhotos')<p class="mt-1 text-[11px] font-bold text-rose-600">{{ $message }}</p>@enderror
                                @error('replyPhotos.*')<p class="mt-1 text-[11px] font-bold text-rose-600">{{ $message }}</p>@enderror
                            </div>

                            <button type="submit"
                                    class="inline-flex min-h-[44px] w-full items-center justify-center gap-2 rounded-xl bg-[#5964AE] px-5 text-xs font-extrabold text-white shadow-sm transition hover:bg-[#4a55a0] focus:outline-none focus:ring-4 focus:ring-[#5964AE]/25 sm:text-sm"
                                    wire:loading.attr="disabled" wire:target="sendReply">
                                <span wire:loading.remove wire:target="sendReply"><i class="bi bi-send" aria-hidden="true"></i> ارسال پاسخ</span>
                                <span wire:loading wire:target="sendReply">در حال ارسال…</span>
                            </button>
                        </form>
                    @endif
                </div>
            </section>
        @else
            {{-- ارسال پیام جدید --}}
            <section class="mt-3 sm:mt-4">
                @if($showNewForm)
                    <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm sm:p-6">
                        <h2 class="text-sm font-black text-slate-900 sm:text-base">ارسال پیام جدید</h2>
                        <form wire:submit="createThread" class="mt-4 space-y-3">
                            <div>
                                <label for="member-subject" class="mb-1 block text-xs font-bold text-slate-600">موضوع</label>
                                <input id="member-subject" type="text" wire:model="newSubject" placeholder="موضوع پیام را بنویسید..."
                                       class="w-full rounded-xl border-slate-200 text-xs focus:border-[#5964AE] focus:ring-[#5964AE]/30 sm:text-sm">
                                @error('newSubject')<p class="mt-1 text-[11px] font-bold text-rose-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="member-body" class="mb-1 block text-xs font-bold text-slate-600">متن پیام</label>
                                <textarea id="member-body" wire:model="newBody" rows="5" placeholder="پیام خود را برای مدیریت مرکز بنویسید..."
                                          class="w-full rounded-xl border-slate-200 text-xs focus:border-[#5964AE] focus:ring-[#5964AE]/30 sm:text-sm"></textarea>
                                @error('newBody')<p class="mt-1 text-[11px] font-bold text-rose-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label class="mb-1 block text-xs font-bold text-slate-600">پیوست تصویر (حداکثر ۳ عکس، هر عکس تا ۲ مگابایت)</label>
                                <input type="file" wire:model="newPhotos" accept=".jpg,.jpeg,.png,.webp" multiple
                                       class="block w-full text-xs text-slate-600 file:ml-3 file:rounded-lg file:border-0 file:bg-[#f1f1fb] file:px-3 file:py-2 file:text-xs file:font-bold file:text-[#5964AE]">
                                @error('newPhotos')<p class="mt-1 text-[11px] font-bold text-rose-600">{{ $message }}</p>@enderror
                                @error('newPhotos.*')<p class="mt-1 text-[11px] font-bold text-rose-600">{{ $message }}</p>@enderror
                            </div>

                            <div class="flex flex-wrap items-center gap-2 pt-1">
                                <button type="submit"
                                        class="inline-flex min-h-[46px] flex-1 items-center justify-center gap-2 rounded-xl bg-[#5964AE] px-5 text-xs font-extrabold text-white shadow-sm transition hover:bg-[#4a55a0] focus:outline-none focus:ring-4 focus:ring-[#5964AE]/25 sm:text-sm"
                                        wire:loading.attr="disabled" wire:target="createThread">
                                    <span wire:loading.remove wire:target="createThread"><i class="bi bi-send" aria-hidden="true"></i> ارسال پیام</span>
                                    <span wire:loading wire:target="createThread">در حال ارسال…</span>
                                </button>
                                <button type="button" wire:click="$set('showNewForm', false)"
                                        class="inline-flex min-h-[46px] items-center justify-center rounded-xl border border-slate-200 px-5 text-xs font-bold text-slate-600 transition hover:bg-slate-50 sm:text-sm">
                                    انصراف
                                </button>
                            </div>
                        </form>
                    </div>
                @else
                    <button type="button" wire:click="$set('showNewForm', true)"
                            class="flex min-h-[56px] w-full items-center justify-center gap-2 rounded-2xl bg-[linear-gradient(140deg,#3f4a8f_0%,#5964AE_55%,#7C6BD8_125%)] px-5 text-sm font-extrabold text-white shadow-lg shadow-[#5964AE]/25 transition hover:opacity-95">
                        <i class="bi bi-plus-circle text-lg" aria-hidden="true"></i>
                        ارسال پیام جدید به مدیریت
                    </button>
                @endif
            </section>

            {{-- فهرست گفتگوها --}}
            <section class="mt-4 space-y-2 sm:mt-5">
                <h2 class="px-1 text-xs font-black text-slate-500 sm:text-sm">گفتگوهای من</h2>
                @forelse($threads as $thread)
                    <button type="button" wire:click="openThread({{ $thread->id }})"
                            class="block w-full rounded-2xl border border-slate-200/80 bg-white px-4 py-3 text-right shadow-sm transition hover:border-[#5964AE]/40 hover:bg-[#f1f1fb]/60">
                        <div class="flex items-center justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <h3 class="truncate text-xs font-black text-slate-900 sm:text-sm">{{ $thread->subject }}</h3>
                                    <span class="shrink-0 rounded-full px-2 py-0.5 text-[9px] font-bold {{ $thread->senderStatusClasses() }}">{{ $thread->statusLabel() }}</span>
                                </div>
                                <p class="mt-1 truncate text-[11px] leading-5 text-slate-500">{{ \Illuminate\Support\Str::limit($thread->lastMessage?->body ?? '', 70) }}</p>
                            </div>
                            <div class="flex shrink-0 flex-col items-end gap-1">
                                <span class="text-[10px] text-slate-400" dir="ltr">{{ \App\Helpers\Morilog\Jalalian::fromDateTime($thread->last_message_at ?? $thread->created_at)->format('Y/m/d') }}</span>
                                @if($thread->unread_member_count > 0)
                                    <span class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-rose-500 px-1.5 text-[10px] font-bold leading-none text-white">
                                        {{ \App\Helpers\Morilog\CalendarUtils::convertNumbers((string) $thread->unread_member_count) }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </button>
                @empty
                    <div class="rounded-2xl border border-dashed border-[#5964AE]/30 bg-[#f1f1fb]/60 px-4 py-8 text-center">
                        <i class="bi bi-chat-square-text text-2xl text-[#5964AE]" aria-hidden="true"></i>
                        <p class="mt-2 text-[11px] font-bold text-slate-600 sm:text-xs">هنوز گفتگویی ندارید. برای ارتباط با مدیریت، پیام جدیدی ارسال کنید.</p>
                    </div>
                @endforelse
            </section>
        @endif

        <footer class="mt-auto pt-5 sm:pt-8">
            <a href="{{ route('member.dashboard') }}"
               class="flex min-h-[48px] w-full items-center justify-center gap-2 rounded-xl border border-[#5964AE]/25 bg-white px-5 text-sm font-extrabold text-[#5964AE] shadow-sm transition hover:border-[#5964AE]/45 hover:bg-[#f1f1fb] focus:outline-none focus:ring-4 focus:ring-[#5964AE]/20 sm:min-h-[50px]">
                <i class="bi bi-arrow-right text-lg" aria-hidden="true"></i>
                بازگشت به پیشخوان
            </a>
        </footer>
    </main>
</div>
