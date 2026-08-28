<?php

namespace Modules\Akademik\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Jenssegers\Agent\Agent;
use Modules\Akademik\Models\AcademicYear;
use Modules\Akademik\Models\AgendaAttendance;
use Modules\Akademik\Models\LearningObjectiveTP;
use Modules\Akademik\Models\ScheduleDetail;
use Modules\Akademik\Models\Student;
use Modules\Akademik\Models\StudentPermit;
use Modules\Akademik\Models\Teacher;
use Modules\Akademik\Models\TeachingAgenda;
use Modules\Akademik\Models\Schedule;

class GuruAgendaController extends Controller
{
    private function resolveView($viewName)
    {
        // Selalu gunakan view mobile/smartphone untuk guru (desktop view dinonaktifkan)
        return "Guru/Agenda/Mobile/{$viewName}";
    }

    private function getIndoDay($date)
    {
        $englishDay = Carbon::parse($date)->format('D');
        $map = [
            'Mon' => 'senin',
            'Tue' => 'selasa',
            'Wed' => 'rabu',
            'Thu' => 'kamis',
            'Fri' => 'jumat',
            'Sat' => 'sabtu',
            'Sun' => 'minggu',
        ];

        return $map[$englishDay] ?? null;
    }

    public function index(Request $request)
    {

        $teacher = Teacher::where('user_id', Auth::id())->firstOrFail();
        $activeYear = AcademicYear::where('is_active', true)->firstOrFail();
        $filter = $request->query('filter', 'semua');

        $query = TeachingAgenda::query()
            ->select('teaching_agendas.*')
            // Join ke detail untuk sorting jam
            ->leftJoin('schedule_details', 'teaching_agendas.schedule_detail_id', '=', 'schedule_details.id')
            ->with([
                'classroom:id,name',
                'subject:id,name',
                'tp:id,kode_tp',
                'scheduleDetail', // Gunakan relasi baru hasil migrasi
            ])
            ->withCount([
                'attendances as total_siswa',
                'attendances as hadir_count' => fn ($q) => $q->where('is_present', true),
                'attendances as tidak_hadir_count' => fn ($q) => $q->where('is_present', false),
            ])
            ->where('teaching_agendas.teacher_id', $teacher->id)
            ->where('teaching_agendas.academic_year_id', $activeYear->id);

        $now = Carbon::now('Asia/Jakarta');
        // Filter Tanggal Custom dari DatePicker
        if ($request->has('date')) {
            $query->whereDate('date', $request->date);
        }

        if ($filter === 'hari_ini') {
            $query->whereDate('teaching_agendas.date', $now->toDateString());
        } elseif ($filter === 'minggu_ini') {
            $query->whereBetween('teaching_agendas.date', [
                $now->startOfWeek()->toDateString(),
                $now->endOfWeek()->toDateString(),
            ]);
        } elseif ($filter === 'bulan_ini') {
            $query->whereMonth('teaching_agendas.date', $now->month)
                ->whereYear('teaching_agendas.date', $now->year);
        }

        $agendas = $query->orderByDesc('teaching_agendas.date')
            ->orderBy('schedule_details.start_slot', 'asc')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render($this->resolveView('Index'), [
            'agendas' => $agendas,
            'academicYear' => $activeYear,
            'filters' => ['filter' => $filter],
        ]);
    }

    /**
     * Halaman create agenda
     */
    public function create()
    {
        $teacher = Teacher::where('user_id', Auth::id())->firstOrFail();
        $academicYear = AcademicYear::where('is_active', true)->firstOrFail();

        return inertia($this->resolveView('Create'), [
            'academicYear' => $academicYear,
            'teacher' => $teacher,
        ]);
    }

    public function schedulesByDate(Request $request)
    {
        $request->validate(['date' => 'required|date']);
        $dayName = $this->getIndoDay($request->date);

        $teacher = Teacher::where('user_id', Auth::id())->first();
        $academicYear = AcademicYear::where('is_active', true)->first();

        $details = ScheduleDetail::with(['schedule.classroom', 'schedule.subject'])
            ->whereHas('schedule', function ($q) use ($teacher, $academicYear) {
                $q->where('teacher_id', $teacher->id)
                    ->where('academic_year_id', $academicYear->id);
            })
            ->where('day', $dayName)
            ->get()
            ->map(function ($detail) {
                return [
                    'id' => $detail->id, // Ini ID Detail
                    'label' => "{$detail->schedule->classroom->name} · {$detail->schedule->subject->name} (Jam {$detail->start_slot}-{$detail->end_slot})",
                    'schedule_id' => $detail->schedule_id,
                ];
            });

        return response()->json($details);
    }

