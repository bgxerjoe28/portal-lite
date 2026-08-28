<?php

namespace Modules\Akademik\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Jenssegers\Agent\Agent;
use Modules\Akademik\Models\AcademicYear;
use Modules\Akademik\Models\Classroom;
use Modules\Akademik\Models\StudentPermit;
use Modules\Akademik\Models\Teacher;
use Modules\Akademik\Models\TeachingAgenda;
use Barryvdh\DomPDF\Facade\Pdf;

class AgendaReportController extends Controller
{
    private function resolveView(string $viewName)
    {
        // Selalu gunakan view mobile/smartphone untuk guru (desktop view dinonaktifkan)
        return "Report/Agenda/Mobile/{$viewName}";
    }

    private function formatAgendasWithPermits($rawAgendas, ?int $activeYearId)
    {
        if ($rawAgendas->isEmpty()) {
            return $rawAgendas;
        }

        $agendaDates = $rawAgendas->pluck('date')->map(fn ($d) => Carbon::parse($d)->toDateString())->unique()->toArray();

        $permits = StudentPermit::query()
            ->when($activeYearId, fn ($q) => $q->where('academic_year_id', $activeYearId))
            ->whereIn('date', $agendaDates)
            ->get();

        return $rawAgendas->map(function ($agenda) use ($permits) {
            $agendaDate = Carbon::parse($agenda->date)->toDateString();
            $agendaSlot = (int) ($agenda->start_slot ?? $agenda->scheduleDetail?->start_slot ?? 0);

            $h = 0; $s = 0; $i = 0; $d = 0; $a = 0;
            $absentStudents = [];

            foreach ($agenda->attendances as $att) {
                $student = $att->student;
                $studentName = $student?->full_name ?? "Siswa #{$att->student_id}";

                // Cari permit resmi jika ada (cocokkan tanggal dan slot jam ke jika ada)
                $studentPermit = $permits->where('student_id', $att->student_id)->filter(function ($permit) use ($agendaDate, $agendaSlot) {
                    if (Carbon::parse($permit->date)->toDateString() !== $agendaDate) {
                        return false;
                    }
                    if ($permit->start_slot && $permit->end_slot && $agendaSlot > 0) {
                        if ($agendaSlot < $permit->start_slot || $agendaSlot > $permit->end_slot) {
                            return false;
                        }
                    }
                    return true;
                })->first();

                $isPresent = (bool) $att->is_present;
                $rawNote = strtoupper(trim((string)$att->note));

                if ($isPresent) {
                    $h++;
                } else {
                    if ($studentPermit) {
                        $pType = strtoupper(trim($studentPermit->permit_type));
                        if ($pType === 'S') {
                            $s++;
                            $absentStudents[] = "{$studentName} (S)";
                        } elseif ($pType === 'I') {
                            $i++;
                            $absentStudents[] = "{$studentName} (I)";
                        } elseif ($pType === 'D') {
                            $d++;
                            $absentStudents[] = "{$studentName} (D)";
                        } elseif ($pType === 'T') {
                            $h++;
                        } else {
                            $a++;
                            $absentStudents[] = "{$studentName} (A)";
                        }
                    } else {
                        if ($rawNote === 'S') {
                            $s++;
                            $absentStudents[] = "{$studentName} (S)";
                        } elseif ($rawNote === 'I') {
                            $i++;
                            $absentStudents[] = "{$studentName} (I)";
                        } elseif ($rawNote === 'D') {
                            $d++;
                            $absentStudents[] = "{$studentName} (D)";
                        } elseif ($rawNote === 'T') {
                            $h++;
                        } else {
                            $a++;
                            $absentStudents[] = "{$studentName} (A)";
                        }
                    }
                }
            }

            $agenda->hadir_count = $h;
            $agenda->sakit_count = $s;
            $agenda->izin_count = $i;
            $agenda->dispen_count = $d;
            $agenda->alpa_count = $a;
            $agenda->th_count = $s + $i + $d + $a;
            $agenda->absent_students = $absentStudents;
            $agenda->absent_students_str = !empty($absentStudents) ? implode(', ', $absentStudents) : '-';

            return $agenda;
        });
    }

