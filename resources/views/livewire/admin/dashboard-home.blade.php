<div class="flex h-screen overflow-hidden" dir="rtl">
    @include('layouts.partials.sidebar', ['dashboardMode' => true, 'activeSection' => $activeSection])

    <div class="flex flex-col flex-1 w-full overflow-y-auto">
        @include('layouts.partials.header')

        <main class="p-6">
            <div class="container mx-auto">
                @switch($activeSection)
                    @case('people-fast-create')
                        <livewire:people.fast-create-person :embedded="true" :key="'people-fast-create'" />
                        @break

                    @case('people-list')
                        <livewire:people.index-people :embedded="true" :key="'people-list'" />
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

                    @default
                        <div>
                            <h1 class="text-2xl font-bold text-gray-800 mb-6">خلاصه وضعیت مرکز</h1>

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
                                                <td class="p-4 text-sm text-gray-700">{{ $person->national_id }}</td>
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
