<?php

namespace Modules\Akademik\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Jenssegers\Agent\Agent;
use Modules\Akademik\Models\StudentPermit;
use Modules\Akademik\Services\LateStudentService;

class LateStudentController extends Controller implements HasMiddleware
{
    // Constructor Property Promotion
    public function __construct(
        protected LateStudentService $lateService
    ) {}

    public static function middleware(): array
    {
        return [new Middleware('permission:manage-lates', only: ['index', 'store', 'destroy'])];
    }

    private function resolveView($viewName)
    {
        // Selalu gunakan view mobile/smartphone untuk guru (desktop view dinonaktifkan)
        return "Guru/Lates/Mobile/{$viewName}";
    }

    public function index()
    {
        return Inertia::render($this->resolveView('Index'), [
            'lates' => $this->lateService->getTodayLates(),
            'today' => now()->format('Y-m-d'),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'reason' => 'nullable|string|max:255',
        ]);

        try {
            $this->lateService->storeLate($request->all());

            return redirect()->back()->with('success', 'Data keterlambatan berhasil dicatat.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function searchStudents(Request $request)
    {
        return response()->json($this->lateService->searchStudents($request->query('query', '')));
    }

    public function print($id)
    {
        $late = $this->lateService->getLateDetail($id);

        return view('akademik::late.print_surat_jalan_ver2', compact('late'));
    }

    public function destroy($id)
    {
        StudentPermit::where('id', $id)->where('permit_type', 'T')->delete();

        return redirect()->back()->with('success', 'Data berhasil dihapus.');
    }

    public function downloadPdf($id)
    {
        $pdf = $this->lateService->generateLatePdf($id);

        return $pdf->download('Surat_Keterlambatan_'.$id.'.pdf');
    }
}
