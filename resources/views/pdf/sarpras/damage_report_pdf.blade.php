<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Berita Acara Kerusakan - {{ $report->report_code }}</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 10pt; color: #222; margin: 25px; line-height: 1.5; }
        .text-center { text-align: center; }
        .header { border-bottom: 3px double #000; padding-bottom: 12px; margin-bottom: 20px; }
        .header h2 { margin: 0; font-size: 14pt; text-transform: uppercase; }
        .header h3 { margin: 2px 0; font-size: 12pt; color: #b91c1c; }
        .header p { margin: 0; font-size: 8.5pt; color: #555; }
        .doc-title { text-align: center; margin-bottom: 20px; }
        .doc-title h4 { margin: 0; font-size: 12pt; text-decoration: underline; }
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .info-table td { padding: 5px 8px; vertical-align: top; border: none; }
        .info-table td.label { width: 28%; font-weight: bold; color: #374151; }
        .info-table td.colon { width: 3%; text-align: center; }
        .box { border: 1px solid #d1d5db; background-color: #f9fafb; padding: 12px; border-radius: 4px; margin-bottom: 15px; }
        .signature-table { width: 100%; border-collapse: collapse; margin-top: 40px; }
        .signature-table td { width: 50%; text-align: center; vertical-align: top; border: none; font-size: 9.5pt; }
        .sign-space { height: 75px; }
    </style>
</head>
<body>
    <div class="header text-center">
        <h2>PORTAL SARANA & PRASARANA</h2>
        <h3>SMA NEGERI 16 SEMARANG</h3>
        <p>Jl. Raya Ngaliyan No. 16, Semarang | Telp: (024) 7601234</p>
    </div>

    <div class="doc-title">
        <h4>BERITA ACARA KERUSAKAN / KEHILANGAN ASET</h4>
        <span>Nomor: <strong>{{ $report->report_code }}</strong></span>
    </div>

    <p>Pada hari ini, tanggal <strong>{{ \Carbon\Carbon::parse($report->created_at)->translatedFormat('l, d F Y') }}</strong>, telah dilakukan pemeriksaan fisik dan dicatat kejadian kerusakan/kehilangan sarana dan prasarana dengan rincian sebagai berikut:</p>

    <table class="info-table">
        <tr>
            <td class="label">Jenis Kejadian</td>
            <td class="colon">:</td>
            <td><strong>{{ strtoupper($report->damage_type === 'lost' ? 'KEHILANGAN' : 'KERUSAKAN BARANG') }}</strong></td>
        </tr>
        <tr>
            <td class="label">Peminjaman Terkait</td>
            <td class="colon">:</td>
            <td>{{ $report->reservation ? $report->reservation->reservation_code . ' (' . $report->reservation->title . ')' : 'Insiden Langsung / Non-Peminjaman' }}</td>
        </tr>
        <tr>
            <td class="label">Peminjam / Penanggung Jawab</td>
            <td class="colon">:</td>
            <td><strong>{{ $report->reservation->user->name ?? '-' }}</strong> {{ $report->reservation?->extracurricular ? '('.$report->reservation->extracurricular->name.')' : '' }}</td>
        </tr>
        @if($report->asset)
        <tr>
            <td class="label">Aset / Barang</td>
            <td class="colon">:</td>
            <td><strong>{{ $report->asset->name }}</strong> [{{ $report->asset->asset_code }}] (S/N: {{ $report->asset->serial_number ?? '-' }})</td>
        </tr>
        @endif
        @if($report->room)
        <tr>
            <td class="label">Ruangan Terkait</td>
            <td class="colon">:</td>
            <td>{{ $report->room->name }}</td>
        </tr>
        @endif
        <tr>
            <td class="label">Petugas Pemeriksa</td>
            <td class="colon">:</td>
            <td>{{ $report->reporter->name ?? '-' }}</td>
        </tr>
    </table>

    <div class="box">
        <strong>Kronologi / Deskripsi Kerusakan:</strong>
        <p style="margin: 5px 0 0 0;">{{ $report->description }}</p>
    </div>

    <div class="box">
        <strong>Tindak Lanjut & Kesepakatan:</strong>
        <p style="margin: 5px 0;"><strong>Rencana Penanganan:</strong> {{ $report->action_plan ?? '-' }}</p>
        <p style="margin: 5px 0;"><strong>Estimasi Biaya Ganti Rugi / Servis:</strong> Rp {{ number_format($report->compensation_fee, 0, ',', '.') }}</p>
        <p style="margin: 5px 0;"><strong>Status Pembayaran/Kompensasi:</strong> {{ $report->is_compensated ? 'LUNAS / SELESAI' : 'BELUM DIBAYAR / PROSES' }}</p>
    </div>

    <table class="signature-table">
        <tr>
            <td>
                Penanggung Jawab / Peminjam,<br>
                <div class="sign-space"></div>
                <strong>{{ $report->reservation->user->name ?? 'Peminjam' }}</strong>
            </td>
            <td>
                Petugas Sarpras Pemeriksa,<br>
                <div class="sign-space"></div>
                <strong>{{ $report->reporter->name ?? 'Petugas Sarpras' }}</strong>
            </td>
        </tr>
    </table>
</body>
</html>
