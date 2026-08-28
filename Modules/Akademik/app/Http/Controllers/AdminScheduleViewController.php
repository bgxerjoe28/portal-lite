<?php

namespace Modules\Akademik\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Modules\Akademik\Models\AcademicYear;
use Modules\Akademik\Models\Classroom;
use Modules\Akademik\Models\Schedule;
use Modules\Akademik\Models\ScheduleDetail;
use Modules\Akademik\Models\Teacher;
use Modules\Akademik\Models\TeachingAgenda;

class AdminScheduleViewController extends Controller
{
    // 1. LIHAT JADWAL PER KELAS
    public function byClass(Request $request)
    {
        $activeYear = AcademicYear::where('is_active', true)->firstOrFail();

        // ✅ Dropdown kelas HANYA tahun ajaran aktif
        $classrooms = Classroom::where('academic_year_id', $activeYear->id)
            ->orderBy('level')
            ->orderBy('name')
            ->get();

        $schedules = [];
        $selectedClassroom = null;

        if ($request->classroom_id) {
            // ✅ Pastikan kelas milik tahun ajaran aktif
            $selectedClassroom = Classroom::where('id', $request->classroom_id)
                ->where('academic_year_id', $activeYear->id)
                ->first();

            if ($selectedClassroom) {
                $schedules = ScheduleDetail::whereHas('schedule', function ($q) use ($selectedClassroom, $activeYear) {
                    $q->where('classroom_id', $selectedClassroom->id)
                        ->where('academic_year_id', $activeYear->id);
                })
                    ->with(['schedule.subject', 'schedule.teacher'])
                    ->get()
                    ->map(function ($detail) {
                        return [
                            'day' => $detail->day,
                            'start_slot' => $detail->start_slot,
                            'end_slot' => $detail->end_slot,
                            'subject' => $detail->schedule->subject->name,
                            'teacher' => $detail->schedule->teacher->full_name ?? '-',
                            'code' => $detail->schedule->subject->code ?? '',
                            'color' => $this->getColorBySubject($detail->schedule->subject->id),
                        ];
                    });
            }
        }

        return Inertia::render('Akademik/Monitoring/ScheduleByClass', [
            'classrooms' => $classrooms,
            'schedules' => $schedules,
            'filters' => $request->only(['classroom_id']),
            'selectedClassroom' => $selectedClassroom,
            'activeYear' => $activeYear,
        ]);
    }


    // 2. LIHAT JADWAL PER GURU
    public function byTeacher(Request $request)
    {
        $activeYear = AcademicYear::where('is_active', true)->firstOrFail();

        // Ambil list guru beserta perhitungan total JP dari detail jadwal
        $teachers = Teacher::orderBy('full_name')
            ->get()
            ->map(function ($teacher) use ($activeYear) {
                // Hitung total jam mengajar guru ini di tahun ajaran aktif
                $totalJp = DB::table('schedule_details')
                    ->join('schedules', 'schedule_details.schedule_id', '=', 'schedules.id')
                    ->where('schedules.teacher_id', $teacher->id)
                    ->where('schedules.academic_year_id', $activeYear->id)
                    ->select(DB::raw('SUM(end_slot - start_slot + 1) as total'))
                    ->first()->total ?? 0;

                return [
                    'id' => $teacher->id,
                    'full_name' => $teacher->full_name,
                    'total_jp' => $totalJp,
                ];
            });

        $schedules = [];
        $selectedTeacher = null;

        if ($request->teacher_id) {
            $selectedTeacher = $teachers->firstWhere('id', $request->teacher_id);

            $schedules = ScheduleDetail::whereHas('schedule', function ($q) use ($request, $activeYear) {
                $q->where('teacher_id', $request->teacher_id)
                    ->where('academic_year_id', $activeYear->id);
            })
                ->with(['schedule.subject', 'schedule.classroom'])
                ->get()
                ->map(function ($detail) {
                    return [
                        'day' => $detail->day,
                        'start_slot' => $detail->start_slot,
                        'end_slot' => $detail->end_slot,
                        'subject' => $detail->schedule->subject->name,
                        'classroom' => $detail->schedule->classroom->name,
                    ];
                });
        }

        return Inertia::render('Akademik/Monitoring/ScheduleByTeacher', [
            'teachers' => $teachers,
            'schedules' => $schedules,
            'filters' => $request->only(['teacher_id']),
            'selectedTeacher' => $selectedTeacher,
            'activeYear' => $activeYear,
        ]);
    }

    // Helper sederhana untuk warna (Opsional)
    private function getColorBySubject($id)
    {
        $colors = ['bg-blue-100', 'bg-green-100', 'bg-yellow-100', 'bg-purple-100', 'bg-pink-100', 'bg-indigo-100'];

        return $colors[$id % count($colors)];
    }

    // 3. RESET JADWAL (HAPUS SCHEDULE DETAIL)
    public function resetSchedules(Request $request)
    {
        $request->validate([
            'teacher_ids' => 'required|array',
            'teacher_ids.*' => 'exists:teachers,id'
        ]);

        $activeYear = AcademicYear::where('is_active', true)->firstOrFail();

        DB::transaction(function () use ($request, $activeYear) {
            // Cari semua ID Jadwal (Schedules/Plotting) untuk guru-guru yang dipilih di tahun ajaran aktif
            $scheduleIds = Schedule::whereIn('teacher_id', $request->teacher_ids)
                ->where('academic_year_id', $activeYear->id)
                ->pluck('id');

            $detailIds = ScheduleDetail::whereIn('schedule_id', $scheduleIds)->pluck('id');

            // Amankan histori agenda/jurnal mengajar guru: simpan snapshot start_slot & end_slot dan set schedule_detail_id menjadi NULL
            $agendasToSnapshot = TeachingAgenda::whereIn('schedule_detail_id', $detailIds)->with('scheduleDetail')->get();
            foreach ($agendasToSnapshot as $agenda) {
                if ($agenda->scheduleDetail) {
                    $agenda->update([
                        'start_slot' => $agenda->scheduleDetail->start_slot,
                        'end_slot' => $agenda->scheduleDetail->end_slot,
                    ]);
                }
            }
            TeachingAgenda::whereIn('schedule_detail_id', $detailIds)->update(['schedule_detail_id' => null]);

            // Hapus HANYA detail slot jadwalnya (hari, jam slot)
            ScheduleDetail::whereIn('schedule_id', $scheduleIds)->delete();
        });

        return back()->with('success', 'Jadwal guru terpilih berhasil direset.');
    }
}
