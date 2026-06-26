<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\People\CreatePerson;
use App\Livewire\People\FastCreatePerson;
use App\Livewire\People\IndexPeople;
use App\Livewire\People\DeletedPeople;
use App\Livewire\People\AdvancedFilterBuilder;
use App\Livewire\Guardians\EditGuardian;
use App\Livewire\Guardians\IndexGuardians;
use App\Livewire\Guardians\DeletedGuardians;
use App\Livewire\SocialWorkers\EditSocialWorker;
use App\Livewire\SocialWorkers\CreateSocialWorker;
use App\Livewire\SocialWorkers\IndexSocialWorkers;
use App\Livewire\SocialWorkers\DeletedSocialWorkers;
use App\Livewire\Admin\DashboardHome;
use App\Livewire\Admin\UserAccount;
use App\Livewire\Auth\Login;
use App\Livewire\DistributionOperators\DefineService;
use App\Livewire\DistributionOperators\EditMiscService;
use App\Livewire\DistributionOperators\EditServiceAllocations;
use App\Livewire\DistributionOperators\Gates\DeliveryGate;
use App\Livewire\DistributionOperators\Gates\EntryGate;
use App\Livewire\DistributionOperators\Gates\ExitGate;
use App\Livewire\DistributionOperators\ServiceList;
use App\Livewire\DistributionOperators\UserAccount as DistributionOperatorUserAccount;
use App\Livewire\ChildSupporters\Dashboard as ChildSupporterDashboard;
use App\Livewire\ChildSupporters\SponsorList;
use App\Livewire\ChildSupporters\SponsorRegistration;
use App\Livewire\ChildSupporters\UserAccount as ChildSupporterUserAccount;
use App\Livewire\SocialWorkers\DeliveryHistory as SocialWorkerDeliveryHistory;
use App\Livewire\SocialWorkers\Dashboard as SocialWorkerDashboard;
use App\Livewire\SocialWorkers\UserAccount as SocialWorkerUserAccount;
use App\Http\Controllers\QrIdentityController;
use App\Http\Controllers\ActivityCheckInController;


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
        return redirect()->to(auth()->user()->getPanelRedirectPath());
    }

    return redirect()->route('login');
});

Route::get('/qr/r/{token}', [QrIdentityController::class, 'resolve'])
    ->middleware(['auth'])
    ->name('qr-identities.resolve');

// مسیر ثبت‌نام سریع فرد جدید
Route::get('/people/fast-create/{person?}', FastCreatePerson::class)
    ->middleware(['auth'])
    ->name('people.fast-create');

// مسیر لیست مددجویان
Route::get('/people', IndexPeople::class)->middleware(['auth', 'can:manage-people'])->name('people.index');
Route::get('/people/advanced-reporting', AdvancedFilterBuilder::class)->middleware(['auth', 'can:full-access'])->name('people.advanced-reporting');
Route::get('/people/block-list', DeletedPeople::class)->middleware(['auth', 'can:people-delete'])->name('people.block-list');
Route::get('/guardians', IndexGuardians::class)->middleware(['auth', 'can:full-access'])->name('guardians.index');
Route::get('/guardians/block-list', DeletedGuardians::class)->middleware(['auth', 'can:full-access'])->name('guardians.block-list');
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

Route::get('/admin/services/service-definition', DashboardHome::class)
    ->middleware(['auth', 'can:full-access'])
    ->name('admin.service-definition');

Route::redirect('/admin/services/define-services', '/admin/services/service-definition')
    ->middleware(['auth', 'can:full-access'])
    ->name('admin.define-services');

Route::get('/admin/services/service-management', DashboardHome::class)
    ->middleware(['auth', 'can:full-access'])
    ->name('admin.service-management');

Route::get('/admin/services/service-archive', DashboardHome::class)
    ->middleware(['auth', 'can:full-access'])
    ->name('admin.service-archive');

Route::get('/admin/services/service-list', DashboardHome::class)
    ->middleware(['auth', 'can:full-access'])
    ->name('admin.service-list');

Route::get('/admin/services/service-delivery', DashboardHome::class)
    ->middleware(['auth', 'can:full-access'])
    ->name('admin.service-delivery');

Route::get('/admin/activities/activity-definition', DashboardHome::class)
    ->middleware(['auth', 'can:full-access'])
    ->name('admin.activity-definition');

Route::get('/admin/activities/activity-list', DashboardHome::class)
    ->middleware(['auth', 'can:access-admin-panel'])
    ->name('admin.activity-list');


