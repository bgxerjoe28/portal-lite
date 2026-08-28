<?php

namespace Modules\Cbt\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Modules\Cbt\Models\CbtProctorSchedule;
use Modules\Cbt\Models\CbtRoomStudent;
use Modules\Cbt\Models\CbtStudentExam;
use Modules\Cbt\Services\CbtGradingService;
use App\Services\ActivityLogger;
use Carbon\Carbon;

class CbtProctorController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $query = CbtProctorSchedule::with(['room', 'session', 'teacher'])
            ->where('date', now()->toDateString());

        // Jika bukan admin, hanya bisa melihat sesi proktoring miliknya hari ini
        if (!$user->hasRole('admin') && $user->hasRole('guru')) {
            $teacherId = $user->teacher?->id;
            $query->where('teacher_id', $teacherId);
        }

        $schedules = $query->orderBy('created_at', 'desc')->paginate(10);

        return Inertia::render('Cbt/Proctor/Index', [
            'sessions' => $schedules, // Keep variable name sessions for frontend compatibility
        ]);
    }

    public function show($id)
    {
        $user = Auth::user();
        $schedule = CbtProctorSchedule::with(['room', 'session', 'teacher'])->findOrFail($id);

        // Validasi hak akses proktor
        if (!$user->hasRole('admin') && $user->teacher?->id !== $schedule->teacher_id) {
            abort(404);
        }

        return Inertia::render('Cbt/Proctor/Monitor', [
            'session' => $schedule,
        ]);
    }

    public function generateToken($id)
    {
        $schedule = CbtProctorSchedule::findOrFail($id);
        
        // Generate 6-digit alphanumeric uppercase token
        $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // Avoid confusing characters like O, 0, I, 1
        $token = '';
        for ($i = 0; $i < 6; $i++) {
            $token .= $characters[rand(0, strlen($characters) - 1)];
        }

        $schedule->update([
            'token' => $token,
            'token_generated_at' => now(),
        ]);

        ActivityLogger::log('CBT_PROCTOR_GENERATE_TOKEN', "Pengawas menggenerate Token Ujian baru [{$token}] untuk Ruang " . ($schedule->room->name ?? '-'), $schedule);

        return redirect()->back()->with('success', 'Token ujian berhasil dibuat: ' . $token);
    }

    public function saveAttendance(Request $request, $id)
    {
        $schedule = CbtProctorSchedule::findOrFail($id);
        
        $request->validate([
            'present_students' => 'array',
        ]);

        $schedule->update([
            'present_students' => $request->present_students,
        ]);

        $count = is_array($request->present_students) ? count($request->present_students) : 0;
        ActivityLogger::log('CBT_PROCTOR_ATTENDANCE', "Pengawas menyimpan presensi ({$count} siswa hadir) di Ruang " . ($schedule->room->name ?? '-'), $schedule);

        return redirect()->back()->with('success', 'Presensi peserta berhasil disimpan.');
    }

    public function startExam($id)
    {
        $schedule = CbtProctorSchedule::findOrFail($id);
        $schedule->update([
            'status' => 'started',
        ]);

        ActivityLogger::log('CBT_PROCTOR_START', "Pengawas memulai sesi ujian untuk Ruang " . ($schedule->room->name ?? '-'), $schedule);

        return redirect()->back()->with('success', 'Sesi di ruangan ini telah dibuka.');
    }

    public function endExam($id)
    {
        $schedule = CbtProctorSchedule::findOrFail($id);
        $schedule->update([
            'status' => 'ended',
        ]);

        // Paksa semua siswa yang sedang ujian di ruangan ini (apapun ujiannya) untuk selesai/logout
        $assignedStudentIds = CbtRoomStudent::where('cbt_room_id', $schedule->cbt_room_id)
            ->pluck('student_id')
            ->toArray();

        $activeStudentExams = CbtStudentExam::whereIn('student_id', $assignedStudentIds)
            ->whereIn('status', ['login', 'started'])
            ->whereHas('exam', function($query) {
                $query->where('is_independent', false);
            })
            ->get();

        foreach ($activeStudentExams as $se) {
            // Siswa yang sedang tersuspensi (layar dikunci sementara / blocked_until di masa depan) TIDAK ikut ter-logout/submit massal
            if ($se->blocked_until && Carbon::parse($se->blocked_until)->isFuture()) {
                continue;
            }

            // Submit pengerjaan paksa (grade otomatis) HANYA untuk siswa yang memang dicatat HADIR
            if ($schedule->present_students === null || (is_array($schedule->present_students) && in_array($se->student_id, $schedule->present_students))) {
                CbtGradingService::gradeExam($se->id, 'system_proctor');
            } else {
                $se->update(['status' => 'logged_out']);
            }
        }

        ActivityLogger::log('CBT_PROCTOR_END', "Pengawas menutup sesi ujian di Ruang " . ($schedule->room->name ?? '-'), $schedule);

        return redirect()->back()->with('success', 'Sesi di ruangan ini telah ditutup. Semua peserta dipaksa selesai.');
    }

    public function restartRoomSession($id)
    {
        $schedule = CbtProctorSchedule::findOrFail($id);

        // Ubah status ruangan kembali ke 'started'
        $schedule->update([
            'status' => 'started',
        ]);

        // Ambil penempatan siswa di ruangan ini
        $assignedStudentIds = CbtRoomStudent::where('cbt_room_id', $schedule->cbt_room_id)
            ->pluck('student_id')
            ->toArray();

        // Buka kembali semua sesi ujian siswa di ruangan ini yang berstatus submitted atau logged_out
        // (kecuali yang diblokir permanen / warning >= 4)
        $studentExams = CbtStudentExam::whereIn('student_id', $assignedStudentIds)
            ->where('is_blocked', false)
            ->where('warning_count', '<', 4)
            ->whereIn('status', ['submitted', 'logged_out'])
            ->get();

        foreach ($studentExams as $se) {
            $se->update([
                'status'        => 'started',
                'score'         => null,
                'submitted_at'  => null,
                'submit_type'   => null,
                'blocked_until' => null,
            ]);
        }

        $count = $studentExams->count();
        ActivityLogger::log('CBT_PROCTOR_RESTART_ROOM', "Pengawas membuka kembali sesi di Ruang " . ($schedule->room->name ?? '-') . " ({$count} pengerjaan siswa dibuka kembali serentak)", $schedule);

        return redirect()->back()->with('success', "Sesi ruangan berhasil dibuka kembali. {$count} pengerjaan siswa di-reset ke status Aktif secara bersama-sama.");
    }

    public function forceLogoutStudent($id, $studentId)
    {
        $schedule = CbtProctorSchedule::findOrFail($id);
        
        // Cari sesi ujian aktif siswa ini (ujian apapun)
        $studentExam = CbtStudentExam::where('student_id', $studentId)
            ->whereIn('status', ['login', 'started'])
            ->first();

        if ($studentExam) {
            // Ubah status jadi logged_out
            $studentExam->update([
                'status' => 'logged_out',
            ]);

            ActivityLogger::log('CBT_PROCTOR_LOGOUT_STUDENT', "Pengawas mengeluarkan paksa siswa {$studentExam->student->full_name} dari Ruang " . ($schedule->room->name ?? '-'), $studentExam);

            return redirect()->back()->with('success', 'Siswa berhasil dikeluarkan paksa.');
        }

        return redirect()->back()->with('error', 'Sesi pengerjaan siswa tidak ditemukan atau sudah selesai.');
    }

    public function reopenStudentExam($id, $studentId)
    {
        $schedule = CbtProctorSchedule::findOrFail($id);

        if ($schedule->status === 'ended') {
            return redirect()->back()->with('error', 'Sesi di ruangan ini telah ditutup. Pembukaan kembali ujian yang telah ditutup hanya dapat dilakukan oleh Guru Penguji / Admin melalui Halaman Hasil Ujian.');
        }

        $studentExam = CbtStudentExam::where('student_id', $studentId)
            ->whereIn('status', ['submitted', 'logged_out', 'blocked'])
            ->first();

        if ($studentExam) {
            if ($studentExam->is_blocked || $studentExam->warning_count >= 4) {
                return redirect()->back()->with('error', 'Siswa ini telah diblokir permanen (ban pelanggaran). Status diblokir tidak dapat dibuka kembali.');
            }

            $studentExam->update([
                'status'        => 'started',
                'score'         => null,
                'submitted_at'  => null,
                'submit_type'   => null,
                'is_blocked'    => false,
                'blocked_until' => null,
            ]);

            ActivityLogger::log('CBT_PROCTOR_REOPEN_STUDENT', "Pengawas mereset/membuka kembali pengerjaan siswa {$studentExam->student->full_name} di Ruang " . ($schedule->room->name ?? '-'), $studentExam);

            return redirect()->back()->with('success', 'Status ujian siswa berhasil di-reset ke Aktif (Buka Kembali).');
        }

        return redirect()->back()->with('error', 'Sesi ujian siswa tidak ditemukan.');
    }

    public function getStatus($id)
    {
        $schedule = CbtProctorSchedule::findOrFail($id);
        $roomId = $schedule->cbt_room_id;
        $sessionId = $schedule->cbt_session_id;

        // Cek token kedaluwarsa (3 menit)
        $tokenExpired = false;
        $expiresIn = 0;
        if ($schedule->token && $schedule->token_generated_at) {
            $ageSeconds = now()->timestamp - $schedule->token_generated_at->timestamp;
            $remaining = 180 - $ageSeconds;
            if ($remaining <= 0) {
                $tokenExpired = true;
                $schedule->update([
                    'token' => null,
                    'token_generated_at' => null
                ]);
            } else {
                $expiresIn = $remaining;
            }
        }

        // Ambil penempatan tempat duduk untuk Ruang ini
        $seating = CbtRoomStudent::where('cbt_room_id', $roomId)
            ->with(['student.classrooms'])
            ->get()
            ->keyBy('seat_number');

        // Ambil sesi ujian siswa saat ini (apapun ujiannya)
        $assignedStudentIds = $seating->pluck('student_id')->toArray();
        
        $statusOrder = [
            'started' => 1,
            'login' => 2,
            'suspensi' => 3,
            'blocked' => 4,
            'logged_out' => 5,
            'submitted' => 6,
            'not_started' => 7,
        ];

        $studentExams = collect();
        if ($schedule->status !== 'not_started') {
            $studentExams = CbtStudentExam::with(['exam', 'answers'])
                ->whereIn('student_id', $assignedStudentIds)
                ->where(function ($q) use ($schedule) {
                    $q->whereDate('created_at', $schedule->date)
                      ->orWhereHas('exam', function ($eq) use ($schedule) {
                          $eq->whereDate('start_time', $schedule->date);
                      });
                })
                ->get()
                ->sortBy(function ($exam) use ($statusOrder) {
                    return $statusOrder[$exam->status] ?? 99;
                })
                ->groupBy('student_id');
        }

        $seatStatuses = [];
        for ($i = 1; $i <= 36; $i++) {
            $seat = $seating->get($i);
            if (!$seat) {
                $seatStatuses[$i] = [
                    'seat_number' => $i,
                    'status' => 'empty',
                    'student' => null
                ];
                continue;
            }

            $student = $seat->student;
            // Ambil exam yang paling relevan (aktif)
            $examSession = $studentExams->has($student->id) ? $studentExams->get($student->id)->first() : null;

            $status = 'not_started';
            $warningCount = 0;
            $isSuspended = false;
            $suspendedSeconds = 0;
            $examTitle = '-';
            $answeredCount = 0;
            $totalQuestions = 0;

            if ($examSession) {
                $status = $examSession->status;
                $warningCount = $examSession->warning_count;
                $examTitle = $examSession->exam->title ?? '-';

                $qOrder = $examSession->question_order ?? [];
                $totalQuestions = is_array($qOrder) ? count($qOrder) : 0;

                if ($examSession->relationLoaded('answers')) {
                    $answeredCount = $examSession->answers->filter(function ($ans) {
                        $val = $ans->selected_answer;
                        if ($val === null || $val === '') return false;
                        if (is_array($val)) return count($val) > 0;
                        return true;
                    })->unique('cbt_question_id')->count();
                }

                if ($examSession->is_blocked) {
                    $status = 'blocked';
                } elseif ($examSession->blocked_until && Carbon::parse($examSession->blocked_until)->isFuture()) {
                    $isSuspended = true;
                    $suspendedSeconds = now()->diffInSeconds(Carbon::parse($examSession->blocked_until));
                    $status = 'suspensi';
                }
            }

            $seatStatuses[$i] = [
                'seat_number' => $i,
                'status' => $status,
                'submit_type' => $examSession?->submit_type,
                'warning_count' => $warningCount,
                'is_suspended' => $isSuspended,
                'suspended_seconds' => $suspendedSeconds,
                'exam_title' => $examTitle,
                'answered_count' => $answeredCount,
                'total_questions' => $totalQuestions,
                'student' => [
                    'id' => $student->id,
                    'name' => $student->full_name,
                    'nisn' => $student->nisn,
                    'classroom_name' => $student->classrooms->first()->name ?? '-',
                ]
            ];
        }

        return response()->json([
            'session' => [
                'id' => $schedule->id,
                'token' => $schedule->token,
                'token_expired' => $tokenExpired,
                'expires_in' => $expiresIn,
                'status' => $schedule->status,
                'room_name' => $schedule->room->name ?? '-',
                'session_name' => $schedule->session->name ?? '-',
                'present_students' => $schedule->present_students,
            ],
            'seats' => $seatStatuses,
        ]);
    }
}
