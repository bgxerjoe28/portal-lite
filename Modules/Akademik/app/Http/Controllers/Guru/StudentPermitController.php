<?php

namespace Modules\Akademik\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Jenssegers\Agent\Agent;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Akademik\Exports\PermitFrequencyExport;
use Modules\Akademik\Models\StudentPermit;
use Modules\Akademik\Services\StudentPermitService;

class StudentPermitController extends Controller implements HasMiddleware
{
    public function __construct(
        protected StudentPermitService $permitService
    ) {}

    private function resolveView($viewName)
    {
        // Selalu gunakan view mobile/smartphone untuk guru (desktop view dinonaktifkan)
        if (in_array($viewName, ['Trash', 'Frequency'])) {
            return "Guru/Permits/Mobile/{$viewName}";
        }
        
        return "Guru/Permits/{$viewName}";
    }

    /**
     * Tentukan middleware di sini, bukan di constructor
     */
    public static function middleware(): array
    {
        return [
            // Menggunakan permission dari Spatie
            new Middleware('permission:manage-permits', only: ['index', 'store', 'destroy']),
        ];
    }

    public function index(Request $request)
    {
        $date = $request->query('date', date('Y-m-d'));
        $studentId = $request->query('student_id');
        $studentName = null;
        if ($studentId) {
            $studentName = \Modules\Akademik\Models\Student::where('id', $studentId)->value('full_name');
        }

        $sheetSyncService = app(\Modules\Akademik\Services\GoogleSheetPermitSyncService::class);

        return Inertia::render($this->resolveView('Index'), [
            'permits' => $this->permitService->getPermitListData($request->all()),
            'savedSheetUrl' => $sheetSyncService->getSavedSheetUrl(),
            'filters' => [
                'date' => $date,
                'student_id' => $studentId,
                'student_name' => $studentName,
            ],
        ]);
    }

    public function syncGoogleSheet(Request $request, \Modules\Akademik\Services\GoogleSheetPermitSyncService $syncService)
    {
        $sheetUrl = $request->input('sheet_url');
        $onlyUnchecked = $request->boolean('only_unchecked', true);

        try {
            $result = $syncService->syncFromSheetUrl($sheetUrl, $onlyUnchecked, Auth::id());

            $msg = "Sinkronisasi berhasil! {$result['imported_count']} izin siswa berhasil disimpan.";
            if ($result['skipped_count'] > 0) {
                $msg .= " ({$result['skipped_count']} dilewati karena sudah berstatus TRUE).";
            }
            if (!empty($result['unmatched_rows'])) {
                $msg .= " " . count($result['unmatched_rows']) . " data gagal dicocokkan.";
            }

            return redirect()->back()->with('success', $msg)->with('sheetSyncResult', $result);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'student_ids' => 'required_without:student_id|array',
            'student_id' => 'required_without:student_ids',
            'date_range' => 'required|array|min:1',
            'permit_type' => 'required',
            'reason' => 'nullable|string',
            'start_slot' => 'nullable|integer|min:1|max:10',
            'end_slot' => 'nullable|integer|min:1|max:10|gte:start_slot',
        ]);

        try {
            $this->permitService->storePermit($request->all());

            return redirect()->back()->with('success', 'Izin siswa berhasil dicatat.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function storeBulk(Request $request)
    {
        $request->validate([
            'date_range' => 'required|array|min:1',
            'rows' => 'required|array|min:1',
            'rows.*.student_id' => 'required',
            'rows.*.permit_type' => 'required',
            'rows.*.reason' => 'nullable|string',
            'rows.*.start_slot' => 'nullable|integer|min:1|max:10',
            'rows.*.end_slot' => 'nullable|integer|min:1|max:10|gte:rows.*.start_slot',
        ]);

        try {
            $this->permitService->storeBulkPermits($request->all());

            return redirect()->back()->with('success', 'Izin siswa massal berhasil dicatat.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function searchStudents(Request $request)
    {
        return response()->json($this->permitService->searchStudents($request->query('q')));
    }

    public function destroy(StudentPermit $permit)
    {
        $permit->delete();

        return redirect()->back()->with('success', 'Data izin masuk kotak sampah.');
    }

    public function trash()
    {
        return Inertia::render($this->resolveView('Trash'), [
            'trash' => $this->permitService->getTrashData(),
        ]);
    }

    public function restore($id)
    {
        StudentPermit::onlyTrashed()->findOrFail($id)->restore();

        return redirect()->back()->with('success', 'Data berhasil dipulihkan.');
    }

    public function forceDelete($id)
    {
        StudentPermit::onlyTrashed()->findOrFail($id)->forceDelete();

        return redirect()->back()->with('success', 'Data dihapus permanen.');
    }

    public function emptyTrash()
    {
        StudentPermit::onlyTrashed()->forceDelete();

        return redirect()->route('permits.index')->with('success', 'Sampah dikosongkan.');
    }

    public function frequency(Request $request)
    {
        if (!$request->has('academic_year_ids')) {
            $activeYearId = \Modules\Akademik\Models\AcademicYear::where('is_active', true)->value('id');
            if ($activeYearId) {
                $request->merge(['academic_year_ids' => (string) $activeYearId]);
            }
        }

        $stats = $this->permitService->getStudentFrequency($request->all());
        $academicYears = \Modules\Akademik\Models\AcademicYear::orderBy('name', 'desc')->orderBy('semester', 'desc')->get();

        return Inertia::render($this->resolveView('Frequency'), [
            'statistics' => $stats,
            'academicYears' => $academicYears,
            'filters' => $request->only(['start_date', 'end_date', 'academic_year_ids']),
        ]);
    }

    public function exportExcel(Request $request)
    {
        if (!$request->has('academic_year_ids')) {
            $activeYearId = \Modules\Akademik\Models\AcademicYear::where('is_active', true)->value('id');
            if ($activeYearId) {
                $request->merge(['academic_year_ids' => (string) $activeYearId]);
            }
        }

        $data = $this->permitService->getStudentFrequency($request->all());

        return Excel::download(new PermitFrequencyExport($data), 'Rekap_Kedisiplinan_Siswa.xlsx');
    }

    public function exportPdf(Request $request)
    {
        if (!$request->has('academic_year_ids')) {
            $activeYearId = \Modules\Akademik\Models\AcademicYear::where('is_active', true)->value('id');
            if ($activeYearId) {
                $request->merge(['academic_year_ids' => (string) $activeYearId]);
            }
        }

        $statistics = $this->permitService->getStudentFrequency($request->all());
        $filters = $request->only(['start_date', 'end_date', 'academic_year_ids']);

        $pdf = Pdf::loadView('akademik::permits.pdf_frequency', compact('statistics', 'filters'))
            ->setPaper('a4', 'landscape'); // Landscape agar tabel S,I,D,T,A muat

        return $pdf->download('Rekap_Kedisiplinan_Siswa.pdf');
    }
}
