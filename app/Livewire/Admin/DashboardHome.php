<?php

namespace App\Livewire\Admin;

use App\Models\Person;
use App\Models\SocialWorker;
use Livewire\Component;
use Livewire\Attributes\Layout;

class DashboardHome extends Component
{
    #[AllowDynamicProperties]
    #[Layout('layouts.admin')] // متصل کردن به لایوت ساخته شده
    public function render()
    {
        return view('livewire.admin.dashboard-home', [
            'totalPeople' => Person::count(),
            'totalSocialWorkers' => SocialWorker::count(),
            // فرض بر این است که فیلد gender در مدل Person دارید
            'maleCount' => Person::where('gender', 'male')->count(),
            'femaleCount' => Person::where('gender', 'female')->count(),
            'latestPeople' => Person::with(['guardian.socialWorker']) // لود کردن زنجیره‌ای روابط
            ->latest()
                ->take(50)
                ->get(),
        ]);
    }
}
