<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\People\CreatePerson;
use App\Livewire\SocialWorkers\EditSocialWorker;
use App\Livewire\SocialWorkers\CreateSocialWorker;
use App\Livewire\SocialWorkers\IndexSocialWorkers;
use App\Livewire\Admin\DashboardHome;
use App\Livewire\Auth\Login;


// مسیر لاگین با استفاده از کامپوننت Livewire
Route::get('/login', Login::class)->name('login')->middleware('guest');


// روت خروج (چون یک عملیات ساده است، می‌تواند در یک کنترلر یا به صورت Closure باشد)
Route::post('/logout', function () {
    auth()->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect()->route('login');
})->name('logout');

Route::get('/', function () {
    return redirect()->route('login');
});

// مسیر نمایش فرم به Livewire تغییر می‌کند
Route::get('/people/create', CreatePerson::class)->name('people.create');
// Route to handle form submission
Route::get('/social-workers/create', CreateSocialWorker::class)->name('social-workers.create');

Route::get('/social-workers', IndexSocialWorkers::class)->name('social-workers.index');
Route::get('/social-workers/{socialWorker}/edit', EditSocialWorker::class)->name('social-workers.edit');
Route::get('/admin/dashboard', DashboardHome::class)->name('admin.dashboard');
