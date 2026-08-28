<?php

namespace App\Http\Controllers\Admin\Sarpras;

use App\Http\Controllers\Controller;
use App\Models\FacilityAsset;
use App\Models\FacilityReservation;
use App\Services\FacilityReservationService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class AdminFacilityHandoverController extends Controller
{
    protected FacilityReservationService $reservationService;

    public function __construct(FacilityReservationService $reservationService)
    {
        $this->reservationService = $reservationService;
    }

    protected function checkAuthorization()
    {
        $user = Auth::user();
        if (!$user->hasRole('admin') && !$user->hasPermissionTo('manage-sarpras', 'web')) {
            abort(403, 'Anda tidak memiliki akses ke manajemen sarpras.');
        }
    }

    public function scannerIndex()
    {
        $this->checkAuthorization();

        return Inertia::render('Admin/Sarpras/Scanner/HandoverReturn');
    }

    public function verifyQr(Request $request)
    {
        $this->checkAuthorization();

        $token = trim($request->input('token'));

        if (!$token) {
            return response()->json(['success' => false, 'message' => 'Token QR tidak boleh kosong.'], 422);
        }

        // 1. Cek apakah token adalah QR Permit Reservasi
        $reservation = FacilityReservation::with(['user', 'extracurricular', 'room', 'assets'])
            ->where('qr_token', $token)
            ->orWhere('reservation_code', $token)
            ->first();

        if ($reservation) {
            return response()->json([
                'success' => true,
                'type' => 'reservation',
                'data' => $reservation,
            ]);
        }

        // 2. Cek apakah token adalah QR Aset Fisik
        $asset = FacilityAsset::where('qr_code_token', $token)
            ->orWhere('asset_code', $token)
            ->first();

        if ($asset) {
            // Cari reservasi aktif yang melibatkan aset ini
            $activeReservation = FacilityReservation::with(['user', 'extracurricular', 'room', 'assets'])
                ->whereIn('status', ['approved', 'in_use'])
                ->whereHas('reservationAssets', fn($q) => $q->where('asset_id', $asset->id))
                ->latest()
                ->first();

            return response()->json([
                'success' => true,
                'type' => 'asset',
                'asset' => $asset,
                'active_reservation' => $activeReservation,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Kode QR / Token tidak valid atau tidak terdaftar dalam sistem.',
        ], 404);
    }

    public function submitHandover(Request $request)
    {
        $this->checkAuthorization();

        $request->validate([
            'reservation_id' => 'required|exists:facility_reservations,id',
            'notes' => 'nullable|string|max:500',
        ]);

        $reservation = FacilityReservation::findOrFail($request->reservation_id);

        try {
            $this->reservationService->processHandover($reservation, Auth::id(), $request->input('notes'));
            return response()->json([
                'success' => true,
                'message' => "Serah terima berhasil. Status peminjaman {$reservation->reservation_code} berubah menjadi 'Dipinjam (In Use)'.",
                'reservation' => $reservation->fresh(['room', 'assets', 'user']),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function submitReturn(Request $request)
    {
        $this->checkAuthorization();

        $request->validate([
            'reservation_id' => 'required|exists:facility_reservations,id',
            'asset_returns' => 'nullable|array',
            'notes' => 'nullable|string|max:500',
            'has_damage' => 'boolean',
            'damage_type' => 'nullable|in:damaged,lost',
            'damage_description' => 'nullable|string',
            'compensation_fee' => 'nullable|numeric|min:0',
        ]);

        $reservation = FacilityReservation::findOrFail($request->reservation_id);

        $damageData = null;
        if ($request->boolean('has_damage')) {
            $damageData = [
                'damage_type' => $request->input('damage_type', 'damaged'),
                'description' => $request->input('damage_description', 'Kerusakan/kehilangan pada saat pengembalian.'),
                'compensation_fee' => $request->input('compensation_fee', 0),
                'action_plan' => $request->input('action_plan', 'Menunggu tindak lanjut / ganti rugi peminjam.'),
            ];
        }

        try {
            $updated = $this->reservationService->processReturn(
                $reservation,
                Auth::id(),
                $request->input('asset_returns', []),
                $request->input('notes'),
                $damageData
            );

            return response()->json([
                'success' => true,
                'message' => "Pengembalian berhasil dicatat. Status peminjaman {$reservation->reservation_code} telah diperbarui.",
                'reservation' => $updated,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}
