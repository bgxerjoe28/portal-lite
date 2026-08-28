<?php

namespace Modules\Penilaian\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\TeacherService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Akademik\Models\AcademicYear;
use Modules\Akademik\Models\Subject;
use Modules\Penilaian\Models\GradingComponent;

class GradingComponentController extends Controller
{
    public function index()
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        $teacherId = TeacherService::getAuthTeacherId();
        if (! $teacherId) {
            return redirect()->route('dashboard')->with('error', 'Hanya guru yang bisa mengakses halaman ini.');
        }
        $subjects = Subject::whereHas('assignments', function ($q) use ($teacherId) {
            $q->where('teacher_id', $teacherId);
        })->get();

        return Inertia::render('Penilaian/Components/Index', [
            'components' => GradingComponent::with('subject')
                ->where('academic_year_id', $activeYear->id)
                ->where('teacher_id', $teacherId)
                ->orderBy('sort_order', 'asc')
                ->get(),
            'subjects' => $subjects,

        ]);
    }

    public function store(Request $request)
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        $teacherId = TeacherService::getAuthTeacherId();
        if (! $teacherId) {
            return redirect()->route('dashboard')->with('error', 'Hanya guru yang bisa mengakses halaman ini.');
        }
        $request->validate([
            'subject_id' => 'required',
            'name' => 'required|string|max:100',
            'weight' => 'required|numeric|min:0|max:100',
            'sort_order' => 'required|numeric',
            'passing_grade' => 'required|numeric|min:0|max:100',
        ]);
        GradingComponent::create([
            'academic_year_id' => $activeYear->id,
            'subject_id' => $request->subject_id,
            'name' => $request->name,
            'weight' => $request->weight,
            'teacher_id' => $teacherId,
            'sort_order' => $request->sort_order,
            'passing_grade' => $request->passing_grade,
        ]);

        return redirect()->back()->with('success', 'Komponen berhasil ditambahkan');
    }

    public function update(Request $request, $id)
    {
        $component = GradingComponent::findOrFail($id);
        $component->update($request->validate([
            'name' => 'required|string',
            'weight' => 'required|numeric|min:0|max:100',
            'sort_order' => 'required|numeric',
            'passing_grade' => 'required|numeric|min:0|max:100',
        ]));

        return redirect()->back()->with('success', 'Komponen berhasil diperbarui');
    }

    public function destroy($id)
    {
        GradingComponent::findOrFail($id)->delete();

        return redirect()->back()->with('success', 'Komponen berhasil dihapus');
    }
}
