<?php

namespace Modules\Akademik\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Modules\Akademik\Models\AcademicYear;
use Modules\Akademik\Models\Classroom;
use Modules\Akademik\Models\Religion;
use Modules\Akademik\Models\Schedule;
use Modules\Akademik\Models\SubjectMapping;
use Modules\Akademik\Models\Teacher;

class ScheduleController extends Controller
{
    // Halaman Depan: Daftar Kelas
    public function index(Request $request)
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        $activeYearId = $activeYear?->id;

        $query = Classroom::with('teacher')
            ->when($activeYearId, function ($q) use ($activeYearId) {
                $q->where('academic_year_id', $activeYearId);
            })
            ->orderBy('level')
            ->orderBy('name');

        if ($request->search) {
            $query->where('name', 'ilike', '%'.$request->search.'%');
        }

        return Inertia::render('Akademik/Schedule/Index', [
            'classrooms' => $query->paginate(15)->withQueryString(),
            'activeYear' => $activeYear,
            'filters' => $request->only(['search']),
        ]);
    }

    // Halaman Form: Plotting Guru per Kelas
    public function manage($classroomId)
    {
        $activeYear = AcademicYear::where('is_active', true)->firstOrFail();
        $classroom = Classroom::findOrFail($classroomId);

        $mappings = SubjectMapping::with(['subject', 'group'])
            ->where('level', $classroom->level)
            ->get()
            ->sortBy(fn ($q) => $q->group->id.$q->subject->name)
            ->values();

        $teachers = Teacher::orderBy('full_name')->get()->map(fn ($t) => [
            'id' => $t->id,
            'name' => $t->full_name.($t->degree ? ', '.$t->degree : ''),
            'religion_id' => $t->religion_id, // ⬅️ PENTING
            'subject_id' => $t->subject_id,
        ]);

        $religions = Religion::orderBy('name')->get();

        // 🔑 KEY BARU
        $existingSchedules = Schedule::where('academic_year_id', $activeYear->id)
            ->where('classroom_id', $classroomId)
            ->get()
            ->mapWithKeys(function ($s) {
                $rid = $s->religion_id ?? 0;

                return [
                    $s->subject_id.'_'.$rid => [
                        'teacher_id' => $s->teacher_id,
                        'quota' => $s->quota,
                    ],
                ];
            });

        return Inertia::render('Akademik/Schedule/Manage', [
            'classroom' => $classroom,
            'activeYear' => $activeYear,
            'mappings' => $mappings,
            'teachers' => $teachers,
            'religions' => $religions,
            'existingSchedules' => $existingSchedules,
        ]);
    }

    // Simpan Plotting
    public function update(Request $request, $classroomId)
    {
        $activeYear = AcademicYear::where('is_active', true)->firstOrFail();
        $schedules = $request->input('schedules', []);

        DB::transaction(function () use ($schedules, $classroomId, $activeYear) {

            foreach ($schedules as $key => $data) {

                // key = subjectId_religionId
                [$subjectId, $religionId] = explode('_', $key);

                $subjectId = (int) $subjectId;
                $religionId = (int) $religionId;
                $religionId = $religionId === 0 ? null : $religionId;

                $teacherId = $data['teacher_id'] ?? null;
                $quota = isset($data['quota']) ? (int) $data['quota'] : 0;

                if ($teacherId) {
                    Schedule::updateOrCreate(
                        [
                            'academic_year_id' => $activeYear->id,
                            'classroom_id' => $classroomId,
                            'subject_id' => $subjectId,
                            'religion_id' => $religionId,
                        ],
                        [
                            'teacher_id' => (int) $teacherId,
                            'quota' => $quota,
                        ]
                    );
                } else {
                    Schedule::where('academic_year_id', $activeYear->id)
                        ->where('classroom_id', $classroomId)
                        ->where('subject_id', $subjectId)
                        ->where('religion_id', $religionId)
                        ->delete();
                }
            }
        });

        return redirect()
            ->route('admin.schedules.index')
            ->with('success', 'Schedule berhasil disimpan (agama terpisah).');
    }

    

    // Ambil detail jadwal (dipanggil via API/Axios saat tombol jam diklik)
    public function getDetails($scheduleId)
    {
        $schedule = Schedule::with('details')->findOrFail($scheduleId);

        return response()->json($schedule);
    }

    // Simpan rincian hari & jam
    public function updateDetails(Request $request, $scheduleId)
    {
        $request->validate([
            'details' => 'array',
            'details.*.day' => 'required|in:senin,selasa,rabu,kamis,jumat,sabtu',
            'details.*.start_slot' => 'required|integer|min:1',
            'details.*.end_slot' => 'required|integer|gte:details.*.start_slot',
        ]);

        $schedule = Schedule::findOrFail($scheduleId);

        DB::transaction(function () use ($schedule, $request) {
            // Hapus detail lama, ganti baru (Reset strategy)
            $schedule->details()->delete();

            foreach ($request->details as $detail) {
                $schedule->details()->create([
                    'day' => $detail['day'],
                    'start_slot' => $detail['start_slot'],
                    'end_slot' => $detail['end_slot'],
                ]);
            }
        });

        return back()->with('success', 'Rincian jadwal berhasil disimpan.');
    }

    public function store(Request $request, $classroomId)
    {
        $activeYear = AcademicYear::where('is_active', true)->firstOrFail();

        $request->validate([
            'plotting' => 'required|array',
            'plotting.*.teacher_id' => 'nullable|exists:teachers,id',
            'plotting.*.religion_id' => 'nullable|exists:religions,id', // Tambahkan ini
            'plotting.*.quota' => 'nullable|integer|min:0',
        ]);

        DB::transaction(function () use ($request, $classroomId, $activeYear) {
            foreach ($request->plotting as $subjectId => $data) {
                if (! empty($data['teacher_id'])) {
                    // Gunakan updateOrCreate agar data lama ter-update, data baru tercipta
                    Schedule::updateOrCreate(
                        [
                            'academic_year_id' => $activeYear->id,
                            'classroom_id' => $classroomId,
                            'subject_id' => $subjectId,
                            // PENTING: Jangan masukkan religion_id di array pencarian (array pertama)
                            // agar kita bisa mengubah agama pada mapel yang sama.
                        ],
                        [
                            'teacher_id' => $data['teacher_id'],
                            'religion_id' => $data['religion_id'] ?? null, // Masukkan di sini
                            'quota' => $data['quota'] ?? 0,
                        ]
                    );
                } else {
                    // Jika teacher dikosongkan, hapus plotting-nya
                    Schedule::where('academic_year_id', $activeYear->id)
                        ->where('classroom_id', $classroomId)
                        ->where('subject_id', $subjectId)
                        ->delete();
                }
            }
        });

        return redirect()->route('admin.schedules.index')->with('success', 'Plotting guru dan kategori agama berhasil disimpan.');
    }
}
