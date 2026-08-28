<?php

namespace Modules\Akademik\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Akademik\Imports\TpImport;
use Modules\Akademik\Models\AcademicYear;
use Modules\Akademik\Models\LearningObjectiveTP;
use Modules\Akademik\Models\LearningOutcomeCP;
use Modules\Akademik\Models\Schedule;
use Modules\Akademik\Models\Subject;
use Illuminate\Validation\Rule;
use Modules\Akademik\Exports\TpTemplateExport;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Validators\Failure;
use Illuminate\Support\Facades\Auth;


class GuruTPController extends Controller
{
    private function buildKodeTP($cp, string $nomorTp): string
    {
        // subject code (mis: INF)
        $subjectCode = strtoupper($cp->subject->code);

        // fase (E/F)
        $fase = strtoupper($cp->fase);

        // urutan CP dalam mapel & fase (1-based)
        $cpUrut = LearningOutcomeCP::where('subjects_id', $cp->subjects_id)
            ->where('fase', $cp->fase)
            ->where('id', '<=', $cp->id)
            ->count();

        // nomor TP (mis: 1.1)
        $nomor = trim($nomorTp);

        return "{$subjectCode}.{$fase}.{$cpUrut}.{$nomor}";
    }
    private function generateKodeTP(LearningOutcomeCP $cp, string $nomorTp, ?string $oldKode = null): string
    {
        $subjectCode = strtoupper($cp->subject->code);
        $fase = strtoupper($cp->fase);

        // ✅ Ambil cp_urut dari kode lama (saat edit)
        if ($oldKode) {
            $parts = explode('.', $oldKode);
            $cpUrut = $parts[2] ?? null;
        }

        // 🔒 Fallback (jika create / darurat)
        if (empty($cpUrut)) {
            $cpUrut = LearningOutcomeCP::where('subjects_id', $cp->subjects_id)
                ->where('fase', $cp->fase)
                ->where('id', '<=', $cp->id)
                ->count();
        }

        return "{$subjectCode}.{$fase}.{$cpUrut}.{$nomorTp}";
    }

    private function allowedSubjectIdsForGuru(): Collection
    {
        $activeYearId = AcademicYear::where('is_active', true)->value('id');
        $teacherId = Auth::user()->teacher->id;

        if (! $teacherId || ! $activeYearId) {
            return collect();
        }

        return Schedule::query()
            ->where('teacher_id', $teacherId)
            ->where('academic_year_id', $activeYearId)
            ->pluck('subject_id')
            ->unique();
    }

    private function assertCpAllowed(LearningOutcomeCP $cp): void
    {
        $allowed = $this->allowedSubjectIdsForGuru();
        abort_unless(
            $allowed->contains($cp->subjects_id),
            403,
            'CP bukan mapel Anda.'
        );
    }
    public function index(Request $request, LearningOutcomeCP $cp)
    {
        $this->assertCpAllowed($cp);
        $query = LearningObjectiveTP::where('cp_id', $cp->id);

        // 🔍 SEARCH TP
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where('rumusan_tp', 'like', "%$s%");
        }

        return Inertia::render('Guru/TP/Index', [
            'cp' => $cp->load('subject'),
            'tps' => $query
                ->orderBy('urutan')
                ->orderBy('nomor_tp')
                ->get(),
            'filters' => $request->only('search'),
            'cp_urut' => LearningOutcomeCP::where('subjects_id', $cp->subjects_id)
                ->where('fase', $cp->fase)
                ->where('id', '<=', $cp->id)
                ->count(),
        ]);
    }
    public function store(Request $request, LearningOutcomeCP $cp)
    {
        $data = $request->validate([
            'nomor_tp' => [
                'required', 'string',
                Rule::unique('learning_objectives_tp')->where(fn ($q) => $q->where('cp_id', $cp->id)),
            ],
            'rumusan_tp' => 'required|string',
            'urutan' => 'nullable|integer',
            'is_active' => 'boolean',
        ], [
            'nomor_tp.unique' => 'Nomor TP sudah digunakan pada CP ini.',
        ]);

        $data['kode_tp'] = $this->buildKodeTP($cp, $data['nomor_tp']);

        $cp->tps()->create($data);

        return back()->with('success', 'TP berhasil ditambahkan');
    }
    public function update(Request $request, LearningObjectiveTP $tp)
    {
        // 🔐 Ambil CP dari TP
        $cp = $tp->cp;

        // 🔐 Pastikan CP sesuai mapel guru
        $this->assertCpAllowed($cp);

        $data = $request->validate([
            'nomor_tp'   => 'required|string|max:10',
            'rumusan_tp' => 'required|string',
            'urutan'     => 'nullable|integer',
            'is_active'  => 'boolean',
        ]);

        // 🔁 Generate ulang kode TP (aman saat edit nomor)
        $data['kode_tp'] = $this->generateKodeTP(
            $cp, 
            $data['nomor_tp'], 
            $tp->kode_tp
        );

        $tp->update($data);

        return back()->with('success', 'TP berhasil diperbarui');
    }
    
    public function import(Request $request, LearningOutcomeCP $cp)
    {
        // dd($cp);
        $request->validate([
            'file' => 'required|mimes:xlsx,xls',
        ]);

        try {
            Excel::import(new TpImport($cp), $request->file('file'));

            return back()->with('success', 'Import TP berhasil diproses');
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal import TP: '.$e->getMessage());
        }
    }

    public function template(LearningOutcomeCP $cp)
    {
        return Excel::download(
            new TpTemplateExport,
            'template_tp_guru.xlsx'
        );
    }

    public function toggle(LearningObjectiveTP $tp)
    {
        $tp->update([
            'is_active' => ! $tp->is_active,
        ]);

        return back()->with('success', 'Status TP diperbarui');
    }
}
