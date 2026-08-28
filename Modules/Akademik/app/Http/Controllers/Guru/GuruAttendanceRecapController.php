<?php

namespace Modules\Akademik\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Akademik\Exports\GuruAttendanceRecapExport;
use Modules\Akademik\Models\AcademicYear;
use Modules\Akademik\Models\Schedule;
use Modules\Akademik\Models\Student;
use Modules\Akademik\Models\StudentPermit;
use Modules\Akademik\Models\Teacher;
use Modules\Akademik\Models\TeachingAgenda;

class GuruAttendanceRecapController extends Controller
{
    private function resolveView(string $viewName)
    {
        return "Guru/AttendanceRecap/{$viewName}";
    }

    private function getTeacherOrAbort()
    {
        $teacher = Teacher::where('user_id', Auth::id())->first();
        if (!$teacher) {
            abort(403, 'Akun Anda tidak terdaftar sebagai Guru Aktif.');
        }
        return $teacher;
    }

    private function prepareRecapData(Request $request)
    {
        $teacher = $this->getTeacherOrAbort();
        $activeYear = AcademicYear::where('is_active', true)->firstOrFail();

        // 1. Ambil seluruh jadwal mengajar guru pada tahun ajaran aktif
        $schedules = Schedule::with(['classroom', 'subject'])
            ->where('academic_year_id', $activeYear->id)
            ->where('teacher_id', $teacher->id)
            ->get()
            ->map(function ($schedule) {
                return [
                    'id' => $schedule->id,
                    'classroom_id' => $schedule->classroom_id,
                    'subject_id' => $schedule->subject_id,
                    'classroom_name' => $schedule->classroom?->name ?? '-',
                    'subject_name' => $schedule->subject?->name ?? '-',
                    'label' => ($schedule->classroom?->name ?? '-') . ' · ' . ($schedule->subject?->name ?? '-'),
                ];
            });

        $selectedScheduleId = $request->query('schedule_id', $schedules->first()['id'] ?? null);
        $selectedScheduleItem = $schedules->firstWhere('id', (int)$selectedScheduleId) ?? $schedules->first();

        $selectedSchedule = null;
        if ($selectedScheduleItem) {
            $selectedSchedule = Schedule::with(['classroom', 'subject'])->find($selectedScheduleItem['id']);
        }

        // 2. Filter mode: 'month' atau 'range'
        $filterType = $request->query('filter_type', 'month');
        $month = (int) $request->query('month', date('n'));
        $year = (int) $request->query('year', date('Y'));

        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        if ($filterType === 'range') {
            $startDate = $request->query('start_date', now()->startOfMonth()->toDateString());
            $endDate = $request->query('end_date', now()->toDateString());
            $periodLabel = Carbon::parse($startDate)->translatedFormat('d F Y') . ' s.d. ' . Carbon::parse($endDate)->translatedFormat('d F Y');
            $periodSubLabel = Carbon::parse($startDate)->format('d/m/Y') . ' - ' . Carbon::parse($endDate)->format('d/m/Y');
        } else {
            $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth()->toDateString();
            $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth()->toDateString();
            $periodLabel = ($months[$month] ?? 'Bulan ' . $month) . ' ' . $year;
            $periodSubLabel = "Bulan {$months[$month]} {$year}";
        }

        if (!$selectedSchedule) {
            return [
                'teacher' => $teacher,
                'activeYear' => $activeYear,
                'schedules' => $schedules,
                'selectedSchedule' => null,
                'classroom' => null,
                'subject' => null,
                'agendas' => collect([]),
                'agendaHeaders' => collect([]),
                'students' => collect([]),
                'agendaSummary' => collect([]),
                'filters' => [
                    'schedule_id' => null,
                    'filter_type' => $filterType,
                    'month' => $month,
                    'year' => $year,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ],
                'periodLabel' => $periodLabel,
                'periodSubLabel' => $periodSubLabel,
                'monthOptions' => collect($months)->map(fn($label, $val) => ['val' => $val, 'label' => $label])->values(),
                'yearOptions' => collect(range(date('Y') - 2, date('Y') + 1))->map(fn($y) => ['val' => $y, 'label' => (string)$y])->values(),
            ];
        }

        // 3. Ambil data agenda dalam rentang waktu & kelas/mapel yang dipilih
        $agendas = TeachingAgenda::with(['tp', 'scheduleDetail', 'attendances'])
            ->where('teacher_id', $teacher->id)
            ->where('classroom_id', $selectedSchedule->classroom_id)
            ->where('subject_id', $selectedSchedule->subject_id)
            ->where('academic_year_id', $activeYear->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'asc')
            ->orderByRaw('COALESCE(start_slot, 1) ASC')
            ->get();

        // 4. Ambil data siswa aktif di kelas
        $studentsQuery = Student::whereHas('classrooms', function ($q) use ($selectedSchedule, $activeYear) {
            $q->where('classroom_students.classroom_id', $selectedSchedule->classroom_id)
              ->where('classroom_students.status', 'aktif')
              ->where('classroom_students.academic_year_id', $activeYear->id);
        });

        if ($selectedSchedule->subject && $selectedSchedule->subject->is_religion && $selectedSchedule->religion_id) {
            $studentsQuery->where('religion_id', $selectedSchedule->religion_id);
        }

        $students = $studentsQuery->select('id', 'full_name', 'nisn', 'nis', 'gender', 'religion_id')
            ->orderBy('full_name', 'asc')
            ->get();

        // 5. Query data izin/dispensasi resmi (StudentPermit) untuk overlay dinamis (sinkronisasi izin backdated)
        $studentIds = $students->pluck('id')->toArray();
        $agendaDates = $agendas->pluck('date')->map(fn ($d) => Carbon::parse($d)->toDateString())->unique()->toArray();

        $permits = StudentPermit::where('academic_year_id', $activeYear->id)
            ->whereIn('student_id', $studentIds)
            ->whereIn('date', $agendaDates)
            ->get();

        // 6. Matriks Presensi Siswa
        $agendaHeaders = $agendas->map(function ($agenda, $index) {
            return [
                'id' => $agenda->id,
                'date' => $agenda->date,
                'formatted_date' => Carbon::parse($agenda->date)->format('d/m'),
                'full_date' => Carbon::parse($agenda->date)->translatedFormat('l, d F Y'),
                'meeting_no' => $index + 1,
                'jam_ke' => $agenda->start_slot ?? $agenda->scheduleDetail?->start_slot ?? '-',
                'materi' => $agenda->materi_pembelajaran,
                'kode_tp' => $agenda->tp?->kode_tp,
            ];
        });

        $matrixStudents = $students->map(function ($student) use ($agendas, $permits) {
            $presensiMap = [];
            $hCount = 0;
            $sCount = 0;
            $iCount = 0;
            $aCount = 0;

            $studentPermits = $permits->where('student_id', $student->id);

            foreach ($agendas as $agenda) {
                $agendaDate = Carbon::parse($agenda->date)->toDateString();
                $att = $agenda->attendances->firstWhere('student_id', $student->id);

                // Cek apakah ada izin resmi untuk siswa pada tanggal ini (cocokkan juga slot jam ke jika ada)
                $applicablePermit = $studentPermits->filter(function ($permit) use ($agendaDate, $agenda) {
                    if (Carbon::parse($permit->date)->toDateString() !== $agendaDate) {
                        return false;
                    }
                    if ($permit->start_slot && $permit->end_slot) {
                        $agendaSlot = (int) ($agenda->start_slot ?? $agenda->scheduleDetail?->start_slot ?? 0);
                        if ($agendaSlot > 0 && ($agendaSlot < $permit->start_slot || $agendaSlot > $permit->end_slot)) {
                            return false;
                        }
                    }
                    return true;
                })->first();

                if ($att) {
                    $rawNote = strtoupper(trim((string)$att->note));
                    $isPresent = (bool) $att->is_present;

                    if ($isPresent) {
                        if ($applicablePermit && in_array(strtoupper($applicablePermit->permit_type), ['D', 'T'])) {
                            $status = strtoupper($applicablePermit->permit_type);
                        } else {
                            $status = ($rawNote === 'T' || $rawNote === 'D') ? $rawNote : 'H';
                        }
                        $hCount++;
                    } else {
                        // Guru menandai TIDAK HADIR
                        // Prioritas 1: Gunakan izin resmi dari StudentPermit (misal backdated Paskibra/Dispensasi/Sakit/Izin)
                        if ($applicablePermit) {
                            $pType = strtoupper(trim($applicablePermit->permit_type));
                            if ($pType === 'S') {
                                $status = 'S';
                                $sCount++;
                            } elseif ($pType === 'I') {
                                $status = 'I';
                                $iCount++;
                            } elseif ($pType === 'D') {
                                $status = 'D';
                                $iCount++;
                            } elseif ($pType === 'T') {
                                $status = 'T';
                                $hCount++;
                            } else {
                                $status = 'A';
                                $aCount++;
                            }
                        } else {
                            // Prioritas 2: Baca dari catatan agenda guru
                            if ($rawNote === 'S') {
                                $status = 'S';
                                $sCount++;
                            } elseif ($rawNote === 'I') {
                                $status = 'I';
                                $iCount++;
                            } elseif ($rawNote === 'D') {
                                $status = 'D';
                                $iCount++;
                            } elseif ($rawNote === 'T') {
                                $status = 'T';
                                $hCount++;
                            } else {
                                $status = 'A';
                                $aCount++;
                            }
                        }
                    }
                } else {
                    // Belum ada baris di agenda_attendances, periksa apakah ada permit resmi
                    if ($applicablePermit) {
                        $pType = strtoupper(trim($applicablePermit->permit_type));
                        if ($pType === 'S') {
                            $status = 'S';
                            $sCount++;
                        } elseif ($pType === 'I') {
                            $status = 'I';
                            $iCount++;
                        } elseif ($pType === 'D') {
                            $status = 'D';
                            $iCount++;
                        } elseif ($pType === 'T') {
                            $status = 'T';
                            $hCount++;
                        } else {
                            $status = 'A';
                            $aCount++;
                        }
                    } else {
                        $status = '-';
                    }
                }

                $presensiMap[$agenda->id] = $status;
            }

            $thCount = $sCount + $iCount + $aCount;
            $totalCount = $hCount + $thCount;
            $percentageNum = $totalCount > 0 ? round(($hCount / $totalCount) * 100) : 0;

            return [
                'id' => $student->id,
                'full_name' => $student->full_name,
                'nisn' => $student->nisn ?: ($student->nis ?: '-'),
                'nis' => $student->nis ?: '-',
                'gender_label' => $student->gender_label,
                'presensi' => $presensiMap,
                'h_count' => $hCount,
                's_count' => $sCount,
                'i_count' => $iCount,
                'a_count' => $aCount,
                'th_count' => $thCount,
                'total_count' => $totalCount,
                'percentage' => $totalCount > 0 ? ($percentageNum . '%') : '-',
                'percentage_num' => $percentageNum,
                'keterangan' => $totalCount > 0 ? ($percentageNum . '%') : '-',
            ];
        });

        // Rekap total per kolom agenda (footer)
        $agendaSummary = $agendas->map(function ($agenda) use ($matrixStudents) {
            $h = 0; $th = 0;
            foreach ($matrixStudents as $st) {
                $status = $st['presensi'][$agenda->id] ?? '-';
                if (in_array($status, ['H', 'T'])) {
                    $h++;
                } elseif (in_array($status, ['S', 'I', 'A', 'D'])) {
                    $th++;
                }
            }
            return [
                'agenda_id' => $agenda->id,
                'hadir' => $h,
                'tidak_hadir' => $th,
                'total' => $h + $th,
            ];
        });

        return [
            'teacher' => $teacher,
            'activeYear' => $activeYear,
            'schedules' => $schedules,
            'selectedSchedule' => $selectedSchedule,
            'classroom' => $selectedSchedule->classroom,
            'subject' => $selectedSchedule->subject,
            'agendas' => $agendas,
            'agendaHeaders' => $agendaHeaders,
            'students' => $matrixStudents,
            'agendaSummary' => $agendaSummary,
            'filters' => [
                'schedule_id' => (int) $selectedSchedule->id,
                'filter_type' => $filterType,
                'month' => $month,
                'year' => $year,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'periodLabel' => $periodLabel,
            'periodSubLabel' => $periodSubLabel,
            'monthOptions' => collect($months)->map(fn($label, $val) => ['val' => $val, 'label' => $label])->values(),
            'yearOptions' => collect(range(date('Y') - 2, date('Y') + 1))->map(fn($y) => ['val' => $y, 'label' => (string)$y])->values(),
        ];
    }

    public function index(Request $request)
    {
        $data = $this->prepareRecapData($request);
        return Inertia::render($this->resolveView('Index'), $data);
    }

    public function exportPdf(Request $request)
    {
        $data = $this->prepareRecapData($request);
        if (!$data['selectedSchedule']) {
            return redirect()->back()->with('error', 'Jadwal mengajar tidak ditemukan.');
        }

        $getLogoBase64 = function ($key) {
            $path = Setting::get($key);
            if (!$path) return null;
            
            $fullPath = null;
            if (file_exists(storage_path('app/public/' . $path))) {
                $fullPath = storage_path('app/public/' . $path);
            } elseif (file_exists(public_path('storage/' . $path))) {
                $fullPath = public_path('storage/' . $path);
            } elseif (file_exists(public_path($path))) {
                $fullPath = public_path($path);
            }

            if ($fullPath && file_exists($fullPath)) {
                $ext = pathinfo($fullPath, PATHINFO_EXTENSION);
                $fileData = @file_get_contents($fullPath);
                if ($fileData !== false) {
                    return 'data:image/' . ($ext === 'svg' ? 'svg+xml' : $ext) . ';base64,' . base64_encode($fileData);
                }
            }
            return null;
        };

        $kop = [
            'school_name' => Setting::get('school_name', 'SMA NEGERI 16 SEMARANG'),
            'school_address' => Setting::get('school_address', ''),
            'school_city' => Setting::get('school_city', 'Semarang'),
            'school_province' => Setting::get('school_province', 'Jawa Tengah'),
            'school_postal_code' => Setting::get('school_postal_code', ''),
            'school_phone' => Setting::get('school_phone', ''),
            'school_email' => Setting::get('school_email', ''),
            'school_website' => Setting::get('school_website', ''),
            'kop_pemprov' => Setting::get('kop_pemprov', 'PEMERINTAH PROVINSI JAWA TENGAH'),
            'kop_dinas' => Setting::get('kop_dinas', 'DINAS PENDIDIKAN DAN KEBUDAYAAN'),
            'principal_name' => Setting::get('principal_name', 'Kepala Sekolah'),
            'principal_nip' => Setting::get('principal_nip', '-'),
            'logo_sekolah_base64' => $getLogoBase64('site_logo'),
            'logo_pemda_base64' => $getLogoBase64('site_logo_pemda'),
        ];

        $data['kop'] = $kop;
        $data['city'] = Setting::get('school_city', 'Semarang');
        $data['printDate'] = Carbon::now('Asia/Jakarta')->translatedFormat('d F Y');

        $pdf = Pdf::loadView('akademik::pdf.attendance_recap', $data)
            ->setPaper('a4', 'landscape');

        $subjectSlug = str_replace([' ', '/', '\\'], '_', $data['subject']->name);
        $classroomSlug = str_replace([' ', '/', '\\'], '_', $data['classroom']->name);
        $periodSlug = str_replace([' ', '/', '\\'], '_', $data['periodLabel']);

        return $pdf->stream("Rekap_Presensi_{$subjectSlug}_{$classroomSlug}_{$periodSlug}.pdf");
    }

    public function exportExcel(Request $request)
    {
        $data = $this->prepareRecapData($request);
        if (!$data['selectedSchedule']) {
            return redirect()->back()->with('error', 'Jadwal mengajar tidak ditemukan.');
        }

        $kop = [
            'school_name' => Setting::get('school_name', 'SMA NEGERI 16 SEMARANG'),
            'school_address' => Setting::get('school_address', ''),
            'school_city' => Setting::get('school_city', 'Semarang'),
            'school_province' => Setting::get('school_province', 'Jawa Tengah'),
            'kop_pemprov' => Setting::get('kop_pemprov', 'PEMERINTAH PROVINSI JAWA TENGAH'),
            'kop_dinas' => Setting::get('kop_dinas', 'DINAS PENDIDIKAN DAN KEBUDAYAAN'),
        ];
        $data['kop'] = $kop;
        $data['city'] = Setting::get('school_city', 'Semarang');
        $data['printDate'] = Carbon::now('Asia/Jakarta')->translatedFormat('d F Y');

        $subjectSlug = str_replace([' ', '/', '\\'], '_', $data['subject']->name);
        $classroomSlug = str_replace([' ', '/', '\\'], '_', $data['classroom']->name);
        $fileName = "Rekap_Presensi_{$subjectSlug}_{$classroomSlug}.xlsx";

        return Excel::download(
            new GuruAttendanceRecapExport($data),
            $fileName
        );
    }
}