    /**
     * FIX: Mengambil TP berdasarkan ID Detail Jadwal
     */
    public function tpBySchedule($detailId)
    {
        // 1. Ambil detail jadwal beserta relasi schedule dan classroom (eager loading)
        $detail = ScheduleDetail::with(['schedule.classroom'])->findOrFail($detailId);

        // 2. Tentukan Fase berdasarkan nama kelas
        // Kita ambil nama kelas dari relasi: $detail -> schedule -> classroom -> name
        $className = $detail->schedule->classroom->name;

        // Logika: Jika nama kelas mengandung 'X ', maka fase E, selain itu fase F
        $fase = str_contains($className, 'X ') ? 'E' : 'F';

        // 3. Filter TP berdasarkan subject_id DAN fase yang ada di tabel CP
        $tps = LearningObjectiveTP::whereHas('cp', function ($q) use ($detail, $fase) {
            $q->where('subjects_id', $detail->schedule->subject_id)
                ->where('fase', $fase); // 🔑 Kuncinya di sini: Filter fase di level CP
        })
            ->where('is_active', true)
            ->orderBy('urutan')
            ->get()
            ->map(fn ($tp) => [
                'id' => $tp->id,
                'label' => $tp->kode_tp.' — '.$tp->rumusan_tp,
            ]);

        return response()->json($tps);
    }

    /**
     * FIX: Mengambil Siswa berdasarkan ID Detail Jadwal
     */
    public function studentsBySchedule($detailId, Request $request)
    {
        // 1. Tangkap parameter 'date' dari request
        $dateInput = $request->query('date') ;
        if (! $dateInput) {
            // Fallback jika null, gunakan tanggal hari ini
            $date = now()->format('Y-m-d');
        } else {
            // 🔑 PARSING: Pastikan formatnya Y-m-d (2026-01-20)
            $date = Carbon::parse($dateInput)->format('Y-m-d');
        }
        // 1. Ambil detail jadwal dan tanggal agenda dari request
        $detail = ScheduleDetail::with(['schedule', 'schedule.subject'])->findOrFail($detailId);
        // $date = $request->query('date'); // Tanggal dikirim dari Vue (form.agenda_date)

        // 2. Ambil siswa di kelas tersebut (Filter status aktif, urutkan nama A-Z, & filter agama jika mapel agama)
        $students = Student::with('religion:id,name')
            ->whereHas('classrooms', function ($q) use ($detail) {
                $q->where('classroom_students.classroom_id', $detail->schedule->classroom_id)
                  ->where('classroom_students.status', 'aktif');
            })
            ->when($detail->schedule->subject->is_religion && $detail->schedule->religion_id, function ($q) use ($detail) {
                $q->where('religion_id', $detail->schedule->religion_id);
            })
            ->select('id', 'full_name', 'nisn', 'gender', 'religion_id')
            ->orderBy('full_name', 'asc')
            ->get()
            ->map(function ($student) use ($date, $detail) {
                // 3. CARI DATA IZIN: Cek apakah ada permit di tanggal tersebut
                $permit = StudentPermit::where('student_id', $student->id)
                    ->where('date', $date)
                    ->first();

                // 4. CARI DATA PRESENSI HARIAN (attendances) untuk SAKIT/IZIN
                $attendance = DB::table('attendances')
                    ->where('student_id', $student->id)
                    ->where('date', $date)
                    ->first();

                $lockedStatus = null;
                $permitReason = null;
                $isLate = false;
                $isDispensasi = false;

                if ($permit && !in_array($permit->permit_type, ['T', 'D'])) {
                    $lockedStatus = $permit->permit_type;
                    $permitReason = $permit->reason;
                } elseif ($attendance && in_array($attendance->status, ['S', 'I'])) {
                    $lockedStatus = $attendance->status;
                    $permitReason = $attendance->note ?? ($attendance->status === 'S' ? 'Sakit (Data Presensi Harian)' : 'Izin (Data Presensi Harian)');
                }

                if ($permit && $permit->permit_type === 'T') {
                    if ($detail->start_slot <= 2) {
                        $isLate = true;
                    }
                }
                
                if ($permit && $permit->permit_type === 'D') {
                    $isDispenActive = true;
                    if ($permit->start_slot !== null && $detail->start_slot < $permit->start_slot) {
                        $isDispenActive = false;
                    }
                    if ($permit->end_slot !== null && $detail->start_slot > $permit->end_slot) {
                        $isDispenActive = false;
                    }

                    if ($isDispenActive) {
                        $isDispensasi = true;
                    }
                }

                return [
                    'id' => $student->id,
                    'full_name' => $student->full_name,
                    'nisn' => $student->nisn ?? '-',
                    'gender_label' => $student->gender_label,
                    'religion_name' => $student->religion ? $student->religion->name : '-',
                    'locked_status' => $lockedStatus,
                    'is_late' => $isLate,
                    'is_dispensasi' => $isDispensasi,
                    'permit_reason' => $permitReason,
                ];
            });
        // dd($students);

        return response()->json($students);
    }


