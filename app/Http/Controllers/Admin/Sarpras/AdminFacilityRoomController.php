<?php

namespace App\Http\Controllers\Admin\Sarpras;

use App\Http\Controllers\Controller;
use App\Models\FacilityRoom;
use App\Exports\FacilityRoomTemplateExport;
use App\Imports\FacilityRoomImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AdminFacilityRoomController extends Controller
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

        $query = FacilityRoom::query();

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('code', 'like', "%{$request->search}%")
                  ->orWhere('location', 'like', "%{$request->search}%");
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $rooms = $query->orderBy('name', 'asc')->paginate(10)->withQueryString();

        return Inertia::render('Admin/Sarpras/Rooms/Index', [
            'rooms' => $rooms,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function store(Request $request)
    {
        $this->checkAuthorization();

        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:facility_rooms,code',
            'name' => 'required|string|max:255',
            'capacity' => 'required|integer|min:0',
            'location' => 'nullable|string|max:255',
            'facilities' => 'nullable|array',
            'description' => 'nullable|string',
            'status' => 'required|in:available,maintenance,inactive',
            'is_reservable' => 'boolean',
            'image' => 'nullable|image|max:3072',
            'images' => 'nullable|array',
            'images.*' => 'nullable|image|max:3072',
        ]);

        $disk = config('filesystems.default');
        $imagePaths = [];

        // Handle single image fallback
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('facility/rooms', $disk);
            $imagePaths[] = Storage::disk($disk)->url($path);
        }

        // Handle multiple images
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                if ($file) {
                    $path = $file->store('facility/rooms', $disk);
                    $imagePaths[] = Storage::disk($disk)->url($path);
                }
            }
        }

        if (!empty($imagePaths)) {
            $validated['images'] = array_values(array_unique($imagePaths));
            $validated['image'] = $validated['images'][0];
        }

        FacilityRoom::create($validated);

        return redirect()->back()->with('success', 'Ruangan berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $this->checkAuthorization();

        $room = FacilityRoom::findOrFail($id);

        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:facility_rooms,code,' . $room->id,
            'name' => 'required|string|max:255',
            'capacity' => 'required|integer|min:0',
            'location' => 'nullable|string|max:255',
            'facilities' => 'nullable|array',
            'description' => 'nullable|string',
            'status' => 'required|in:available,maintenance,inactive',
            'is_reservable' => 'boolean',
            'image' => 'nullable|image|max:3072',
            'images' => 'nullable|array',
            'images.*' => 'nullable|image|max:3072',
            'existing_images' => 'nullable|array',
        ]);

        $disk = config('filesystems.default');
        $baseUrl = Storage::disk($disk)->url('');

        // Retain requested existing images
        $keptImages = $request->input('existing_images', []);
        if (!is_array($keptImages)) {
            $keptImages = [];
        }

        // Clean up previously stored images that were removed
        $oldImages = $room->images ?? ($room->image ? [$room->image] : []);
        foreach ($oldImages as $oldImg) {
            if (!in_array($oldImg, $keptImages)) {
                $cleanPath = ltrim(str_replace([$baseUrl, '/storage/'], '', $oldImg), '/');
                Storage::disk($disk)->delete($cleanPath);
            }
        }

        $imagePaths = $keptImages;

        // Handle single image fallback
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('facility/rooms', $disk);
            $imagePaths[] = Storage::disk($disk)->url($path);
        }

        // Handle new multiple images upload
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                if ($file) {
                    $path = $file->store('facility/rooms', $disk);
                    $imagePaths[] = Storage::disk($disk)->url($path);
                }
            }
        }

        $validated['images'] = !empty($imagePaths) ? array_values(array_unique($imagePaths)) : null;
        $validated['image'] = !empty($validated['images']) ? $validated['images'][0] : null;

        $room->update($validated);

        return redirect()->back()->with('success', 'Data ruangan berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $this->checkAuthorization();

        $room = FacilityRoom::findOrFail($id);
        $disk = config('filesystems.default');
        $baseUrl = Storage::disk($disk)->url('');

        $allImages = $room->images ?? ($room->image ? [$room->image] : []);
        foreach ($allImages as $img) {
            if ($img) {
                $cleanPath = ltrim(str_replace([$baseUrl, '/storage/'], '', $img), '/');
                Storage::disk($disk)->delete($cleanPath);
            }
        }

        $room->delete();

        return redirect()->back()->with('success', 'Ruangan berhasil dihapus.');
    }

    public function downloadTemplate()
    {
        $this->checkAuthorization();

        return Excel::download(new FacilityRoomTemplateExport, 'template_import_ruangan.xlsx');
    }

    public function import(Request $request)
    {
        $this->checkAuthorization();

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ], [
            'file.required' => 'Silakan pilih file Excel terlebih dahulu.',
            'file.mimes' => 'File harus berformat .xlsx, .xls, atau .csv.',
            'file.max' => 'Ukuran file maksimal 5MB.',
        ]);

        try {
            $import = new FacilityRoomImport();
            Excel::import($import, $request->file('file'));

            $count = $import->getImportedCount();
            $errors = $import->getErrors();

            if (!empty($errors) && $count === 0) {
                return redirect()->back()->with('error', 'Gagal mengimpor data: ' . implode(' ', array_slice($errors, 0, 3)));
            }

            if (!empty($errors)) {
                return redirect()->back()->with('warning', "Berhasil mengimpor {$count} ruangan. Beberapa baris bermasalah: " . implode(' ', array_slice($errors, 0, 3)));
            }

            return redirect()->back()->with('success', "Berhasil mengimpor {$count} data ruangan.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal import file: ' . $e->getMessage());
        }
    }
}

