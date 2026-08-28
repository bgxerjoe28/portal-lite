<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\FacilityAsset;
use App\Models\FacilityReservation;
use App\Models\FacilityRoom;
use App\Services\FacilityCollisionService;
use App\Services\FacilityReservationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Modules\Kesiswaan\Models\Extracurricular;

class TeacherFacilityReservationController extends Controller
{
    protected FacilityReservationService $reservationService;
    protected FacilityCollisionService $collisionService;

    public function __construct(
        FacilityReservationService $reservationService,
        FacilityCollisionService $collisionService
    ) {
        $this->reservationService = $reservationService;
        $this->collisionService = $collisionService;
    }

    public function index(Request $request)
    {
        $user = Auth::user();

        $query = FacilityReservation::with(['room', 'assets', 'extracurricular', 'stage1Approver', 'stage2Approver'])
            ->where('user_id', $user->id);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $reservations = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

        return Inertia::render('Teacher/SarprasReservation/Index', [
            'reservations' => $reservations,
            'filters' => $request->only(['status']),
        ]);
    }

    public function create()
    {
        $user = Auth::user();

        $rooms = FacilityRoom::where('status', 'available')
            ->where('is_reservable', true)
            ->orderBy('name', 'asc')
            ->get();

        $assets = FacilityAsset::where('status', 'available')
            ->whereIn('condition', ['good', 'minor_damage'])
            ->orderBy('name', 'asc')
            ->get();

        // Ambil eskul yang dibina oleh guru ini jika ada (opsional untuk mewakili eskul)
        $extracurriculars = Extracurricular::whereHas('teachers', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->where('is_active', true)
            ->get(['id', 'name', 'coach_name']);

        return Inertia::render('Teacher/SarprasReservation/Create', [
            'rooms' => $rooms,
            'assets' => $assets,
            'extracurriculars' => $extracurriculars,
        ]);
    }

    public function checkAvailability(Request $request)
    {
        $type = $request->input('type', 'both');
        $roomId = $request->input('room_id');
        $assetIds = $request->input('asset_ids', []);
        $startTime = $request->input('start_time');
        $endTime = $request->input('end_time');

        if (!$startTime || !$endTime) {
            return response()->json(['valid' => false, 'conflicts' => ['Waktu mulai dan selesai wajib dipilih.']], 422);
        }

        $result = $this->collisionService->validateBooking($type, $roomId, $assetIds, $startTime, $endTime);

        return response()->json([
            'valid' => $result['valid'],
            'conflicts' => $result['conflicts'],
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'type' => 'required|in:room_only,asset_only,both',
            'room_id' => 'required_if:type,room_only,both|nullable|exists:facility_rooms,id',
            'asset_ids' => 'required_if:type,asset_only|nullable|array',
            'asset_ids.*' => 'exists:facility_assets,id',
            'extracurricular_id' => 'nullable|exists:extracurriculars,id',
            'title' => 'required|string|max:255',
            'purpose' => 'required|string',
            'start_time' => 'required|date|after:now',
            'end_time' => 'required|date|after:start_time',
            'participant_count' => 'nullable|integer|min:1',
            'proposal_file' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
        ], [
            'room_id.required_if' => 'Wajib memilih ruangan yang ingin dipinjam.',
            'asset_ids.required_if' => 'Minimal pilih 1 peralatan/aset jika memilih peminjaman aset.',
            'start_time.after' => 'Waktu mulai harus di waktu mendatang.',
            'end_time.after' => 'Waktu selesai harus setelah waktu mulai.',
        ]);

        // Karena guru adalah pengajar/staf resmi, tidak memerlukan approval tahap 1 pembina eskul
        $validated['requires_coach_approval'] = false;
        $validated['stage1_status'] = 'approved';

        if ($request->hasFile('proposal_file')) {
            $path = $request->file('proposal_file')->store('facility/proposals', config('filesystems.default'));
            $validated['proposal_file_path'] = \Illuminate\Support\Facades\Storage::url($path);
        }

        try {
            $reservation = $this->reservationService->createReservation($validated, $user->id);
            return redirect()->route('guru.sarpras.reservations.show', $reservation->id)
                ->with('success', 'Permohonan peminjaman sarpras berhasil diajukan. Menunggu persetujuan Tim Sarpras.');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function show($id)
    {
        $user = Auth::user();

        $reservation = FacilityReservation::with([
            'room',
            'assets',
            'extracurricular',
            'stage1Approver',
            'stage2Approver',
            'handoverUser',
            'returnUser',
            'damageReports',
        ])
        ->where('user_id', $user->id)
        ->findOrFail($id);

        return Inertia::render('Teacher/SarprasReservation/Show', [
            'reservation' => $reservation,
        ]);
    }

    public function cancel(Request $request, $id)
    {
        $user = Auth::user();
        $reservation = FacilityReservation::where('user_id', $user->id)->findOrFail($id);

        if (!in_array($reservation->status, ['pending_coach', 'pending_sarpras', 'approved'])) {
            return redirect()->back()->withErrors(['error' => 'Peminjaman tidak dapat dibatalkan pada status saat ini.']);
        }

        $reservation->update(['status' => 'cancelled']);

        return redirect()->back()->with('success', 'Peminjaman sarpras telah dibatalkan.');
    }
}
