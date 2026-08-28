<?php

namespace Modules\Akademik\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Modules\Akademik\Models\AcademicYear;
use Modules\Akademik\Models\LearningOutcomeCP;
use Modules\Akademik\Models\Schedule;
use Modules\Akademik\Models\Subject;
use Modules\Akademik\Exports\CpGuruTemplateExport;
use Modules\Akademik\Imports\CpGuruImport;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Akademik\Models\Teacher;
use Illuminate\Support\Collection;


class GuruCPController extends Controller
{
    private function allowedSubjectIdsForGuru(): Collection
    {
        $activeYearId = AcademicYear::where('is_active', true)->value('id');
        $user = Auth::user();
        $teacherId = $user->teacher->id;
        if (! $teacherId || ! $activeYearId) {
            return collect();
        }

        return Schedule::query()
            ->where('teacher_id', $teacherId)
            ->where('academic_year_id', $activeYearId)
            ->pluck('subject_id')
            ->unique()
            ->values();
    }
    private function assertCpAllowed(LearningOutcomeCP $cp): void
    {
        $allowedSubjectIds = $this->allowedSubjectIdsForGuru();

        abort_unless(
            $allowedSubjectIds->contains($cp->subjects_id),
            403,
            'CP tidak sesuai mapel jadwal guru.'
        );
    }


    public function index(Request $request)
    {

        $allowed = $this->allowedSubjectIdsForGuru();
        $query = LearningOutcomeCP::with('subject')
            ->whereIn('subjects_id', $allowed);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('judul_cp', 'ilike', "%$s%")
                    ->orWhereHas('subject', fn ($qs) => $qs->where('name', 'ilike', "%$s%"));
            });
        }

        // dropdown mapel guru (optional filter)
        $subjects = Subject::whereIn('id', $allowed)->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Guru/CP/Index', [
            'cps' => $query->orderBy('subjects_id')->orderBy('fase')->paginate(20)->withQueryString(),
            'subjects' => $subjects,
            'filters' => $request->only('search'),
        ]);
    }

    public function store(Request $request)
    {
        $allowed = $this->allowedSubjectIdsForGuru();

        $data = $request->validate([
            'subjects_id' => ['required', 'integer', Rule::in($allowed)],
            'fase' => ['required', Rule::in(['E', 'F'])],
            'judul_cp' => ['required', 'string', 'max:255'],
            'rumusan_cp' => ['required', 'string'],
            'kata_kunci' => ['nullable', 'string'],
        ]);

        // kata_kunci: "koding,python,bahasa c" → json array
        $data['kata_kunci'] = $data['kata_kunci']
            ? array_values(array_filter(array_map('trim', explode(',', $data['kata_kunci']))))
            : null;

        LearningOutcomeCP::create($data);

        return back()->with('success', 'CP berhasil ditambahkan');
    }

    public function exportTemplate()
    {
        return Excel::download(new CpGuruTemplateExport, 'cp_guru_template.xlsx');

    }
    public function import(Request $request)
    {
        $request->validate([
            'subjects_id' => 'required|integer',
            'file' => 'required|mimes:xlsx,xls',
        ]);

        $import = new CpGuruImport($request->subjects_id);

        Excel::import($import, $request->file('file'));

        // 🔔 Jika ada error baris
        if (count($import->failures())) {
            $errors = collect($import->failures())
                ->map(function ($failure) {
                    return "Baris {$failure->row()}: " . implode(', ', $failure->errors());
                })
                ->values()
                ->toArray();

            return back()->with('error', $errors);
        }

        return back()->with('success', 'Import CP berhasil diproses');
    }

    public function update(Request $request, LearningOutcomeCP $cp)
    {
        $this->assertCpAllowed($cp);

        $request->validate([
            'fase'       => 'required|in:E,F',
            'judul_cp'   => 'required|string|max:255',
            'rumusan_cp' => 'nullable|string',
            'kata_kunci' => 'nullable|string', // input dari UI: "koding,python"
            // subjects_id: jangan dari input! (kunci sesuai CP)
        ]);

        // parsing kata_kunci (text -> json array)
        $kataKunci = collect(explode(',', (string) $request->kata_kunci))
            ->map(fn ($v) => trim($v))
            ->filter()
            ->values()
            ->toArray();

        // validasi unique manual (karena ada unique subjects_id,fase,judul_cp)
        $exists = LearningOutcomeCP::where('subjects_id', $cp->subjects_id)
            ->where('fase', $request->fase)
            ->where('judul_cp', $request->judul_cp)
            ->where('id', '!=', $cp->id)
            ->exists();

        if ($exists) {
            return back()->withErrors([
                'judul_cp' => 'Judul CP sudah ada untuk mapel & fase tersebut.'
            ]);
        }

        $cp->update([
            'fase'       => $request->fase,
            'judul_cp'   => $request->judul_cp,
            'rumusan_cp' => $request->rumusan_cp,
            'kata_kunci' => $kataKunci,
        ]);

        return back()->with('success', 'CP berhasil diperbarui');
    }


    public function showTP(LearningOutcomeCP $cp)
    {
        return redirect()->route('guru.tp.index', $cp->id);
    }
}
