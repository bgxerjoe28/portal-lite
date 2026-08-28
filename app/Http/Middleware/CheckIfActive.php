<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckIfActive
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check() && ! Auth::user()->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/')
                ->with('error', 'Akun Anda telah dinonaktifkan. Hubungi administrator.');
        }

        if (Auth::check() && ! Auth::user()->hasRole('admin') && \App\Models\Setting::get('site_active', '1') === '0') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/')
                ->with('error', 'Situs sedang dalam mode pemeliharaan (Maintenance Mode). Hanya Admin yang dapat mengakses sistem saat ini.');
        }

        return $next($request);
    }
}
