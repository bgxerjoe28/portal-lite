<?php

namespace Modules\Akademik\Imports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Modules\Akademik\Models\Subject;

class SubjectImport implements ToCollection, WithHeadingRow
{
    private function normalizeBoolean($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        if (is_string($value)) {
            return in_array(
                strtolower(trim($value)),
                ['1', 'true', 'ya', 'yes', 'y'],
                true
            );
        }

        return false;
    }

    public function collection(Collection $rows)
    {
        // Format Excel:
        // | kode | nama_mapel |is_religion |
        // | MTK  | Matematika |    0      |
        // | PA   | Pend Agama |    1      |

        DB::transaction(function () use ($rows) {
            foreach ($rows as $row) {
                // Validasi sederhana
                if (empty($row['kode']) || empty($row['nama_mapel'])) {
                    continue;
                }

                Subject::updateOrCreate(
                    ['code' => strtoupper(trim($row['kode']))], // Kunci Unik
                    [
                        'name' => Str::title(trim($row['nama_mapel'])),
                        'is_religion' => $this->normalizeBoolean($row['is_religion'] ?? false),
                    ],
                );
            }
        });
    }
}