Route::post('/admin/activities/{activity}/check-in/qr', [ActivityCheckInController::class, 'qr'])
    ->middleware(['auth', 'can:access-admin-panel'])
    ->name('admin.activities.check-in.qr');

Route::get('/admin/system-settings/user-definition', DashboardHome::class)
    ->middleware(['auth', 'can:full-access'])
    ->name('admin.user-definition');

Route::redirect('/admin/system-settings/users', '/admin/system-settings/user-definition')
    ->middleware(['auth', 'can:full-access'])
    ->name('admin.user-management');

Route::get('/admin/system-settings/user-list', DashboardHome::class)
    ->middleware(['auth', 'can:full-access'])
    ->name('admin.user-list');

Route::get('/admin/system-settings/user-list/deleted', DashboardHome::class)
    ->middleware(['auth', 'can:full-access'])
    ->name('admin.user-list.deleted');

Route::redirect('/admin/system-settings/users/deleted', '/admin/system-settings/user-list/deleted')
    ->middleware(['auth', 'can:full-access'])
    ->name('admin.user-management.deleted');

Route::get('/admin/system-settings/user-account', UserAccount::class)
    ->middleware(['auth'])
    ->name('admin.user-account');

Route::get('/social-worker/dashboard', SocialWorkerDashboard::class)
    ->middleware(['auth', 'can:access-social-worker-panel'])
    ->name('social-worker.dashboard');

Route::get('/social-worker/delivery-history', SocialWorkerDeliveryHistory::class)
    ->middleware(['auth', 'can:access-social-worker-panel'])
    ->name('social-worker.delivery-history');

Route::get('/social-worker/system-settings/user-account', SocialWorkerUserAccount::class)
    ->middleware(['auth', 'can:access-social-worker-panel'])
    ->name('social-worker.user-account');

Route::get('/distribution-operator/dashboard', fn () => redirect()->route('distribution-operator.define-service'))
    ->middleware(['auth', 'can:access-distribution-operator-panel'])
    ->name('distribution-operator.dashboard');

Route::get('/distribution-operator/define-service', DefineService::class)
    ->middleware(['auth', 'can:access-distribution-operator-panel'])
    ->name('distribution-operator.define-service');

Route::get('/distribution-operator/services', ServiceList::class)
    ->middleware(['auth', 'can:access-distribution-operator-panel'])
    ->name('distribution-operator.service-list');

Route::get('/distribution-operator/services/{serviceId}/edit', EditMiscService::class)
    ->middleware(['auth', 'can:access-distribution-operator-panel'])
    ->name('distribution-operator.edit-service');

Route::get('/distribution-operator/services/{serviceId}/allocations', EditServiceAllocations::class)
    ->middleware(['auth', 'can:access-distribution-operator-panel'])
    ->name('distribution-operator.edit-allocations');

Route::get('/distribution-operator/gates/entry', EntryGate::class)
    ->middleware(['auth', 'can:access-distribution-inbound-gate'])
    ->name('distribution-operator.gates.entry');

Route::get('/distribution-operator/gates/delivery', DeliveryGate::class)
    ->middleware(['auth', 'can:access-distribution-delivery-gate'])
    ->name('distribution-operator.gates.delivery');

Route::get('/distribution-operator/gates/exit', ExitGate::class)
    ->middleware(['auth', 'can:access-distribution-outbound-gate'])
    ->name('distribution-operator.gates.exit');

Route::get('/distribution-operator/system-settings/user-account', DistributionOperatorUserAccount::class)
    ->middleware(['auth', 'can:access-distribution-operator-panel'])
    ->name('distribution-operator.user-account');

Route::get('/child-supporter/dashboard', ChildSupporterDashboard::class)
    ->middleware(['auth', 'can:access-child-supporter-panel'])
    ->name('child-supporter.dashboard');

Route::get('/child-supporter/sponsor-registration', SponsorRegistration::class)
    ->middleware(['auth', 'can:access-admin-panel'])
    ->name('child-supporter.sponsor-registration');

Route::get('/child-supporter/sponsors', SponsorList::class)
    ->middleware(['auth', 'can:access-admin-panel'])
    ->name('child-supporter.sponsor-list');

Route::get('/child-supporter/system-settings/user-account', ChildSupporterUserAccount::class)
    ->middleware(['auth', 'can:access-child-supporter-panel'])
    ->name('child-supporter.user-account');
