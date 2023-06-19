<?php

namespace App\Providers;

use App\Models;
use App\Observers;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        Models\Role::observe(Observers\RoleObserver::class);
        Models\PrivilegeRole::observe(Observers\PrivilegeRoleObserver::class);
        Models\InstitutionUser::observe(Observers\InstitutionUserObserver::class);
        Models\InstitutionUserRole::observe(Observers\InstitutionUserRoleObserver::class);
        Models\Institution::observe(Observers\InstitutionObserver::class);
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
