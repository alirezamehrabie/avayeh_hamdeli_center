<?php

namespace App\Providers;

use App\Models\AcademicLevel;
use App\Models\AccountOwnerRelation;
use App\Models\Bank;
use App\Models\DisabilityType;
use App\Models\District;
use App\Models\EducationLevel;
use App\Models\Guardian;
use App\Models\GuardianRelationType;
use App\Models\HarmType;
use App\Models\InsuranceType;
use App\Models\JobType;
use App\Models\NeedLevelType;
use App\Models\Occupation;
use App\Models\Person;
use App\Models\SadaatRelation;
use App\Models\Service;
use App\Models\Skill;
use App\Models\SocialWorker;
use App\Models\SupportOrganization;
use App\Models\User;
use App\Models\VehicleType;
use App\Models\ResidenceStatusType;
use App\Observers\GuardianObserver;
use App\Observers\PersonObserver;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (Schema::hasTable('users')) {
            $createPayload = [
                'name' => User::PRIMARY_ADMIN_USERNAME,
                'password' => Hash::make('admin123'),
                'is_admin' => true,
            ];

            $updatePayload = [
                'is_admin' => true,
                'name' => User::PRIMARY_ADMIN_USERNAME,
            ];

            if (Schema::hasColumn('users', 'access_level')) {
                $createPayload['access_level'] = User::ACCESS_LEVEL_MANAGER;
                $updatePayload['access_level'] = User::ACCESS_LEVEL_MANAGER;
            }

            if (Schema::hasColumn('users', 'permissions')) {
                $createPayload['permissions'] = [User::PERMISSION_FULL_ACCESS];
                $updatePayload['permissions'] = [User::PERMISSION_FULL_ACCESS];
            }

            // اطمینان از وجود و سطح دسترسی حساب مدیریت اصلی
            $adminQuery = Schema::hasColumn('users', 'deleted_at')
                ? User::withTrashed()
                : User::withoutGlobalScope(SoftDeletingScope::class);

            $admin = $adminQuery->firstOrCreate(
                ['email' => User::PRIMARY_ADMIN_EMAIL],
                $createPayload
            );

            if (Schema::hasColumn('users', 'deleted_at') && $admin->trashed()) {
                $admin->restore();
            }

            $admin->update($updatePayload);
        }

        Guardian::observe(GuardianObserver::class);
        Person::observe(PersonObserver::class);

        Relation::enforceMorphMap([
            'person' => Person::class,
            'guardian' => Guardian::class,
            'social_worker' => SocialWorker::class,
            'sadaat_relation' => SadaatRelation::class,
            'skill' => Skill::class,
            'disability_type' => DisabilityType::class,
            'guardian_relation_type' => GuardianRelationType::class,
            'occupation' => Occupation::class,
            'job_type' => JobType::class,
            'insurance_type' => InsuranceType::class,
            'vehicle_type' => VehicleType::class,
            'residence_status_type' => ResidenceStatusType::class,
            'district' => District::class,
            'account_owner_relation' => AccountOwnerRelation::class,
            'bank' => Bank::class,
            'education_level' => EducationLevel::class,
            'academic_level' => AcademicLevel::class,
            'need_level_type' => NeedLevelType::class,
            'harm_type' => HarmType::class,
            'support_organization' => SupportOrganization::class,
        ]);

        Gate::define('manage-people', function (User $user) {
            return $user->canManagePeople();
        });

        Gate::define('people-register', function (User $user) {
            return $user->hasPermission(User::PERMISSION_PEOPLE_REGISTER);
        });

        Gate::define('people-edit', function (User $user) {
            return $user->hasPermission(User::PERMISSION_PEOPLE_EDIT);
        });

        Gate::define('people-delete', function (User $user) {
            return $user->hasPermission(User::PERMISSION_PEOPLE_DELETE);
        });

        // تعریف یک Gate کلی برای دسترسی به کل پنل ادمین
        Gate::define('access-admin-panel', function (User $user) {
            return $user->canAccessAdminPanel();
        });

        Gate::define('full-access', function (User $user) {
            return $user->hasFullAccess();
        });

        Gate::define('manage-social-workers', function (User $user) {
            return $user->canManageSocialWorkers();
        });

        Gate::define('access-social-worker-panel', function (User $user) {
            return $user->canAccessSocialWorkerPanel();
        });

        Gate::define('access-distribution-operator-panel', function (User $user) {
            return $user->canAccessDistributionOperatorPanel();
        });

        Gate::define('access-child-supporter-panel', function (User $user) {
            return $user->canAccessChildSupporterPanel();
        });

        Gate::define('view-distribution-operator-service', function (User $user, Service $service) {
            return $user->canAccessDistributionOperatorService($service);
        });
    }
}
