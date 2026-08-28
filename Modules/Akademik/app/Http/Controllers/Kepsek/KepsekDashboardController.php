<?php

namespace Modules\Akademik\Http\Controllers\Kepsek;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Akademik\Models\AcademicYear;
use Modules\Akademik\Models\TeachingAgenda;
use Modules\Akademik\Models\AgendaAttendance;
use Modules\Akademik\Models\Classroom;
use Modules\Akademik\Models\Teacher;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Akademik\Models\ScheduleDetail;

class KepsekDashboardController extends Controller
{
    public function index(Request $request)
    {
        $activeYear = AcademicYear::where('is_active', true)->firstOrFail();

        $date        = $request->query('date', Carbon::now('Asia/Jakarta')->toDateString());
        $classroomId = $request->query('classroom_id');
        $teacherId   = $request->query('teacher_id');

        $agendas = $this->buildAgendaQuery($activeYear->id, $date, $classroomId, $teacherId);

        // Statistik Presensi untuk Tanggal Terpilih
        $attendanceStats = AgendaAttendance::query()
            ->select(
                DB::raw("COUNT(CASE WHEN is_present = true AND (note != 'T' OR note IS NULL) THEN 1 END) as hadir"),
                DB::raw("COUNT(CASE WHEN note = 'S' THEN 1 END) as sakit"),
                DB::raw("COUNT(CASE WHEN note = 'I' THEN 1 END) as izin"),
                DB::raw("COUNT(CASE WHEN is_present = false AND (note IS NULL OR note IN ('A', 'absen', 'tidak_hadir')) THEN 1 END) as alfa"),
                DB::raw("COUNT(CASE WHEN note = 'D' THEN 1 END) as dispen"),
                DB::raw("COUNT(CASE WHEN note = 'T' THEN 1 END) as telat"),
                DB::raw("COUNT(*) as total")
            )
            ->whereHas('agenda', function ($q) use ($date, $activeYear) {
                $q->where('academic_year_id', $activeYear->id);
                if ($date) {
                    $q->whereDate('date', $date);
                }
            })
            ->first();

        // Data Filter Dropdown
        $classrooms = Classroom::where('academic_year_id', $activeYear->id)
            ->select('id', 'name')->orderBy('name')->get();
        $teachers = Teacher::with('user:id,name')->get()->map(fn($t) => [
            'id'   => $t->id,
            'name' => $t->user->name ?? $t->nip,
        ])->sortBy('name')->values();

        [$schedules, $filledSlots, $selectedClassroom] = $this->buildScheduleData(
            $request->classroom_id, $activeYear->id, $date
        );

        return Inertia::render('Kepsek/Dashboard/Index', [
            'agendas'           => $agendas,
            'classrooms'        => $classrooms,
            'teachers'          => $teachers,
            'filters'           => [
                'date'          => $date,
                'classroom_id'  => $classroomId,
                'teacher_id'    => $teacherId,
            ],
            'attendanceStats'   => $attendanceStats,
            'schedules'         => $schedules,
            'filledSlots'       => $filledSlots,
            'selectedDate'      => $date,
            'selectedClassroom' => $selectedClassroom,
            'activeYear'        => $activeYear,
        ]);
    }
    
    public function showAgenda(int $id)
    {
        $agenda = TeachingAgenda::with([
            'teacher.user:id,name',
            'classroom:id,name',
            'subject:id,name',
            'tp:id,kode_tp,rumusan_tp',
            'schedule.details:id,schedule_id,start_slot,end_slot',
            'attendances' => function ($query) {
                $query->with('student:id,full_name,nis');
            },
            'attachments',
        ])->findOrFail($id);
        
        return response()->json($agenda);
    }
    
