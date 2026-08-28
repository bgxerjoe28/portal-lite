<?php

namespace Modules\Akademik\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class GuruAttendanceRecapExport implements FromView, ShouldAutoSize, WithTitle
{
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function view(): View
    {
        return view('akademik::exports.attendance_recap_excel', $this->data);
    }

    public function title(): string
    {
        $subject = $this->data['subject']->name ?? 'Mapel';
        $classroom = $this->data['classroom']->name ?? 'Kelas';
        return substr("Rekap {$subject} {$classroom}", 0, 31);
    }
}
