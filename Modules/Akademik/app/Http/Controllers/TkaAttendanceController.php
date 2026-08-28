<?php

namespace Modules\Akademik\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Akademik\Models\TkaSession;
use Modules\Akademik\Models\TkaAttendance;
use Modules\Akademik\Models\TkaStudent;

class TkaAttendanceController extends Controller
{
    public function index(Request $request, TkaSession $tka_session)
    {
        $tka_session->load('tkaSubject');
        
        // Get all students assigned to this subject
        $enrolledStudents = TkaStudent::with('student.classroom')
            ->where('tka_subject_id', $tka_session->tka_subject_id)
            ->where('status', 'active')
            ->get();

        // Get existing attendances for this session
        $existingAttendances = TkaAttendance::where('tka_session_id', $tka_session->id)
            ->get()
            ->keyBy('student_id');

        // Combine them
        $attendances = $enrolledStudents->map(function ($enrolled) use ($existingAttendances) {
            $existing = $existingAttendances->get($enrolled->student_id);
            return [
                'student_id' => $enrolled->student_id,
                'student' => $enrolled->student,
                'status' => $existing ? $existing->status : null,
                'notes' => $existing ? $existing->notes : '',
            ];
        });

        return Inertia::render('Akademik/Tka/Attendances/Index', [
            'session' => $tka_session,
            'attendances' => $attendances
        ]);
    }

    public function store(Request $request, TkaSession $tka_session)
    {
        $validated = $request->validate([
            'attendances' => 'required|array',
            'attendances.*.student_id' => 'required|exists:students,id',
            'attendances.*.status' => 'required|in:hadir,izin,sakit,alpa',
            'attendances.*.notes' => 'nullable|string',
        ]);

        foreach ($validated['attendances'] as $data) {
            TkaAttendance::updateOrCreate(
                [
                    'tka_session_id' => $tka_session->id,
                    'student_id' => $data['student_id']
                ],
                [
                    'status' => $data['status'],
                    'notes' => $data['notes']
                ]
            );
        }

        return back()->with('success', 'Presensi berhasil disimpan.');
    }
}
