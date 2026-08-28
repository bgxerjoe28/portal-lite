<?php

namespace Modules\Akademik\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Modules\Akademik\Models\Student;
use Modules\Akademik\Models\Religion;

class StudentProfileController extends Controller
{
    /**
     * Tampilkan formulir biodata mandiri siswa aktif.
     */
    public function showForm(Request $request)
    {
        $user = $request->user();
        
        // Ambil data siswa yang berelasi dengan user
        $student = Student::with('religion')->where('user_id', $user->id)->first();
        
        if (!$student) {
            abort(404, 'Data siswa tidak ditemukan.');
        }

        return Inertia::render('Student/Profile', [
            'student' => $student,
            'religions' => Religion::all()
        ]);
    }

    /**
     * Simpan data formulir biodata lengkap dan kelola unggahan berkas.
     */
    public function submitForm(Request $request)
    {
        $user = $request->user();
        $student = Student::where('user_id', $user->id)->firstOrFail();

        // Validasi Seluruh Isian Form
        $request->validate([
            // Data Pribadi
            'full_name' => 'required|string|max:255',
            'gender' => 'required|boolean',
            'nisn' => 'required|string|regex:/^[0-9]{1,10}$/|unique:students,nisn,' . $student->id,
            'prev_school_type' => 'required|string|in:SMP,MTS',
            'prev_school_status' => 'required|string|in:Negeri,Swasta',
            'prev_school_name' => 'required|string|max:255',
            'nik' => 'required|regex:/^[0-9]{1,16}$/',
            'no_kk' => 'required|regex:/^[0-9]{1,16}$/',
            'birth_place' => 'required|regex:/^[a-zA-Z\s]+$/|max:255',
            'birth_date' => 'required|date',
            'akta_no' => 'nullable|string|max:255',
            'religion_id' => 'required|integer|exists:religions,id',
            'citizenship' => 'required|string|in:WNI,WNA',
            'special_needs' => 'nullable|string|max:255',
            'address' => 'required|regex:/^[a-zA-Z0-9\s\.\-]+$/|max:1000',
            'rt' => 'required|regex:/^[0-9]+$/|max:10',
            'rw' => 'required|regex:/^[0-9]+$/|max:10',
            'kelurahan' => 'required|regex:/^[a-zA-Z\s]+$/|max:255',
            'kecamatan' => 'required|regex:/^[a-zA-Z\s]+$/|max:255',
            'postal_code' => 'required|regex:/^[0-9]+$/|max:10',
            'latitude' => 'nullable|string',
            'longitude' => 'nullable|string',
            'residence_type' => 'required|string|max:100',
            'transportation' => 'required|string|max:100',
            'child_order' => 'required|integer|min:1',
            'phone' => 'required|regex:/^[0-9]+$/|max:25',

            // Data Ayah
            'father_name' => 'required|string|max:255',
            'father_deceased' => 'required|boolean',
            'father_nik' => 'required_if:father_deceased,false|nullable|string|max:16',
            'father_birth_year' => 'required_if:father_deceased,false|nullable|integer|min:1900|max:' . date('Y'),
            'father_education' => 'required_if:father_deceased,false|nullable|string|max:255',
            'father_job' => 'required_if:father_deceased,false|nullable|string|max:255',
            'father_income' => 'required_if:father_deceased,false|nullable|string|max:255',
            'father_special_needs' => 'nullable|string|max:255',
            'father_phone' => 'required_if:father_deceased,false|nullable|regex:/^[0-9]+$/|max:25',

            // Data Ibu
            'mother_name' => 'required|string|max:255',
            'mother_deceased' => 'required|boolean',
            'mother_nik' => 'required_if:mother_deceased,false|nullable|string|max:16',
            'mother_birth_year' => 'required_if:mother_deceased,false|nullable|integer|min:1900|max:' . date('Y'),
            'mother_education' => 'required_if:mother_deceased,false|nullable|string|max:255',
            'mother_job' => 'required_if:mother_deceased,false|nullable|string|max:255',
            'mother_income' => 'required_if:mother_deceased,false|nullable|string|max:255',
            'mother_special_needs' => 'nullable|string|max:255',
            'mother_phone' => 'required_if:mother_deceased,false|nullable|regex:/^[0-9]+$/|max:25',

            // Data Wali (Opsional)
            'guardian_name' => 'nullable|required_if:has_guardian,true|string|max:255',
            'guardian_nik' => 'nullable|required_if:has_guardian,true|string|max:16',
            'guardian_birth_year' => 'nullable|required_if:has_guardian,true|integer|min:1900|max:' . date('Y'),
            'guardian_education' => 'nullable|required_if:has_guardian,true|string|max:255',
            'guardian_job' => 'nullable|required_if:has_guardian,true|string|max:255',
            'guardian_income' => 'nullable|required_if:has_guardian,true|string|max:255',
            'guardian_phone' => 'nullable|required_if:has_guardian,true|regex:/^[0-9]+$/|max:25',

            // Data Periodik
            'height' => 'required|integer|min:50|max:250',
            'weight' => 'required|integer|min:10|max:200',
            'head_circumference' => 'required|integer|min:20|max:100',
            'distance_to_school_km' => 'required|numeric|min:0',
            'travel_time_minutes' => 'required|integer|min:0',
            'sibling_count' => 'required|integer|min:0',

            // Berkas Upload (Max 2MB per berkas, gambar / PDF)
            'file_kk' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'file_akta' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'file_ijazah' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'file_foto' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'file_other' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        // Menyimpan isian data text (hanya kolom database yang valid)
        $columns = \Illuminate\Support\Facades\Schema::getColumnListing('students');
        $data = $request->only($columns);
        $data = array_diff_key($data, array_flip(['id', 'file_kk', 'file_akta', 'file_ijazah', 'file_foto', 'file_other', 'created_at', 'updated_at', 'deleted_at']));
        
        // Transform data text ke format yang benar
        if (isset($data['full_name'])) {
            $data['full_name'] = strtoupper(trim($data['full_name']));
        }
        if (isset($data['birth_place'])) {
            $data['birth_place'] = strtoupper(trim($data['birth_place']));
        }
        if (isset($data['prev_school_name'])) {
            $data['prev_school_name'] = strtoupper(trim($data['prev_school_name']));
        }
        if (isset($data['kelurahan'])) {
            $data['kelurahan'] = ucwords(strtolower(trim($data['kelurahan'])));
        }
        if (isset($data['kecamatan'])) {
            $data['kecamatan'] = ucwords(strtolower(trim($data['kecamatan'])));
        }

        // Simpan Berkas jika diunggah
        $disk = config('filesystems.default');
        $fileFields = ['file_kk', 'file_akta', 'file_ijazah', 'file_foto', 'file_other'];
        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                // Hapus file lama jika ada
                if ($student->$field) {
                    Storage::disk($disk)->delete($student->$field);
                }
                
                $path = $request->file($field)->store('biodata_siswa', $disk);
                $data[$field] = $path;
            }
        }

        $student->update($data);

        return redirect()->back()->with('success', 'Biodata Anda berhasil disimpan.');
    }

