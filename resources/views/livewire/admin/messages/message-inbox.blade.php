<div class="space-y-6">
    {{-- هدر و فیلترها --}}
    <div class="rounded-xl border border-gray-100 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">صندوق پیام‌ها</h1>
                <p class="mt-1 text-sm text-gray-500">
                    پیام‌های دریافتی از اعضا و حامیان کودک.
                    @if($unreadCount > 0)
                        <span class="mr-1 inline-flex items-center rounded-full bg-rose-50 px-2 py-0.5 text-xs font-bold text-rose-600">
                            {{ \App\Helpers\Morilog\CalendarUtils::convertNumbers((string) $unreadCount) }} پیام خوانده‌نشده
                        </span>
                    @endif
                </p>
            </div>

            @if($unreadCount > 0)
                <button type="button" wire:click="markAllAsRead"
                        class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-200">
                    علامت‌گذاری همه به‌عنوان خوانده‌شده
                </button>
            @endif
        </div>

        <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-5">
            <div>
                <label for="msg-status-filter" class="mb-1 block text-xs font-semibold text-gray-600">وضعیت</label>
                <select id="msg-status-filter" wire:model.live="statusFilter" class="w-full rounded-xl border-gray-200 text-sm focus:border-indigo-300 focus:ring-indigo-200">
                    <option value="all">همه</option>
                    <option value="unread">خوانده‌نشده</option>
                    <option value="pending">در انتظار بررسی</option>
                    <option value="answered">پاسخ داده شده</option>
                    <option value="closed">بسته شده</option>
                </select>
            </div>

            <div>
                <label for="msg-search" class="mb-1 block text-xs font-semibold text-gray-600">جست‌وجو</label>
                <input id="msg-search" type="text" wire:model.live.debounce.500ms="search" placeholder="نام، کد یا موضوع..."
                       class="w-full rounded-xl border-gray-200 text-sm focus:border-indigo-300 focus:ring-indigo-200">
            </div>

            <div>
                <label for="msg-date-from" class="mb-1 block text-xs font-semibold text-gray-600">از تاریخ</label>
                <input id="msg-date-from" type="text" dir="ltr" placeholder="1405/01/01" wire:model.live.debounce.500ms="dateFrom"
                       class="w-full rounded-xl border-gray-200 text-center text-sm focus:border-indigo-300 focus:ring-indigo-200">
            </div>

            <div>
                <label for="msg-date-to" class="mb-1 block text-xs font-semibold text-gray-600">تا تاریخ</label>
                <input id="msg-date-to" type="text" dir="ltr" placeholder="1405/12/29" wire:model.live.debounce.500ms="dateTo"
                       class="w-full rounded-xl border-gray-200 text-center text-sm focus:border-indigo-300 focus:ring-indigo-200">
            </div>

            <div class="flex items-end">
                <button type="button" wire:click="clearFilters"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm font-semibold text-gray-600 transition hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-100">
                    حذف فیلترها
                </button>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-5">
        {{-- فهرست گفتگوها --}}
        <div class="rounded-xl border border-gray-100 bg-white shadow-sm lg:col-span-2 {{ $selectedThread ? 'hidden lg:block' : '' }}"
             wire:loading.class="opacity-60" wire:target="statusFilter,search,dateFrom,dateTo,clearFilters">
            <div class="divide-y divide-gray-50">
                @forelse($threads as $thread)
                    <button type="button" wire:click="selectThread({{ $thread->id }})"
                            class="block w-full px-5 py-4 text-right transition hover:bg-indigo-50/40 {{ $selectedThreadId === $thread->id ? 'bg-indigo-50/70' : '' }}">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <h2 class="text-sm font-bold text-gray-800">{{ $thread->subject }}</h2>
                                    <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $thread->sender_role === 'child_supporter' ? 'bg-violet-50 text-violet-600' : 'bg-sky-50 text-sky-600' }}">
                                        {{ $thread->senderRoleLabel() }}
                                    </span>
                                </div>
                                <p class="mt-1 truncate text-xs leading-5 text-gray-500">{{ \Illuminate\Support\Str::limit($thread->lastMessage?->body ?? '', 80) }}</p>
                                <p class="mt-1 truncate text-[11px] text-gray-400">
                                    {{ $thread->sender_name }}
                                    @if($thread->sender_code)
                                        <span dir="ltr">• {{ $thread->sender_code }}</span>
                                    @endif
                                </p>
                            </div>

                            <div class="flex shrink-0 flex-col items-end gap-1.5">
                                <span class="rounded-full px-2 py-0.5 text-[10px] font-bold {{ $thread->senderStatusClasses() }}">{{ $thread->statusLabel() }}</span>
                                <span class="text-[11px] text-gray-400">{{ \App\Helpers\Morilog\Jalalian::fromDateTime($thread->last_message_at ?? $thread->created_at)->format('Y/m/d') }}</span>
                                @if($thread->unread_staff_count > 0)
                                    <span class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-rose-500 px-1.5 text-[10px] font-bold leading-none text-white">
                                        {{ \App\Helpers\Morilog\CalendarUtils::convertNumbers((string) $thread->unread_staff_count) }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </button>
                @empty
                    <div class="px-6 py-14 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                        </svg>
                        <p class="mt-3 text-sm font-semibold text-gray-500">گفتگویی یافت نشد.</p>
                        <p class="mt-1 text-xs text-gray-400">پیام‌های ارسالی اعضا و حامیان از همین بخش مدیریت می‌شود.</p>
                    </div>
                @endforelse
            </div>

            @if($threads->hasPages())
                <div class="border-t border-gray-100 px-5 py-4">
                    {{ $threads->links() }}
                </div>
            @endif
        </div>

        {{-- نخ گفتگو --}}
        <div class="rounded-xl border border-gray-100 bg-white shadow-sm lg:col-span-3">
            @if($selectedThread)
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-5 py-4">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <button type="button" wire:click="$set('selectedThreadId', null)"
                                    class="rounded-lg border border-gray-200 px-2 py-1 text-xs font-semibold text-gray-500 transition hover:bg-gray-50 lg:hidden"
                                    aria-label="بازگشت به فهرست">
                                ←
                            </button>
                            <h2 class="text-base font-bold text-gray-800">{{ $selectedThread->subject }}</h2>
                            <span class="rounded-full px-2 py-0.5 text-[10px] font-bold {{ $selectedThread->senderStatusClasses() }}">{{ $selectedThread->statusLabel() }}</span>
                        </div>
                        <p class="mt-1 text-xs text-gray-400">
                            {{ $selectedThread->sender_name }}
                            @if($selectedThread->sender_code)
                                <span dir="ltr">• {{ $selectedThread->sender_code }}</span>
                            @endif
                            ({{ $selectedThread->senderRoleLabel() }})
                        </p>
                    </div>

                    <div class="flex shrink-0 items-center gap-2">
                        @if($selectedThread->status !== \App\Models\Conversation::STATUS_CLOSED)
                            <button type="button"
                                    @click="if (confirm('این گفتگو بسته شود؟')) $wire.closeThread({{ $selectedThread->id }})"
                                    class="rounded-lg border border-amber-100 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-600 transition hover:bg-amber-100">
                                بستن گفتگو
                            </button>
                        @else
                            <button type="button" wire:click="reopenThread({{ $selectedThread->id }})"
                                    class="rounded-lg border border-emerald-100 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-600 transition hover:bg-emerald-100">
                                بازگشایی
                            </button>
                        @endif

                        <button type="button"
                                @click="if (confirm('این گفتگو و همهٔ پیام‌های آن حذف شود؟')) $wire.deleteThread({{ $selectedThread->id }})"
                                class="rounded-lg border border-rose-100 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-500 transition hover:bg-rose-100"
                                aria-label="حذف گفتگو">
                            حذف
                        </button>
                    </div>
                </div>

                <div class="max-h-[28rem] space-y-4 overflow-y-auto px-5 py-5">
                    @foreach($selectedThread->messages as $message)
                        <div class="flex {{ $message->is_from_staff ? 'justify-start' : 'justify-end' }}">
                            <div class="max-w-[85%] rounded-2xl px-4 py-3 shadow-sm {{ $message->is_from_staff ? 'rounded-bl-sm bg-indigo-600 text-white' : 'rounded-br-sm bg-gray-100 text-gray-800' }}">
                                <div class="mb-1 flex items-center gap-2 text-[11px] {{ $message->is_from_staff ? 'text-indigo-100' : 'text-gray-500' }}">
                                    <span class="font-bold">{{ $message->is_from_staff ? 'پاسخ مدیریت' : ($message->sender_name ?? $selectedThread->sender_name) }}</span>
                                    <span>{{ \App\Helpers\Morilog\Jalalian::fromDateTime($message->created_at)->format('Y/m/d ساعت H:i') }}</span>
                                </div>
                                <p class="whitespace-pre-line text-sm leading-6">{{ $message->body }}</p>

                                @if($message->attachments->isNotEmpty())
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        @foreach($message->attachments as $attachment)
                                            <a href="{{ route('messages.attachments.download', $attachment) }}" target="_blank"
                                               class="group relative block overflow-hidden rounded-lg ring-2 {{ $message->is_from_staff ? 'ring-white/30' : 'ring-white' }} transition hover:ring-indigo-300"
                                               title="{{ $attachment->original_name }}">
                                                <img src="{{ route('messages.attachments.download', $attachment) }}" alt="{{ $attachment->original_name }}"
                                                     class="h-20 w-20 object-cover">
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="border-t border-gray-100 px-5 py-4">
                    @if($selectedThread->status === \App\Models\Conversation::STATUS_CLOSED)
                        <p class="rounded-xl bg-slate-50 px-4 py-3 text-center text-xs font-semibold text-slate-500">
                            این گفتگو بسته شده است. برای پاسخ‌دهی، ابتدا از دکمهٔ «بازگشایی» استفاده کنید.
                        </p>
                    @else
                        <form wire:submit="sendReply">
                            <label for="reply-body" class="mb-1 block text-xs font-semibold text-gray-600">پاسخ مدیریت</label>
                            <textarea id="reply-body" wire:model="replyBody" rows="3"
                                      placeholder="پاسخ خود را برای فرستنده بنویسید..."
                                      class="w-full rounded-xl border-gray-200 text-sm focus:border-indigo-300 focus:ring-indigo-200"></textarea>
                            @error('replyBody')
                                <p class="mt-1 text-xs font-semibold text-rose-500">{{ $message }}</p>
                            @enderror

                            <div class="mt-3 flex items-center justify-between gap-3">
                                <p class="text-[11px] text-gray-400">پاسخ شما در پنل فرستنده نمایش داده می‌شود.</p>
                                <button type="submit"
                                        class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-200"
                                        wire:loading.attr="disabled" wire:target="sendReply">
                                    <span wire:loading.remove wire:target="sendReply">ارسال پاسخ</span>
                                    <span wire:loading wire:target="sendReply" class="flex items-center gap-2">
                                        <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                        در حال ارسال…
                                    </span>
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
            @else
                <div class="hidden px-6 py-16 text-center lg:block">
                    <svg class="mx-auto h-12 w-12 text-gray-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3">
                        <path d="M8 10h8m-8 4h5"/><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                    </svg>
                    <p class="mt-3 text-sm font-semibold text-gray-500">گفتگویی را از فهرست انتخاب کنید.</p>
                    <p class="mt-1 text-xs text-gray-400">برای مشاهدهٔ جزئیات و پاسخ‌دهی، روی یکی از پیام‌ها بزنید.</p>
                </div>
                <div class="px-6 py-10 text-center lg:hidden">
                    <p class="text-sm font-semibold text-gray-500">گفتگویی را از فهرست انتخاب کنید.</p>
                </div>
            @endif
        </div>
    </div>
</div>
