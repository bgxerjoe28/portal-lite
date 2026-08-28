<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Ticket - {{ $late->student->full_name }}</title>
    <style>
        /* Pengaturan Ukuran Kertas Thermal 58mm */
        @page { 
            size: 58mm auto; 
            margin: 0; 
        }
        
        body { 
            font-family: 'Courier New', Courier, monospace; 
            width: 54mm; /* Printable area rata-rata printer 58mm */
            margin: 0 auto;
            padding: 5mm 2mm;
            font-size: 9pt;
            line-height: 1.2;
            color: #000;
        }

        .header { text-align: center; margin-bottom: 5px; }
        .school-name { font-weight: bold; font-size: 10pt; text-transform: uppercase; }
        
        .divider { border-top: 1px dashed #000; margin: 5px 0; }
        
        .title { text-align: center; font-weight: bold; margin: 8px 0; font-size: 10pt; }
        
        .info-row { margin-bottom: 3px; display: flex; }
        .label { width: 35%; flex-shrink: 0; }
        .value { width: 65%; font-weight: bold; word-wrap: break-word; }

        .footer { text-align: center; margin-top: 15px; font-size: 8pt; }
        
        /* Tombol print hanya muncul di layar, tidak di kertas */
        @media print { .no-print { display: none; } }
    </style>
</head>
<body onload="window.print()">

    <div class="no-print" style="text-align: center; margin-bottom: 10px;">
        <button onclick="window.print()">CETAK ULANG</button>
    </div>

    <div class="header">
        <div class="school-name">SMAN 16 SEMARANG</div>
        <div style="font-size: 7pt;">PORTAL SMAN16</div>
    </div>

    <div class="divider"></div>
    <div class="title">IZIN MASUK KELAS</div>
    <div class="divider"></div>

    <div class="content">
        <div class="info-row">
            <span class="label">NAMA:</span>
            <span class="value">{{ strtoupper($late->student->full_name) }}</span>
        </div>
        <div class="info-row">
            <span class="label">KELAS:</span>
            <span class="value">{{ $late->student->currentClassroom->name ?? '-' }}</span>
        </div>
        <div class="info-row">
            <span class="label">JAM:</span>
            <span class="value">{{ now()->format('H:i') }} WIB</span>
        </div>
        <div class="info-row">
            <span class="label">TGL:</span>
            <span class="value">{{ now()->format('d/m/y') }}</span>
        </div>
        <div class="divider"></div>
        <div style="font-size: 8pt; text-align: justify;">
            <strong>ALASAN:</strong><br>
            {{ $late->reason }}
        </div>
    </div>

    <div class="divider"></div>

    <div class="footer">
        <p>Petugas Piket,</p>
        <br><br>
        <p>___________________</p>
        <p>{{ Auth::user()->name }}</p>
        <p style="margin-top: 5px; font-size: 7pt;">-- Simpan sebagai bukti --</p>
    </div>

</body>
</html>