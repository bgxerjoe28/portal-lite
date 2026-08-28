<html>
<head>
    <style>
        body { font-family: sans-serif; font-size: 10pt; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #000; padding: 5px; text-align: center; }
        .header { text-align: center; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>REKAPITULASI KEDISIPLINAN SISWA</h2>
        <p>Periode: {{ $filters['start_date'] ?? 'Awal' }} s/d {{ $filters['end_date'] ?? 'Sekarang' }}</p>
    </div>
    <table>
        <thead>
            <tr style="background: #f2f2f2;">
                <th>NO</th>
                <th>NAMA SISWA</th>
                <th>KELAS</th>
                <th>S</th><th>I</th><th>D</th><th>T</th><th>A</th>
                <th>TOTAL</th>
            </tr>
        </thead>
        <tbody>
            @foreach($statistics as $row)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td style="text-align: left;">{{ $row->student->full_name }}</td>
                <td>{{ $row->student->currentClassroom->name ?? '-' }}</td>
                <td>{{ $row->total_sakit }}</td>
                <td>{{ $row->total_izin }}</td>
                <td>{{ $row->total_dispen }}</td>
                <td>{{ $row->total_terlambat }}</td>
                <td>{{ $row->total_alfa }}</td>
                <td>{{ $row->total_akumulasi }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>