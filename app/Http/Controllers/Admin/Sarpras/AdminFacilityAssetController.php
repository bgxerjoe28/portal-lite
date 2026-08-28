<?php

namespace App\Http\Controllers\Admin\Sarpras;

use App\Http\Controllers\Controller;
use App\Models\FacilityAsset;
use App\Exports\FacilityAssetTemplateExport;
use App\Imports\FacilityAssetImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminFacilityAssetController extends Controller
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

        $query = FacilityAsset::query();

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('asset_code', 'like', "%{$request->search}%")
                  ->orWhere('brand_model', 'like', "%{$request->search}%")
                  ->orWhere('serial_number', 'like', "%{$request->search}%")
                  ->orWhere('qr_code_token', 'like', "%{$request->search}%");
            });
        }

        if ($request->category) {
            $query->where('category', $request->category);
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->condition) {
            $query->where('condition', $request->condition);
        }

        $assets = $query->orderBy('name', 'asc')->paginate(12)->withQueryString();
        $dbCategories = FacilityAsset::whereNotNull('category')->where('category', '!=', '')->distinct()->pluck('category')->toArray();
        $defaultCategories = ['Elektronik', 'Multimedia', 'Olahraga', 'Laboratorium', 'Musik', 'Umum'];
        $categories = array_values(array_unique(array_merge($defaultCategories, $dbCategories)));

        return Inertia::render('Admin/Sarpras/Assets/Index', [
            'assets' => $assets,
            'categories' => $categories,
            'filters' => $request->only(['search', 'category', 'status', 'condition']),
        ]);
    }

    public function store(Request $request)
    {
        $this->checkAuthorization();

        $validated = $request->validate([
            'asset_code' => 'required|string|max:50|unique:facility_assets,asset_code',
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'brand_model' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:100',
            'condition' => 'required|in:good,minor_damage,heavy_damage',
            'status' => 'required|in:available,borrowed,maintenance,lost,disposed',
            'location' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'image' => 'nullable|image|max:3072',
            'images' => 'nullable|array',
            'images.*' => 'nullable|image|max:3072',
        ]);

        $disk = config('filesystems.default');
        $imagePaths = [];

        // Handle single image fallback
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('facility/assets', $disk);
            $imagePaths[] = Storage::disk($disk)->url($path);
        }

        // Handle multiple images
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                if ($file) {
                    $path = $file->store('facility/assets', $disk);
                    $imagePaths[] = Storage::disk($disk)->url($path);
                }
            }
        }

        if (!empty($imagePaths)) {
            $validated['images'] = array_values(array_unique($imagePaths));
            $validated['image'] = $validated['images'][0];
        }

        $validated['qr_code_token'] = 'AST-' . strtoupper(Str::random(10));

        FacilityAsset::create($validated);

        return redirect()->back()->with('success', 'Aset baru berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $this->checkAuthorization();

        $asset = FacilityAsset::findOrFail($id);

        $validated = $request->validate([
            'asset_code' => 'required|string|max:50|unique:facility_assets,asset_code,' . $asset->id,
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'brand_model' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:100',
            'condition' => 'required|in:good,minor_damage,heavy_damage',
            'status' => 'required|in:available,borrowed,maintenance,lost,disposed',
            'location' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
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
        $oldImages = $asset->images ?? ($asset->image ? [$asset->image] : []);
        foreach ($oldImages as $oldImg) {
            if (!in_array($oldImg, $keptImages)) {
                $cleanPath = ltrim(str_replace([$baseUrl, '/storage/'], '', $oldImg), '/');
                Storage::disk($disk)->delete($cleanPath);
            }
        }

        $imagePaths = $keptImages;

        // Handle single image fallback
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('facility/assets', $disk);
            $imagePaths[] = Storage::disk($disk)->url($path);
        }

        // Handle new multiple images upload
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                if ($file) {
                    $path = $file->store('facility/assets', $disk);
                    $imagePaths[] = Storage::disk($disk)->url($path);
                }
            }
        }

        $validated['images'] = !empty($imagePaths) ? array_values(array_unique($imagePaths)) : null;
        $validated['image'] = !empty($validated['images']) ? $validated['images'][0] : null;

        $asset->update($validated);

        return redirect()->back()->with('success', 'Data aset berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $this->checkAuthorization();

        $asset = FacilityAsset::findOrFail($id);
        $disk = config('filesystems.default');
        $baseUrl = Storage::disk($disk)->url('');

        $allImages = $asset->images ?? ($asset->image ? [$asset->image] : []);
        foreach ($allImages as $img) {
            if ($img) {
                $cleanPath = ltrim(str_replace([$baseUrl, '/storage/'], '', $img), '/');
                Storage::disk($disk)->delete($cleanPath);
            }
        }

        $asset->delete();

        return redirect()->back()->with('success', 'Aset berhasil dihapus.');
    }

    public function printQrCodes(Request $request)
    {
        $this->checkAuthorization();

        $ids = $request->input('ids');
        if (empty($ids) || !is_array($ids)) {
            $assets = FacilityAsset::where('status', '!=', 'disposed')->orderBy('name', 'asc')->get();
        } else {
            $assets = FacilityAsset::whereIn('id', $ids)->get();
        }

        return Inertia::render('Admin/Sarpras/Assets/PrintQr', [
            'assets' => $assets,
        ]);
    }

    public function downloadTemplate()
    {
        $this->checkAuthorization();

        return Excel::download(new FacilityAssetTemplateExport, 'template_import_aset.xlsx');
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
            $import = new FacilityAssetImport();
            Excel::import($import, $request->file('file'));

            $count = $import->getImportedCount();
            $errors = $import->getErrors();

            if (!empty($errors) && $count === 0) {
                return redirect()->back()->with('error', 'Gagal mengimpor data: ' . implode(' ', array_slice($errors, 0, 3)));
            }

            if (!empty($errors)) {
                return redirect()->back()->with('warning', "Berhasil mengimpor {$count} aset. Beberapa baris bermasalah: " . implode(' ', array_slice($errors, 0, 3)));
            }

            return redirect()->back()->with('success', "Berhasil mengimpor {$count} data aset & peralatan.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal import file: ' . $e->getMessage());
        }
    }
}

