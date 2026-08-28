<?php

namespace Modules\Cbt\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Modules\Cbt\Models\CbtExam;
use Modules\Cbt\Models\CbtQuestion;
use Modules\Cbt\Models\CbtStudentExam;
use Modules\Cbt\Models\CbtStudentAnswer;
use Modules\Cbt\Models\CbtRoomStudent;
use Modules\Cbt\Models\CbtProctorSchedule;
use Modules\Cbt\Services\CbtGradingService;
use App\Services\ActivityLogger;
use Carbon\Carbon;

class StudentCbtController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $student = $user->student;

        if (!$student) {
            return Inertia::render('Dashboard/DashboardStudent', [
                'error' => 'Akun Anda tidak terhubung dengan data Siswa.'
            ]);
        }

        // Ambil kelas aktif siswa
        $classroom = $student->classrooms()->wherePivot('status', 'aktif')->first();
        $classroomId = $classroom ? $classroom->id : null;

        // Ambil semua ujian aktif untuk kelas siswa
        $exams = CbtExam::whereHas('classrooms', function ($q) use ($classroomId) {
                $q->where('classrooms.id', $classroomId);
            })
            ->where('is_active', true)
            ->with(['bank.subject', 'teacher'])
            ->get();

        // Ambil riwayat pengerjaan siswa
        $studentExams = CbtStudentExam::where('student_id', $student->id)
            ->get()
            ->keyBy('cbt_exam_id');

        // Petakan data ujian dengan status keikutsertaan siswa
        $examList = $exams->map(function ($exam) use ($studentExams) {
            $session = $studentExams->get($exam->id);
            $now = Carbon::now();
            
            $isOpen = $now->between($exam->start_time, $exam->end_time);
            $isUpcoming = $now->lessThan($exam->start_time);
            $isClosed = $now->greaterThan($exam->end_time);

            return [
                'id' => $exam->id,
                'title' => $exam->title,
                'subject_name' => $exam->bank->subject->name ?? '-',
                'duration' => $exam->duration,
                'is_independent' => $exam->is_independent,
                'start_time' => $exam->start_time->toIso8601String(),
                'end_time' => $exam->end_time->toIso8601String(),
                'is_open' => $isOpen,
                'is_upcoming' => $isUpcoming,
                'is_closed' => $isClosed,
                'session_status' => $session ? $session->status : 'not_started',
                'score' => $session ? $session->score : null,
                'submitted_at' => $session && $session->submitted_at ? $session->submitted_at->format('d M Y, H:i') : null,
            ];
        });

        return Inertia::render('Cbt/Student/Index', [
            'exams' => $examList,
            'classroom' => $classroom,
        ]);
    }

    public function showExam($cbtExamId)
    {
        $user = Auth::user();
        $student = $user->student;

        if (!$student) {
            return redirect()->route('student.cbt.index')->with('error', 'Data siswa tidak ditemukan.');
        }

        $exam = CbtExam::with(['bank.subject', 'classrooms'])->findOrFail($cbtExamId);
        $classroomIds = $exam->classrooms->pluck('id')->toArray();
        $studentClassroom = $student->classrooms()->wherePivot('status', 'aktif')->first();

        // Validasi rombel/kelas siswa
        if (!$studentClassroom || !in_array($studentClassroom->id, $classroomIds)) {
            return redirect()->route('student.cbt.index')->with('error', 'Anda tidak terdaftar sebagai peserta ujian ini.');
        }

        $now = Carbon::now();
        // Validasi waktu akses ujian
        if ($now->lessThan($exam->start_time)) {
            return redirect()->route('student.cbt.index')->with('error', 'Ujian belum dimulai.');
        }
        if ($now->greaterThan($exam->end_time)) {
            return redirect()->route('student.cbt.index')->with('error', 'Ujian sudah berakhir.');
        }

        // Cari sesi pengerjaan siswa
        $studentExam = CbtStudentExam::where('cbt_exam_id', $cbtExamId)
            ->where('student_id', $student->id)
            ->first();

        // Jika tidak ada sesi pengerjaan atau statusnya not_started/logged_out
        if (!$studentExam || in_array($studentExam->status, ['not_started', 'logged_out'])) {
            // Bypass token JIKA ujian mandiri DAN status masih not_started/belum pernah mulai
            if ($exam->is_independent && (!$studentExam || $studentExam->status === 'not_started')) {
                if (!$studentExam) {
                    $studentExam = CbtStudentExam::create([
                        'cbt_exam_id' => $cbtExamId,
                        'student_id' => $student->id,
                        'status' => 'login',
                        'warning_count' => 0,
                        'is_blocked' => false,
                    ]);
                } else {
                    $studentExam->update(['status' => 'login']);
                }
            } elseif ($exam->is_independent && $studentExam->status === 'logged_out') {
                // Ujian mandiri: siswa di-kick karena pelanggaran ke-3.
                // Harus menunggu guru memberi izin masuk kembali melalui halaman hasil ujian.
                return redirect()->route('student.cbt.index')
                    ->with('error', 'Anda telah dikeluarkan dari ujian ini karena pelanggaran fokus (peringatan ke-3). Hubungi Guru/Pengajar untuk mendapat izin masuk kembali.');
            } else {
                return redirect()->route('student.cbt.index')
                    ->with('error', 'Harap masukkan token pengawas untuk memulai/melanjutkan ujian.');
            }
        }

        if ($studentExam->is_blocked || $studentExam->status === 'blocked') {
            return redirect()->route('student.cbt.index')->with('error', 'Akses Anda diblokir permanen dari ujian ini karena pelanggaran fokus.');
        }

        if ($studentExam->status === 'submitted') {
            if ($studentExam->warning_count >= 4) {
                return redirect()->route('student.cbt.index')->with('error', 'Ujian Anda telah selesai secara paksa karena pelanggaran fokus.');
            }
            return redirect()->route('student.cbt.index')->with('error', 'Anda sudah mengumpulkan ujian ini.');
        }

        // Jika statusnya 'login', ubah status ke 'started'
        if ($studentExam->status === 'login') {
            // Cek apakah siswa ini melanjutkan pengerjaan (re-entry/re-verify token)
            $hasExistingOrder = !empty($studentExam->question_order) && is_array($studentExam->question_order);

            if ($hasExistingOrder) {
                // Siswa melanjutkan ujian yang pernah dimulai:
                // JANGAN acak ulang nomor soal/opsi dan JANGAN reset started_at agar tidak mengubah susunan soal & sisa waktu
                $studentExam->update([
                    'status' => 'started',
                    'started_at' => $studentExam->started_at ?? now(),
                ]);
            } else {
                // Pertama kali mulai ujian: generate urutan soal dan opsi
                $questionIds = CbtQuestion::where('cbt_bank_id', $exam->cbt_bank_id)
                    ->pluck('id')
                    ->toArray();

                if (empty($questionIds)) {
                    return redirect()->route('student.cbt.index')->with('error', 'Ujian ini belum memiliki soal.');
                }

                // Acak soal jika disetting acak
                if ($exam->shuffle_questions) {
                    shuffle($questionIds);
                }

                // Acak opsi jawaban per soal jika dikonfigurasi acak
                $optionsOrder = [];
                if ($exam->shuffle_options) {
                    $questions = CbtQuestion::whereIn('id', $questionIds)->get();
                    foreach ($questions as $q) {
                        if (in_array($q->question_type, ['pilihan_ganda', 'list', 'checklist', 'skor_berbeda'])) {
                            $keys = array_keys($q->options ?? []);
                            shuffle($keys);
                            $optionsOrder[$q->id] = $keys;
                        }
                    }
                }

                $studentExam->update([
                    'status' => 'started',
                    'started_at' => now(),
                    'question_order' => $questionIds,
                    'options_order' => $optionsOrder,
                ]);
            }
        }

        // Hitung sisa waktu pengerjaan
        $startedAt = Carbon::parse($studentExam->started_at);
        $durationMinutes = $exam->duration;
        $endTime = $startedAt->copy()->addMinutes($durationMinutes);

        // Batasi sisa waktu maksimal s/d end_time ujian utama
        if ($endTime->greaterThan($exam->end_time)) {
            $endTime = $exam->end_time;
        }

        $remainingSeconds = $now->diffInSeconds($endTime, false);

        if ($remainingSeconds <= 0) {
            // Jika waktu sudah habis, otomatis submit oleh sistem
            CbtGradingService::gradeExam($studentExam->id, 'system_timeout');
            return redirect()->route('student.cbt.index')->with('error', 'Waktu ujian telah habis. Jawaban Anda otomatis tersimpan.');
        }

        // Ambil soal berdasarkan urutan yang tersimpan
        $questionOrder = $studentExam->question_order;
        $dbQuestions = CbtQuestion::whereIn('id', $questionOrder)->get()->keyBy('id');

        $questions = collect($questionOrder)->map(function ($qId) use ($dbQuestions, $studentExam, $exam) {
            $q = $dbQuestions->get($qId);
            if (!$q) return null;

            // Sesuaikan urutan opsi jawaban jika diacak
            $options = $q->options;
            $savedOptionOrder = $studentExam->options_order[$q->id] ?? null;

            if ($exam->shuffle_options && $savedOptionOrder && in_array($q->question_type, ['pilihan_ganda', 'list', 'checklist', 'skor_berbeda'])) {
                $shuffledOptions = [];
                foreach ($savedOptionOrder as $optKey) {
                    if (isset($options[$optKey])) {
                        $shuffledOptions[$optKey] = $options[$optKey];
                    }
                }
                $options = $shuffledOptions;
            }

            return [
                'id' => $q->id,
                'question_type' => $q->question_type,
                'question_text' => $q->question_text,
                'options' => $options,
            ];
        })->filter()->values();

        // Ambil jawaban yang telah diisi siswa
        $answers = CbtStudentAnswer::where('cbt_student_exam_id', $studentExam->id)
            ->get()
            ->mapWithKeys(function ($ans) {
                return [$ans->cbt_question_id => [
                    'selected_answer' => $ans->selected_answer,
                    'is_doubtful' => (bool)$ans->is_doubtful
                ]];
            });

        $studentExam->load('student');

        return Inertia::render('Cbt/Student/ExamSession', [
            'exam' => $exam,
            'studentExam' => $studentExam,
            'questions' => $questions,
            'initialAnswers' => $answers,
            'remainingSeconds' => $remainingSeconds,
        ]);
    }

    public function saveAnswer(Request $request, $cbtExamId)
    {
        $user = Auth::user();
        $student = $user->student;

        if (!$student) {
            return response()->json(['error' => 'Data siswa tidak ditemukan.'], 404);
        }

        $studentExam = CbtStudentExam::where('cbt_exam_id', $cbtExamId)
            ->where('student_id', $student->id)
            ->first();

        if (!$studentExam || $studentExam->status !== 'started') {
            return response()->json(['error' => 'Sesi pengerjaan ujian tidak aktif.'], 400);
        }

        $request->validate([
            'cbt_question_id' => 'required|exists:cbt_questions,id',
            'selected_answer' => 'nullable', // Boleh null/kosong jika dihapus atau ragu-ragu saja
            'is_doubtful' => 'required|boolean',
        ]);

        try {
            $answer = CbtStudentAnswer::updateOrCreate(
                [
                    'cbt_student_exam_id' => $studentExam->id,
                    'cbt_question_id' => $request->cbt_question_id,
                ],
                [
                    'selected_answer' => $request->selected_answer,
                    'is_doubtful' => $request->is_doubtful,
                ]
            );
        } catch (\Illuminate\Database\UniqueConstraintViolationException|\Illuminate\Database\QueryException $e) {
            $answer = CbtStudentAnswer::updateOrCreate(
                [
                    'cbt_student_exam_id' => $studentExam->id,
                    'cbt_question_id' => $request->cbt_question_id,
                ],
                [
                    'selected_answer' => $request->selected_answer,
                    'is_doubtful' => $request->is_doubtful,
                ]
            );
        }

        return response()->json(['success' => true]);
    }

    public function submitExam($cbtExamId)
    {
        $user = Auth::user();
        $student = $user->student;

        if (!$student) {
            return redirect()->route('student.cbt.index')->with('error', 'Data siswa tidak ditemukan.');
        }

        $studentExam = CbtStudentExam::where('cbt_exam_id', $cbtExamId)
            ->where('student_id', $student->id)
            ->where('status', 'started')
            ->firstOrFail();

        // Validasi jika harus menyelesaikan semua soal (tidak ada jawaban kosong & ragu-ragu)
        $exam = CbtExam::findOrFail($cbtExamId);
        if ($exam->must_complete_all) {
            $questionIds = $studentExam->question_order ?? [];
            $answers = CbtStudentAnswer::where('cbt_student_exam_id', $studentExam->id)
                ->get()
                ->keyBy('cbt_question_id');
            
            $unansweredCount = 0;
            $doubtfulCount = 0;
            
            foreach ($questionIds as $qId) {
                $ans = $answers->get($qId);
                
                $isAnswered = false;
                if ($ans) {
                    $val = $ans->selected_answer;
                    if ($val !== null && $val !== '') {
                        if (is_array($val)) {
                            // Filter array (termasuk multi-input dan asosiatif) agar nilai kosong/null tidak dihitung terisi
                            $filledValues = array_filter($val, function ($item) {
                                if (is_array($item)) {
                                    return count(array_filter($item, fn($sub) => $sub !== null && $sub !== '')) > 0;
                                }
                                return $item !== null && $item !== '';
                            });
                            $isAnswered = count($filledValues) > 0;
                        } else {
                            $isAnswered = true;
                        }
                    }
                    if ($ans->is_doubtful) {
                        $doubtfulCount++;
                    }
                }
                
                if (!$isAnswered) {
                    $unansweredCount++;
                }
            }
            
            if ($unansweredCount > 0 || $doubtfulCount > 0) {
                return redirect()->back()->with('error', 'Semua soal harus dijawab dan tidak boleh ada soal ragu-ragu.');
            }
        }

        // Jalankan penilaian otomatis & CTT scoring (Selesai Mandiri oleh Siswa)
        CbtGradingService::gradeExam($studentExam->id, 'student');
        app(\Modules\Cbt\Services\CttAnalyticsService::class)->calculateStudentCtt($studentExam->id);
        app(\Modules\Cbt\Services\CttAnalyticsService::class)->calculateExamCtt($cbtExamId);

        ActivityLogger::log('CBT_SUBMIT', "Siswa {$student->full_name} mengumpulkan pengerjaan ujian: " . ($studentExam->exam->title ?? 'Ujian CBT'), $studentExam, null, null, $user);

        return redirect()->route('student.cbt.index')->with('success', 'Ujian Anda telah berhasil dikumpulkan.');
    }

    public function verifyToken(Request $request, $cbtExamId)
    {
        $request->validate([
            'token' => 'required|string|max:10',
        ]);

        $user = Auth::user();
        $student = $user->student;

        if (!$student) {
            return response()->json(['error' => 'Data siswa tidak ditemukan.'], 404);
        }

        // 1 & 2. Cek apakah ada jadwal pengawas yang cocok dengan penempatan siswa dan sedang aktif hari ini
        $roomStudents = CbtRoomStudent::where('student_id', $student->id)->get();
        if ($roomStudents->isEmpty()) {
            return response()->json(['error' => 'Anda belum terdaftar di ruang ujian manapun. Hubungi pengawas.'], 422);
        }

        $proctorSchedule = null;
        foreach ($roomStudents as $rs) {
            $schedule = CbtProctorSchedule::where('date', now()->toDateString())
                ->where('cbt_room_id', $rs->cbt_room_id)
                ->where('token', strtoupper($request->token))
                ->where('status', 'started')
                ->first();
                
            if ($schedule) {
                // Presensi check: jika absensi sudah disimpan dan siswa tidak dicentang (absen)
                if (is_array($schedule->present_students)) {
                    $presentList = array_map('strval', $schedule->present_students);
                    if (!in_array((string)$student->id, $presentList, true)) {
                        return response()->json(['error' => 'Anda dicatat TIDAK HADIR oleh Pengawas Ruangan untuk sesi ujian ini.'], 422);
                    }
                }
                $proctorSchedule = $schedule;
                break;
            }
        }

        if (!$proctorSchedule) {
            return response()->json(['error' => 'Token tidak valid, sesi belum dimulai, atau Anda tidak terdaftar di sesi dengan token tersebut.'], 422);
        }

        // Cek kedaluwarsa token (3 menit)
        $ageSeconds = now()->timestamp - $proctorSchedule->token_generated_at->timestamp;
        if ($ageSeconds > 180) {
            return response()->json(['error' => 'Token kedaluwarsa. Minta token baru kepada pengawas.'], 422);
        }

        // 4. Buat atau perbarui sesi ujian siswa dengan status 'login'
        $studentExam = CbtStudentExam::firstOrNew([
            'cbt_exam_id' => $cbtExamId,
            'student_id' => $student->id,
        ]);

        if ($studentExam->exists && $studentExam->is_blocked) {
            return response()->json(['error' => 'Akses Anda diblokir permanen dari ujian ini karena pelanggaran fokus.'], 403);
        }

        $studentExam->status = 'login';
        $studentExam->blocked_until = null;
        if (!$studentExam->exists) {
            $studentExam->warning_count = 0;
            $studentExam->is_blocked = false;
        }
        $studentExam->save();

        ActivityLogger::log('CBT_VERIFY_TOKEN', "Siswa {$student->full_name} berhasil verifikasi token untuk ujian: " . ($proctorSchedule->exam->title ?? 'Ujian CBT'), $studentExam, null, null, $user);

        return response()->json(['success' => true]);
    }

    public function heartbeat(Request $request, $cbtExamId)
    {
        $user = Auth::user();
        $student = $user->student;

        if (!$student) {
            return response()->json(['status' => 'error', 'message' => 'Siswa tidak ditemukan.'], 404);
        }

        $studentExam = CbtStudentExam::where('cbt_exam_id', $cbtExamId)
            ->where('student_id', $student->id)
            ->first();

        if (!$studentExam) {
            return response()->json(['status' => 'ok']);
        }

        // Cek apakah ujian sudah dikumpulkan/selesai paksa
        if ($studentExam->status === 'submitted') {
            return response()->json([
                'status' => 'submitted',
                'message' => $studentExam->warning_count >= 4
                    ? 'Ujian Anda telah selesai secara paksa karena pelanggaran fokus.'
                    : 'Ujian Anda telah dikumpulkan.'
            ]);
        }

        // Cek apakah siswa diblokir permanen
        if ($studentExam->is_blocked || $studentExam->status === 'blocked') {
            return response()->json([
                'status' => 'blocked',
                'message' => 'Akses Anda diblokir permanen dari ujian ini karena melanggar aturan fokus.'
            ]);
        }

        // Cek apakah siswa dikeluarkan paksa oleh proktor
        if ($studentExam->status === 'logged_out') {
            return response()->json([
                'status' => 'logged_out',
                'message' => 'Anda telah dikeluarkan paksa dari ujian ini oleh Pengawas.'
            ]);
        }

        // Cek apakah ruangan ujian sudah ditutup atau presensi diubah
        $exam = CbtExam::find($cbtExamId);
        if ($exam && !$exam->is_independent) {
            $roomStudents = CbtRoomStudent::where('student_id', $student->id)->get();

            // BUG FIX: Ujian non-independent (berpengawas) tanpa penempatan ruang = celah keamanan.
            // Siswa bisa menyelesaikan ujian tanpa pengawasan apapun jika data ruangan kosong.
            // Konsisten dengan verifyToken() yang sudah menolak siswa tanpa room assignment.
            if ($roomStudents->isEmpty()) {
                return response()->json([
                    'status' => 'ended',
                    'message' => 'Anda tidak terdaftar di ruang ujian manapun untuk ujian ini. Hubungi pengawas atau admin untuk penempatan ruang.'
                ]);
            }

            $hasActiveSchedule = false;
            $hasValidPresence = false;
            $hasAttendanceList = false;
            
            foreach ($roomStudents as $rs) {
                $activeSchedule = CbtProctorSchedule::where('date', now()->toDateString())
                    ->where('cbt_room_id', $rs->cbt_room_id)
                    ->where('status', 'started')
                    ->first();
                    
                if ($activeSchedule) {
                    $hasActiveSchedule = true;
                    // Cek jika proktor sudah menyimpan absensi di sesi ini
                    if (is_array($activeSchedule->present_students)) {
                        $hasAttendanceList = true;
                        $presentList = array_map('strval', $activeSchedule->present_students);
                        if (in_array((string)$student->id, $presentList, true)) {
                            $hasValidPresence = true;
                        }
                    } else {
                        // Presensi belum dikunci/disimpan oleh pengawas -> otomatis dianggap hadir
                        $hasValidPresence = true;
                    }
                }
            }

            if (!$hasActiveSchedule) {
                // Hanya otomatis grade jika siswa memang sedang aktif dikerjakan (started)
                if ($studentExam->status === 'started') {
                    CbtGradingService::gradeExam($studentExam->id, 'system_proctor');
                }
                return response()->json([
                    'status' => 'ended',
                    'message' => 'Sesi ujian di ruangan Anda telah ditutup oleh Pengawas.'
                ]);
            }

            // Siswa hanya dianggap absen jika ada jadwal aktif dengan daftar hadir tersimpan,
            // namun siswa tidak tercatat hadir di satupun jadwal aktifnya.
            if ($hasAttendanceList && !$hasValidPresence) {
                $studentExam->update(['status' => 'logged_out']);
                return response()->json([
                    'status' => 'logged_out',
                    'message' => 'Anda dikeluarkan dari ujian karena dicatat Tidak Hadir oleh Pengawas.'
                ]);
            }
        }

        // Cek apakah sedang ditangguhkan/suspensi sementara
        $isSuspended = false;
        $remainingSeconds = 0;
        if ($studentExam->blocked_until && Carbon::parse($studentExam->blocked_until)->isFuture()) {
            $isSuspended = true;
            $remainingSeconds = now()->diffInSeconds(Carbon::parse($studentExam->blocked_until));
        }

        return response()->json([
            'status' => $isSuspended ? 'suspensi' : 'ok',
            'remaining_seconds' => $remainingSeconds,
            'warning_count' => $studentExam->warning_count,
        ]);
    }

    public function cheatWarning(Request $request, $cbtExamId)
    {
        $user = Auth::user();
        $student = $user->student;

        if (!$student) {
            return response()->json(['error' => 'Siswa tidak ditemukan.'], 404);
        }

        $studentExam = CbtStudentExam::where('cbt_exam_id', $cbtExamId)
            ->where('student_id', $student->id)
            ->firstOrFail();

        $newCount = $studentExam->warning_count + 1;
        $blockedUntil = null;
        $isBlocked = false;
        $status = $studentExam->status;

        if ($newCount === 1) {
            // Peringatan ke-1: lock 1 menit
            $blockedUntil = now()->addMinute();
            $status = $studentExam->status; // tetap 'started'
        } elseif ($newCount === 2) {
            // Peringatan ke-2: lock 5 menit
            $blockedUntil = now()->addMinutes(5);
            $status = $studentExam->status; // tetap 'started'
        } elseif ($newCount === 3) {
            // Peringatan ke-3: logout paksa (keluar dari sesi, harus pakai token lagi)
            $status = 'logged_out';
        } else {
            // Peringatan ke-4+: ban permanen & submit otomatis
            $isBlocked = true;
            $status = 'submitted';
        }

        $studentExam->update([
            'warning_count' => $newCount,
            'blocked_until' => $blockedUntil,
            'is_blocked' => $isBlocked,
            'status' => $status,
        ]);

        // Catat Log Pelanggaran Fokus
        $warnText = match($newCount) {
            1 => "Pelanggaran Fokus ke-1: Layar dikunci 1 menit",
            2 => "Pelanggaran Fokus ke-2: Layar dikunci 5 menit",
            3 => "Pelanggaran Fokus ke-3: Siswa dikeluarkan dari ujian (logged_out)",
            default => "Pelanggaran Fokus ke-{$newCount}: Diblokir permanen & diselesaikan paksa",
        };
        ActivityLogger::log('CBT_CHEAT_WARNING', "{$warnText} ({$student->full_name})", $studentExam, null, null, $user);

        // Jika pelanggaran ke-4 (ban permanen): lakukan grading otomatis
        if ($newCount >= 4) {
            CbtGradingService::gradeExam($studentExam->id, 'system_cheat');
        }

        return response()->json([
            'warning_count' => $newCount,
            'blocked_until' => $blockedUntil ? $blockedUntil->toIso8601String() : null,
            'is_blocked' => $isBlocked,
            'status' => $status,
        ]);
    }
}
