<?php

namespace App\Http\Controllers\Admin\Sarpras;

use App\Http\Controllers\Controller;
use App\Models\FacilityReservation;
use App\Models\FacilityRoom;
use App\Models\FacilityAsset;
use App\Services\FacilityReservationService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class AdminFacilityReservationController extends Controller
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

    public function index(Request $request)
    {
        $this->checkAuthorization();

        $query = FacilityReservation::with(['user', 'extracurricular', 'room', 'assets']);

        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->type) {
            $query->where('type', $request->type);
        }
        if ($request->room_id) {
            $query->where('room_id', $request->room_id);
        }
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', "%{$request->search}%")
                  ->orWhere('reservation_code', 'like', "%{$request->search}%")
                  ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$request->search}%"));
            });
        }

        $reservations = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();
        $rooms = FacilityRoom::where('status', 'available')->get(['id', 'name', 'code']);

        // Stats summary for dashboard cards
        $stats = [
            'pending_approval' => FacilityReservation::where('status', 'pending_sarpras')->count(),
            'active_in_use' => FacilityReservation::where('status', 'in_use')->count(),
            'approved_upcoming' => FacilityReservation::where('status', 'approved')->where('start_time', '>=', Carbon::now())->count(),
            'total_completed' => FacilityReservation::where('status', 'completed')->count(),
        ];

        return Inertia::render('Admin/Sarpras/Reservations/Index', [
            'reservations' => $reservations,
            'rooms' => $rooms,
            'stats' => $stats,
            'filters' => $request->only(['status', 'type', 'room_id', 'search']),
        ]);
    }

    public function show($id)
    {
        $this->checkAuthorization();

        $reservation = FacilityReservation::with([
            'user',
            'extracurricular',
            'room',
            'assets',
            'stage1Approver',
            'stage2Approver',
            'handoverUser',
            'returnUser',
            'damageReports.reporter',
        ])->findOrFail($id);

        return Inertia::render('Admin/Sarpras/Reservations/Show', [
            'reservation' => $reservation,
        ]);
    }

    public function approve(Request $request, $id)
    {
        $this->checkAuthorization();

        $reservation = FacilityReservation::findOrFail($id);
        $notes = $request->input('notes');

        try {
            $this->reservationService->approveStage2($reservation, Auth::id(), $notes);
            return redirect()->back()->with('success', "Peminjaman {$reservation->reservation_code} berhasil disetujui & jadwal terkunci.");
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function reject(Request $request, $id)
    {
        $this->checkAuthorization();

        $request->validate([
            'notes' => 'required|string|min:5',
        ], [
            'notes.required' => 'Alasan penolakan wajib diisi.',
            'notes.min' => 'Alasan penolakan minimal 5 karakter.',
        ]);

        $reservation = FacilityReservation::findOrFail($id);

        try {
            $this->reservationService->rejectStage2($reservation, Auth::id(), $request->input('notes'));
            return redirect()->back()->with('success', "Peminjaman {$reservation->reservation_code} telah ditolak.");
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function calendar(Request $request)
    {
        $this->checkAuthorization();

        $rooms = FacilityRoom::where('is_reservable', true)->get(['id', 'name', 'code']);

        return Inertia::render('Admin/Sarpras/Reservations/Calendar', [
            'rooms' => $rooms,
        ]);
    }

    public function calendarEvents(Request $request)
    {
        $this->checkAuthorization();

        $start = $request->query('start');
        $end = $request->query('end');
        $roomId = $request->query('room_id');

        $query = FacilityReservation::with(['room', 'user', 'extracurricular'])
            ->whereIn('status', ['pending_sarpras', 'approved', 'in_use', 'completed']);

        if ($start && $end) {
            $query->where(function ($q) use ($start, $end) {
                $q->where('start_time', '<=', $end)
                  ->where('end_time', '>=', $start);
            });
        }

        if ($roomId) {
            $query->where('room_id', $roomId);
        }

        $reservations = $query->get();

        $events = $reservations->map(function ($res) {
            $color = match ($res->status) {
                'pending_sarpras' => '#f59e0b',
                'approved' => '#3b82f6',
                'in_use' => '#8b5cf6',
                'completed' => '#10b981',
                default => '#6b7280',
            };

            $roomName = $res->room ? " [{$res->room->name}]" : '';
            $borrower = $res->extracurricular ? $res->extracurricular->name : ($res->user->name ?? 'Pengguna');

            return [
                'id' => $res->id,
                'title' => $res->title . $roomName,
                'start' => $res->start_time->toISOString(),
                'end' => $res->end_time->toISOString(),
                'backgroundColor' => $color,
                'borderColor' => $color,
                'extendedProps' => [
                    'code' => $res->reservation_code,
                    'status' => $res->status,
                    'borrower' => $borrower,
                    'room' => $res->room?->name,
                    'type' => $res->type,
                    'show_url' => route('admin.sarpras.reservations.show', $res->id),
                ],
            ];
        });

        return response()->json($events);
    }

    public function exportPdf(Request $request)
    {
        $this->checkAuthorization();

        $query = FacilityReservation::with(['user', 'extracurricular', 'room', 'assets']);

        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->start_date && $request->end_date) {
            $query->whereBetween('start_time', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
        }

        $reservations = $query->orderBy('start_time', 'desc')->get();

        $pdf = Pdf::loadView('pdf.sarpras.reservation_recap', compact('reservations'));
        return $pdf->download('Rekap-Peminjaman-Sarpras.pdf');
    }
}
