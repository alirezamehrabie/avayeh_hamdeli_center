<?php

namespace App\Providers;

use App\Contracts\Ai\GeneratesBeneficiaryCaseAnalysis;
use App\Contracts\Ai\GeneratesBeneficiarySearchFilters;
use App\Contracts\Ai\GeneratesFollowUpMessage;
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
use App\Models\ResidenceStatusType;
use App\Models\SadaatRelation;
use App\Models\Service;
use App\Models\Skill;
use App\Models\SocialWorker;
use App\Models\SupportOrganization;
use App\Models\User;
use App\Models\VehicleType;
use App\Observers\GuardianObserver;
use App\Observers\PersonObserver;
use App\Observers\ServiceNotificationObserver;
use App\Services\Ai\OpenAiBeneficiaryCaseAssistant;
use App\Services\Ai\OpenAiBeneficiarySearchFilterGenerator;
use App\Services\Ai\OpenAiFollowUpMessageGenerator;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(GeneratesBeneficiaryCaseAnalysis::class, OpenAiBeneficiaryCaseAssistant::class);
        $this->app->bind(GeneratesBeneficiarySearchFilters::class, OpenAiBeneficiarySearchFilterGenerator::class);
        $this->app->bind(GeneratesFollowUpMessage::class, OpenAiFollowUpMessageGenerator::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Guardian::observe(GuardianObserver::class);
        Person::observe(PersonObserver::class);
        Service::observe(ServiceNotificationObserver::class);

        // NotifyManagersOfUserLogin و NotifyManagersOfUserLogout در app/Listeners به‌صورت خودکار کشف می‌شوند.

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
            'service' => Service::class,
            'user' => User::class,
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

        // مدیریت و مشاهده اعلان‌های داشبورد مدیریت
        Gate::define('manage-notifications', function (User $user) {
            return $user->isManager();
        });

        Gate::define('manage-social-workers', function (User $user) {
            return $user->canManageSocialWorkers();
        });

        Gate::define('access-social-worker-panel', function (User $user) {
            return $user->canAccessSocialWorkerPanel();
        });

        Gate::define('edit-social-worker-deliveries', function (User $user) {
            return $user->canAccessSocialWorkerPanel() && filled($user->social_worker_id);
        });

        Gate::define('access-distribution-operator-panel', function (User $user) {
            return $user->canAccessDistributionOperatorPanel();
        });

        Gate::define('manage-distribution-operator-services', function (User $user) {
            return $user->canManageDistributionOperatorServices();
        });

        foreach (User::distributionAccessDefinitions() as $accessType => $definition) {
            Gate::define($definition['gate'], function (User $user) use ($accessType) {
                return $user->canAccessDistributionGate($accessType);
            });
        }

        Gate::define('access-child-supporter-panel', function (User $user) {
            return $user->canAccessChildSupporterPanel();
        });

        Gate::define('access-activity-operator-panel', function (User $user) {
            return $user->canAccessActivityOperatorPanel();
        });

        Gate::define('view-distribution-operator-service', function (User $user, Service $service) {
            return $user->canAccessDistributionOperatorService($service);
        });

        Gate::define('edit-distribution-operator-service', function (User $user, Service $service) {
            return $user->canEditDistributionOperatorService($service);
        });
    }
}
