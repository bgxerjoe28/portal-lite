<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FacilityReport;
use Modules\Akademik\Models\Classroom;
use Modules\Akademik\Models\AcademicYear;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class FacilityReportController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = FacilityReport::where('reported_by', $user->id)
            ->with(['classroom']);

        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->type) {
            $query->where('type', $request->type);
        }

        $reports = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

        // Count unread updates
        $unreadCount = FacilityReport::where('reported_by', $user->id)
            ->whereHas('updates', function ($q) {
                $q->where('is_read_by_reporter', false);
            })->count();

        return Inertia::render('FacilityReport/Index', [
            'reports' => $reports,
            'unreadCount' => $unreadCount,
            'filters' => $request->only(['status', 'type']),
        ]);
    }

    public function create()
    {
        $activeYear = AcademicYear::where('is_active', true)->first();

        // Get list of classrooms for drop down, filtered by active academic year
        $classroomsQuery = Classroom::orderBy('level')->orderBy('name');
        if ($activeYear) {
            $classroomsQuery->where('academic_year_id', $activeYear->id);
        }
        $classrooms = $classroomsQuery->get();

        return Inertia::render('FacilityReport/Create', [
            'classrooms' => $classrooms,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'type' => 'required|in:sarana,prasarana',
            'item_name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'classroom_id' => 'nullable|exists:classrooms,id',
            'severity' => 'required|in:ringan,sedang,berat',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'files' => 'nullable|array',
            'files.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120', // max 5MB
        ]);

        $photoPaths = [];
        if ($request->hasFile('files')) {
            $disk = config('filesystems.default');
            foreach ($request->file('files') as $file) {
                $path = $file->store('facility-reports', $disk);
                $photoPaths[] = Storage::url($path);
            }
        }

        FacilityReport::create([
            'title' => $request->title,
            'description' => $request->description,
            'type' => $request->type,
            'item_name' => $request->item_name,
            'location' => $request->location,
            'classroom_id' => $request->classroom_id,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'severity' => $request->severity,
            'status' => 'pending',
            'reported_by' => Auth::id(),
            'photos' => $photoPaths,
        ]);

        return redirect()->route('facility.reports.index')->with('success', 'Laporan kerusakan berhasil dikirim.');
    }

    public function show($id)
    {
        $user = Auth::user();
        $report = FacilityReport::with(['classroom', 'updates.updater'])
            ->where('reported_by', $user->id)
            ->findOrFail($id);

        // Mark updates as read
        $report->updates()->where('is_read_by_reporter', false)->update([
            'is_read_by_reporter' => true
        ]);

        return Inertia::render('FacilityReport/Show', [
            'report' => $report
        ]);
    }
}
