<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow, noarchive, nosnippet">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        @php
            $favicon = null;
            try {
                if (\Illuminate\Support\Facades\Schema::hasTable('settings')) {
                    $favicon = \App\Models\Setting::get('site_favicon');
                }
            } catch (\Exception $e) {
                // Silently fallback if DB is not ready
            }
        @endphp
        <link rel="icon" type="image/x-icon" href="{{ $favicon ? asset('storage/' . $favicon) : asset('favicon.ico') }}">
        @routes
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @inertiaHead
    </head>
    <body class="font-sans antialiased m-0">
        @inertia
    </body>
</html>