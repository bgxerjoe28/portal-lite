<?php

namespace Modules\Akademik\Http\Controllers\GuruBK;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Akademik\Models\AcademicYear;
use Modules\Akademik\Models\AgendaAttendance;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GuruBKDashboardController extends Controller
{
    public function index(Request $request)
    {
        $activeYear = AcademicYear::where('is_active', true)->firstOrFail();
        
        $timeRange = $request->query('time_range', 'hari'); // hari, minggu, bulan, semester, tahun
        $date = $request->query('date', Carbon::now('Asia/Jakarta')->toDateString());
        $now = Carbon::parse($date, 'Asia/Jakarta');

        $query = AgendaAttendance::query()
            ->whereHas('agenda', function ($q) use ($activeYear) {
                $q->where('academic_year_id', $activeYear->id);
            });

        if ($timeRange === 'hari') {
            $query->whereHas('agenda', fn($q) => $q->whereDate('date', $now->toDateString()));
        } elseif ($timeRange === 'minggu') {
            $query->whereHas('agenda', fn($q) => $q->whereBetween('date', [$now->copy()->startOfWeek()->toDateString(), $now->copy()->endOfWeek()->toDateString()]));
        } elseif ($timeRange === 'bulan') {
            $query->whereHas('agenda', fn($q) => $q->whereMonth('date', $now->month)->whereYear('date', $now->year));
        } elseif ($timeRange === 'semester') {
            // Already scoped by activeYear
        } elseif ($timeRange === 'tahun') {
            $yearName = $activeYear->name;
            $yearIds = AcademicYear::where('name', $yearName)->pluck('id');
            $query = AgendaAttendance::query()
                ->whereHas('agenda', function ($q) use ($yearIds) {
                    $q->whereIn('academic_year_id', $yearIds);
                });
        }
            
        $attendanceStats = (clone $query)->select(
            DB::raw("COUNT(CASE WHEN is_present = true AND (note != 'T' OR note IS NULL) THEN 1 END) as hadir"),
            DB::raw("COUNT(CASE WHEN note = 'S' THEN 1 END) as sakit"),
            DB::raw("COUNT(CASE WHEN note = 'I' THEN 1 END) as izin"),
            DB::raw("COUNT(CASE WHEN note = 'A' THEN 1 END) as alfa"),
            DB::raw("COUNT(CASE WHEN note = 'D' THEN 1 END) as dispen"),
            DB::raw("COUNT(CASE WHEN note = 'T' THEN 1 END) as telat"),
            DB::raw("COUNT(*) as total")
        )->first();

        // Khusus Telat yang selalu dihitung berdasarkan semester aktif
        $telatSemester = AgendaAttendance::where('note', 'T')
            ->whereHas('agenda', fn($q) => $q->where('academic_year_id', $activeYear->id))
            ->count();
            
        // -------------------------------------------------------------
        // BARU: Alfa Sistem (dari tabel Kesiswaan\Attendance)
        // -------------------------------------------------------------
        $queryAlfaSekolah = \Modules\Kesiswaan\Models\Attendance::query()
            ->where('academic_year_id', $activeYear->id)
            ->where('status', 'A');

        if ($timeRange === 'hari') {
            $queryAlfaSekolah->whereDate('date', $now->toDateString());
        } elseif ($timeRange === 'minggu') {
            $queryAlfaSekolah->whereBetween('date', [$now->copy()->startOfWeek()->toDateString(), $now->copy()->endOfWeek()->toDateString()]);
        } elseif ($timeRange === 'bulan') {
            $queryAlfaSekolah->whereMonth('date', $now->month)->whereYear('date', $now->year);
        } elseif ($timeRange === 'tahun') {
            $yearName = $activeYear->name;
            $yearIds = AcademicYear::where('name', $yearName)->pluck('id');
            $queryAlfaSekolah->whereIn('academic_year_id', $yearIds);
        }
        $alfaSekolah = $queryAlfaSekolah->count();
            
        return Inertia::render('GuruBK/Dashboard/Index', [
            'filters' => [
                'time_range' => $timeRange,
                'date' => $date,
            ],
            'attendanceStats' => $attendanceStats,
            'telatSemester' => $telatSemester,
            'alfaSekolah' => $alfaSekolah,
            'activeYear' => $activeYear,
        ]);
    }

    public function details(Request $request)
    {
        $activeYear = AcademicYear::where('is_active', true)->firstOrFail();
        $timeRange = $request->query('time_range', 'hari');
        $date = $request->query('date', Carbon::now('Asia/Jakarta')->toDateString());
        $status = $request->query('status'); // S, I, A, D, T
        
        $now = Carbon::parse($date, 'Asia/Jakarta');

        $query = AgendaAttendance::with([
                'student:id,full_name,nis',
                'agenda.classroom:id,name',
                'agenda.subject:id,name'
            ])
            ->whereHas('agenda', function ($q) use ($activeYear) {
                $q->where('academic_year_id', $activeYear->id);
            });

        if ($timeRange === 'hari') {
            $query->whereHas('agenda', fn($q) => $q->whereDate('date', $now->toDateString()));
        } elseif ($timeRange === 'minggu') {
            $query->whereHas('agenda', fn($q) => $q->whereBetween('date', [$now->copy()->startOfWeek()->toDateString(), $now->copy()->endOfWeek()->toDateString()]));
        } elseif ($timeRange === 'bulan') {
            $query->whereHas('agenda', fn($q) => $q->whereMonth('date', $now->month)->whereYear('date', $now->year));
        } elseif ($timeRange === 'semester') {
            // Already scoped
        } elseif ($timeRange === 'tahun') {
            $yearName = $activeYear->name;
            $yearIds = AcademicYear::where('name', $yearName)->pluck('id');
            $query = AgendaAttendance::with([
                    'student:id,full_name,nis',
                    'agenda.classroom:id,name',
                    'agenda.subject:id,name'
                ])
                ->whereHas('agenda', function ($q) use ($yearIds) {
                    $q->whereIn('academic_year_id', $yearIds);
                });
        }

        if ($status === 'A_sekolah') {
            $queryAlfaSekolah = \Modules\Kesiswaan\Models\Attendance::with([
                'student:id,full_name,nis',
                'student.currentClassroom:classrooms.id,classrooms.name'
            ])
            ->where('academic_year_id', $activeYear->id)
            ->where('status', 'A');

            if ($timeRange === 'hari') {
                $queryAlfaSekolah->whereDate('date', $now->toDateString());
            } elseif ($timeRange === 'minggu') {
                $queryAlfaSekolah->whereBetween('date', [$now->copy()->startOfWeek()->toDateString(), $now->copy()->endOfWeek()->toDateString()]);
            } elseif ($timeRange === 'bulan') {
                $queryAlfaSekolah->whereMonth('date', $now->month)->whereYear('date', $now->year);
            } elseif ($timeRange === 'tahun') {
                $yearName = $activeYear->name;
                $yearIds = AcademicYear::where('name', $yearName)->pluck('id');
                $queryAlfaSekolah->whereIn('academic_year_id', $yearIds);
            }

            $attendances = $queryAlfaSekolah->orderBy('created_at', 'desc')->get();
        } else {
            // Logika untuk absen mapel (AgendaAttendance)
            if ($status === 'H') {
                $query->where('is_present', true)->where(function($q) {
                    $q->where('note', '!=', 'T')->orWhereNull('note');
                });
            } else {
                $query->where('note', $status);
            }
            $attendances = $query->orderBy('created_at', 'desc')->get();
        }

        return Inertia::render('GuruBK/Dashboard/Details', [
            'filters' => [
                'time_range' => $timeRange,
                'date' => $date,
                'status' => $status
            ],
            'attendances' => $attendances,
            'activeYear' => $activeYear,
        ]);
    }
}