    /**
     * Simpan draf formulir biodata mandiri siswa.
     */
    public function saveDraft(Request $request)
    {
        $user = $request->user();
        $student = Student::where('user_id', $user->id)->firstOrFail();

        // Simpan data text (hanya kolom database yang valid, kecuali berkas & status)
        $columns = \Illuminate\Support\Facades\Schema::getColumnListing('students');
        $data = $request->only($columns);
        $data = array_diff_key($data, array_flip(['id', 'file_kk', 'file_akta', 'file_ijazah', 'file_foto', 'file_other', 'created_at', 'updated_at', 'deleted_at']));
        
        // Transform data text ke format yang benar
        if (isset($data['full_name'])) {
            $data['full_name'] = strtoupper(trim($data['full_name']));
        }
        if (isset($data['birth_place'])) {
            $data['birth_place'] = strtoupper(trim($data['birth_place']));
        }
        if (isset($data['prev_school_name'])) {
            $data['prev_school_name'] = strtoupper(trim($data['prev_school_name']));
        }
        if (isset($data['kelurahan'])) {
            $data['kelurahan'] = ucwords(strtolower(trim($data['kelurahan'])));
        }
        if (isset($data['kecamatan'])) {
            $data['kecamatan'] = ucwords(strtolower(trim($data['kecamatan'])));
        }

        // Simpan Berkas jika diunggah
        $fileFields = ['file_kk', 'file_akta', 'file_ijazah', 'file_foto', 'file_other'];
        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                // Hapus file lama jika ada
                if ($student->$field) {
                    Storage::disk($disk)->delete($student->$field);
                }
                
                $path = $request->file($field)->store('biodata_siswa', $disk);
                $data[$field] = $path;
            }
        }

        $student->update($data);

        return redirect()->back()->with('success', 'Draf biodata berhasil disimpan.');
    }
}
