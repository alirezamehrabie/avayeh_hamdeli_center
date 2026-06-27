@if (session()->has('error') || session()->has('success'))
    <div class="fixed inset-x-3 top-3 z-[80] mx-auto max-w-xl space-y-2 sm:top-5" dir="rtl">
        @if (session()->has('error'))
            <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold leading-6 text-rose-700 shadow-lg shadow-rose-950/10">
                {{ session('error') }}
            </div>
        @endif

        @if (session()->has('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold leading-6 text-emerald-700 shadow-lg shadow-emerald-950/10">
                {{ session('success') }}
            </div>
        @endif
    </div>
@endif
