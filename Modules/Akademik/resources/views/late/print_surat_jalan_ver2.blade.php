<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Ticket - {{ $late->student->full_name }}</title>

    <style>
        /* =============================
           PENGATURAN KERTAS 1/2 A4 (A5)
           ============================= */
        @page {
            size: A5 portrait;
            margin: 15mm;
        }

        body {
            font-family: "Times New Roman", serif;
            margin: 0;
            font-size: 11pt;
            line-height: 1.4;
            color: #000;
        }

        /* =============================
           HEADER
           ============================= */
        .header {
            text-align: center;
            margin-bottom: 10px;
        }

        .school-name {
            font-size: 14pt;
            font-weight: bold;
            text-transform: uppercase;
        }

        .school-sub {
            font-size: 10pt;
        }

        .divider {
            border-top: 1px solid #000;
            margin: 10px 0;
        }

        .title {
            text-align: center;
            font-size: 13pt;
            font-weight: bold;
            margin: 10px 0;
            letter-spacing: 1px;
        }

        /* =============================
           KONTEN
           ============================= */
        .content {
            margin-top: 10px;
        }

        table.info {
            width: 100%;
            border-collapse: collapse;
            font-size: 11pt;
        }

        table.info td {
            padding: 4px 0;
            vertical-align: top;
        }

        table.info td.label {
            width: 30%;
        }

        table.info td.value {
            width: 70%;
            font-weight: bold;
        }

        .reason-box {
            margin-top: 10px;
            padding: 8px;
            border: 1px solid #000;
            min-height: 60px;
            font-size: 11pt;
        }

        /* =============================
           FOOTER / TTD
           ============================= */
        .signature {
            margin-top: 25px;
            width: 100%;
        }

        .signature td {
            text-align: center;
            font-size: 11pt;
        }

        .signature .name {
            margin-top: 60px;
            font-weight: bold;
            text-decoration: underline;
        }

        .note {
            text-align: center;
            font-size: 9pt;
            margin-top: 15px;
        }

        /* =============================
           PRINT ONLY
           ============================= */
        .no-print {
            text-align: center;
            margin-bottom: 10px;
        }

        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>

<body onload="window.print()">

    <!-- Tombol hanya muncul di layar -->
    <div class="no-print">
        <button onclick="window.print()">CETAK ULANG</button>
    </div>

    <!-- HEADER -->
    <div class="header">
        <div class="school-name">SMAN 16 SEMARANG</div>
        <div class="school-sub">PORTAL SMAN 16 SEMARANG</div>
    </div>

    <div class="divider"></div>

    <div class="title">SURAT IZIN MASUK KELAS</div>

    <div class="divider"></div>

    <!-- ISI -->
    <div class="content">
        <table class="info">
            <tr>
                <td class="label">Nama</td>
                <td class="value">: {{ strtoupper($late->student->full_name) }}</td>
            </tr>
            <tr>
                <td class="label">Kelas</td>
                <td class="value">: {{ $late->student->currentClassroom->name ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Hari / Tanggal</td>
                <td class="value">: {{ now()->translatedFormat('l, d F Y') }}</td>
            </tr>
            <tr>
                <td class="label">Jam</td>
                <td class="value">: {{ now()->format('H:i') }} WIB</td>
            </tr>
        </table>

        <div class="reason-box">
            <strong>Alasan:</strong><br>
            {{ $late->reason }}
        </div>
    </div>

    <!-- TANDA TANGAN -->
    <table class="signature">
        <tr>
            <td>
                Semarang, {{ now()->format('d F Y') }}<br>
                Petugas Piket
                <div class="name">{{ Auth::user()->name }}</div>
            </td>
        </tr>
    </table>

    <div class="note">
        -- Harap disimpan sebagai bukti izin --
    </div>

</body>
</html>
