<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        // Log::info('🔥 HandleInertiaRequests::share() called', [
        //    'version' => config('version.version'),
        // ]);

        $settings = [
            'school_name' => 'SMAN 16 SEMARANG',
            'school_address' => '',
            'school_phone' => '',
            'school_email' => '',
            'principal_name' => '',
            'principal_nip' => '',
            'site_logo' => null,
            'site_favicon' => null,
        ];

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('settings')) {
                $settings = [
                    'school_name' => \App\Models\Setting::get('school_name', 'SMAN 16 SEMARANG'),
                    'school_address' => \App\Models\Setting::get('school_address', ''),
                    'school_phone' => \App\Models\Setting::get('school_phone', ''),
                    'school_email' => \App\Models\Setting::get('school_email', ''),
                    'principal_name' => \App\Models\Setting::get('principal_name', ''),
                    'principal_nip' => \App\Models\Setting::get('principal_nip', ''),
                    'site_logo' => \App\Models\Setting::get('site_logo', null),
                    'site_favicon' => \App\Models\Setting::get('site_favicon', null),
                    'lock_daftar_ulang_login' => \App\Models\Setting::get('lock_daftar_ulang_login', '0') === '1',
                    'site_active' => \App\Models\Setting::get('site_active', '1') === '1',
                ];
            }
        } catch (\Exception $e) {
            // Biarkan default jika database belum dimigrasi
        }

        $academicYearName = 'Belum Diset';
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('academic_years')) {
                $activeYear = \Modules\Akademik\Models\AcademicYear::where('is_active', true)->first();
                if ($activeYear) {
                    $academicYearName = $activeYear->name . ' - ' . ucfirst($activeYear->semester);
                }
            }
        } catch (\Exception $e) {}

        $host = $request->getHost();
        $isStaging = str_contains($host, 'uji-portal') || 
                     str_contains($host, 'staging') || 
                     str_contains($host, 'test') || 
                     str_contains($host, 'localhost') || 
                     str_contains($host, 'laragon') || 
                     str_contains($host, 'portal-sma') ||
                     config('app.env') === 'staging' ||
                     env('APP_SHOW_CHANGELOG', false);

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user() ? $request->user()->load('roles') : null,
                'permissions' => $request->user() ? $request->user()->getAllPermissions()->pluck('name') : [],
                'menu' => $request->user()
                    ? \App\Services\NewMenuService::getDynamicMenu()
                    : [],
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error'   => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'import_errors' => fn () => $request->session()->get('import_errors'),
            ],
            'settings' => $settings,
            'app' => [
                'version'    => config('version.version'),
                'build'      => config('version.build'),
                'channel'    => config('version.channel'),
                'settings'   => $settings,
                'is_staging' => $isStaging,
                'changelog'  => $isStaging ? $this->getChangelogContent() : null,
                'academic_year_name' => $academicYearName,
                'production_url' => env('PRODUCTION_URL', 'https://portal.sman16smg.sch.id'),
            ],
            // Impersonation state — dibaca oleh semua layout untuk banner
            'impersonating' => (function () use ($request) {
                $manager = app('impersonate');
                if (!$manager->isImpersonating()) return null;
                $impersonator = \App\Models\User::query()->find($manager->getImpersonatorId());
                return [
                    'active'         => true,
                    'admin_name'     => $impersonator?->name ?? 'Admin',
                    'leave_url'      => route('admin.users.impersonate.leave'),
                ];
            })(),
            'extracurricular_warning' => fn () => ['show' => false],
            'tka_warning' => (function () use ($request) {
                if (!$request->user() || !$request->user()->hasRole('siswa')) {
                    return ['show' => false];
                }
                try {
                    $enabled = \App\Models\Setting::get('tka_registration_active', '0') === '1';
                    if (!$enabled) return ['show' => false];

                    $activeYear = \Modules\Akademik\Models\AcademicYear::where('is_active', true)->first();
                    if (!$activeYear) return ['show' => false];

                    $student = \Modules\Akademik\Models\Student::where('user_id', $request->user()->id)->first();
                    if (!$student) return ['show' => false];

                    $cs = \Modules\Akademik\Models\ClassroomStudent::with('classroom')
                        ->where('student_id', $student->id)
                        ->where('academic_year_id', $activeYear->id)
                        ->first();
                    if (!$cs || !$cs->classroom) return ['show' => false];

                    $className = strtoupper(trim($cs->classroom->name));
                    if (!str_starts_with($className, 'XII') && !str_starts_with($className, '12')) {
                        return ['show' => false]; // Only for class XII
                    }

                    $chosenTkaIds = \Modules\Akademik\Models\TkaStudent::where('student_id', $student->id)
                        ->where('academic_year_id', $activeYear->id)
                        ->pluck('tka_subject_id')
                        ->toArray();

                    $tkaCount = count($chosenTkaIds);
                    if ($tkaCount >= 2) {
                        return ['show' => false]; // Already registered 2 subjects
                    }

                    // Fetch active TKA subjects
                    $subjects = \Modules\Akademik\Models\TkaSubject::where('is_active', true)->get(['id', 'name', 'code']);

                    return [
                        'show' => true,
                        'chosen_count' => $tkaCount,
                        'chosen_ids' => $chosenTkaIds,
                        'message' => "Anda adalah siswa Kelas XII yang diwajibkan untuk memilih 2 (dua) Mata Pelajaran TKA pada tahun ajaran ini." . ($tkaCount === 1 ? " (Anda baru memilih 1 mapel, kurang 1 mapel lagi)." : ""),
                        'subjects' => $subjects
                    ];
                } catch (\Exception $e) {
                    return ['show' => false];
                }
            })(),
        ]);
    }

    private function getChangelogContent()
    {
        $path = base_path('CHANGELOG.md');
        if (!file_exists($path)) {
            return [];
        }

        $raw = file_get_contents($path);
        $releases = [];
        $lines = explode("\n", $raw);
        $currentRelease = null;

        foreach ($lines as $line) {
            if (preg_match('/^##\s+\[(.*?)\]\s*-\s*(.*)/', trim($line), $matches)) {
                if ($currentRelease) {
                    $currentRelease['content'] = trim($currentRelease['content']);
                    $releases[] = $currentRelease;
                }
                $currentRelease = [
                    'version' => $matches[1],
                    'date'    => $matches[2],
                    'content' => '',
                ];
            } elseif ($currentRelease) {
                $currentRelease['content'] .= $line . "\n";
            }
        }
        if ($currentRelease) {
            $currentRelease['content'] = trim($currentRelease['content']);
            $releases[] = $currentRelease;
        }

        return array_slice($releases, 0, 15);
    }
}
