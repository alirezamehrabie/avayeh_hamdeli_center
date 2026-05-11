<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\People\CreatePerson;
use App\Livewire\People\FastCreatePerson;
use App\Livewire\People\IndexPeople;
use App\Livewire\People\DeletedPeople;
use App\Livewire\People\AdvancedFilterBuilder;
use App\Livewire\Guardians\EditGuardian;
use App\Livewire\Guardians\IndexGuardians;
use App\Livewire\SocialWorkers\EditSocialWorker;
use App\Livewire\SocialWorkers\CreateSocialWorker;
use App\Livewire\SocialWorkers\IndexSocialWorkers;
use App\Livewire\SocialWorkers\DeletedSocialWorkers;
use App\Livewire\Admin\DashboardHome;
use App\Livewire\Admin\UserAccount;
use App\Livewire\Admin\UserManagement;
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
    if (auth()->check()) {
        return redirect()->route('admin.dashboard');
    }

    return redirect()->route('login');
});

// مسیر ثبت‌نام سریع فرد جدید
Route::get('/people/fast-create/{person?}', FastCreatePerson::class)
    ->middleware(['auth'])
    ->name('people.fast-create');

// مسیر لیست مددجویان
Route::get('/people', IndexPeople::class)->middleware(['auth', 'can:manage-people'])->name('people.index');
Route::get('/people/advanced-reporting', AdvancedFilterBuilder::class)->middleware(['auth', 'can:full-access'])->name('people.advanced-reporting');
Route::get('/people/block-list', DeletedPeople::class)->middleware(['auth', 'can:people-delete'])->name('people.block-list');
Route::get('/guardians', IndexGuardians::class)->middleware(['auth', 'can:full-access'])->name('guardians.index');
Route::get('/guardians/{guardian}/edit', EditGuardian::class)->middleware(['auth', 'can:full-access'])->name('guardians.edit');

// مسیر نمایش فرم به Livewire تغییر می‌کند
Route::get('/people/{mode}/{person?}', CreatePerson::class)
    ->middleware(['auth'])
    ->name('people.form');
// Route to handle form submission
Route::get('/social-workers/create', CreateSocialWorker::class)->middleware(['auth', 'can:manage-social-workers'])->name('social-workers.create');

Route::get('/social-workers', IndexSocialWorkers::class)->middleware(['auth', 'can:manage-social-workers'])->name('social-workers.index');
Route::get('/social-workers/block-list', DeletedSocialWorkers::class)->middleware(['auth', 'can:manage-social-workers'])->name('social-workers.block-list');
Route::get('/social-workers/{socialWorker}/edit', EditSocialWorker::class)->middleware(['auth', 'can:manage-social-workers'])->name('social-workers.edit');
Route::get('/admin/dashboard', DashboardHome::class)
    ->middleware(['auth', 'can:access-admin-panel'])
    ->name('admin.dashboard');

Route::get('/admin/system-settings/users', UserManagement::class)
    ->middleware(['auth', 'can:full-access'])
    ->name('admin.user-management');

Route::get('/admin/system-settings/user-account', UserAccount::class)
    ->middleware(['auth'])
    ->name('admin.user-account');
