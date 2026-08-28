<?php

namespace Modules\Akademik\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Modules\Akademik\Models\AcademicYear;
use Modules\Akademik\Models\Classroom;
use Modules\Akademik\Models\ScheduleDetail;
use Modules\Akademik\Models\Teacher;
use Modules\Akademik\Models\TeachingAgenda;

class GuruDashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    private function resolveView(string $viewName)
    {
        // Selalu gunakan view mobile/smartphone untuk guru (desktop view dinonaktifkan)
        return "Dashboard/Mobile/{$viewName}";
    }

    public function index()
    {
        $teacher = Teacher::where('user_id', Auth::id())->firstOrFail();
        $activeYear = AcademicYear::where('is_active', true)->firstOrFail();
        $today = Carbon::today();
        
        $isWaliKelas = Classroom::where('teacher_id', $teacher->id)
            ->where('academic_year_id', $activeYear->id)
            ->exists();
        $dayName = strtolower($today->format('l')); // contoh: 'monday' -> kita butuh map ke indo jika db pakai indo

        // Map hari ke Indonesia jika perlu
        $mapHari = [
            'monday' => 'senin', 'tuesday' => 'selasa', 'wednesday' => 'rabu',
            'thursday' => 'kamis', 'friday' => 'jumat', 'saturday' => 'sabtu', 'sunday' => 'minggu',
        ];
        $hariIndo = $mapHari[$dayName];
        $panggilan = ($teacher->gender) ? 'Pak' : 'Bu';

        // 1. Statistik Kelas Hari Ini
        $totalSchedulesToday = ScheduleDetail::whereHas('schedule', function ($q) use ($teacher, $activeYear) {
            $q->where('teacher_id', $teacher->id)
              ->where('academic_year_id', $activeYear->id);
        })->where('day', $hariIndo)->count();

        $agendasTodayCount = TeachingAgenda::where('teacher_id', $teacher->id)
            ->where('academic_year_id', $activeYear->id)
            ->whereDate('date', $today)
            ->count();

        $attendancePercentage = $totalSchedulesToday > 0
            ? round(($agendasTodayCount / $totalSchedulesToday) * 100)
            : 0;

        // 2. Agenda Aktif (Sederhana: Ambil agenda terbaru hari ini yang belum lewat jamnya)
        // Note: Idealnya membandingkan jam sekarang dengan start_slot jam pelajaran
        $activeAgenda = TeachingAgenda::with(['classroom', 'subject', 'scheduleDetail'])
            ->where('teacher_id', $teacher->id)
            ->where('academic_year_id', $activeYear->id)
            ->whereDate('date', $today)
            ->orderBy('created_at', 'desc')
            ->first();

        // 3. Aktivitas Terakhir (5 Agenda terakhir)
        $recentAgendas = TeachingAgenda::with(['classroom', 'subject'])
            ->where('teacher_id', $teacher->id)
            ->where('academic_year_id', $activeYear->id)
            ->withCount([
                'attendances as hadir_count' => function ($query) {
                    $query->where('is_present', true);
                },
            ])
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // 4. Count unread facility report updates
        $unreadFacilityReports = $this->countUnreadFacilityReports();

        // 5. Rekap Presensi Wali Kelas Hari Ini (jika Wali Kelas)
        $waliKelasSummary = $isWaliKelas
            ? $this->buildWaliKelasSummary($teacher->id, $activeYear->id, $today)
            : null;

        $academicEvents = \Modules\Akademik\Models\AcademicEvent::where('academic_year_id', $activeYear->id)
            ->orderBy('start_date')
            ->get();

        $isExtracurricularTeacher = \Illuminate\Support\Facades\DB::table('extracurricular_teachers')
            ->join('extracurriculars', 'extracurricular_teachers.extracurricular_id', '=', 'extracurriculars.id')
            ->where('extracurricular_teachers.teacher_id', $teacher->id)
            ->where('extracurriculars.academic_year_id', $activeYear->id)
            ->where('extracurriculars.is_active', true)
            ->exists();

        // Ambil disposisi surat pending dari Kepala Sekolah untuk Guru yang login
        $disposisiPending = \Modules\Surat\Models\DisposisiTujuan::with([
            'disposisi.suratMasuk:id,no_surat_masuk,perihal,asal_surat,tanggal_surat',
            'disposisi.didisposisikanOleh:id,name',
        ])
        ->where('pegawai_id', Auth::id())
        ->where('status_tindak_lanjut', '!=', 'selesai')
        ->latest()
        ->get();

        return Inertia::render($this->resolveView('DashboardGuru'), [
            'is_walikelas' => $isWaliKelas,
            'is_extracurricular_teacher' => $isExtracurricularTeacher,
            'wali_kelas_summary' => $waliKelasSummary,
            'teacher_info' => [
                'nama_panggilan' => $panggilan,
                'nama_lengkap' => $teacher->name,
            ],
            'stats' => [
                'today_classes' => $totalSchedulesToday,
                'presensi_done' => $agendasTodayCount,
                'percentage' => $attendancePercentage,
            ],
            'activeAgenda' => $activeAgenda,
            'recentAgendas' => $recentAgendas,
            'unread_facility_reports' => $unreadFacilityReports,
            'events' => $academicEvents,
            'disposisi_pending' => $disposisiPending,
            // Kirim daftar permission guru agar dashboard bisa filter Aksi Cepat
            'guru_permissions' => Auth::user()->getAllPermissions()->pluck('name')->values(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        abort(404);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {}

    /**
     * Show the specified resource.
     */
    public function show(int $id)
    {
        abort(404);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(int $id)
    {
        abort(404);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $id) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id) {}

    /**
     * Count unread facility report updates for the current user.
     */
    private function countUnreadFacilityReports(): int
    {
        /** @var int $count */
        $count = \App\Models\FacilityReport::where('reported_by', Auth::id())
            ->whereHas('updates', function ($q) {
                $q->where('is_read_by_reporter', false);
            })->count();

        return $count;
    }

    /**
     * Build the wali kelas daily attendance summary.
     */
    private function buildWaliKelasSummary(int $teacherId, int $activeYearId, Carbon $today): ?array
    {
        $waliClassroom = Classroom::where('teacher_id', $teacherId)
            ->where('academic_year_id', $activeYearId)
            ->first();

        if (! $waliClassroom || ! class_exists('Modules\Kesiswaan\Services\AttendanceReportService')) {
            return null;
        }

        $reportService = app('Modules\Kesiswaan\Services\AttendanceReportService');
        $dailyData = $reportService->getDailyReport($waliClassroom->id, $today->format('Y-m-d'));

        // Ambil semua record Alpa Mapel hari ini untuk kelas ini (dengan detail: mapel & jam)
        $alfaMapelRecords = \Modules\Akademik\Models\AgendaAttendance::where('note', 'A')
            ->with([
                'student:id,full_name,nis',
                'agenda:id,classroom_id,subject_id,start_slot,end_slot,teacher_id',
                'agenda.subject:id,name',
                'agenda.teacher.user:id,name',
            ])
            ->whereHas('agenda', function ($q) use ($today, $waliClassroom) {
                $q->whereDate('date', $today)
                  ->where('classroom_id', $waliClassroom->id);
            })->get();

        // Susun data TH per siswa: daftar mapel yang dibolos
        $thByStudent = [];
        foreach ($alfaMapelRecords as $rec) {
            $sid = $rec->student_id;
            if (! isset($thByStudent[$sid])) {
                $thByStudent[$sid] = [
                    'student_id'  => $sid,
                    'name'        => $rec->student?->full_name ?? '-',
                    'nis'         => $rec->student?->nis ?? '-',
                    'mapel_absen' => [],
                ];
            }
            $thByStudent[$sid]['mapel_absen'][] = [
                'subject' => $rec->agenda?->subject?->name ?? '-',
                'jam_ke'  => $rec->agenda?->start_slot ?? '-',
                'guru'    => $rec->agenda?->teacher?->user?->name ?? '-',
            ];
        }

        // Hitung jumlah siswa unik TH (bukan jumlah record)
        $thCount = count($thByStudent);
        $dailyData['summary']['TH'] = $thCount;

        return [
            'classroom_name' => $waliClassroom->name,
            'classroom_id'   => $waliClassroom->id,
            'summary'        => $dailyData['summary'] ?? [],
            'students'       => $dailyData['students'] ?? [],
            'th_students'    => array_values($thByStudent),
            'date'           => $today->translatedFormat('l, d F Y'),
        ];
    }
}
