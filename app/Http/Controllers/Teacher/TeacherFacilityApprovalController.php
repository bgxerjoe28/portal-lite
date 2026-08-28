<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\FacilityReservation;
use App\Services\FacilityReservationService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Modules\Kesiswaan\Models\Extracurricular;

class TeacherFacilityApprovalController extends Controller
{
    protected FacilityReservationService $reservationService;

    public function __construct(FacilityReservationService $reservationService)
    {
        $this->reservationService = $reservationService;
    }

    public function index(Request $request)
    {
        $user = Auth::user();

        // Cari eskul yang dibina oleh guru ini
        $coachedEskulIds = Extracurricular::whereHas('teachers', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->orWhere('coach_name', 'like', "%{$user->name}%")
            ->pluck('id');

        $query = FacilityReservation::with(['user', 'extracurricular', 'room', 'assets'])
            ->where(function ($q) use ($user, $coachedEskulIds) {
                $q->where('coach_user_id', $user->id)
                  ->orWhereIn('extracurricular_id', $coachedEskulIds);
            });

        if ($request->status) {
            $query->where('stage1_status', $request->status);
        }

        $reservations = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

        return Inertia::render('Teacher/SarprasApproval/Index', [
            'reservations' => $reservations,
            'filters' => $request->only(['status']),
        ]);
    }

    public function show($id)
    {
        $user = Auth::user();

        $reservation = FacilityReservation::with([
            'user',
            'extracurricular',
            'room',
            'assets',
            'stage1Approver',
            'stage2Approver'
        ])->findOrFail($id);

        return Inertia::render('Teacher/SarprasApproval/Show', [
            'reservation' => $reservation,
        ]);
    }

    public function approve(Request $request, $id)
    {
        $reservation = FacilityReservation::findOrFail($id);
        $notes = $request->input('notes');

        try {
            $this->reservationService->approveStage1($reservation, Auth::id(), $notes);
            return redirect()->back()->with('success', "Persetujuan Tahap 1 untuk {$reservation->reservation_code} berhasil diberikan. Pengajuan diteruskan ke Petugas Sarpras.");
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'notes' => 'required|string|min:5',
        ], [
            'notes.required' => 'Alasan penolakan izin kegiatan wajib diisi.',
        ]);

        $reservation = FacilityReservation::findOrFail($id);

        try {
            $this->reservationService->rejectStage1($reservation, Auth::id(), $request->input('notes'));
            return redirect()->back()->with('success', "Pengajuan izin kegiatan {$reservation->reservation_code} telah ditolak.");
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