    /**
     * FIX: Simpan Agenda menggunakan schedule_detail_id
     */
    public function store(Request $request)
    {
        $teacher = Teacher::where('user_id', Auth::id())->firstOrFail();
        $academicYear = AcademicYear::where('is_active', true)->firstOrFail();

        $data = $request->validate([
            'agenda_date' => 'required|date',
            'schedule_id' => 'required|exists:schedule_details,id', // ID Detail dari Vue
            'tp_id' => 'nullable|exists:learning_objectives_tp,id',
            'materi' => 'required|string',
            'presensi' => 'required|array',
            'keterangan' => 'nullable|string',
            'selfie' => 'required|image|max:10240',
        ]);

        DB::transaction(function () use ($data, $teacher, $academicYear, $request) {
            // 1. Ambil Detail dan Induk Jadwal
            $detail = ScheduleDetail::with('schedule')->findOrFail($data['schedule_id']);
            $schedule = $detail->schedule;
            $agendaDate = Carbon::parse($data['agenda_date']);
            $dayName = $this->getIndoDay($data['agenda_date']);

            // 2. Update atau Create
            $agenda = TeachingAgenda::updateOrCreate(
                [
                    'teacher_id' => $teacher->id,
                    'schedule_detail_id' => $detail->id,
                    'date' => $agendaDate->toDateString(),

                ],
                [
                    'academic_year_id' => $academicYear->id, // Pastikan bukan $activeYear
                    'schedule_id' => $schedule->id,
                    'start_slot' => $detail->start_slot ?? null,
                    'end_slot' => $detail->end_slot ?? null,
                    'teacher_id' => $teacher->id,
                    'classroom_id' => $schedule->classroom_id,
                    'subject_id' => $schedule->subject_id,
                    'day' => $detail ? $detail->day : $dayName,
                    'learning_objective_tp_id' => $data['tp_id'],
                    'materi_pembelajaran' => $data['materi'],
                    'keterangan' => $data['keterangan'] ?? null,
                ]);

            // 3. Sync Presensi
            $agenda->attendances()->delete();
            foreach ($data['presensi'] as $studentId => $status) {
                $isLateAtGate = false;
                if (($detail->start_slot ?? 0) <= 2) {
                    $isLateAtGate = StudentPermit::where('student_id', $studentId)
                        ->where('date', $agendaDate->toDateString())
                        ->where('permit_type', 'T')
                        ->exists();
                }

                $isDispensasi = StudentPermit::where('student_id', $studentId)
                    ->where('date', $agendaDate->toDateString())
                    ->where('permit_type', 'D')
                    ->where(function ($query) use ($detail) {
                        $slot = $detail->start_slot ?? 0;
                        $query->where(function ($q) use ($slot) {
                            $q->whereNull('start_slot')->orWhere('start_slot', '<=', $slot);
                        })->where(function ($q) use ($slot) {
                            $q->whereNull('end_slot')->orWhere('end_slot', '>=', $slot);
                        });
                    })
                    ->exists();

                $isPresent = ($status === 'hadir' || $status === 'H');
                $note = null;
                
                if ($isPresent) {
                    $note = $isLateAtGate ? 'T' : ($isDispensasi ? 'D' : null);
                } elseif ($status === 'absen' || $status === 'A') {
                    $note = $isDispensasi ? 'D' : null;
                } else {
                    $note = $status; // S, I, etc.
                }

                $agenda->attendances()->create([
                    'student_id' => $studentId,
                    'note' => $note,
                    'is_present' => $isPresent,
                ]);
            }

            // 4. Save Selfie Attachment
            if ($request->hasFile('selfie')) {
                $disk = config('filesystems.default');
                // Hapus selfie lama jika ada
                $existingSelfies = $agenda->attachments()->where('type', 'photo')->get();
                foreach ($existingSelfies as $existing) {
                    Storage::disk($disk)->delete($existing->file_path);
                    $existing->delete();
                }

                $file = $request->file('selfie');
                $path = $file->store('agenda_selfies', $disk);
                $agenda->attachments()->create([
                    'type' => 'photo',
                    'file_path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'file_size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                ]);
            }
        });

        return redirect()->route('guru.agenda.index')->with('success', 'Agenda berhasil disimpan');
    }

