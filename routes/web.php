<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\People\CreatePerson;
use App\Livewire\People\FastCreatePerson;
use App\Livewire\People\IndexPeople;
use App\Livewire\People\DeletedPeople;
use App\Livewire\People\AdvancedFilterBuilder;
use App\Livewire\SocialWorkers\EditSocialWorker;
use App\Livewire\SocialWorkers\CreateSocialWorker;
use App\Livewire\SocialWorkers\IndexSocialWorkers;
use App\Livewire\SocialWorkers\DeletedSocialWorkers;
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

// مسیر ثبت‌نام سریع فرد جدید
Route::get('/people/fast-create/{person?}', FastCreatePerson::class)->name('people.fast-create');

// مسیر لیست مددجویان
Route::get('/people', IndexPeople::class)->name('people.index');
Route::get('/people/advanced-reporting', AdvancedFilterBuilder::class)->name('people.advanced-reporting');
Route::get('/people/block-list', DeletedPeople::class)->name('people.block-list');

// مسیر نمایش فرم به Livewire تغییر می‌کند
Route::get('/people/{mode}/{person?}', CreatePerson::class)->name('people.form');
// Route to handle form submission
Route::get('/social-workers/create', CreateSocialWorker::class)->name('social-workers.create');

Route::get('/social-workers', IndexSocialWorkers::class)->name('social-workers.index');
Route::get('/social-workers/block-list', DeletedSocialWorkers::class)->name('social-workers.block-list');
Route::get('/social-workers/{socialWorker}/edit', EditSocialWorker::class)->name('social-workers.edit');
Route::get('/admin/dashboard', DashboardHome::class)
    ->middleware(['auth', 'can:access-admin-panel'])
    ->name('admin.dashboard');
