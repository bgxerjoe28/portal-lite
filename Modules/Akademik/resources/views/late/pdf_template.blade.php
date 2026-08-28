<html>
<head>
    <style>
        body { font-family: sans-serif; }
        .header { text-align: center; border-bottom: 2px solid #000; margin-bottom: 20px; }
        .content table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .content td { padding: 10px; border: 1px solid #ddd; }
        .label { background-color: #f9f9f9; font-weight: bold; width: 30%; }
    </style>
</head>
<body>
    <div class="header">
        <h2>SURAT KETERANGAN KETERLAMBATAN</h2>
        <p>SMA NEGERI 16 SEMARANG </p>
    </div>

    <div class="content">
        <table>
            <tr><td class="label">Nama Siswa</td><td>{{ $late->student->full_name }}</td></tr>
            <tr><td class="label">NIS</td><td>{{ $late->student->nis }}</td></tr>
            <tr><td class="label">Kelas</td><td>{{ $late->student->currentClassroom->name }}</td></tr>
            <tr><td class="label">Tanggal</td><td>{{ $late->date }}</td></tr>
            <tr><td class="label">Alasan</td><td>{{ $late->reason }}</td></tr>
        </table>
    </div>

    <div style="margin-top: 50px; float: right; text-align: center;">
        <p>Dicetak pada: {{ now()->format('d/m/Y H:i') }}</p>
        <br><br><br>
        <p><strong>( {{ Auth::user()->name }})</strong></p>
    </div>
</body>
</html>