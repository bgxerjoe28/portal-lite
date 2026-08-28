<?php

namespace Modules\Akademik\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Akademik\Models\TkaSubject;
use Modules\Akademik\Models\AcademicYear;

class TkaSubjectController extends Controller
{
    public function index(Request $request)
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        
        $subjects = TkaSubject::withCount('students')
            ->when($activeYear, function ($query, $activeYear) {
                return $query->where('academic_year_id', $activeYear->id);
            })
            ->when($request->search, function ($query, $search) {
                return $query->where('name', 'like', "%{$search}%")
                             ->orWhere('code', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $isRegistrationActive = \App\Models\Setting::get('tka_registration_active', '0') === '1';

        return Inertia::render('Akademik/Tka/Index', [
            'subjects' => $subjects,
            'filters' => $request->only(['search']),
            'is_registration_active' => $isRegistrationActive
        ]);
    }

    public function store(Request $request)
    {
        $activeYear = AcademicYear::where('is_active', true)->firstOrFail();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);

        $validated['academic_year_id'] = $activeYear->id;

        TkaSubject::create($validated);

        return back()->with('success', 'Mata Pelajaran TKA berhasil ditambahkan.');
    }

    public function update(Request $request, TkaSubject $tka_subject)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);

        $tka_subject->update($validated);

        return back()->with('success', 'Mata Pelajaran TKA berhasil diperbarui.');
    }

    public function destroy(TkaSubject $tka_subject)
    {
        $tka_subject->delete();

        return back()->with('success', 'Mata Pelajaran TKA berhasil dihapus.');
    }

    public function toggleRegistration(Request $request)
    {
        $validated = $request->validate([
            'is_active' => 'required|boolean'
        ]);

        \App\Models\Setting::updateOrCreate(
            ['key' => 'tka_registration_active'],
            ['value' => $validated['is_active'] ? '1' : '0']
        );

        $status = $validated['is_active'] ? 'dibuka' : 'ditutup';
        return back()->with('success', "Pendaftaran TKA berhasil {$status}.");
    }
}
