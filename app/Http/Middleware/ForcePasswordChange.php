<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class ForcePasswordChange
{
    public function handle($request, Closure $next)
    {
        $user = Auth::user();

        if (
            $user &&
            $user->password_must_change &&
            ! $request->routeIs('password.change', 'password.update', 'logout')
        ) {
            return redirect()->route('password.change');
        }

        return $next($request);
    }
}
