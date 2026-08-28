<?php

namespace Modules\Akademik\Services;

use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Akademik\Models\AcademicYear;
use Modules\Akademik\Models\Student;
use Modules\Akademik\Models\StudentPermit;

class GoogleSheetPermitSyncService
{
    public const DEFAULT_SHEET_URL = 'https://docs.google.com/spreadsheets/d/1St-UOseLrUUg6MIUUS64b3CMynAtaK0BvxxbftfzXrM/edit?gid=0';

    public function getSavedSheetUrl(): string
    {
        return Setting::get('google_sheet_permit_url', self::DEFAULT_SHEET_URL);
    }

    public function saveSheetUrl(string $url): void
    {
        Setting::set('google_sheet_permit_url', trim($url));
    }

    public function getCsvUrlFromSheetUrl(?string $url): ?string
    {
        if (!$url) {
            $url = $this->getSavedSheetUrl();
        }

        $sheetId = null;
        $gid = 0;

        if (preg_match('/spreadsheets\/d\/([a-zA-Z0-9-_]+)/', $url, $matches)) {
            $sheetId = $matches[1];
        }

        if (preg_match('/[#&?]gid=([0-9]+)/', $url, $matches)) {
            $gid = $matches[1];
        }

        if (!$sheetId) {
            return null;
        }

        return "https://docs.google.com/spreadsheets/d/{$sheetId}/export?format=csv&gid={$gid}";
    }