    public function show(TeachingAgenda $agenda)
    {
        $teacher = Teacher::where('user_id', Auth::id())->firstOrFail();

        // Proteksi agar guru lain tidak bisa melihat agenda guru lain
        abort_unless($agenda->teacher_id === $teacher->id, 403);

        $agenda->load([
            'classroom:id,name',
            'subject:id,name',
            'tp:id,kode_tp,rumusan_tp',
            'schedule.details:id,schedule_id,start_slot,end_slot',
            'attendances' => function ($query) {
                $query->with('student:id,full_name,nis');
                // Pastikan kolom 'status' dan 'notes/keterangan' ikut terambil
            },
            'attachments',
        ])->loadCount([
            // Hitung Hadir (H) - Mendukung data lama (is_present true)
            'attendances as hadir_count' => function ($query) {
                $query->where('note', 'H')->orWhere(function ($q) {
                    $q->whereNull('note')->where('is_present', true);
                });
            },
            // Hitung Sakit (S)
            'attendances as sakit_count' => fn ($q) => $q->where('note', 'S'),
            // Hitung Izin (I)
            'attendances as izin_count' => fn ($q) => $q->where('note', 'I'),
            // Hitung Alfa (A)
            'attendances as alfa_count' => fn ($q) => $q->where('note', 'A'),
            // Hitung Dispen (D)
            'attendances as dispen_count' => fn ($q) => $q->where('note', 'D'),
            // Hitung Terlambat (T)
            'attendances as terlambat_count' => fn ($q) => $q->where('note', 'T'),
        ]);
        // dd($agenda);

        return Inertia::render($this->resolveView('Show'), [
            'agenda' => $agenda,
        ]);
    }

    public function updatePresensi(Request $request, TeachingAgenda $agenda)
    {
        $teacher = Teacher::where('user_id', Auth::id())->firstOrFail();
        abort_unless($agenda->teacher_id === $teacher->id, 403);

        $request->validate([
            'presensi' => 'required|array',
            'presensi.*' => 'required|in:H,S,I,A,D,true,false,1,0',
        ]);
        // Ambil tanggal agenda untuk pengecekan di tabel permits
        $agendaDate = $agenda->date;

        foreach ($request->presensi as $attendanceId => $value) {
            // 1. Ambil data attendance untuk mendapatkan student_id
            $attendance = AgendaAttendance::findOrFail($attendanceId);
            $studentId = $attendance->student_id;

            // 2. Cek apakah siswa terlambat atau dispensasi di gerbang (tabel student_permits) pada tanggal tersebut
            $isLateAtGate = false;
            if (($agenda->start_slot ?? 0) <= 2) {
                $isLateAtGate = StudentPermit::where('student_id', $studentId)
                    ->where('date', $agendaDate)
                    ->where('permit_type', 'T')
                    ->exists();
            }

            $isDispensasi = StudentPermit::where('student_id', $studentId)
                ->where('date', $agendaDate)
                ->where('permit_type', 'D')
                ->where(function ($query) use ($agenda) {
                    $slot = $agenda->start_slot ?? 0;
                    $query->where(function ($q) use ($slot) {
                        $q->whereNull('start_slot')->orWhere('start_slot', '<=', $slot);
                    })->where(function ($q) use ($slot) {
                        $q->whereNull('end_slot')->orWhere('end_slot', '>=', $slot);
                    });
                })
                ->exists();

            // 3. Standarisasi nilai input dari Vue
            $inputStatus = ($value === true || $value === 'H' || $value === 1) ? 'H' : $value;
            if ($inputStatus === false || $inputStatus === 0) {
                $inputStatus = 'A';
            }

            $finalNote = null;
            $isPresent = false;

            if ($inputStatus === 'H') {
                $isPresent = true;
                $finalNote = $isLateAtGate ? 'T' : ($isDispensasi ? 'D' : null);
            } elseif (in_array($inputStatus, ['S', 'I', 'D', 'A'])) {
                $isPresent = false;
                $finalNote = $inputStatus;
            } else {
                // Status adalah 'A' (Absen Manual)
                $isPresent = false;
                $finalNote = $isDispensasi ? 'D' : null;
            }

            // 5. Update data
            $attendance->update([
                'note' => $finalNote,
                'is_present' => $isPresent,
            ]);
        }

        return redirect()->route('guru.agenda.index')->with('success', 'Presensi berhasil diperbarui');
    }

