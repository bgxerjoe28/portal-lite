<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\FacilityAsset;
use App\Models\FacilityReservation;
use App\Models\FacilityRoom;
use App\Services\FacilityCollisionService;
use App\Services\FacilityReservationService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Modules\Kesiswaan\Models\Extracurricular;
use Barryvdh\DomPDF\Facade\Pdf;

class StudentFacilityReservationController extends Controller
{
    protected FacilityCollisionService $collisionService;
    protected FacilityReservationService $reservationService;

    public function __construct(
        FacilityCollisionService $collisionService,
        FacilityReservationService $reservationService
    ) {
        $this->collisionService = $collisionService;
        $this->reservationService = $reservationService;
    }

    public function index(Request $request)
    {
        $user = Auth::user();

        $query = FacilityReservation::with(['room', 'assets', 'extracurricular'])
            ->where('user_id', $user->id);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $reservations = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

        return Inertia::render('Student/FacilityReservation/Index', [
            'reservations' => $reservations,
            'filters' => $request->only(['status']),
        ]);
    }

    public function create()
    {
        $user = Auth::user();
        $student = $user->student;

        $rooms = FacilityRoom::where('status', 'available')
            ->where('is_reservable', true)
            ->orderBy('name', 'asc')
            ->get();

        $assets = FacilityAsset::where('status', 'available')
            ->whereIn('condition', ['good', 'minor_damage'])
            ->orderBy('name', 'asc')
            ->get();

        // Hanya ekstrakulikuler aktif yang diikuti oleh siswa ini
        $extracurriculars = collect();
        if ($student) {
            $extracurriculars = Extracurricular::whereHas('students', function ($q) use ($student) {
                    $q->where('student_id', $student->id)
                      ->where('extracurricular_students.status', 'active');
                })
                ->where('is_active', true)
                ->get(['id', 'name', 'coach_name']);
        }

        return Inertia::render('Student/FacilityReservation/Create', [
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
        $student = $user->student;

        $validated = $request->validate([
            'type' => 'required|in:room_only,asset_only,both',
            'room_id' => 'required_if:type,room_only,both|nullable|exists:facility_rooms,id',
            'asset_ids' => 'required_if:type,asset_only|nullable|array',
            'asset_ids.*' => 'exists:facility_assets,id',
            'extracurricular_id' => 'required|exists:extracurriculars,id',
            'title' => 'required|string|max:255',
            'purpose' => 'required|string',
            'start_time' => 'required|date|after:now',
            'end_time' => 'required|date|after:start_time',
            'participant_count' => 'nullable|integer|min:1',
            'proposal_file' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
        ], [
            'extracurricular_id.required' => 'Peminjaman fasilitas (ruangan/aset) wajib memilih ekstrakulikuler yang Anda ikuti.',
            'extracurricular_id.exists' => 'Ekstrakulikuler yang dipilih tidak valid.',
            'room_id.required_if' => 'Wajib memilih ruangan yang akan dipinjam.',
            'asset_ids.required_if' => 'Minimal pilih 1 peralatan/aset jika memilih peminjaman peralatan.',
        ]);

        if (!empty($validated['extracurricular_id'])) {
            // Validasi kepesertaan siswa pada eskul tersebut
            $isEnrolled = Extracurricular::where('id', $validated['extracurricular_id'])
                ->whereHas('students', function ($q) use ($student) {
                    $q->where('student_id', $student?->id)
                      ->where('extracurricular_students.status', 'active');
                })
                ->exists();

            if (!$isEnrolled) {
                return redirect()->back()
                    ->withErrors(['extracurricular_id' => 'Anda tidak terdaftar sebagai anggota aktif pada ekstrakulikuler yang dipilih.'])
                    ->withInput();
            }

            $eskul = Extracurricular::with('teachers.user')->find($validated['extracurricular_id']);
            $firstCoachUser = $eskul?->teachers->first()?->user_id;
            if ($firstCoachUser) {
                $validated['coach_user_id'] = $firstCoachUser;
                $validated['requires_coach_approval'] = true;
            }
        }

        if ($request->hasFile('proposal_file')) {
            $path = $request->file('proposal_file')->store('facility/proposals', config('filesystems.default'));
            $validated['proposal_file_path'] = \Illuminate\Support\Facades\Storage::url($path);
        }

        try {
            $reservation = $this->reservationService->createReservation($validated, $user->id);
            return redirect()->route('student.sarpras.reservations.show', $reservation->id)
                ->with('success', 'Permohonan peminjaman berhasil dikirim. Menunggu proses persetujuan.');
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
            'damageReports'
        ])
        ->where('user_id', $user->id)
        ->findOrFail($id);

        return Inertia::render('Student/FacilityReservation/Show', [
            'reservation' => $reservation,
        ]);
    }

    public function downloadPermit($id)
    {
        $user = Auth::user();

        $reservation = FacilityReservation::with([
            'room',
            'assets',
            'extracurricular',
            'user',
            'stage1Approver',
            'stage2Approver',
            'handoverUser'
        ])
        ->where('user_id', $user->id)
        ->findOrFail($id);

        if (!in_array($reservation->status, ['approved', 'in_use', 'completed'])) {
            abort(403, 'Surat Izin (E-Permit) hanya dapat diunduh untuk pengajuan yang telah disetujui.');
        }

        $pdf = Pdf::loadView('pdf.sarpras.e_permit', compact('reservation'));
        return $pdf->download("E-Permit-{$reservation->reservation_code}.pdf");
    }

    public function cancel($id)
    {
        $user = Auth::user();

        $reservation = FacilityReservation::where('user_id', $user->id)->findOrFail($id);

        if (!in_array($reservation->status, ['draft', 'pending_coach', 'pending_sarpras'])) {
            return redirect()->back()->withErrors(['error' => 'Peminjaman ini sudah disetujui/sedang berjalan dan tidak dapat dibatalkan mandiri. Hubungi petugas Sarpras.']);
        }

        $reservation->update(['status' => 'cancelled']);

        return redirect()->back()->with('success', 'Peminjaman berhasil dibatalkan.');
    }
}
