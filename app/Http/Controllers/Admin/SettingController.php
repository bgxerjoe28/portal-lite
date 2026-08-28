<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use App\Models\Setting;

class SettingController extends Controller
{
    /**
     * Tampilkan halaman formulir pengaturan situs.
     */
    public function index()
    {
        $settings = [
            'school_name'      => Setting::get('school_name', 'SMAN 16 SEMARANG'),
            'school_address'   => Setting::get('school_address', ''),
            'school_phone'     => Setting::get('school_phone', ''),
            'school_email'     => Setting::get('school_email', ''),
            'principal_name'   => Setting::get('principal_name', ''),
            'principal_nip'    => Setting::get('principal_nip', ''),
            'kop_pemprov'      => Setting::get('kop_pemprov', 'PEMERINTAH PROVINSI JAWA TENGAH'),
            'kop_dinas'        => Setting::get('kop_dinas', 'DINAS PENDIDIKAN'),
            'school_postal_code' => Setting::get('school_postal_code', ''),
            'school_website'   => Setting::get('school_website', ''),
            'school_city'      => Setting::get('school_city', 'Kota Semarang'),
            'school_province'  => Setting::get('school_province', 'Jawa Tengah'),
            'school_latitude'  => Setting::get('school_latitude', env('SCHOOL_LATITUDE', '-7.024644700237852')),
            'school_longitude' => Setting::get('school_longitude', env('SCHOOL_LONGITUDE', '110.30859975367461')),
            'school_radius'    => Setting::get('school_radius', env('SCHOOL_RADIUS', '100')),
            'site_logo'        => Setting::get('site_logo', null),
            'site_logo_pemda'  => Setting::get('site_logo_pemda', null),
            'site_favicon'     => Setting::get('site_favicon', null),
            // Pengaturan Presensi
            'attendance_seed_time'     => Setting::get('attendance_seed_time', '03:00'),
            'checkin_window_before'    => Setting::get('checkin_window_before', '60'),
            'checkin_window_after'     => Setting::get('checkin_window_after', '120'),
            'checkout_window_before'   => Setting::get('checkout_window_before', '60'),
            'checkout_window_after'    => Setting::get('checkout_window_after', '240'),
            // Status situs
            'site_active'              => Setting::get('site_active', '1') === '1',
        ];

        // Deteksi apakah ini server staging (bisa dicek dari nama host, APP_ENV, atau URL)
        $host = request()->getHost();
        $isStaging = str_contains($host, 'uji-portal') || 
                     str_contains($host, 'staging') || 
                     str_contains($host, 'test') || 
                     str_contains($host, 'localhost') || 
                     str_contains($host, 'laragon') || 
                     str_contains($host, 'portal-sma') ||
                     config('app.env') === 'staging';

        return Inertia::render('Admin/Setting/Index', [
            'settings' => $settings,
            'isStaging' => $isStaging,
            'productionUrl' => 'https://portal.sman16smg.sch.id',
        ]);
    }

    /**
     * Simpan pembaruan pengaturan situs.
     */
    public function update(Request $request)
    {
        $request->validate([
            'school_name'    => 'required|string|max:255',
            'school_address' => 'nullable|string|max:1000',
            'school_phone'   => 'nullable|string|max:50',
            'school_email'   => 'nullable|email|max:255',
            'principal_name' => 'nullable|string|max:255',
            'principal_nip'  => 'nullable|string|max:50',
            'kop_pemprov'    => 'nullable|string|max:255',
            'kop_dinas'      => 'nullable|string|max:255',
            'school_city'    => 'nullable|string|max:100',
            'school_province'=> 'nullable|string|max:100',
            'school_postal_code' => 'nullable|string|max:20',
            'school_website' => 'nullable|string|max:255',
            'school_latitude'  => 'nullable|numeric',
            'school_longitude' => 'nullable|numeric',
            'school_radius'    => 'nullable|numeric',
            'site_logo'        => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048',
            'site_logo_pemda'  => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048',
            'site_favicon'     => 'nullable|file|mimes:ico,png,jpg,jpeg|max:512',
            // Validasi Presensi
            'attendance_seed_time'   => 'nullable|date_format:H:i',
            'checkin_window_before'  => 'nullable|integer|min:0|max:480',
            'checkin_window_after'   => 'nullable|integer|min:0|max:480',
            'checkout_window_before' => 'nullable|integer|min:0|max:480',
            'checkout_window_after'  => 'nullable|integer|min:0|max:480',
            'site_active'            => 'nullable|boolean',
        ]);

        // Simpan data teks
        Setting::set('school_name', $request->school_name);
        Setting::set('school_address', $request->school_address);
        Setting::set('school_phone', $request->school_phone);
        Setting::set('school_email', $request->school_email);
        Setting::set('principal_name', $request->principal_name);
        Setting::set('principal_nip', $request->principal_nip);
        Setting::set('kop_pemprov', $request->kop_pemprov);
        Setting::set('kop_dinas', $request->kop_dinas);
        Setting::set('school_city', $request->school_city);
        Setting::set('school_province', $request->school_province);
        Setting::set('school_postal_code', $request->school_postal_code);
        Setting::set('school_website', $request->school_website);
        Setting::set('school_latitude', $request->school_latitude);
        Setting::set('school_longitude', $request->school_longitude);
        Setting::set('school_radius', $request->school_radius);
        // Simpan pengaturan presensi
        Setting::set('attendance_seed_time',   $request->attendance_seed_time   ?? '03:00');
        Setting::set('checkin_window_before',  $request->checkin_window_before  ?? 60);
        Setting::set('checkin_window_after',   $request->checkin_window_after   ?? 120);
        Setting::set('checkout_window_before', $request->checkout_window_before ?? 60);
        Setting::set('checkout_window_after',  $request->checkout_window_after  ?? 240);
        Setting::set('site_active', $request->has('site_active') ? ($request->site_active ? '1' : '0') : '1');

        $disk = config('filesystems.default');

        // Upload Logo Sekolah
        if ($request->hasFile('site_logo')) {
            $oldLogo = Setting::get('site_logo');
            if ($oldLogo) {
                Storage::disk($disk)->delete($oldLogo);
            }
            $logoPath = $request->file('site_logo')->store('settings', $disk);
            Setting::set('site_logo', $logoPath);
        }

        // Upload Logo Pemda
        if ($request->hasFile('site_logo_pemda')) {
            $oldLogoPemda = Setting::get('site_logo_pemda');
            if ($oldLogoPemda) {
                Storage::disk($disk)->delete($oldLogoPemda);
            }
            $logoPemdaPath = $request->file('site_logo_pemda')->store('settings', $disk);
            Setting::set('site_logo_pemda', $logoPemdaPath);
        }

        // Upload Favicon
        if ($request->hasFile('site_favicon')) {
            $oldFavicon = Setting::get('site_favicon');
            if ($oldFavicon) {
                Storage::disk($disk)->delete($oldFavicon);
            }
            $faviconPath = $request->file('site_favicon')->store('settings', $disk);
            Setting::set('site_favicon', $faviconPath);
        }

        return redirect()->back()->with('success', 'Pengaturan situs berhasil diperbarui.');
    }
}
