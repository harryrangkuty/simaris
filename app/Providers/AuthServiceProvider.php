<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\View;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        View::composer('*', function ($view) {
            $user = auth()->user();

            if ($user) {
                $user->load('roles');

                $view->with('authUser', [
                    'id' => $user->id,
                    'identifier' => $user->identifier,
                    'name' => $user->name,
                    'email' => $user->email,
                    'division' => $user->division,
                    'department' => $user->department,
                    'position' => $user->position,
                    'photo' => $user->photo,
                    'roles' => $user->roles->pluck('name'),
                    'permissions' => $user->getAllPermissions()->pluck('name'),
                ]);
            } else {
                $view->with('authUser', null);
            }
        });
    }
}
