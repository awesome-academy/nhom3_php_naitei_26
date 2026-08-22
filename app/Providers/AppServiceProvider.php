<?php

namespace App\Providers;

use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\Department;
use App\Models\ServiceCategory;
use App\Models\ServiceType;
use App\Models\User;
use App\Policies\ApplicationDocumentPolicy;
use App\Policies\ApplicationPolicy;
use App\Policies\DepartmentPolicy;
use App\Policies\ServiceCategoryPolicy;
use App\Policies\ServiceTypePolicy;
use App\Policies\UserPolicy;
use Dedoc\Scramble\Scramble;
use Illuminate\Support\Facades\Gate;
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
        Gate::policy(Application::class, ApplicationPolicy::class);
        Gate::policy(ApplicationDocument::class, ApplicationDocumentPolicy::class);
        Gate::policy(Department::class, DepartmentPolicy::class);
        Gate::policy(ServiceCategory::class, ServiceCategoryPolicy::class);
        Gate::policy(ServiceType::class, ServiceTypePolicy::class);
        Gate::policy(User::class, UserPolicy::class);

        Gate::define('viewApiDocs', function (?User $user) {
            if ($this->app->environment('local', 'testing', 'staging')) {
                return true;
            }

            return $user !== null && in_array($user->role->value, ['manager', 'super_admin'], true);
        });

        if (class_exists(Scramble::class)) {
            Scramble::registerUiRoute('docs/api');
            Scramble::registerJsonSpecificationRoute('docs/api.json');
        }
    }
}
