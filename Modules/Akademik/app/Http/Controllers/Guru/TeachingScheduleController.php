<?php

namespace Modules\Akademik\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; // Wajib Import Auth
use Inertia\Inertia;
use Jenssegers\Agent\Agent;
use Modules\Akademik\Models\AcademicYear;
use Modules\Akademik\Models\Schedule;
use Modules\Akademik\Models\Teacher;

class TeachingScheduleController extends Controller
{
    // Helper private untuk mendapatkan Guru yang sedang login
    private function getTeacherOrAbort()
    {
        $user = Auth::user();

        // Asumsi: Tabel teachers punya kolom 'user_id'
        $teacher = Teacher::where('user_id', $user->id)->first();

        if (! $teacher) {
            // Jika user login tapi datanya tidak ada di tabel teachers
            abort(403, 'Akun Anda tidak terdaftar sebagai Guru Aktif.');
        }

        return $teacher;
    }

    private function resolveView($viewName)
    {
        return "Guru/TeachingSchedule/{$viewName}";
    }

    public function index()
    {
        $activeYear = AcademicYear::where('is_active', true)->firstOrFail();

        // 1. AMBIL GURU DINAMIS
        $teacher = $this->getTeacherOrAbort();

        $schedules = Schedule::with(['classroom', 'subject', 'details'])
            ->where('academic_year_id', $activeYear->id)
            ->where('teacher_id', $teacher->id) // 2. FILTER SESUAI ID GURU LOGIN
            ->get()
            ->map(function ($schedule) {
                $scheduledJp = $schedule->details->sum(function ($detail) {
                    return $detail->end_slot - $detail->start_slot + 1;
                });

                return [
                    'id' => $schedule->id,
                    'classroom' => $schedule->classroom->name,
                    'subject' => $schedule->subject->name,
                    'quota' => $schedule->quota,
                    'scheduled_jp' => $scheduledJp,
                    'is_complete' => $scheduledJp >= $schedule->quota,
                    'details' => $schedule->details,
                    'details_count' => $schedule->details->count(),
                    'kelas' => [
                        'id' => $schedule->classroom_id,
                        'name' => $schedule->classroom->name,
                    ],
                    'mapel' => [
                        'id' => $schedule->subject_id,
                        'name' => $schedule->subject->name,
                    ],
                ];
            });

        return Inertia::render($this->resolveView('Index'), [
            'schedules' => $schedules,
            'activeYear' => $activeYear,
            'teacherId' => $teacher->id,
        ]);
    }

    public function edit($id)
    {
        $teacher = $this->getTeacherOrAbort();

        // 3. SECURITY CHECK: Pastikan jadwal ini milik guru tersebut
        $schedule = Schedule::with(['classroom', 'subject', 'details'])
            ->where('teacher_id', $teacher->id) // Kunci agar tidak bisa edit punya orang lain
            ->findOrFail($id);

        return Inertia::render($this->resolveView('Detail'), [
            'schedule' => $schedule,
            'existingDetails' => $schedule->details,
        ]);
    }


    public function update(Request $request, $id)
    {
        $request->validate([
            'details' => 'present|array',
            'details.*.day' => 'required',
            'details.*.start_slot' => 'required|integer|min:1',
            'details.*.end_slot' => 'required|integer|gte:details.*.start_slot',
        ]);

        $teacher = $this->getTeacherOrAbort();

        $schedule = Schedule::with(['classroom', 'subject'])
            ->where('teacher_id', $teacher->id)
            ->findOrFail($id);

        $teacherId = $schedule->teacher_id;
        $classroomId = $schedule->classroom_id;
        $isReligionSubject = (bool) $schedule->subject->is_religion;
        $details = $request->input('details', []);

        DB::beginTransaction();

        try {
            /* ======================================================
            * A. VALIDASI INTERNAL (input user sendiri)
            * ====================================================== */
            foreach ($details as $i => $a) {
                foreach ($details as $j => $b) {
                    if ($i !== $j && $a['day'] === $b['day']) {
                        if ($a['start_slot'] <= $b['end_slot'] && $a['end_slot'] >= $b['start_slot']) {
                            throw new \Exception(
                                'Input Anda bentrok sendiri! Hari '.ucfirst($a['day']).' ada irisan waktu.'
                            );
                        }
                    }
                }
            }

            /* ======================================================
            * B. VALIDASI DATABASE
            * ====================================================== */
            foreach ($details as $newSlot) {

                /* ---------- 1. CEK GURU (WAJIB, TIDAK PERNAH DI-SKIP) ---------- */
                $teacherConflict = DB::table('schedule_details')
                    ->join('schedules', 'schedule_details.schedule_id', '=', 'schedules.id')
                    ->where('schedules.teacher_id', $teacherId)
                    ->where('schedules.academic_year_id', $schedule->academic_year_id)
                    ->where('schedules.id', '!=', $schedule->id)
                    ->where('schedule_details.day', $newSlot['day'])
                    ->where(function ($q) use ($newSlot) {
                        $q->where('schedule_details.start_slot', '<=', $newSlot['end_slot'])
                            ->where('schedule_details.end_slot', '>=', $newSlot['start_slot']);
                    })
                    ->first();

                if ($teacherConflict) {
                    $conflict = Schedule::with('classroom')->find($teacherConflict->schedule_id);
                    throw new \Exception(
                        "Bentrok! Anda sudah mengajar di kelas {$conflict->classroom->name} ".
                        'hari '.ucfirst($newSlot['day']).' '.
                        "(Jam {$teacherConflict->start_slot}-{$teacherConflict->end_slot})."
                    );
                }

                /* ---------- 2. CEK KELAS (LOGIC BARU) ---------- */
                $classConflict = DB::table('schedule_details')
                    ->join('schedules', 'schedule_details.schedule_id', '=', 'schedules.id')
                    ->join('subjects', 'schedules.subject_id', '=', 'subjects.id')
                    ->where('schedules.classroom_id', $classroomId)
                    ->where('schedules.academic_year_id', $schedule->academic_year_id)
                    ->where('schedules.id', '!=', $schedule->id)
                    ->where('schedule_details.day', $newSlot['day'])
                    ->where(function ($q) use ($newSlot) {
                        $q->where('schedule_details.start_slot', '<=', $newSlot['end_slot'])
                            ->where('schedule_details.end_slot', '>=', $newSlot['start_slot']);
                    })
                    ->select(
                        'subjects.name',
                        'subjects.is_religion',
                        'schedule_details.start_slot',
                        'schedule_details.end_slot'
                    )
                    ->first();

                if ($classConflict) {
                    $bothReligion =
                        $isReligionSubject &&
                        (bool) $classConflict->is_religion;

                    if (! $bothReligion) {
                        throw new \Exception(
                            "Bentrok Kelas! Kelas ini sedang belajar {$classConflict->name} ".
                            'hari '.ucfirst($newSlot['day']).' '.
                            "(Jam {$classConflict->start_slot}-{$classConflict->end_slot})."
                        );
                    }
                    // jika keduanya mapel agama → BOLEH, tidak error
                }
            }

            /* ======================================================
            * C. SIMPAN
            * ====================================================== */
            $schedule->details()->delete();

            foreach ($details as $detail) {
                $schedule->details()->create([
                    'day' => $detail['day'],
                    'start_slot' => $detail['start_slot'],
                    'end_slot' => $detail['end_slot'],
                ]);
            }

            DB::commit();

            return redirect()
                ->route('guru.teaching-schedules.index')
                ->with('success', 'Jadwal berhasil diperbarui.');

        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
