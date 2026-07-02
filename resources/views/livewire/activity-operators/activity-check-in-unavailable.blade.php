<div class="flex min-h-[60vh] items-center justify-center p-4">
    <div class="w-full max-w-md rounded-2xl border border-rose-200 bg-rose-50 p-6 text-center shadow-sm">
        <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-rose-100">
            <svg class="h-6 w-6 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
        </div>
        <h2 class="text-lg font-black text-rose-800">دسترسی غیرمجاز</h2>
        <p class="mt-2 text-sm text-rose-700">
            شما به این فعالیت دسترسی ندارید یا این فعالیت به شما تخصیص داده نشده است.
        </p>
        <a href="{{ route('activity-operator.activity-list') }}" class="mt-5 inline-flex items-center justify-center rounded-xl bg-rose-600 px-4 py-2 text-sm font-bold text-white transition hover:bg-rose-700">
            بازگشت به فعالیت‌های من
        </a>
    </div>
</div>
