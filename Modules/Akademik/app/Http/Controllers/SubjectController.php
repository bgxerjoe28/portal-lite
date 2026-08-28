<?php

namespace Modules\Akademik\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Akademik\Exports\SubjectTemplateExport;
use Modules\Akademik\Imports\SubjectImport;
use Modules\Akademik\Models\Subject;
use Modules\Akademik\Models\SubjectGroup;

class SubjectController extends Controller
{
    public function index(Request $request)
    {
        // Pastikan Model SubjectGroup di-import di paling atas file:
        // use Modules\Akademik\Models\SubjectGroup;

        $query = Subject::with('mappings');

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                // ILIKE itu bagus (PostgreSQL case-insensitive), sangat cocok!
                $q->where('name', 'ILIKE', '%'.$request->search.'%')
                    ->orWhere('code', 'ILIKE', '%'.$request->search.'%');
            });
        }

        if ($request->trash) {
            $query->onlyTrashed();
        }

        $query->orderBy('name', 'asc');

        return Inertia::render('Akademik/SubjectIndex', [
            'subjects' => $query->paginate(15)->withQueryString(),

            // --- KOREKSI DI SINI ---
            // Tambahkan 'trash' agar tombol sampah di Vue tahu dia sedang aktif/tidak
            'filters' => $request->only(['search', 'trash']),
            // -----------------------

            'groups' => SubjectGroup::orderBy('id')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:10|unique:subjects,code',
            'name' => 'required|string|max:100',
            'is_religion' => 'boolean',
        ]);

        Subject::create([
            'name' => $request->name,
            'code' => strtoupper($request->code), // Paksa huruf besar
            'is_religion' => $request->boolean('is_religion'),
        ]);

        return redirect()->back()->with('success', 'Mata pelajaran berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $subject = Subject::findOrFail($id);

        $request->validate([
            // Ignore ID saat cek unique code agar tidak error saat update diri sendiri
            'code' => 'required|string|max:10|unique:subjects,code,'.$id,
            'name' => 'required|string|max:100',
            'is_religion' => 'boolean',

        ]);

        $subject->update([
            'name' => $request->name,
            'code' => strtoupper($request->code),
            'is_religion' => $request->boolean('is_religion'),

        ]);

        return redirect()->back()->with('success', 'Data diperbarui.');
    }

    public function destroy($id)
    {
        $subject = Subject::findOrFail($id);
        $subject->delete();

        return redirect()->back()->with('success', 'Mata pelajaran dipindah ke tray.');
    }

    public function restore($id)
    {
        $subject = Subject::withTrashed()->findOrFail($id);
        $subject->restore();

        return redirect()->back()->with('success', 'Mata pelajaran berhasil dikembalikan.');
    }

    public function forceDelete($id)
    {
        $subject = Subject::withTrashed()->findOrFail($id);
        $subject->forceDelete();

        return redirect()->back()->with('success', 'Mata pelajaran dihapus permanen.');
    }

    public function downloadTemplate()
    {
        return Excel::download(new SubjectTemplateExport, 'template_mapel.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate(['file' => 'required|mimes:xlsx,xls']);

        try {
            Excel::import(new SubjectImport, $request->file('file'));

            return redirect()->back()->with('success', 'Data Master Mapel berhasil diimport!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error: '.$e->getMessage());
        }
    }

    // Simpan Mapping
    public function updateMapping(Request $request, $subjectId)
    {
        $mappings = $request->input('mappings');

        DB::transaction(function () use ($subjectId, $mappings) {
            foreach ($mappings as $map) {
                // 1. Cari data mapping (termasuk yang ada di sampah)
                $existingMapping = \Modules\Akademik\Models\SubjectMapping::withTrashed()
                    ->where('subject_id', $subjectId)
                    ->where('level', $map['level'])
                    ->first();

                // Skenario A: User Memilih Kelompok (Aktifkan)
                if ($map['group_id']) {
                    if ($existingMapping) {
                        // Jika ada (baik aktif/di sampah), kita update & restore
                        $existingMapping->update(['subject_group_id' => $map['group_id']]);
                        if ($existingMapping->trashed()) {
                            $existingMapping->restore();
                        }
                    } else {
                        // Jika belum ada sama sekali, buat baru
                        \Modules\Akademik\Models\SubjectMapping::create([
                            'subject_id' => $subjectId,
                            'level' => $map['level'],
                            'subject_group_id' => $map['group_id'],
                        ]);
                    }
                }
                // Skenario B: User Mengosongkan Kelompok (Matikan/Hapus)
                else {
                    if ($existingMapping) {
                        $existingMapping->delete(); // Soft Delete
                    }
                }
            }
        });

        return redirect()->back()->with('success', 'Aturan mata pelajaran disimpan.');
    }
}