    // Tambahkan di GuruAgendaController.php

    public function edit(TeachingAgenda $agenda)
    {
        $teacher = Teacher::where('user_id', Auth::id())->firstOrFail();
        abort_unless($agenda->teacher_id === $teacher->id, 403);

        // Load data pendukung agar form terisi otomatis
        $agenda->load([
            'classroom:id,name', // Pastikan relasi ini ada di model TeachingAgenda
            'subject:id,name',
            'scheduleDetail',
            'tp',
            'attendances',
        ]);

        return Inertia::render($this->resolveView('Edit'), [
            'agenda' => $agenda,
        ]);
    }

    public function update(Request $request, TeachingAgenda $agenda)
    {
        $teacher = Teacher::where('user_id', Auth::id())->firstOrFail();
        abort_unless($agenda->teacher_id === $teacher->id, 403);

        $data = $request->validate([
            'tp_id' => 'nullable|exists:learning_objectives_tp,id',
            'materi' => 'required|string',
            'presensi' => 'required|array',
            'keterangan' => 'nullable|string',
        ]);

        DB::transaction(function () use ($data, $agenda) {
            // 1. Update Agenda
            $agenda->update([
                'learning_objective_tp_id' => $data['tp_id'],
                'materi_pembelajaran' => $data['materi'],
                'keterangan' => $data['keterangan'],
            ]);

            // 2. Sync Presensi (Hapus yang lama, isi yang baru)
            $agenda->attendances()->delete();
            foreach ($data['presensi'] as $studentId => $status) {
                $isPresent = in_array($status, ['hadir', 'H', 'T', true, 1], true);
                $note = null;
                if (in_array($status, ['S', 'I', 'D', 'A', 'T'], true)) {
                    $note = $status;
                } elseif ($status === 'tidak_hadir' || $status === 'absen') {
                    $note = 'A';
                }

                $agenda->attendances()->create([
                    'student_id' => $studentId,
                    'note' => $note,
                    'is_present' => $isPresent,
                ]);
            }
        });

        return redirect()->route('guru.agenda.index')->with('success', 'Agenda berhasil diperbarui');
    }

    public function getTpBySchedule($id)
    {
        // 1. Ambil data jadwal beserta kelas dan mapelnya
        $schedule = Schedule::with(['classroom', 'subject'])->findOrFail($id);

        // 2. Tentukan Fase berdasarkan nama kelas atau kolom grade
        // Asumsi: Nama kelas diawali 'X ' (sepuluh), 'XI ' (sebelas), atau 'XII ' (duabelas)
        $className = $schedule->classroom->name;

        if (str_starts_with($className, 'X ')) {
            $fase = 'E';
        } else {
            // XI dan XII masuk Fase F
            $fase = 'F';
        }

        // 3. Ambil TP yang hanya sesuai dengan Mapel DAN Fase tersebut
        // Kita join ke table CP karena kolom 'fase' dan 'subjects_id' ada di table CP
        $tps = LearningObjectiveTP::whereHas('cp', function ($query) use ($schedule, $fase) {
            $query->where('subjects_id', $schedule->subject_id)
                ->where('fase', $fase);
        })
            ->where('is_active', true)
            ->orderBy('urutan')
            ->get()
            ->map(fn ($tp) => [
                'id' => $tp->id,
                'label' => $tp->kode_tp . ' — ' . $tp->rumusan_tp,
            ]);

        return response()->json($tps);
    }
    
}
