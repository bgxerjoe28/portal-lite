<?php

namespace Modules\Akademik\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Modules\Akademik\Models\AcademicYear;
use Modules\Akademik\Services\AcademicYearService;

class AcademicYearController extends Controller
{
    public function __construct(
        protected AcademicYearService $service
    ) {}

    public function index()
    {
        return Inertia::render('Akademik/AcademicYear/Index', [
            'academicYears' => $this->service->getAll(),
            'activeYear' => $this->service->getActive(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:20',
            'semester' => 'required|in:ganjil,genap',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $this->service->create($data);

        return back()->with('success', 'Tahun ajaran berhasil dibuat');
    }

    public function update(Request $request, AcademicYear $academicYear)
    {
        $data = $request->validate([
            'name' => 'required|string|max:20',
            'semester' => 'required|in:ganjil,genap',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $this->service->update($academicYear, $data);

        return back()->with('success', 'Tahun ajaran diperbarui');
    }

    public function setActive(AcademicYear $academicYear)
    {
        $this->service->activate($academicYear);

        return back()->with('success', 'Tahun ajaran diaktifkan');
    }
    public function updateSchoolDays(Request $request, AcademicYear $academicYear)
    {
        $validated = $request->validate([
            'school_days' => ['required', 'array', 'min:1'],
            'school_days.*' => ['in:mon,tue,wed,thu,fri,sat'],
        ]);

        $academicYear->update([
            'school_days' => $validated['school_days'],
        ]);

        return redirect()
            ->route('admin.academic-years.index')
            ->with('success', 'Hari sekolah efektif berhasil diperbarui');
    }
    public function editSchoolDays(AcademicYear $academicYear)
    {
        return Inertia::render('Akademik/AcademicYear/Settings', [
            'academicYear' => $academicYear
        ]);
    }
}

