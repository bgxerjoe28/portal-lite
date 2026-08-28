<?php

namespace App\Imports;

use App\Models\FacilityAsset;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class FacilityAssetImport implements ToCollection, WithHeadingRow
{
    protected int $importedCount = 0;
    protected array $errors = [];

    public function getImportedCount(): int
    {
        return $this->importedCount;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $rowNum = $index + 2;

            $assetCode = trim((string)($row['kode_aset'] ?? $row['asset_code'] ?? $row['kode'] ?? ''));
            $name = trim((string)($row['nama_aset'] ?? $row['nama'] ?? $row['name'] ?? ''));

            // Abaikan baris kosong
            if ($assetCode === '' && $name === '') {
                continue;
            }

            if ($assetCode === '') {
                $this->errors[] = "Baris {$rowNum}: Kode aset wajib diisi.";
                continue;
            }

            if ($name === '') {
                $this->errors[] = "Baris {$rowNum}: Nama aset wajib diisi.";
                continue;
            }

            $category = trim((string)($row['kategori'] ?? $row['category'] ?? 'Umum'));
            if ($category === '') {
                $category = 'Umum';
            }

            $brandModel = trim((string)($row['merk_tipe'] ?? $row['brand_model'] ?? $row['merk'] ?? $row['tipe'] ?? ''));
            $serialNumber = trim((string)($row['nomor_seri'] ?? $row['serial_number'] ?? $row['no_seri'] ?? ''));
            $location = trim((string)($row['lokasi_simpan'] ?? $row['location'] ?? $row['lokasi'] ?? ''));
            $notes = trim((string)($row['catatan'] ?? $row['notes'] ?? $row['keterangan'] ?? ''));

            // Parse Condition
            $condRaw = strtolower(trim((string)($row['kondisi'] ?? $row['condition'] ?? 'good')));
            if (in_array($condRaw, ['good', 'baik', 'bagus', 'normal'])) {
                $condition = 'good';
            } elseif (in_array($condRaw, ['minor_damage', 'rusak ringan', 'rusak_ringan', 'ringan'])) {
                $condition = 'minor_damage';
            } elseif (in_array($condRaw, ['heavy_damage', 'rusak berat', 'rusak_berat', 'berat', 'rusak'])) {
                $condition = 'heavy_damage';
            } else {
                $condition = 'good';
            }

            // Parse Status
            $statRaw = strtolower(trim((string)($row['status'] ?? 'available')));
            if (in_array($statRaw, ['available', 'tersedia', 'ready', 'aktif'])) {
                $status = 'available';
            } elseif (in_array($statRaw, ['borrowed', 'dipinjam', 'pinjam'])) {
                $status = 'borrowed';
            } elseif (in_array($statRaw, ['maintenance', 'perbaikan', 'servis', 'pemeliharaan'])) {
                $status = 'maintenance';
            } elseif (in_array($statRaw, ['lost', 'hilang'])) {
                $status = 'lost';
            } elseif (in_array($statRaw, ['disposed', 'afkir', 'dihapus', 'musnah'])) {
                $status = 'disposed';
            } else {
                $status = 'available';
            }

            DB::transaction(function () use ($assetCode, $name, $category, $brandModel, $serialNumber, $condition, $status, $location, $notes) {
                $existing = FacilityAsset::where('asset_code', $assetCode)->first();
                $qrToken = $existing ? $existing->qr_code_token : ('AST-' . strtoupper(Str::random(10)));

                FacilityAsset::updateOrCreate(
                    ['asset_code' => $assetCode],
                    [
                        'name' => $name,
                        'category' => $category,
                        'brand_model' => $brandModel !== '' ? $brandModel : null,
                        'serial_number' => $serialNumber !== '' ? $serialNumber : null,
                        'condition' => $condition,
                        'status' => $status,
                        'location' => $location !== '' ? $location : null,
                        'notes' => $notes !== '' ? $notes : null,
                        'qr_code_token' => $qrToken,
                    ]
                );
            });

            $this->importedCount++;
        }
    }
}
