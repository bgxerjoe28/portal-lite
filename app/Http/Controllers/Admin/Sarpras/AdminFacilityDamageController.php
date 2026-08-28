<?php

namespace App\Http\Controllers\Admin\Sarpras;

use App\Http\Controllers\Controller;
use App\Models\FacilityDamageReport;
use App\Models\FacilityReservation;
use App\Models\FacilityAsset;
use App\Models\FacilityRoom;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

class AdminFacilityDamageController extends Controller
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

        $query = FacilityDamageReport::with([
            'reservation.user',
            'reservation.extracurricular',
            'asset',
            'room',
            'reporter',
            'resolver'
        ]);

        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->damage_type) {
            $query->where('damage_type', $request->damage_type);
        }
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('report_code', 'like', "%{$request->search}%")
                  ->orWhere('description', 'like', "%{$request->search}%");
            });
        }

        $reports = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

        return Inertia::render('Admin/Sarpras/Damages/Index', [
            'reports' => $reports,
            'filters' => $request->only(['status', 'damage_type', 'search']),
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $this->checkAuthorization();

        $report = FacilityDamageReport::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:open,in_review,resolved',
            'action_plan' => 'nullable|string',
            'compensation_fee' => 'nullable|numeric|min:0',
            'is_compensated' => 'boolean',
        ]);

        if ($validated['status'] === 'resolved' && $report->status !== 'resolved') {
            $validated['resolved_by'] = Auth::id();
            $validated['resolved_at'] = now();

            // Jika aset sudah selesai diperbaiki, kembalikan status aset jadi available
            if ($report->asset && $report->damage_type === 'damaged') {
                $report->asset->update(['status' => 'available', 'condition' => 'good']);
            }
        }

        $report->update($validated);

        return redirect()->back()->with('success', 'Status Berita Acara Kerusakan berhasil diperbarui.');
    }

    public function exportPdf($id)
    {
        $this->checkAuthorization();

        $report = FacilityDamageReport::with([
            'reservation.user',
            'reservation.extracurricular',
            'asset',
            'room',
            'reporter',
            'resolver'
        ])->findOrFail($id);

        $pdf = Pdf::loadView('pdf.sarpras.damage_report_pdf', compact('report'));
        return $pdf->download("Berita-Acara-Kerusakan-{$report->report_code}.pdf");
    }
}
