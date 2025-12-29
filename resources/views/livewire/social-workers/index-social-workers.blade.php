<div>
    {{-- resources/views/livewire/social-workers/index-social-workers.blade.php --}}

    <div class="container mx-auto p-4">
        <h1 class="text-2xl font-bold mb-4">لیست مددکاران اجتماعی</h1>

        <div class="mb-4">
            <a href="{{ route('social-workers.create') }}" class="btn btn-primary">ثبت مددکار جدید</a>
        </div>

        <table class="min-w-full bg-white border rounded">
            <thead class="bg-gray-100">
            <tr>
                <th class="py-2 px-4 border-b text-center">کد مددکاری</th>
                <th class="py-2 px-4 border-b">نام و نام خانوادگی</th>
                <th class="py-2 px-4 border-b text-center">کد ملی</th>
                <th class="py-2 px-4 border-b text-center">موبایل</th>
                <th class="py-2 px-4 border-b text-center">عملیات</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($socialWorkers as $worker)
                <tr class="hover:bg-gray-50">
                    <td class="py-2 px-4 border-b text-center">{{ $worker->worker_code }}</td>
                    <td class="py-2 px-4 border-b">{{ $worker->full_name }}</td>
                    <td class="py-2 px-4 border-b text-center">{{ $worker->national_id }}</td>
                    <td class="py-2 px-4 border-b text-center">{{ $worker->mobile }}</td>
                    <td class="py-2 px-4 border-b text-center">
                        <a href="{{ route('social-workers.edit', $worker) }}" class="text-blue-500 hover:underline">ویرایش</a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>

        <div class="mt-4">
            {{ $socialWorkers->links() }}
        </div>
    </div>
</div>
