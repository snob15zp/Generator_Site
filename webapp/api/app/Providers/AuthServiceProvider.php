<?php

namespace App\Providers;

use App\Models\UserPrivileges;
use App\Models\UserRole;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Boot the authentication services for the application.
     *
     * @return void
     */
    public function boot()
    {
        try {
            $roles = UserRole::query()->get();
            UserPrivileges::query()->get()->mapToGroups(function ($item) use ($roles) {
                $role = $roles->first(function ($role) use ($item) {
                    return $role->id == $item->user_role_id;
                });
                return [$item->name => $role->name];
            })->each(function ($roles, $privilege) {
                Gate::define($privilege, function ($user) use ($roles) {
                    return collect($roles)->contains($user->role->name);
                });
            });
        } catch(QueryException $e) {
            Log::error("Unable to boot AuthServiceProvider");
        }
    }
}
