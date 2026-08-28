<?php

namespace App\Http\Controllers\Admin\Sarpras;

use App\Http\Controllers\Controller;
use App\Models\FacilityBlackout;
use App\Models\FacilityRoom;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class AdminFacilityBlackoutController extends Controller
{
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

        $blackouts = FacilityBlackout::with(['room', 'creator'])
            ->orderBy('start_time', 'desc')
            ->paginate(10)
            ->withQueryString();

        $rooms = FacilityRoom::where('status', 'available')->get(['id', 'name', 'code']);

        return Inertia::render('Admin/Sarpras/Blackouts/Index', [
            'blackouts' => $blackouts,
            'rooms' => $rooms,
        ]);
    }

    public function store(Request $request)
    {
        $this->checkAuthorization();

        $validated = $request->validate([
            'room_id' => 'nullable|exists:facility_rooms,id',
            'name' => 'required|string|max:255',
            'reason' => 'nullable|string',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
        ], [
            'end_time.after' => 'Waktu selesai harus setelah waktu mulai.',
        ]);

        $validated['created_by'] = Auth::id();

        FacilityBlackout::create($validated);

        return redirect()->back()->with('success', 'Jadwal terkunci (Blackout) berhasil ditambahkan.');
    }

    public function destroy($id)
    {
        $this->checkAuthorization();

        $blackout = FacilityBlackout::findOrFail($id);
        $blackout->delete();

        return redirect()->back()->with('success', 'Jadwal terkunci berhasil dihapus.');
    }
}
