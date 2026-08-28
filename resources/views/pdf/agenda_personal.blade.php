<!DOCTYPE html>
<html>
<head>
    <title>Jurnal Mengajar</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 10pt; color: #333; }
        .text-center { text-align: center; }
        .header { margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #333; padding: 6px; }
        th { background-color: #f2f2f2; font-weight: bold; text-transform: uppercase; font-size: 8pt; }
        .day-header { background-color: #e9ecef; font-weight: bold; padding: 5px; border: 1px solid #333; }
        .footer { margin-top: 30px; }
        .signature { float: right; width: 200px; text-align: center; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    <div class="header text-center">
        <h2 style="margin: 0;">JURNAL MENGAJAR GURU</h2>
        <h3 style="margin: 0;">SMA NEGERI 16 SEMARANG</h3>
        <p style="margin: 5px 0;">Bulan: <strong>{{ $monthName }} {{ $year }}</strong> | Guru: <strong>{{ $teacher->full_name }}</strong></p>
    </div>

    @foreach($reports as $date => $dayAgendas)
        <div class="day-header">
            📅 {{ \Carbon\Carbon::parse($date)->translatedFormat('l, d F Y') }}
        </div>
        <table>
            <thead>
                <tr>
                    <th rowspan="2" width="10%">Jam</th>
                    <th rowspan="2" width="10%">Kelas</th>
                    <th rowspan="2">Materi & Tujuan Pembelajaran</th>
                    <th colspan="3">Presensi</th>
                </tr>
                <tr>
                    <th width="5%">Jml</th>
                    <th width="5%">H</th>
                    <th width="5%">A</th>
                </tr>
            </thead>
            <tbody>
                @foreach($dayAgendas as $agenda)
                @php
                    $hadir = $agenda->attendances->whereIn('is_present', [true, 't'])->count();
                    $absen = $agenda->attendances_count - $hadir;
                @endphp
                <tr>
                    <td class="text-center">Ke-{{ $agenda->scheduleDetail?->start_slot }}</td>
                    <td class="text-center"><strong>{{ $agenda->classroom?->name }}</strong></td>
                    <td>
                        <div style="font-size: 8pt; color: #007bff;">{{ $agenda->tp?->kode_tp }}</div>
                        {{ $agenda->materi_pembelajaran }}
                    </td>
                    <td class="text-center">{{ $agenda->attendances_count }}</td>
                    <td class="text-center" style="color: green;">{{ $hadir }}</td>
                    <td class="text-center" style="color: red;">{{ $absen }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach

    <div class="footer">
        <div class="signature">
            Semarang, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}<br>
            Guru Mata Pelajaran,
            <br><br><br><br>
            <strong>{{ $teacher->full_name }}</strong><br>
            NIP. {{ $teacher->nip ?? '-' }}
        </div>
    </div>
</body>
</html>