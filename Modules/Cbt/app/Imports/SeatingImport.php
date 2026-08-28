<?php

namespace Modules\Cbt\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Modules\Cbt\Models\CbtRoomStudent;
use Modules\Akademik\Models\Student;

class SeatingImport implements ToCollection, WithHeadingRow
{
    protected int $cbtRoomId;
    protected array $errors = [];

    public function __construct(int $cbtRoomId)
    {
        $this->cbtRoomId = $cbtRoomId;
    }

    public function collection(Collection $rows)
    {
        $assignments = [];
        $assignedSeats = [];
        $assignedStudents = [];

        foreach ($rows as $index => $row) {
            $rowNum = $index + 2; // Heading is row 1, data starts at row 2

            $nisn = isset($row['nisn']) ? trim($row['nisn']) : '';
            $kursi = isset($row['kursi']) ? trim($row['kursi']) : '';

            // Skip completely empty rows
            if (empty($nisn) && empty($kursi)) {
                continue;
            }

            if (empty($nisn)) {
                $this->errors[] = "Baris {$rowNum}: NISN tidak boleh kosong.";
                continue;
            }

            if (empty($kursi) || !is_numeric($kursi) || $kursi < 1 || $kursi > 36) {
                $this->errors[] = "Baris {$rowNum} (NISN {$nisn}): Nomor kursi '{$kursi}' tidak valid. Kursi harus berupa angka 1 - 36.";
                continue;
            }

            $student = Student::where('nisn', $nisn)->first();
            if (!$student) {
                $this->errors[] = "Baris {$rowNum}: Siswa dengan NISN {$nisn} tidak ditemukan di sistem.";
                continue;
            }

            if (in_array($kursi, $assignedSeats)) {
                $this->errors[] = "Baris {$rowNum} (NISN {$nisn}): Kursi nomor {$kursi} ganda/double di file import.";
                continue;
            }

            if (in_array($student->id, $assignedStudents)) {
                $this->errors[] = "Baris {$rowNum} (NISN {$nisn}): Siswa {$student->full_name} ditugaskan lebih dari satu kali di file import.";
                continue;
            }

            $assignedSeats[] = $kursi;
            $assignedStudents[] = $student->id;

            $assignments[] = [
                'cbt_room_id' => $this->cbtRoomId,
                'student_id' => $student->id,
                'seat_number' => (int)$kursi,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (empty($this->errors) && !empty($assignments)) {
            // Delete existing seat assignments in this room
            CbtRoomStudent::where('cbt_room_id', $this->cbtRoomId)
                ->delete();
            
            // Batch insert new assignments
            CbtRoomStudent::insert($assignments);
        }
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
