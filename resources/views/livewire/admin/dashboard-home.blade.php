<div class="flex h-screen overflow-hidden" dir="rtl">
    @include('layouts.partials.sidebar', ['dashboardMode' => true, 'activeSection' => $activeSection])

    <div class="flex flex-col flex-1 w-full overflow-y-auto">
        @include('layouts.partials.header')

        <main class="p-6">
            <div class="container mx-auto">
                @switch($activeSection)
                    @case('people-fast-create')
                        <livewire:people.fast-create-person :person="$editingPerson" :embedded="true" :key="'people-fast-create-'.($editingPerson?->id ?? 'new')" />
                        @break

                    @case('people-list')
                        <livewire:people.index-people :embedded="true" :key="'people-list'" />
                        @break

                    @case('people-block-list')
                        <livewire:people.deleted-people :embedded="true" :key="'people-block-list'" />
                        @break

                    @case('person-create')
                        <livewire:people.create-person mode="create" :embedded="true" :key="'person-create'" />
                        @break

                    @case('person-edit')
                        @if($editingPerson)
                            <livewire:people.create-person mode="edit" :person="$editingPerson" :embedded="true" :key="'person-edit-'.$editingPerson->id" />
                        @else
                            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                                <p class="text-red-600 mb-4">مددجوی انتخاب شده یافت نشد.</p>
                                <button type="button" wire:click="selectSection('people-list')" class="btn btn-primary">بازگشت به لیست مددجویان</button>
                            </div>
                        @endif
                        @break

                    @case('social-workers-list')
                        <livewire:social-workers.index-social-workers :embedded="true" :key="'social-workers-list'" />
                        @break

                    @case('social-workers-block-list')
                        <livewire:social-workers.deleted-social-workers :embedded="true" :key="'social-workers-block-list'" />
                        @break

                    @case('social-worker-create')
                        <livewire:social-workers.create-social-worker :embedded="true" :key="'social-worker-create'" />
                        @break

                    @case('social-worker-edit')
                        @if($editingSocialWorker)
                            <livewire:social-workers.edit-social-worker :social-worker="$editingSocialWorker" :embedded="true" :key="'social-worker-edit-'.$editingSocialWorker->id" />
                        @else
                            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                                <p class="text-red-600 mb-4">مددکار انتخاب شده یافت نشد.</p>
                                <button type="button" wire:click="selectSection('social-workers-list')" class="btn btn-primary">بازگشت به لیست مددکاران</button>
                            </div>
                        @endif
                        @break

                    @case('guardians-list')
                        <livewire:guardians.index-guardians :embedded="true" :key="'guardians-list'" />
                        @break

                    @case('advanced-reports')
                        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                            <h1 class="text-2xl font-bold text-gray-800 mb-2">گزارش پیشرفته</h1>
                            <p class="text-gray-600">گزارش گیری پیشرفته مرکز تخصصی کودکان آوای همدلی</p>
                        </div>
                        @break

                    @default
                        <div>
                            <h1 class="text-2xl font-bold text-gray-800 mb-6">خلاصه وضعیت مرکز نیکوکاری</h1>

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                                <livewire:admin.dashboard.stat-card
                                    title="کل مددجویان"
                                    :value="$totalPeople"
                                    color="blue"
                                    icon="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>

                                <livewire:admin.dashboard.stat-card
                                    title="تعداد سرپرست (خانوار)"
                                    :value="$guardianCount"
                                    color="black"
                                    icon="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>

                                <livewire:admin.dashboard.stat-card
                                    title="مددکاران اجتماعی"
                                    :value="$totalSocialWorkers"
                                    color="green"
                                    icon="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>

                                <livewire:admin.dashboard.stat-card
                                    title="تعداد بانوان"
                                    :value="$femaleCount"
                                    color="purple"
                                    icon="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>

                                <livewire:admin.dashboard.stat-card
                                    title="تعداد آقایان"
                                    :value="$maleCount"
                                    color="purple"
                                    icon="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </div>

                            @php
                                $campaignCreateRoute = \Illuminate\Support\Facades\Route::has('campaigns.create') ? route('campaigns.create') : null;
                                $campaignIndexRoute = \Illuminate\Support\Facades\Route::has('campaigns.index') ? route('campaigns.index') : null;
                            @endphp

                            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="group relative overflow-hidden rounded-xl border border-emerald-100 bg-white p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md">
                                    <div class="absolute inset-y-0 right-0 w-1 bg-emerald-500"></div>
                                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                        <div class="flex items-center">
                                            <div class="ml-4 rounded-lg bg-emerald-100 p-3 text-emerald-600">
                                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium text-gray-500">راه‌اندازی کمپین جدید</p>
                                                <h2 class="mt-1 text-lg font-bold text-gray-800">ایجاد پویش</h2>
                                            </div>
                                        </div>

                                        @if($campaignCreateRoute)
                                            <a href="{{ $campaignCreateRoute }}" class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-100">
                                                ایجاد پویش
                                            </a>
                                        @else
                                            <button type="button" disabled class="inline-flex cursor-not-allowed items-center justify-center rounded-lg bg-emerald-100 px-4 py-2.5 text-sm font-semibold text-emerald-700 opacity-70">
                                                ایجاد پویش
                                            </button>
                                        @endif
                                    </div>
                                </div>

                                <div class="group relative overflow-hidden rounded-xl border border-indigo-100 bg-white p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md">
                                    <div class="absolute inset-y-0 right-0 w-1 bg-indigo-500"></div>
                                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                        <div class="flex items-center">
                                            <div class="ml-4 rounded-lg bg-indigo-100 p-3 text-indigo-600">
                                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5h6M9 12h6m-6 7h6M5 5h.01M5 12h.01M5 19h.01"></path>
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium text-gray-500">مشاهده و مدیریت کمپین‌ها</p>
                                                <h2 class="mt-1 text-lg font-bold text-gray-800">لیست پویش‌ها</h2>
                                            </div>
                                        </div>

                                        @if($campaignIndexRoute)
                                            <a href="{{ $campaignIndexRoute }}" class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-100">
                                                لیست پویش‌ها
                                            </a>
                                        @else
                                            <button type="button" disabled class="inline-flex cursor-not-allowed items-center justify-center rounded-lg bg-indigo-100 px-4 py-2.5 text-sm font-semibold text-indigo-700 opacity-70">
                                                لیست پویش‌ها
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="mt-8 bg-white p-6 rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                                <div class="flex justify-between items-center mb-4">
                                    <h2 class="text-lg font-semibold text-gray-800">آخرین مددجویان ثبت شده</h2>
                                    <button type="button" wire:click="selectSection('people-list')" class="text-sm text-indigo-600 hover:underline">مشاهده همه</button>
                                </div>

                                <div class="overflow-x-auto">
                                    <table class="w-full text-right border-collapse">
                                        <thead>
                                        <tr class="bg-gray-50 border-b border-gray-100">
                                            <th class="p-4 text-sm font-semibold text-gray-600">کد مددجویی</th>
                                            <th class="p-4 text-sm font-semibold text-gray-600">کد خانوار</th>
                                            <th class="p-4 text-sm font-semibold text-gray-600">نام</th>
                                            <th class="p-4 text-sm font-semibold text-gray-600">نام خانوادگی</th>
                                            <th class="p-4 text-sm font-semibold text-gray-600">نام پدر</th>
                                            <th class="p-4 text-sm font-semibold text-gray-600">کد ملی</th>
                                            <th class="p-4 text-sm font-semibold text-gray-600">نام مددکار</th>
                                            <th class="p-4 text-sm font-semibold text-gray-600">عملیات</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @forelse($latestPeople as $person)
                                            <tr class="border-b border-gray-50 hover:bg-gray-50/50 transition-colors">
                                                <td class="p-4 text-sm text-gray-700 font-mono">{{ $person->person_code }}</td>
                                                <td class="p-4 text-sm text-gray-700 font-mono">{{ $person->guardian?->guardian_code ?? '-' }}</td>
                                                <td class="p-4 text-sm text-gray-700">{{ $person->first_name }}</td>
                                                <td class="p-4 text-sm text-gray-700">{{ $person->last_name }}</td>
                                                <td class="p-4 text-sm text-gray-700">{{ $person->father_name }}</td>
                                                <td class="p-4 text-sm text-gray-700 font-">{{ $person->national_id }}</td>
                                                <td class="p-4 text-sm">
                                                    <span class="px-2 py-1 bg-blue-50 text-blue-700 rounded-md text-xs">
                                                        @if($person->guardian && $person->guardian->socialWorker)
                                                            {{ $person->guardian->socialWorker->first_name }} {{ $person->guardian->socialWorker->last_name }}
                                                        @else
                                                            <span class="text-gray-400 italic">تعیین نشده</span>
                                                        @endif
                                                    </span>
                                                </td>
                                                <td class="p-4 text-sm">
                                                    <button type="button" wire:click="selectSection('person-edit', {{ $person->id }})" class="text-indigo-600 hover:text-indigo-900 ml-3">ویرایش</button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="8" class="p-8 text-center text-gray-400">هیچ مددجویی هنوز ثبت نشده است.</td>
                                            </tr>
                                        @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                @endswitch
            </div>
        </main>
    </div>
</div>