    public function teacherHistory(int $id, Request $request)
    {
        $activeYear = AcademicYear::where('is_active', true)->firstOrFail();
        $teacher = Teacher::with('user:id,name')->findOrFail($id);
        
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');
        
        $query = TeachingAgenda::query()
            ->with([
                'classroom:id,name',
                'subject:id,name',
                'tp:id,kode_tp,rumusan_tp',
                'attachments',
                'scheduleDetail',
            ])
            ->withCount([
                'attendances as total_siswa',
                'attendances as hadir_count' => fn ($q) => $q->where('is_present', true)->where(fn($sub) => $sub->whereNull('note')->orWhere('note', '!=', 'T')),
                'attendances as tidak_hadir_count' => fn ($q) => $q->where('is_present', false),
                'attendances as sakit_count' => fn ($q) => $q->where('note', 'S'),
                'attendances as izin_count' => fn ($q) => $q->where('note', 'I'),
                'attendances as alfa_count' => fn ($q) => $q->where('is_present', false)->where(fn($sub) => $sub->whereNull('note')->orWhereIn('note', ['A', 'absen', 'tidak_hadir'])),
                'attendances as dispen_count' => fn ($q) => $q->where('note', 'D'),
                'attendances as telat_count' => fn ($q) => $q->where('note', 'T'),
            ])
            ->where('teacher_id', $teacher->id)
            ->where('academic_year_id', $activeYear->id);
            
        if ($dateFrom) {
            $query->whereDate('date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('date', '<=', $dateTo);
        }
        
        $agendas = $query->orderBy('date', 'desc')->paginate(15)->withQueryString();
        
        return Inertia::render('Kepsek/Teacher/History', [
            'teacher' => $teacher,
            'agendas' => $agendas,
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'activeYear' => $activeYear,
        ]);
    }
    
    public function lateStudents(Request $request)
    {
        $activeYear = AcademicYear::where('is_active', true)->firstOrFail();
        $date = $request->query('date', Carbon::now('Asia/Jakarta')->toDateString());
        
        $lates = AgendaAttendance::query()
            ->with([
                'student:id,full_name,nis',
                'agenda:id,date,teacher_id,classroom_id,subject_id,schedule_detail_id',
                'agenda.teacher.user:id,name',
                'agenda.classroom:id,name',
                'agenda.subject:id,name',
            ])
            ->where('note', 'T')
            ->whereHas('agenda', function ($q) use ($date, $activeYear) {
                $q->whereDate('date', $date)
                  ->where('academic_year_id', $activeYear->id);
            })
            ->get();
            
        return Inertia::render('Kepsek/Student/Lates', [
            'lates' => $lates,
            'filters' => [
                'date' => $date,
            ],
            'activeYear' => $activeYear,
        ]);
    }

    public function alfaStudents(Request $request)
    {
        $activeYear = AcademicYear::where('is_active', true)->firstOrFail();
        $date = $request->query('date', Carbon::now('Asia/Jakarta')->toDateString());
        
        $alfas = AgendaAttendance::query()
            ->with([
                'student:id,full_name,nis',
                'agenda:id,date,teacher_id,classroom_id,subject_id,schedule_detail_id',
                'agenda.teacher.user:id,name',
                'agenda.classroom:id,name',
                'agenda.subject:id,name',
            ])
            ->where('is_present', false)
            ->where(function ($q) {
                $q->whereNull('note')->orWhereIn('note', ['A', 'absen', 'tidak_hadir']);
            })
            ->whereHas('agenda', function ($q) use ($date, $activeYear) {
                $q->whereDate('date', $date)
                  ->where('academic_year_id', $activeYear->id);
            })
            ->get();
            
        return Inertia::render('Kepsek/Student/Alfas', [
            'alfas' => $alfas,
            'filters' => [
                'date' => $date,
            ],
            'activeYear' => $activeYear,
        ]);
    }

    private function getColorBySubject(int $id): string
    {
        $colors = ['bg-blue-100', 'bg-green-100', 'bg-yellow-100', 'bg-purple-100', 'bg-pink-100', 'bg-indigo-100'];
        return $colors[$id % count($colors)];
    }

    /**
     * Build the main agenda query for the dashboard.
     */
    private function buildAgendaQuery(int $activeYearId, ?string $date, ?string $classroomId, ?string $teacherId)
    {
        $query = TeachingAgenda::query()
            ->with([
                'teacher.user:id,name',
                'classroom:id,name',
                'subject:id,name',
                'tp:id,kode_tp,rumusan_tp',
                'attachments',
                'scheduleDetail',
            ])
            ->withCount([
                'attendances as total_siswa',
                'attendances as hadir_count'      => fn ($q) => $q->where('is_present', true)->where(fn($sub) => $sub->whereNull('note')->orWhere('note', '!=', 'T')),
                'attendances as tidak_hadir_count' => fn ($q) => $q->where('is_present', false),
                'attendances as sakit_count'       => fn ($q) => $q->where('note', 'S'),
                'attendances as izin_count'        => fn ($q) => $q->where('note', 'I'),
                'attendances as alfa_count'        => fn ($q) => $q->where('is_present', false)->where(fn($sub) => $sub->whereNull('note')->orWhereIn('note', ['A', 'absen', 'tidak_hadir'])),
                'attendances as dispen_count'      => fn ($q) => $q->where('note', 'D'),
                'attendances as telat_count'       => fn ($q) => $q->where('note', 'T'),
            ])
            ->where('academic_year_id', $activeYearId);

        if ($date)        $query->whereDate('date', $date);
        if ($classroomId) $query->where('classroom_id', $classroomId);
        if ($teacherId)   $query->where('teacher_id', $teacherId);

        return $query->orderBy('date', 'desc')->get();
    }

    /**
     * Build schedule grid and filled slots for a given classroom request.
     * Returns [$schedules, $filledSlots, $selectedClassroom].
     */
    private function buildScheduleData(?string $classroomId, int $activeYearId, ?string $date): array
    {
        if (! $classroomId) {
            return [[], [], null];
        }

        $selectedClassroom = Classroom::where('id', $classroomId)
            ->where('academic_year_id', $activeYearId)
            ->first();

        if (! $selectedClassroom) {
            return [[], [], null];
        }

        $schedules = ScheduleDetail::whereHas('schedule', function ($q) use ($selectedClassroom, $activeYearId) {
            $q->where('classroom_id', $selectedClassroom->id)
              ->where('academic_year_id', $activeYearId);
        })
            ->with(['schedule.subject', 'schedule.teacher.user:id,name'])
            ->get()
            ->map(function ($detail) {
                return [
                    'id'         => $detail->id,
                    'day'        => $detail->day,
                    'start_slot' => $detail->start_slot,
                    'end_slot'   => $detail->end_slot,
                    'subject'    => $detail->schedule->subject->name,
                    'teacher'    => $detail->schedule->teacher->full_name ?? ($detail->schedule->teacher->user->name ?? '-'),
                    'teacher_id' => $detail->schedule->teacher_id,
                    'code'       => $detail->schedule->subject->code ?? '',
                    'color'      => $this->getColorBySubject($detail->schedule->subject->id),
                ];
            });

        $filledSlots = TeachingAgenda::where('classroom_id', $selectedClassroom->id)
            ->where('academic_year_id', $activeYearId)
            ->whereDate('date', $date)
            ->whereNotNull('schedule_detail_id')
            ->pluck('schedule_detail_id')
            ->toArray();

        return [$schedules, $filledSlots, $selectedClassroom];
    }
}
