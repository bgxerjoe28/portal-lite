<?php

namespace App\Providers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Carbon;

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
        // --- KONFIGURASI LOCALIZATION ---
        Carbon::setLocale('id');
        date_default_timezone_set('Asia/Jakarta');
        
        //---- Version Konfig
        $path = base_path('version.json');
        if (file_exists($path)) {
            $data = json_decode(file_get_contents($path), true);

            if (is_array($data)) {
                config([
                    'version.version' => $data['version'] ?? config('version.version'),
                    'version.build' => $data['build'] ?? config('version.build'),
                    'version.channel' => $data['channel'] ?? config('version.channel'),
                ]);
            }
        }
        
        // --- AUTORISASI PULSE (Hanya Admin) ---
        \Illuminate\Support\Facades\Gate::define('viewPulse', function ($user = null) {
            if ($user && $user->hasRole('admin')) {
                return true;
            }
            abort(404);
        });
    }
}
