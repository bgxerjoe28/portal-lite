<?php

namespace App\Imports;

use App\Models\FacilityRoom;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class FacilityRoomImport implements ToCollection, WithHeadingRow
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
            $rowNum = $index + 2; // +1 0-indexed, +1 header

            $code = trim((string)($row['kode_ruangan'] ?? $row['kode'] ?? $row['code'] ?? ''));
            $name = trim((string)($row['nama_ruangan'] ?? $row['nama'] ?? $row['name'] ?? ''));

            // Abaikan baris kosong
            if ($code === '' && $name === '') {
                continue;
            }

            if ($code === '') {
                $this->errors[] = "Baris {$rowNum}: Kode ruangan wajib diisi.";
                continue;
            }

            if ($name === '') {
                $this->errors[] = "Baris {$rowNum}: Nama ruangan wajib diisi.";
                continue;
            }

            $capacityRaw = $row['kapasitas'] ?? $row['capacity'] ?? 0;
            $capacity = is_numeric($capacityRaw) ? (int)$capacityRaw : 0;
            if ($capacity < 0) {
                $capacity = 0;
            }

            $location = trim((string)($row['lokasi'] ?? $row['location'] ?? ''));

            $facilitiesRaw = trim((string)($row['fasilitas'] ?? $row['facilities'] ?? ''));
            $facilities = null;
            if ($facilitiesRaw !== '') {
                $parsedFacilities = array_values(array_filter(array_map('trim', explode(',', $facilitiesRaw))));
                if (!empty($parsedFacilities)) {
                    $facilities = $parsedFacilities;
                }
            }

            $description = trim((string)($row['deskripsi'] ?? $row['description'] ?? $row['keterangan'] ?? ''));

            $statusRaw = strtolower(trim((string)($row['status'] ?? 'available')));
            if (in_array($statusRaw, ['tersedia', 'aktif', 'ready'])) {
                $status = 'available';
            } elseif (in_array($statusRaw, ['perbaikan', 'pemeliharaan', 'rusak', 'maintenance'])) {
                $status = 'maintenance';
            } elseif (in_array($statusRaw, ['nonaktif', 'non-aktif', 'tidak aktif', 'off', 'inactive'])) {
                $status = 'inactive';
            } elseif (in_array($statusRaw, ['available', 'maintenance', 'inactive'])) {
                $status = $statusRaw;
            } else {
                $status = 'available';
            }

            $reservableRaw = strtolower(trim((string)($row['dapat_dipinjam'] ?? $row['bisa_dipinjam'] ?? $row['is_reservable'] ?? '1')));
            $isReservable = true;
            if ($reservableRaw === '0' || in_array($reservableRaw, ['tidak', 'no', 'false', 'bukan', 't', 'non'])) {
                $isReservable = false;
            }

            DB::transaction(function () use ($code, $name, $capacity, $location, $facilities, $description, $status, $isReservable) {
                FacilityRoom::updateOrCreate(
                    ['code' => $code],
                    [
                        'name' => $name,
                        'capacity' => $capacity,
                        'location' => $location !== '' ? $location : null,
                        'facilities' => $facilities,
                        'description' => $description !== '' ? $description : null,
                        'status' => $status,
                        'is_reservable' => $isReservable,
                    ]
                );
            });

            $this->importedCount++;
        }
    }
}
