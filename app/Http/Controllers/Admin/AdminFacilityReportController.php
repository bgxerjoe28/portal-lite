<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FacilityReport;
use App\Models\FacilityReportItem;
use App\Models\FacilityReportUpdate;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminFacilityReportController extends Controller
{
    protected function checkAuthorization()
    {
        $user = Auth::user();
        if (!$user->hasRole('admin') && !$user->hasPermissionTo('manage-facility-reports', 'web')) {
            abort(403, 'Anda tidak memiliki akses ke fitur ini.');
        }
    }

    public function index(Request $request)
    {
        $this->checkAuthorization();

        $query = FacilityReport::with(['classroom', 'reporter.teacher']);

        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->type) {
            $query->where('type', $request->type);
        }
        if ($request->severity) {
            $query->where('severity', $request->severity);
        }

        $reports = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

        return Inertia::render('Admin/FacilityReport/Index', [
            'reports' => $reports,
            'filters' => $request->only(['status', 'type', 'severity']),
        ]);
    }

    public function itemsIndex(Request $request)
    {
        $this->checkAuthorization();

        $query = FacilityReportItem::with(['facilityReport.classroom', 'facilityReport.reporter.teacher']);

        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->type) {
            $query->where('type', $request->type);
        }
        if ($request->severity) {
            $query->where('severity', $request->severity);
        }
        if ($request->room_name) {
            $query->where('room_name', 'like', '%' . $request->room_name . '%');
        }
        
        $items = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();
        $uniqueRooms = FacilityReportItem::select('room_name')->distinct()->whereNotNull('room_name')->where('room_name', '!=', '')->orderBy('room_name', 'asc')->pluck('room_name');

        return Inertia::render('Admin/FacilityReport/Recap', [
            'items' => $items,
            'uniqueRooms' => $uniqueRooms,
            'filters' => $request->only(['status', 'type', 'severity', 'room_name']),
        ]);
    }

    public function exportPdf(Request $request)
    {
        $this->checkAuthorization();

        $query = FacilityReportItem::with(['facilityReport']);

        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->type) {
            $query->where('type', $request->type);
        }
        if ($request->severity) {
            $query->where('severity', $request->severity);
        }
        if ($request->room_name) {
            $query->where('room_name', 'like', '%' . $request->room_name . '%');
        }

        $items = $query->get();

        $items = $items->sortBy(function($item) {
            return $item->room_name ?: ($item->facilityReport ? $item->facilityReport->location : '');
        })->values();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.facility_reports_pdf', compact('items'));
        
        return $pdf->download('Laporan-Rekap-Sarana-Prasarana.pdf');
    }

    public function show(int $id)
    {
        $this->checkAuthorization();

        $report = FacilityReport::with(['classroom', 'reporter.teacher', 'updates.updater', 'items'])
            ->findOrFail($id);

        return Inertia::render('Admin/FacilityReport/Show', [
            'report' => $report
        ]);
    }

    public function addUpdate(Request $request, int $id)
    {
        $this->checkAuthorization();

        $report = FacilityReport::findOrFail($id);

        $request->validate([
            'new_status' => 'required|in:pending,verified,in_progress,resolved,rejected',
            'notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($report, $request) {
            // Update main status
            $report->update([
                'status' => $request->new_status
            ]);

            // Add update log
            FacilityReportUpdate::create([
                'facility_report_id' => $report->id,
                'updated_by' => Auth::id(),
                'new_status' => $request->new_status,
                'notes' => $request->notes,
                'is_read_by_reporter' => false // Unread by reporter initially
            ]);
        });

        return redirect()->back()->with('success', 'Status laporan berhasil diperbarui.');
    }

    public function storeItems(Request $request, int $id)
    {
        $this->checkAuthorization();

        $report = FacilityReport::findOrFail($id);

        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.type' => 'required|in:sarana,prasarana',
            'items.*.item_name' => 'required|string|max:255',
            'items.*.room_name' => 'nullable|string|max:255',
            'items.*.severity' => 'required|in:ringan,sedang,berat',
            'items.*.recommendation' => 'nullable|string|max:255',
            'items.*.notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($report, $request) {
            foreach ($request->items as $itemData) {
                $report->items()->create([
                    'type' => $itemData['type'],
                    'item_name' => $itemData['item_name'],
                    'room_name' => $itemData['room_name'],
                    'severity' => $itemData['severity'],
                    'recommendation' => $itemData['recommendation'],
                    'notes' => $itemData['notes'],
                    'status' => 'dilaporkan',
                ]);
            }
        });

        return redirect()->back()->with('success', 'Daftar sarana/prasarana berhasil ditambahkan.');
    }

    public function updateItemStatus(Request $request, int $itemId)
    {
        $this->checkAuthorization();

        $item = FacilityReportItem::findOrFail($itemId);

        $request->validate([
            'status' => 'required|in:dilaporkan,diperbaiki,selesai',
        ]);

        $item->update([
            'status' => $request->status
        ]);

        return redirect()->back()->with('success', 'Status item berhasil diperbarui.');
    }

    public function updateItem(Request $request, int $itemId)
    {
        $this->checkAuthorization();

        $item = FacilityReportItem::findOrFail($itemId);

        $request->validate([
            'type' => 'required|in:sarana,prasarana',
            'item_name' => 'required|string|max:255',
            'room_name' => 'nullable|string|max:255',
            'severity' => 'required|in:ringan,sedang,berat',
            'recommendation' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'status' => 'required|in:dilaporkan,diperbaiki,selesai',
        ]);

        $item->update($request->only([
            'type', 'item_name', 'room_name', 'severity', 'recommendation', 'notes', 'status'
        ]));

        return redirect()->back()->with('success', 'Data sarana/prasarana berhasil diperbarui.');
    }

    public function destroyItem(int $itemId)
    {
        $this->checkAuthorization();
        $item = FacilityReportItem::findOrFail($itemId);
        $item->delete();
        
        return redirect()->back()->with('success', 'Data sarana/prasarana berhasil dihapus.');
    }
}
