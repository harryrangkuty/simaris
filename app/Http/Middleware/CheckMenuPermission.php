<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\ApplicationMenu;

class CheckMenuPermission
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        $routeName = Route::currentRouteName();

        // Cek menu berdasarkan route URL atau key
        $menu = ApplicationMenu::where('url', $routeName)->first();

        if ($menu && $menu->permission) {
            if (! $user || (! $user->hasRole('superadmin') && ! $user->can($menu->permission))) {
                abort(403, 'Unauthorized access.');
            }
        }

        return $next($request);
    }
}
