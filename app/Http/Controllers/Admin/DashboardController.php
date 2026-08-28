<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Akademik\Models\AcademicYear;
use Modules\Akademik\Models\Teacher;
use Modules\Akademik\Models\TeachingAgenda;
use Modules\Akademik\Models\AgendaAttendance;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        $date = Carbon::now('Asia/Jakarta')->toDateString();

        // 1. Statistik Guru
        $totalTeachers = Teacher::count();
        
        $guruMengajarHariIni = 0;
        if ($activeYear) {
            $guruMengajarHariIni = TeachingAgenda::where('academic_year_id', $activeYear->id)
                ->whereDate('date', $date)
                ->distinct('teacher_id')
                ->count('teacher_id');
        }
        
        $guruKosong = $totalTeachers - $guruMengajarHariIni;
        
        $teacherStats = [
            'total' => $totalTeachers,
            'mengajar' => $guruMengajarHariIni,
            'kosong' => $guruKosong,
        ];

        // 2. Statistik Kehadiran Siswa Hari Ini
        $attendanceStats = [
            'hadir' => 0, 'sakit' => 0, 'izin' => 0, 'alfa' => 0, 'dispen' => 0, 'telat' => 0, 'total' => 0
        ];

        if ($activeYear) {
            $stats = AgendaAttendance::query()
                ->select(
                    DB::raw("COUNT(CASE WHEN is_present = true AND (note != 'T' OR note IS NULL) THEN 1 END) as hadir"),
                    DB::raw("COUNT(CASE WHEN note = 'S' THEN 1 END) as sakit"),
                    DB::raw("COUNT(CASE WHEN note = 'I' THEN 1 END) as izin"),
                    DB::raw("COUNT(CASE WHEN note = 'A' THEN 1 END) as alfa"),
                    DB::raw("COUNT(CASE WHEN note = 'D' THEN 1 END) as dispen"),
                    DB::raw("COUNT(CASE WHEN note = 'T' THEN 1 END) as telat"),
                    DB::raw("COUNT(*) as total")
                )
                ->whereHas('agenda', function ($q) use ($date, $activeYear) {
                    $q->where('academic_year_id', $activeYear->id)
                      ->whereDate('date', $date);
                })
                ->first();
                
            if ($stats) {
                $attendanceStats = $stats->toArray();
            }
        }

        $pendingFacilityReportsCount = \App\Models\FacilityReport::query()->where('status', 'pending')->count();

        return Inertia::render('Dashboard', [
            'activeYear' => $activeYear,
            'date' => $date,
            'teacherStats' => $teacherStats,
            'attendanceStats' => $attendanceStats,
            'pendingFacilityReportsCount' => $pendingFacilityReportsCount,
        ]);
    }

    public function getTeacherDetails(Request $request)
    {
        $type = $request->query('type');
        $activeYear = AcademicYear::where('is_active', true)->first();
        $date = Carbon::now('Asia/Jakarta')->toDateString();
        $dayName = strtolower(Carbon::now('Asia/Jakarta')->translatedFormat('l'));

        if (!$activeYear) return response()->json([]);

        $agendasToday = TeachingAgenda::with(['teacher', 'classroom'])
            ->where('academic_year_id', $activeYear->id)
            ->whereDate('date', $date)
            ->get();

        $mengajarIds = $agendasToday->pluck('teacher_id')->unique()->toArray();

        if ($type === 'mengajar') {
            $teachers = Teacher::whereIn('id', $mengajarIds)->get();
        } elseif ($type === 'kosong') {
            $teachers = Teacher::whereNotIn('id', $mengajarIds)->get();
        } else {
            $teachers = Teacher::all();
        }

        $schedulesToday = \Modules\Akademik\Models\ScheduleDetail::with(['schedule.classroom'])
            ->where('day', $dayName)
            ->whereHas('schedule', function($q) use ($activeYear) {
                $q->where('academic_year_id', $activeYear->id);
            })->get();

        $records = [];
        foreach ($teachers as $teacher) {
            $kelasJam = [];
            
            if ($type === 'mengajar' || $type === 'all') {
                $agendas = $agendasToday->where('teacher_id', $teacher->id);
                foreach ($agendas as $agenda) {
                    $kelasJam[] = ($agenda->classroom->name ?? '-') . ' (' . $agenda->start_slot . '-' . $agenda->end_slot . ')';
                }
            }
            
            if ($type === 'kosong') {
                $schedules = $schedulesToday->filter(function($item) use ($teacher) {
                    return $item->schedule->teacher_id == $teacher->id;
                });
                foreach ($schedules as $schedule) {
                    $kelasJam[] = ($schedule->schedule->classroom->name ?? '-') . ' (' . $schedule->start_slot . '-' . $schedule->end_slot . ')';
                }
            }

            $keterangan = count($kelasJam) > 0 ? implode(', ', $kelasJam) : 'Tidak ada jadwal/agenda';

            $records[] = [
                'id' => $teacher->id,
                'name' => $teacher->full_name,
                'kelas_jam' => $keterangan,
            ];
        }

        return response()->json($records);
    }

    public function getStudentDetails(Request $request)
    {
        $type = $request->query('type'); // S, I, A, D, T, H
        $activeYear = AcademicYear::where('is_active', true)->first();
        $date = Carbon::now('Asia/Jakarta')->toDateString();

        if (!$activeYear) return response()->json([]);

        $query = AgendaAttendance::with(['student', 'agenda.subject', 'agenda.classroom'])
            ->whereHas('agenda', function ($q) use ($date, $activeYear) {
                $q->where('academic_year_id', $activeYear->id)
                  ->whereDate('date', $date);
            });

        if ($type === 'H') {
            $query->where('is_present', true)->where(function($q) {
                $q->where('note', '!=', 'T')->orWhereNull('note');
            });
        } else {
            $query->where('note', $type);
        }

        $records = $query->get()->map(function ($record) {
            $jamKe = $record->agenda ? "({$record->agenda->start_slot}-{$record->agenda->end_slot})" : "";
            return [
                'id' => $record->id,
                'student_name' => $record->student->full_name ?? '-',
                'classroom_name' => $record->agenda->classroom->name ?? '-',
                'subject_name' => ($record->agenda->subject->name ?? '-') . " $jamKe",
                'note' => $record->note,
                'is_present' => $record->is_present,
            ];
        });

        return response()->json($records);
    }
}
