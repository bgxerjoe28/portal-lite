<?php

namespace Modules\Penilaian\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\TeacherService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Akademik\Models\AcademicYear;
use Modules\Akademik\Models\Schedule;
use Modules\Penilaian\Exports\GradeRecapExport;
use Modules\Penilaian\Http\Requests\StoreGradeRequest;
use Modules\Penilaian\Models\GradingItem;
use Modules\Penilaian\Services\GradeService;

class GradeController extends Controller
{
    // Constructor Promotion: Inject Service
    public function __construct(
        protected GradeService $gradeService
    ) {}

    public function index(Request $request)
    {
        $data = $this->gradeService->getGradeListData(
            $request->only(['subject_id', 'classroom_id', 'academic_year_id'])
        );

        return Inertia::render('Penilaian/Grades/Index', [
            'classrooms'     => $data['classrooms'],
            'subjects'       => $data['subjects'],
            'gradingItems'   => $data['items'],
            'academicYears'  => $data['academicYears'],
            'selectedYearId' => $data['selectedYearId'],
            'filters'        => $request->only(['classroom_id', 'subject_id', 'academic_year_id']),
        ]);
    }

    public function store(StoreGradeRequest $request)
    {
        $this->gradeService->storeGrade($request->validated());

        return redirect()->route('penilaian.grades.index')->with('success', 'Nilai berhasil disimpan');
    }

    public function update(Request $request, $id)
    {
        // Gunakan validasi yang sama atau buat UpdateGradeRequest
        $this->gradeService->updateGrade($id, $request->all());

        return redirect()
            ->route('penilaian.grades.index')
            ->with('success', 'Nilai berhasil diperbarui');
    }

    public function create(Request $request)
    {
        // Validasi input agar tidak error saat query
        $request->validate(['classroom_id' => 'required', 'subject_id' => 'required']);

        $data = $this->gradeService->getCreateData($request->all());

        return Inertia::render('Penilaian/Grades/Create', [
            'components' => $data['components'],
            'students' => $data['students'],
            'classroom' => $data['classroom'],
            'subject' => $data['subject'],
        ]);
    }

    public function edit($id)
    {
        $data = $this->gradeService->getEditData($id);

        return Inertia::render('Penilaian/Grades/Edit', [
            'item' => $data['item'],
            'components' => $data['components'],
            'students' => $data['students'],
            'classroom' => $data['classroom'],
            'subject' => $data['subject'],
        ]);
    }

    public function destroy($id)
    {
        try {
            $item = GradingItem::findOrFail($id);

            // Cascade delete akan bekerja secara otomatis di level Database
            $item->delete();

            return redirect()->route('penilaian.grades.index')
                ->with('success', 'Data penilaian dan semua nilai siswa berhasil dihapus');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menghapus data: '.$e->getMessage());
        }
    }

    public function togglePost($id)
    {
        try {
            $item = GradingItem::findOrFail($id);
            $item->is_posted = !$item->is_posted;
            $item->save();

            $statusLabel = $item->is_posted ? 'Diposting (Aktif Dihitung)' : 'Di-Unpost (Draf / Tidak Dihitung)';
            return redirect()->back()
                ->with('success', "Status penilaian '{$item->title}' berhasil diubah menjadi {$statusLabel}.");
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal mengubah status: '.$e->getMessage());
        }
    }

    public function recap(Request $request)
    {
        $request->validate(['classroom_id' => 'required', 'subject_id' => 'required']);
        $activeYear = AcademicYear::where('is_active', true)->first();

        // 🚀 Satu baris untuk semua data
        $data = $this->gradeService->getRecapData($request->classroom_id, $request->subject_id, $activeYear->id);

        // Ambil list dropdown untuk navigasi UI
        $user = auth()->user();
        $teacherId = TeacherService::getAuthTeacherId();
        $scheduleQuery = Schedule::with(['subject', 'classroom', 'religion'])
            ->where('academic_year_id', $activeYear->id);

        if ($user && !$user->hasRole('admin') && $teacherId) {
            $scheduleQuery->where('teacher_id', $teacherId);
        }

        $teacherSchedules = $scheduleQuery->get();

        $teacherSchedules->each(function ($schedule) {
            if ($schedule->subject && $schedule->religion) {
                $clone = clone $schedule->subject;
                if (!str_contains($clone->name, '(' . $schedule->religion->name . ')')) {
                    $clone->name .= ' (' . $schedule->religion->name . ')';
                }
                $schedule->setRelation('subject', $clone);
            }
        });

        return Inertia::render('Penilaian/Grades/Recap', [
            'components' => $data['components'],
            'recapData' => $data['recapData'],
            'subjects' => $teacherSchedules->pluck('subject')->filter()->unique('id')->values(),
            'classrooms' => $teacherSchedules->pluck('classroom')->filter()->unique('id')->values(),
            'schedules' => $teacherSchedules,
            'filters' => $request->only(['classroom_id', 'subject_id']),
            'classroom' => $data['classroom'],
        ]);
    }

    public function exportExcel(Request $request)
    {
        $activeYear = AcademicYear::where('is_active', true)->first();

        // 🚀 Menggunakan Service yang sama = Data 100% Identik dengan Web
        $data = $this->gradeService->getRecapData($request->classroom_id, $request->subject_id, $activeYear->id);

        $fileName = 'Rekap_Nilai_'.str_replace(' ', '_', $data['subject']->name).'_'.str_replace(' ', '_', $data['classroom']->name).'.xlsx';

        return Excel::download(new GradeRecapExport([
            'components' => $data['components'],
            'recapData' => $data['recapData'],
            'subject' => $data['subject'],
            'classroom' => $data['classroom'],
            'teacher' => $data['teacher'],
        ]), $fileName);
    }
}