    public function personal(Request $request)
    {
        $teacher = Teacher::where('user_id', Auth::id())->firstOrFail();
        $activeYear = AcademicYear::where('is_active', true)->first();
        $month = $request->query('month', date('m'));
        $year = $request->query('year', date('Y'));

        $rawAgendas = TeachingAgenda::query()
            ->leftJoin('schedule_details', 'teaching_agendas.schedule_detail_id', '=', 'schedule_details.id')
            ->select('teaching_agendas.*')
            ->with(['classroom', 'subject', 'tp', 'scheduleDetail', 'attendances.student'])
            ->withCount('attendances')
            ->where('teaching_agendas.teacher_id', $teacher->id)
            ->whereMonth('teaching_agendas.date', $month)
            ->whereYear('teaching_agendas.date', $year)
            ->orderBy('teaching_agendas.date', 'asc')
            ->orderByRaw('COALESCE(teaching_agendas.start_slot, schedule_details.start_slot) ASC')
            ->get();

        $formattedAgendas = $this->formatAgendasWithPermits($rawAgendas, $activeYear?->id);
        $agendasGrouped = $formattedAgendas->groupBy(fn ($item) => Carbon::parse($item->date)->format('Y-m-d'));

        return Inertia::render($this->resolveView('Personal'), [
            'reports' => $agendasGrouped,
            'filters' => [
                'month' => (int) $month,
                'year' => (int) $year,
            ],
            'yearOptions' => collect(range(date('Y') - 2, date('Y')))->map(fn ($y) => ['val' => $y, 'label' => $y]),
        ]);
    }

    public function classroom(Request $request): \Illuminate\Http\RedirectResponse|\Inertia\Response
    {
        // 1. Cari data guru yang sedang login
        $teacher = Teacher::where('user_id', Auth::id())->firstOrFail();

        // 2. Cari kelas di mana guru ini menjadi Wali Kelas di tahun ajaran aktif
        $activeYear = AcademicYear::where('is_active', true)->first();
        
        /** @var Classroom|null $classroom */
        $classroom = Classroom::where('teacher_id', $teacher->id)
            ->when($activeYear, fn (\Illuminate\Database\Eloquent\Builder $q) => $q->where('academic_year_id', $activeYear->id))
            ->latest('academic_year_id')
            ->first();

        if (! $classroom) {
            return redirect()->back()->with('error', 'Anda tidak terdaftar sebagai Wali Kelas.');
        }

        $month = $request->query('month', date('m'));
        $year = $request->query('year', date('Y'));

        // 3. Ambil SEMUA agenda guru manapun yang mengajar di kelas tersebut
        $rawAgendas = TeachingAgenda::query()
            ->leftJoin('schedule_details', 'teaching_agendas.schedule_detail_id', '=', 'schedule_details.id')
            ->select('teaching_agendas.*')
            ->with(['teacher', 'subject', 'tp', 'scheduleDetail', 'attendances.student'])
            ->withCount('attendances')
            ->where('teaching_agendas.classroom_id', $classroom->id)
            ->whereMonth('teaching_agendas.date', $month)
            ->whereYear('teaching_agendas.date', $year)
            ->orderBy('teaching_agendas.date', 'asc')
            ->orderByRaw('COALESCE(teaching_agendas.start_slot, schedule_details.start_slot) ASC')
            ->get();

        $formattedAgendas = $this->formatAgendasWithPermits($rawAgendas, $activeYear?->id);
        $agendasGrouped = $formattedAgendas->groupBy(fn ($item) => Carbon::parse($item->date)->format('Y-m-d'));

        return Inertia::render($this->resolveView('Classroom'), [
            'reports' => $agendasGrouped,
            'classroom' => $classroom,
            'filters' => [
                'month' => (int) $month,
                'year' => (int) $year,
            ],
            'yearOptions' => collect(range(date('Y') - 1, date('Y') + 1))->map(fn ($y) => ['val' => $y, 'label' => $y]),
        ]);
    }

