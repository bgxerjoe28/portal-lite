<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>E-Permit Peminjaman Sarpras - {{ $reservation->reservation_code }}</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 10pt; color: #222; margin: 20px; line-height: 1.5; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .header { border-bottom: 3px double #000; padding-bottom: 12px; margin-bottom: 20px; }
        .header h2 { margin: 0; font-size: 14pt; text-transform: uppercase; }
        .header h3 { margin: 2px 0; font-size: 12pt; color: #1e3a8a; }
        .header p { margin: 0; font-size: 8.5pt; color: #555; }
        .doc-title { text-align: center; margin-bottom: 20px; }
        .doc-title h4 { margin: 0; font-size: 12pt; text-decoration: underline; letter-spacing: 0.5px; }
        .doc-title span { font-size: 9pt; color: #555; }
        .permit-box { border: 2px solid #2563eb; background-color: #eff6ff; padding: 12px; border-radius: 6px; margin-bottom: 20px; }
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .info-table td { padding: 5px 8px; vertical-align: top; border: none; }
        .info-table td.label { width: 28%; font-weight: bold; color: #374151; }
        .info-table td.colon { width: 3%; text-align: center; }
        .item-table { width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 20px; }
        .item-table th, .item-table td { border: 1px solid #9ca3af; padding: 6px 10px; font-size: 9pt; }
        .item-table th { background-color: #f3f4f6; font-weight: bold; text-align: left; text-transform: uppercase; font-size: 8pt; }
        .badge { display: inline-block; padding: 3px 8px; font-size: 8pt; font-weight: bold; border-radius: 4px; color: #fff; background-color: #10b981; }
        .qr-section { float: right; width: 130px; text-align: center; border: 1px dashed #6b7280; padding: 8px; margin-left: 15px; margin-bottom: 10px; background-color: #fafafa; }
        .qr-code-text { font-family: monospace; font-size: 9pt; font-weight: bold; margin-top: 4px; color: #1e3a8a; }
        .signature-table { width: 100%; border-collapse: collapse; margin-top: 35px; }
        .signature-table td { width: 33.33%; text-align: center; vertical-align: top; border: none; font-size: 9pt; }
        .sign-space { height: 65px; }
        .rules-box { font-size: 8pt; color: #4b5563; border-top: 1px dashed #d1d5db; padding-top: 10px; margin-top: 25px; }
    </style>
</head>
<body>
    <div class="header text-center">
        <h2>PORTAL DIGITAL SARANA & PRASARANA</h2>
        <h3>SMA NEGERI 16 SEMARANG</h3>
        <p>Jl. Raya Ngaliyan No. 16, Semarang | Telp: (024) 7601234 | Email: sarpras@sman16smg.sch.id</p>
    </div>

    <div class="doc-title">
        <h4>SURAT IZIN PEMINJAMAN RUANG & ASET (E-PERMIT)</h4>
        <span>Nomor Registrasi: <strong>{{ $reservation->reservation_code }}</strong></span>
    </div>

    <div class="qr-section">
        <div style="font-size: 7.5pt; color: #555; margin-bottom: 4px;">KODE VERIFIKASI QR</div>
        <img src="https://api.qrserver.com/v1/create-qr-code/?size=110x110&data={{ urlencode($reservation->qr_token) }}" alt="QR Permit" width="105" height="105" />
        <div class="qr-code-text">{{ $reservation->qr_token }}</div>
    </div>

    <div class="permit-box">
        <strong>STATUS PERIZINAN: </strong>
        <span class="badge">{{ strtoupper(str_replace('_', ' ', $reservation->status)) }}</span>
        <div style="font-size: 8.5pt; color: #4b5563; margin-top: 4px;">
            Surat izin digital ini sah dan diterbitkan secara otomatis oleh Sistem Sarpras Sekolah.
        </div>
    </div>

    <table class="info-table">
        <tr>
            <td class="label">Nama Pemohon</td>
            <td class="colon">:</td>
            <td><strong>{{ $reservation->user->name ?? '-' }}</strong> ({{ $reservation->user->email ?? '-' }})</td>
        </tr>
        <tr>
            <td class="label">Organisasi / Eskul</td>
            <td class="colon">:</td>
            <td>{{ $reservation->extracurricular ? $reservation->extracurricular->name : 'Pribadi / Panitia Sekolah' }}</td>
        </tr>
        <tr>
            <td class="label">Nama Kegiatan</td>
            <td class="colon">:</td>
            <td><strong>{{ $reservation->title }}</strong></td>
        </tr>
        <tr>
            <td class="label">Keperluan / Tujuan</td>
            <td class="colon">:</td>
            <td>{{ $reservation->purpose }}</td>
        </tr>
        <tr>
            <td class="label">Waktu Penggunaan</td>
            <td class="colon">:</td>
            <td>
                <strong>{{ \Carbon\Carbon::parse($reservation->start_time)->translatedFormat('l, d F Y - H:i') }} WIB</strong>
                s/d 
                <strong>{{ \Carbon\Carbon::parse($reservation->end_time)->translatedFormat('l, d F Y - H:i') }} WIB</strong>
            </td>
        </tr>
        @if($reservation->room)
        <tr>
            <td class="label">Ruangan Terpinjam</td>
            <td class="colon">:</td>
            <td><strong>{{ $reservation->room->name }}</strong> (Lokasi: {{ $reservation->room->location ?? 'Gedung Sekolah' }})</td>
        </tr>
        @endif
        @if($reservation->participant_count)
        <tr>
            <td class="label">Estimasi Peserta</td>
            <td class="colon">:</td>
            <td>{{ $reservation->participant_count }} Orang</td>
        </tr>
        @endif
    </table>

    @if($reservation->assets->isNotEmpty())
    <div style="margin-top: 15px; font-weight: bold; font-size: 9.5pt;">DAFTAR ASET / PERALATAN YANG DIPINJAM:</div>
    <table class="item-table">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="20%">Kode Aset</th>
                <th width="35%">Nama Barang / Spesifikasi</th>
                <th width="20%">Kategori</th>
                <th width="20%">Kondisi Serah Terima</th>
            </tr>
        </thead>
        <tbody>
            @foreach($reservation->assets as $index => $asset)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td><strong>{{ $asset->asset_code }}</strong></td>
                <td>{{ $asset->name }} {{ $asset->brand_model ? '('.$asset->brand_model.')' : '' }}</td>
                <td>{{ $asset->category }}</td>
                <td>{{ ucfirst($asset->pivot->checkout_condition ?? 'Baik') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <table class="signature-table">
        <tr>
            <td>
                Pemohon Kegiatan,<br>
                <div class="sign-space"></div>
                <strong>{{ $reservation->user->name ?? 'Pemohon' }}</strong><br>
                <span>Peminjam</span>
            </td>
            <td>
                @if($reservation->requires_coach_approval)
                Mengetahui & Menyetujui,<br>
                Pembina Eskul / Guru Pembimbing,<br>
                <div class="sign-space"></div>
                <strong>{{ $reservation->stage1Approver->name ?? ($reservation->coachUser->name ?? 'Pembina Eskul') }}</strong><br>
                <span>NIP / Identitas Terverifikasi</span>
                @else
                &nbsp;<br>&nbsp;
                <div class="sign-space"></div>
                &nbsp;
                @endif
            </td>
            <td>
                Semarang, {{ date('d F Y') }}<br>
                Petugas Sarana & Prasarana,<br>
                <div class="sign-space"></div>
                <strong>{{ $reservation->stage2Approver->name ?? 'Petugas Sarpras' }}</strong><br>
                <span>Unit Sarpras Sekolah</span>
            </td>
        </tr>
    </table>

    <div class="rules-box">
        <strong>Ketentuan & Tata Tertib Peminjaman:</strong>
        <ol style="margin: 3px 0; padding-left: 18px;">
            <li>Wajib menunjukkan lembar E-Permit / QR Code ini kepada petugas Sarpras saat pengambilan barang / pembukaan ruangan.</li>
            <li>Peminjam bertanggung jawab penuh atas kebersihan ruangan serta keutuhan dan fungsi aset selama masa peminjaman.</li>
            <li>Segala bentuk kerusakan atau kehilangan wajib diganti atau diperbaiki sesuai Berita Acara Kerusakan yang ditetapkan sekolah.</li>
            <li>Pengembalian barang wajib dilakukan tepat waktu sesuai batas jam yang tertera pada surat izin ini.</li>
        </ol>
    </div>
</body>
</html>
