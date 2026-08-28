<?php

namespace Modules\Akademik\Imports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Modules\Akademik\Models\Student;

class StudentBulkNisImport implements ToCollection, WithHeadingRow
{
    public $errors = [];
    public $updatedCount = 0;

    public function collection(Collection $rows)
    {
        $updates = [];
        $nisList = [];
        $nisnList = [];
        $excelIds = [];

        // Pass 1: Parse and validate internal duplicates
        foreach ($rows as $index => $row) {
            if (!isset($row['id_siswa_jangan_diubah'])) {
                continue;
            }

            $id = $row['id_siswa_jangan_diubah'];
            $nis = $row['nis'] ?? null;
            $nisn = $row['nisn'] ?? null;

            if (!$id || !$nis) {
                continue;
            }

            // Check internal duplicates in Excel
            if (in_array($nis, $nisList)) {
                $this->errors[] = "NIS {$nis} ditulis ganda di dalam file Excel.";
            }
            if ($nisn && in_array($nisn, $nisnList)) {
                $this->errors[] = "NISN {$nisn} ditulis ganda di dalam file Excel.";
            }

            $nisList[] = $nis;
            if ($nisn) $nisnList[] = $nisn;
            $excelIds[] = $id;

            $updates[$id] = [
                'nis' => $nis,
                'nisn' => $nisn,
            ];
        }

        if (count($this->errors) > 0) {
            return; // Stop if there are internal duplicates
        }

        // Pass 2: Validate against database (excluding those being updated)
        foreach ($updates as $id => $data) {
            $nisExists = Student::where('nis', $data['nis'])
                ->whereNotIn('id', $excelIds)
                ->exists();
                
            if ($nisExists) {
                $this->errors[] = "NIS {$data['nis']} sudah dipakai siswa lain di database.";
            }

            if ($data['nisn']) {
                $nisnExists = Student::where('nisn', $data['nisn'])
                    ->whereNotIn('id', $excelIds)
                    ->exists();
                    
                if ($nisnExists) {
                    $this->errors[] = "NISN {$data['nisn']} sudah dipakai siswa lain di database.";
                }
            }
        }

        if (count($this->errors) > 0) {
            return; // Stop if clashes with DB
        }

        // Pass 3: Safe to update
        DB::transaction(function () use ($updates) {
            foreach ($updates as $id => $data) {
                $student = Student::find($id);
                if ($student) {
                    $student->nis = $data['nis'];
                    $student->nisn = $data['nisn'];
                    $student->save();
                    $this->updatedCount++;
                }
            }
        });
    }
}
