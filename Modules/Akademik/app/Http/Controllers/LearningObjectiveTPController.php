<?php

namespace Modules\Akademik\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Akademik\Exports\TpTemplateExport;
use Modules\Akademik\Imports\TpImport;
use Modules\Akademik\Models\LearningObjectiveTP;
use Modules\Akademik\Models\LearningOutcomeCP;
use Modules\Akademik\Models\Subject;

class LearningObjectiveTPController extends Controller
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

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, LearningOutcomeCP $cp)
    {
        $query = $cp->tps()->orderBy('urutan');

        // 🗂️ MODE ARSIP
        if ($request->trash === 'true') {
            $query->onlyTrashed();
        }

        return Inertia::render('Akademik/TP/Index', [
            'cp' => $cp->load('subject'),
            'tps' => $query->get(),
            'filters' => $request->only('trash'),
            'cp_urut' => LearningOutcomeCP::where('subjects_id', $cp->subjects_id)
                ->where('fase', $cp->fase)
                ->where('id', '<=', $cp->id)
                ->count(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('akademik::create');
    }

    /**
     * Store a newly created resource in storage.
     */
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

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('akademik::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('akademik::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, LearningObjectiveTP $tp)
    {
        $data = $request->validate([
            'nomor_tp' => [
                'required', 'string',
                Rule::unique('learning_objectives_tp')->ignore($tp->id)->where(fn ($q) => $q->where('cp_id', $tp->cp_id)),
            ],
            'rumusan_tp' => 'required|string',
            'urutan' => 'nullable|integer',
            'is_active' => 'boolean',
        ]);

        $data['kode_tp'] = $this->buildKodeTP($tp->cp, $data['nomor_tp']);

        $tp->update($data);

        return back()->with('success', 'TP berhasil diperbarui');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(LearningObjectiveTP $tp)
    {
        $tp->update([
            'is_active' => false,
        ]);
        $tp->delete();

        return back()->with('success', 'TP berhasil diarsipkan');
    }

    public function restore($id)
    {
        $tp = LearningObjectiveTP::onlyTrashed()->findOrFail($id);
        $tp->restore();

        return back()->with('success', 'TP berhasil dipulihkan');
    }

    public function exportTemplate(LearningOutcomeCP $cp)
    {
        return Excel::download(
            new TpTemplateExport,
            'template_import_tp.xlsx'
        );
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
}
