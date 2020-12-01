<?php

namespace App\Providers;

use App\Models\User;
use App\Models\UserRole;
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
        $roles = UserRole::all();
        $roles->flatMap(function ($role) {
            return $role->privileges()->get()->map(function ($privileges) use ($role) {
                return [$role->name, $privileges->name];
            });
        })->each(function ($entry) {
            Gate::define($entry[1], function ($user) use ($entry) {
                return $user->role()->get()->name == $entry[0];
            });
        });


        //    $this->app['auth']->viaRequest('api', function ($request) {
        //        if ($request->input('api_token')) {
        //            return User::where('api_token', $request->input('api_token'))->first();
        //        }
        //    });
    }
}