    public function parseIndonesianDate(?string $dateStr): ?string
    {
        if (!$dateStr) return null;
        $dateStr = trim($dateStr);
        if (empty($dateStr)) return null;

        $indoMonths = [
            'jan' => '01', 'januari' => '01',
            'feb' => '02', 'februari' => '02',
            'mar' => '03', 'maret' => '03',
            'apr' => '04', 'april' => '04',
            'mei' => '05', 'may' => '05',
            'jun' => '06', 'juni' => '06',
            'jul' => '07', 'juli' => '07',
            'agu' => '08', 'agustus' => '08', 'aug' => '08',
            'sep' => '09', 'september' => '09',
            'okt' => '10', 'oktober' => '10', 'oct' => '10',
            'nov' => '11', 'november' => '11',
            'des' => '12', 'desember' => '12', 'dec' => '12',
        ];

        if (preg_match('/^(\d{1,2})[-\/\s]([a-zA-Z]+)[-\/\s](\d{4})$/', $dateStr, $matches)) {
            $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            $monthName = strtolower($matches[2]);
            $year = $matches[3];

            if (isset($indoMonths[$monthName])) {
                $month = $indoMonths[$monthName];
                return "{$year}-{$month}-{$day}";
            }
        }

        try {
            return Carbon::parse($dateStr)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function normalizePermitType(?string $typeStr): string
    {
        if (!$typeStr) return 'I';
        $t = strtolower(trim($typeStr));

        if (str_starts_with($t, 'sak') || $t === 's') {
            return 'S';
        }
        if (str_starts_with($t, 'disp') || $t === 'd') {
            return 'D';
        }
        if (str_starts_with($t, 'alp') || str_starts_with($t, 'tanpa') || $t === 'a') {
            return 'A';
        }
        if (str_starts_with($t, 'terl') || str_starts_with($t, 'late') || $t === 't') {
            return 'T';
        }

        return 'I';
    }

    /**
     * Cari siswa di database berdasarkan nama & kelas
     */
    public function findStudent(string $name, ?string $className = null, ?int $activeYearId = null): ?Student
    {
        $cleanName = strtolower(trim($name));
        if (empty($cleanName)) return null;

        // 1. Coba cari exact match nama
        $query = Student::whereRaw('LOWER(TRIM(full_name)) = ?', [$cleanName]);
        $students = $query->get();

        if ($students->count() === 1) {
            return $students->first();
        }

        if ($students->count() > 1 && $className) {
            // Jika ada lebih dari 1 siswa dengan nama persis, cocokkan dengan kelasnya
            $normalizedClass = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $className));
            foreach ($students as $st) {
                $stClass = $st->classInYear($activeYearId);
                if ($stClass) {
                    $stNormClass = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $stClass->name));
                    if ($stNormClass === $normalizedClass || str_contains($stNormClass, $normalizedClass)) {
                        return $st;
                    }
                }
            }
            return $students->first();
        }

        // 2. Coba cari LIKE nama
        $likeStudents = Student::whereRaw('LOWER(full_name) LIKE ?', ['%' . $cleanName . '%'])->get();
        if ($likeStudents->count() === 1) {
            return $likeStudents->first();
        }

        return null;
    }

    /**
     * Sinkronkan data dari Google Sheet CSV URL
     */
    public function syncFromSheetUrl(?string $sheetUrl = null, bool $onlyUnchecked = true, ?int $recorderId = null): array
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        if (!$activeYear) {
            throw new \Exception('Tidak ada tahun ajaran aktif di sistem.');
        }

        $csvUrl = $this->getCsvUrlFromSheetUrl($sheetUrl);
        if (!$csvUrl) {
            throw new \Exception('URL Google Sheet tidak valid.');
        }

        // Simpan URL jika valid
        if ($sheetUrl) {
            $this->saveSheetUrl($sheetUrl);
        }

        try {
            $response = Http::timeout(25)->get($csvUrl);
            if (!$response->successful()) {
                throw new \Exception("Gagal mengambil data dari Google Sheets (HTTP Status: {$response->status()}). Pastikan Google Sheet memiliki izin 'Anyone with the link can view'.");
            }
            $csvContent = $response->body();
        } catch (\Throwable $e) {
            throw new \Exception('Koneksi ke Google Sheets gagal: ' . $e->getMessage());
        }

        $lines = explode("\n", $csvContent);
        if (count($lines) < 2) {
            return [
                'success' => true,
                'total_rows_processed' => 0,
                'imported_count' => 0,
                'skipped_count' => 0,
                'unmatched_rows' => [],
                'synced_row_numbers' => [],
                'message' => 'Spreadsheet kosong atau hanya berisi header.',
            ];
        }

        // Parse CSV rows
        $rows = array_map('str_getcsv', $lines);
        $header = array_map(fn($h) => strtolower(trim((string)$h)), $rows[0]);

        // Cari index kolom
        $colNama = array_search('nama', $header);
        $colKelas = array_search('kelas', $header);
        $colJenis = array_search('jenis', $header);
        $colTglMulai = array_search('tgl mulai', $header);
        $colTglSelesai = array_search('tgl selesai', $header);
        $colKeterangan = array_search('keterangan', $header);
        $colUpload = array_search('upload ijin', $header);
        $colCekPortal = array_search('cek input portal', $header);

        if ($colNama === false || $colTglMulai === false) {
            // Fallback default kolom index jika header agak berbeda
            $colNama = 1;
            $colKelas = 2;
            $colJenis = 3;
            $colTglMulai = 4;
            $colTglSelesai = 5;
            $colKeterangan = 6;
            $colUpload = 7;
            $colCekPortal = 8;
        }

        $totalProcessed = 0;
        $importedCount = 0;
        $skippedCount = 0;
        $unmatchedRows = [];
        $syncedRowNumbers = [];
        $effectiveDays = $activeYear->effective_school_days ?? ['mon', 'tue', 'wed', 'thu', 'fri'];
        $userId = $recorderId ?: (Auth::id() ?? 1);

        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            if (empty($row) || count($row) < 2) continue;

            $nama = trim($row[$colNama] ?? '');
            if (empty($nama)) continue;

            $sheetRowNum = $i + 1; // 1-based index di Google Sheets
            $totalProcessed++;

            $cekPortal = strtoupper(trim($row[$colCekPortal] ?? ''));
            if ($onlyUnchecked && in_array($cekPortal, ['TRUE', '1', 'YA', 'SUDAH'])) {
                $skippedCount++;
                continue;
            }

            $kelas = trim($row[$colKelas] ?? '');
            $jenisRaw = trim($row[$colJenis] ?? '');
            $tglMulaiRaw = trim($row[$colTglMulai] ?? '');
            $tglSelesaiRaw = trim($row[$colTglSelesai] ?? '');
            $keterangan = trim($row[$colKeterangan] ?? '');
            $uploadLink = trim($row[$colUpload] ?? '');

            // 1. Match Student
            $student = $this->findStudent($nama, $kelas, $activeYear->id);
            if (!$student) {
                $unmatchedRows[] = [
                    'row' => $sheetRowNum,
                    'name' => $nama,
                    'class' => $kelas,
                    'reason' => 'Nama siswa tidak ditemukan di database sekolah.',
                ];
                continue;
            }

            // 2. Parse Dates
            $startDate = $this->parseIndonesianDate($tglMulaiRaw);
            if (!$startDate) {
                $unmatchedRows[] = [
                    'row' => $sheetRowNum,
                    'name' => $nama,
                    'class' => $kelas,
                    'reason' => "Format tanggal mulai '{$tglMulaiRaw}' tidak valid.",
                ];
                continue;
            }

            $endDate = $tglSelesaiRaw ? $this->parseIndonesianDate($tglSelesaiRaw) : $startDate;
            if (!$endDate) {
                $endDate = $startDate;
            }

            $permitType = $this->normalizePermitType($jenisRaw);
            $fullReason = $keterangan;
            if ($uploadLink && filter_var($uploadLink, FILTER_VALIDATE_URL)) {
                $fullReason = ($fullReason ? $fullReason . ' ' : '') . "[Bukti: {$uploadLink}]";
            }

            // 3. Simpan per hari aktif
            $curr = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);

            while ($curr->lte($end)) {
                $dayName = strtolower($curr->format('D'));
                if (in_array($dayName, $effectiveDays)) {
                    StudentPermit::updateOrCreate(
                        [
                            'date' => $curr->format('Y-m-d'),
                            'student_id' => $student->id,
                            'academic_year_id' => $activeYear->id,
                        ],
                        [
                            'permit_type' => $permitType,
                            'reason' => $fullReason ?: ($jenisRaw ?: 'Izin'),
                            'teacher_id' => $userId,
                        ]
                    );
                }
                $curr->addDay();
            }

            $importedCount++;
            $syncedRowNumbers[] = $sheetRowNum;
        }

        return [
            'success' => true,
            'total_rows_processed' => $totalProcessed,
            'imported_count' => $importedCount,
            'skipped_count' => $skippedCount,
            'unmatched_rows' => $unmatchedRows,
            'synced_row_numbers' => $syncedRowNumbers,
            'message' => "Sinkronisasi selesai. {$importedCount} data izin berhasil disimpan ke portal.",
        ];
    }
}
