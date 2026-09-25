<div class="space-y-4">
    @if(session('success'))
        <div class="flex items-center gap-2 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    @if($successMessage)
        <div class="flex items-center gap-2 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700">
            {{ $successMessage }}
        </div>
    @endif

    @if($selectedThread)
        {{-- نمایش گفتگو --}}
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-base font-black text-slate-900">{{ $selectedThread->subject }}</h2>
                        <span class="rounded-full px-2 py-0.5 text-[10px] font-bold {{ $selectedThread->senderStatusClasses() }}">{{ $selectedThread->statusLabel() }}</span>
                    </div>
                </div>
                <button type="button" wire:click="backToList"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 px-3 py-1.5 text-xs font-bold text-slate-600 transition hover:bg-slate-50">
                    فهرست گفتگوها
                </button>
            </div>

            <div class="max-h-[26rem] space-y-3 overflow-y-auto px-5 py-5">
                @foreach($selectedThread->messages as $message)
                    <div class="flex {{ $message->is_from_staff ? 'justify-start' : 'justify-end' }}">
                        <div class="max-w-[85%] rounded-2xl px-4 py-3 shadow-sm {{ $message->is_from_staff ? 'rounded-bl-sm bg-indigo-600 text-white' : 'rounded-br-sm bg-slate-100 text-slate-800' }}">
                            <div class="mb-1 flex items-center gap-2 text-[11px] {{ $message->is_from_staff ? 'text-indigo-100' : 'text-slate-500' }}">
                                <span class="font-bold">{{ $message->is_from_staff ? 'پاسخ مدیریت' : 'شما' }}</span>
                                <span dir="ltr">{{ \App\Helpers\Morilog\Jalalian::fromDateTime($message->created_at)->format('Y/m/d - H:i') }}</span>
                            </div>
                            <p class="whitespace-pre-line text-sm leading-6">{{ $message->body }}</p>

                            @if($message->attachments->isNotEmpty())
                                <div class="mt-2 flex flex-wrap gap-2">
                                    @foreach($message->attachments as $attachment)
                                        <a href="{{ route('messages.attachments.download', $attachment) }}" target="_blank"
                                           class="block overflow-hidden rounded-lg ring-2 ring-white/40 transition hover:ring-white/80"
                                           title="{{ $attachment->original_name }}">
                                            <img src="{{ route('messages.attachments.download', $attachment) }}" alt="{{ $attachment->original_name }}" class="h-20 w-20 object-cover">
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="border-t border-slate-200 px-5 py-4">
                @if($selectedThread->status === \App\Models\Conversation::STATUS_CLOSED)
                    <p class="rounded-xl bg-slate-50 px-4 py-3 text-center text-xs font-semibold text-slate-500">
                        این گفتگو بسته شده است. برای پیگیری جدید، از فهرست گفتگوها پیام تازه‌ای ارسال کنید.
                    </p>
                @else
                    <form wire:submit="sendReply" class="space-y-3">
                        <div>
                            <label for="supporter-reply-body" class="mb-1 block text-xs font-bold text-slate-600">پاسخ شما</label>
                            <textarea id="supporter-reply-body" wire:model="replyBody" rows="3"
                                      placeholder="متن پاسخ خود را بنویسید..."
                                      class="w-full rounded-xl border-slate-200 text-sm focus:border-indigo-300 focus:ring-indigo-200"></textarea>
                            @error('replyBody')
                                <p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-bold text-slate-600">پیوست تصویر (حداکثر ۳ عکس)</label>
                            <input type="file" wire:model="replyPhotos" accept=".jpg,.jpeg,.png,.webp" multiple
                                   class="block w-full text-xs text-slate-600 file:ml-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-xs file:font-bold file:text-indigo-600">
                            @error('replyPhotos')<p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
                            @error('replyPhotos.*')<p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
                        </div>

                        <button type="submit"
                                class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-200"
                                wire:loading.attr="disabled" wire:target="sendReply">
                            <span wire:loading.remove wire:target="sendReply">ارسال پاسخ</span>
                            <span wire:loading wire:target="sendReply">در حال ارسال…</span>
                        </button>
                    </form>
                @endif
            </div>
        </div>
    @else
        {{-- ارسال پیام جدید --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold text-indigo-600">پنل حامی کودک</p>
                    <h1 class="mt-1 text-xl font-black text-slate-900 sm:text-2xl">ارتباط با مدیریت</h1>
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        پیام خود را برای مدیریت مرکز ارسال کنید و پاسخ را در همین بخش دنبال کنید.
                    </p>
                </div>
                <span class="inline-flex items-center rounded-xl border border-indigo-100 bg-indigo-50 px-3 py-2 text-xs font-bold text-indigo-700">
                    حامی کودک
                </span>
            </div>

            @if($showNewForm)
                <form wire:submit="createThread" class="mt-5 space-y-4 border-t border-slate-200 pt-5">
                    <div>
                        <label for="supporter-subject" class="mb-1 block text-xs font-bold text-slate-600">موضوع</label>
                        <input id="supporter-subject" type="text" wire:model="newSubject" placeholder="موضوع پیام را بنویسید..."
                               class="w-full rounded-xl border-slate-200 text-sm focus:border-indigo-300 focus:ring-indigo-200">
                        @error('newSubject')<p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="supporter-body" class="mb-1 block text-xs font-bold text-slate-600">متن پیام</label>
                        <textarea id="supporter-body" wire:model="newBody" rows="5" placeholder="پیام خود را برای مدیریت مرکز بنویسید..."
                                  class="w-full rounded-xl border-slate-200 text-sm focus:border-indigo-300 focus:ring-indigo-200"></textarea>
                        @error('newBody')<p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-bold text-slate-600">پیوست تصویر (حداکثر ۳ عکس، هر عکس تا ۲ مگابایت)</label>
                        <input type="file" wire:model="newPhotos" accept=".jpg,.jpeg,.png,.webp" multiple
                               class="block w-full text-xs text-slate-600 file:ml-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-xs file:font-bold file:text-indigo-600">
                        @error('newPhotos')<p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
                        @error('newPhotos.*')<p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <button type="submit"
                                class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-200"
                                wire:loading.attr="disabled" wire:target="createThread">
                            <span wire:loading.remove wire:target="createThread">ارسال پیام</span>
                            <span wire:loading wire:target="createThread">در حال ارسال…</span>
                        </button>
                        <button type="button" wire:click="$set('showNewForm', false)"
                                class="rounded-xl border border-slate-200 px-5 py-2.5 text-sm font-bold text-slate-600 transition hover:bg-slate-50">
                            انصراف
                        </button>
                    </div>
                </form>
            @else
                <button type="button" wire:click="$set('showNewForm', true)"
                        class="mt-5 inline-flex w-full items-center justify-center gap-2 rounded-2xl border border-dashed border-indigo-300 bg-indigo-50/60 px-5 py-4 text-sm font-bold text-indigo-700 transition hover:bg-indigo-100">
                    ارسال پیام جدید به مدیریت
                </button>
            @endif
        </div>

        {{-- فهرست گفتگوها --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h2 class="text-sm font-black text-slate-900">گفتگوهای من</h2>
            <div class="mt-4 space-y-2">
                @forelse($threads as $thread)
                    <button type="button" wire:click="openThread({{ $thread->id }})"
                            class="block w-full rounded-xl border border-slate-200 px-4 py-3 text-right transition hover:border-indigo-300 hover:bg-indigo-50/50">
                        <div class="flex items-center justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <h3 class="truncate text-sm font-bold text-slate-900">{{ $thread->subject }}</h3>
                                    <span class="shrink-0 rounded-full px-2 py-0.5 text-[9px] font-bold {{ $thread->senderStatusClasses() }}">{{ $thread->statusLabel() }}</span>
                                </div>
                                <p class="mt-1 truncate text-xs leading-5 text-slate-500">{{ \Illuminate\Support\Str::limit($thread->lastMessage?->body ?? '', 70) }}</p>
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
                    <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center">
                        <p class="text-xs font-semibold text-slate-500">هنوز گفتگویی ندارید. برای ارتباط با مدیریت، پیام جدیدی ارسال کنید.</p>
                    </div>
                @endforelse
            </div>
        </div>
    @endif
</div>
