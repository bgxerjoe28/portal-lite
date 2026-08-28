<?php

namespace Modules\Akademik\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Akademik\Exports\CpTemplateExport;
use Modules\Akademik\Imports\CpImport;
use Modules\Akademik\Models\LearningOutcomeCP;
use Modules\Akademik\Models\Subject;

class LearningOutcomeCPController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = LearningOutcomeCP::with('subject');

        // 🔍 SEARCH
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('judul_cp', 'like', "%$s%")
                    ->orWhereHas('subject', fn ($qs) => $qs->where('name', 'like', "%$s%")
                    );
            });
        }

        // 🗂️ SOFT DELETE FILTER
        if ($request->trash === 'true') {
            $query->onlyTrashed();
        }

        return Inertia::render('Akademik/CP/Index', [
            'cps' => $query->orderBy('subjects_id')->orderBy('fase')->paginate(20)->withQueryString(),
            'subjects' => Subject::orderBy('name')->get(),
            'filters' => $request->only('search', 'trash'),
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
    public function store(Request $request)
    {
        $data = $request->validate([
            'subjects_id' => 'required|exists:subjects,id',
            'fase' => 'required|in:E,F',
            // 'judul_cp' => 'required|string',
            'judul_cp' => [
                'required', 'string',
                Rule::unique('learning_outcomes_cp')
                    ->where(fn ($q) => $q
                        ->where('subjects_id', $request->subjects_id)
                        ->where('fase', $request->fase)
                    ),
            ],
            'rumusan_cp' => 'required|string',
            'kata_kunci' => 'nullable|string',
        ], [
            'judul_cp.unique' => 'CP dengan mata pelajaran dan fase ini sudah ada.',
        ]);

        $data['kata_kunci'] = collect(explode(',', $data['kata_kunci'] ?? ''))
            ->map(fn ($v) => trim($v))
            ->filter()
            ->values()
            ->toArray();

        LearningOutcomeCP::create($data);

        return back()->with('success', 'CP berhasil ditambahkan');
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
    public function update(Request $request, LearningOutcomeCP $cp)
    {
        $data = $request->validate([
            'judul_cp' => 'required|string',
            'rumusan_cp' => 'required|string',
            'kata_kunci' => 'nullable|string',
        ]);

        $data['kata_kunci'] = collect(explode(',', $data['kata_kunci'] ?? ''))
            ->map(fn ($v) => trim($v))
            ->filter()
            ->values()
            ->toArray();

        $cp->update($data);

        return back()->with('success', 'CP berhasil diperbarui');
    }

    /**
     * Mohon jangan di pakai
     */
    public function destroy(LearningOutcomeCP $cp)
    {
        $cp->delete();

        return back()->with('success', 'CP berhasil dihapus');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx',
        ]);

        Excel::import(new CpImport, $request->file('file'));

        return back()->with('success', 'Import CP selesai diproses');
    }

    public function template()
    {
        return Excel::download(new CpTemplateExport, 'cp_template.xlsx');

    }

    public function restore($id)
    {
        $cp = LearningOutcomeCP::onlyTrashed()->findOrFail($id);
        $cp->restore();

        return back()->with('success', 'CP berhasil dipulihkan');
    }
}