    public function personalPdf(Request $request)
    {
        $teacher = Teacher::where('user_id', Auth::id())->firstOrFail();
        $activeYear = AcademicYear::where('is_active', true)->first();
        $month = $request->query('month', date('m'));
        $year = $request->query('year', date('Y'));

        $rawAgendas = TeachingAgenda::query()
            ->leftJoin('schedule_details', 'teaching_agendas.schedule_detail_id', '=', 'schedule_details.id')
            ->select('teaching_agendas.*')
            ->with(['classroom', 'subject', 'tp', 'scheduleDetail', 'attendances.student'])
            ->withCount('attendances')
            ->where('teaching_agendas.teacher_id', $teacher->id)
            ->whereMonth('teaching_agendas.date', $month)
            ->whereYear('teaching_agendas.date', $year)
            ->orderBy('teaching_agendas.date', 'asc')
            ->orderByRaw('COALESCE(teaching_agendas.start_slot, schedule_details.start_slot) ASC')
            ->get();

        $formattedAgendas = $this->formatAgendasWithPermits($rawAgendas, $activeYear?->id);
        $agendasGrouped = $formattedAgendas->groupBy(fn ($item) => Carbon::parse($item->date)->format('Y-m-d'));

        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        $data = [
            'reports' => $agendasGrouped,
            'monthName' => $months[(int)$month],
            'year' => $year,
            'teacher' => $teacher
        ];

        $pdf = Pdf::loadView('akademik::pdf.agenda_personal', $data)
                ->setPaper('a4', 'portrait');

        $teacherNameSlug = str_replace(' ', '_', $teacher->full_name);
        return $pdf->stream("Jurnal_Mengajar_{$teacherNameSlug}_{$month}_{$year}.pdf");
    }

    public function classroomPdf(Request $request)
    {
        $teacher = Teacher::where('user_id', Auth::id())->firstOrFail();
        $activeYear = AcademicYear::where('is_active', true)->first();
        $classroom = Classroom::where('teacher_id', $teacher->id)
            ->when($activeYear, fn ($q) => $q->where('academic_year_id', $activeYear->id))
            ->latest('academic_year_id')
            ->first();

        if (! $classroom) {
            return redirect()->back()->with('error', 'Anda tidak terdaftar sebagai Wali Kelas.');
        }

        $month = $request->query('month', date('m'));
        $year = $request->query('year', date('Y'));

        $rawAgendas = TeachingAgenda::query()
            ->leftJoin('schedule_details', 'teaching_agendas.schedule_detail_id', '=', 'schedule_details.id')
            ->select('teaching_agendas.*')
            ->with(['teacher', 'subject', 'tp', 'scheduleDetail', 'attendances.student'])
            ->withCount('attendances')
            ->where('teaching_agendas.classroom_id', $classroom->id)
            ->whereMonth('teaching_agendas.date', $month)
            ->whereYear('teaching_agendas.date', $year)
            ->orderBy('teaching_agendas.date', 'asc')
            ->orderByRaw('COALESCE(teaching_agendas.start_slot, schedule_details.start_slot) ASC')
            ->get();

        $formattedAgendas = $this->formatAgendasWithPermits($rawAgendas, $activeYear?->id);
        $agendasGrouped = $formattedAgendas->groupBy(fn ($item) => Carbon::parse($item->date)->format('Y-m-d'));

        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        $data = [
            'reports' => $agendasGrouped,
            'monthName' => $months[(int)$month],
            'year' => $year,
            'classroom' => $classroom,
            'teacher' => $teacher
        ];

        $pdf = Pdf::loadView('akademik::pdf.agenda_classroom', $data)
                ->setPaper('a4', 'portrait');

        $classroomNameSlug = str_replace(' ', '_', $classroom->name);
        return $pdf->stream("Jurnal_Kelas_{$classroomNameSlug}_{$month}_{$year}.pdf");
    }
}
