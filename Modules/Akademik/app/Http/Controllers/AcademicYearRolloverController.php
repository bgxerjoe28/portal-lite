<?php

namespace Modules\Akademik\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Akademik\Models\AcademicYear;
use Modules\Akademik\Models\Student;
use Modules\Akademik\Models\ClassroomStudent;
use Modules\Akademik\Services\AcademicYearRolloverService;
use Exception;

class AcademicYearRolloverController extends Controller
{
    protected $rolloverService;

    public function __construct(AcademicYearRolloverService $rolloverService)
    {
        $this->rolloverService = $rolloverService;
    }

    /**
     * Proses pemindahan tahun ajaran
     */
    public function processRollover(Request $request)
    {
        $request->validate([
            'old_year_id' => 'required|exists:academic_years,id',
            'new_year_id' => 'required|exists:academic_years,id',
            'type'        => 'required|in:semester,year',
        ]);

        $oldYear = AcademicYear::findOrFail($request->old_year_id);
        $newYear = AcademicYear::findOrFail($request->new_year_id);
        $type = $request->type;

        try {
            if ($type === 'semester') {
                // Pindah Semester (Ganjil -> Genap)
                $this->rolloverService->processPindahSemester($oldYear, $newYear);
                $message = 'Berhasil memproses perpindahan semester. Semua kelas dan siswa telah disalin.';
            } else {
                // Ganti Tahun Ajaran (Genap -> Ganjil Baru)
                $this->rolloverService->processGantiTahunAjaran($oldYear, $newYear, [], []);
                $message = 'Berhasil memproses pergantian tahun ajaran baru. Kelas telah diduplikasi (tanpa wali dan tanpa siswa).';
            }

            return redirect()->back()->with('success', $message);
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan saat memproses data: ' . $e->getMessage());
        }
    }

    /**
     * Mengambil daftar siswa kelas X dan XI untuk checklist tidak naik kelas,
     * serta siswa kelas XII untuk checklist tidak lulus
     */
    public function getCandidates(Request $request)
    {
        $yearId = $request->query('year_id');

        if (!$yearId) {
            return response()->json(['error' => 'year_id parameter is required'], 400);
        }

        // Ambil semua pivot di tahun tersebut (kecuali yang pindah/keluar)
        $pivots = ClassroomStudent::with(['student', 'classroom'])
            ->where('academic_year_id', $yearId)
            ->whereIn('status', [
                ClassroomStudent::STATUS_ACTIVE,
                ClassroomStudent::STATUS_PROMOTED,
                ClassroomStudent::STATUS_RETAINED,
                ClassroomStudent::STATUS_LULUS
            ])
            ->get();

        $kelas10_11 = [];
        $kelas12 = [];

        foreach ($pivots as $pivot) {
            if (!$pivot->student || !$pivot->classroom) {
                continue;
            }

            $studentData = [
                'id' => $pivot->student->id,
                'name' => $pivot->student->full_name,
                'nis' => $pivot->student->nis,
                'classroom_name' => $pivot->classroom->name,
                'classroom_id' => $pivot->classroom_id,
                'level' => $pivot->classroom->level,
                'status' => $pivot->status,
            ];

            if ($pivot->classroom->level == 12) {
                $kelas12[] = $studentData;
            } else {
                $kelas10_11[] = $studentData;
            }
        }

        return response()->json([
            'kelas_10_11' => $kelas10_11,
            'kelas_12'    => $kelas12,
        ]);
    }
}
