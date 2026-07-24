<?php

namespace App\Providers;

use App\Enums\Role;
use App\Models\User;
use App\Services\Event\EventAccessService;
use App\Support\ActiveEventContext;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ActiveEventContext::class, function () {
            return new ActiveEventContext;
        });
    }

    public function boot(): void
    {
        $eventAccess = $this->app->make(EventAccessService::class);

        $ketuaEventCanAccess = function (User $user) use ($eventAccess): bool {
            $eventId = app(ActiveEventContext::class)->id();
            if ($eventId === null) {
                return false;
            }
            return $eventAccess->isUserAssignedToEvent($user, $eventId);
        };

        Gate::before(function (User $user) {
            if ($user->role === Role::SuperAdmin) {
                return true;
            }
        });

        Gate::define('view-dashboard', function (User $user) use ($ketuaEventCanAccess) {
            if ($user->role === Role::KetuaEvent) {
                return $ketuaEventCanAccess($user);
            }
            return $user->hasAnyRole(
                Role::Admin, Role::Sekretariat, Role::PjDivisi, Role::Viewer,
            );
        });

        Gate::define('view-master-data', fn (User $user) => $user->hasAnyRole(
            Role::SuperAdmin,
        ));

        Gate::define('manage-master-data', fn (User $user) => $user->hasAnyRole(
            Role::SuperAdmin,
        ));

        Gate::define('manage-events', fn (User $user) => $user->hasAnyRole(
            Role::SuperAdmin, Role::Admin,
        ));

        Gate::define('manage-registration', function (User $user) use ($ketuaEventCanAccess) {
            if ($user->role === Role::KetuaEvent) {
                return $ketuaEventCanAccess($user);
            }
            return $user->hasAnyRole(
                Role::Admin, Role::Sekretariat, Role::OperatorRegistrasi,
            );
        });

        Gate::define('manage-participants', function (User $user) use ($ketuaEventCanAccess) {
            if ($user->role === Role::KetuaEvent) {
                return $ketuaEventCanAccess($user);
            }
            return $user->hasAnyRole(
                Role::Admin, Role::Sekretariat,
            );
        });

        Gate::define('manage-attendance', function (User $user) use ($ketuaEventCanAccess) {
            if ($user->role === Role::KetuaEvent) {
                return $ketuaEventCanAccess($user);
            }
            return $user->hasAnyRole(
                Role::Admin, Role::Sekretariat, Role::PjDivisi, Role::OperatorScan,
            );
        });

        Gate::define('manage-sessions', function (User $user) use ($ketuaEventCanAccess) {
            if ($user->role === Role::KetuaEvent) {
                return $ketuaEventCanAccess($user);
            }
            return $user->hasAnyRole(
                Role::Admin, Role::Sekretariat,
            );
        });

        Gate::define('manage-qr-labels', fn (User $user) => $user->hasAnyRole(
            Role::SuperAdmin, Role::Admin, Role::Sekretariat,
        ));

        Gate::define('manage-secretariat', function (User $user) use ($ketuaEventCanAccess) {
            if ($user->role === Role::KetuaEvent) {
                return $ketuaEventCanAccess($user);
            }
            return $user->hasAnyRole(
                Role::Admin, Role::Sekretariat,
            );
        });

        Gate::define('manage-import', fn (User $user) => $user->hasAnyRole(
            Role::SuperAdmin, Role::Admin, Role::Sekretariat,
        ));

        Gate::define('view-reports', function (User $user) use ($ketuaEventCanAccess) {
            if ($user->role === Role::KetuaEvent) {
                return $ketuaEventCanAccess($user);
            }
            return $user->hasAnyRole(
                Role::Admin, Role::Sekretariat, Role::Viewer,
            );
        });

        Gate::define('manage-pengajian', fn (User $user) => $user->hasAnyRole(
            Role::SuperAdmin, Role::Admin, Role::Sekretariat,
        ));

        Gate::define('view-activity-log', fn (User $user) => $user->hasAnyRole(
            Role::SuperAdmin, Role::Admin, Role::Sekretariat,
        ));

        Gate::define('manage-users', fn (User $user) => $user->hasAnyRole(
            Role::SuperAdmin,
        ));
    }
}
