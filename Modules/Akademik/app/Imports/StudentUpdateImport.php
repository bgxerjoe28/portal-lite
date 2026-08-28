<?php

namespace Modules\Akademik\Imports; // Pastikan namespace-nya ini

use Illuminate\Support\Collection; // Sesuaikan entity student Bapak
use Illuminate\Support\Facades\Crypt;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Modules\Akademik\Models\Student;

class StudentUpdateImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            // Kita cari siswanya. Pastikan di Excel ada kolom 'nisn'
            $student = Student::where('nisn', $row['nisn'])->first();

            if ($student) {
                // 1. Update data biasa (Hanya jika kolom di DB masih kosong)
                $student->birth_place = $student->birth_place ?? $row['birth_place'];
                $student->birth_date = $student->birth_date ?? $row['birth_date'];
                $student->address = $student->address ?? $row['address'];

                // 2. Update data (Nama Orang Tua) - Tanpa Enkripsi
                if (empty($student->father_name) && ! empty($row['nama_ayah'])) {
                    $student->father_name = $row['nama_ayah'];
                }

                if (empty($student->mother_name) && ! empty($row['nama_ibu'])) {
                    $student->mother_name = $row['nama_ibu'];
                }
                if (empty($student->guardian_name) && ! empty($row['nama_wali'])) {
                    $student->guardian_name = $row['nama_wali'];
                }

                $student->save();
            }
        }
    }
}
