<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>DAFTAR NILAI - {{ $bank->name }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 10mm;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10pt;
            color: #000;
            background-color: #fff;
            margin: 0;
            padding: 10px;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        
        .header-title {
            text-align: center;
            font-weight: bold;
            font-size: 16pt;
            text-transform: uppercase;
            text-decoration: underline;
            margin-bottom: 20px;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 10pt;
            font-weight: bold;
        }
        .meta-table td {
            padding: 4px 5px;
            vertical-align: middle;
        }
        .batas-lulus-box {
            border: 2px solid #000;
            text-align: center;
            font-weight: bold;
            width: 100px;
        }

        .student-table {
            width: 100%;
            border-collapse: collapse;
            border: 2px solid #000;
            font-size: 9pt;
        }
        .student-table th {
            border: 1px solid #000;
            padding: 6px 4px;
            text-align: center;
            font-weight: bold;
        }
        .student-table td {
            border: 1px solid #000;
            padding: 4px 6px;
            vertical-align: middle;
        }
        .answers-code {
            font-family: 'Courier New', Courier, monospace;
            letter-spacing: 2px;
            font-size: 9pt;
        }

        .rekap-table {
            width: 100%;
            border-collapse: collapse;
            border: 2px solid #000;
            border-top: none; /* Merges with student table */
            font-size: 9pt;
            page-break-inside: avoid;
        }
        .rekap-table td {
            border: 1px solid #000;
            padding: 4px 6px;
            vertical-align: middle;
        }
        .rekap-header-vert {
            background-color: #d9d9d9;
            font-weight: bold;
            text-align: center;
            width: 4%;
        }
        .rekap-header-vert div {
            writing-mode: vertical-rl;
            transform: rotate(180deg);
            margin: auto;
            letter-spacing: 5px;
        }

        .footer-sig-table {
            width: 100%;
            margin-top: 30px;
            font-size: 10pt;
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .footer-sig-table td {
            vertical-align: top;
        }

        @media print {
            .no-print { display: none !important; }
            body { padding: 0; }
        }
    </style>
</head>
<body>

    <!-- Floating Print Button -->
    <div class="no-print" style="position: fixed; top: 15px; right: 15px; z-index: 9999;">
        <button onclick="window.print()" style="background-color: #2563eb; color: #fff; border: none; padding: 10px 18px; border-radius: 6px; font-weight: bold; cursor: pointer; box-shadow: 0 4px 6px rgba(0,0,0,0.2);">
            🖨️ Cetak / Simpan PDF
        </button>
    </div>

    <!-- Title -->
    <div class="header-title">DAFTAR NILAI</div>

    <!-- Data Umum Header Table -->
    <table class="meta-table">
        <tr>
            <td style="width: 20%;">NAMA SEKOLAH</td>
            <td style="width: 1%;">:</td>
            <td style="width: 60%;">{{ $school_name }}</td>
            <td rowspan="6" style="width: 19%; vertical-align: bottom;">
                <table style="float: right; border-collapse: collapse; font-size: 9pt; width: 100px;">
                    <tr>
                        <td class="batas-lulus-box" style="background-color: #d9d9d9; border-bottom: none;">Batas Lulus</td>
                    </tr>
                    <tr>
                        <td class="batas-lulus-box" style="font-size: 11pt;">70</td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td>NAMA TES</td>
            <td>:</td>
            <td>{{ $bank->name }}</td>
        </tr>
        <tr>
            <td>MATA PELAJARAN</td>
            <td>:</td>
            <td>{{ $bank->subject->name ?? '-' }}</td>
        </tr>
        <tr>
            <td>KELAS/PROGRAM</td>
            <td>:</td>
            <td>{{ $classroom_names }}</td>
        </tr>
        <tr>
            <td>TANGGAL TES</td>
            <td>:</td>
            <td>{{ $exam_date }}</td>
        </tr>
        <tr>
            <td>MATERI POKOK</td>
            <td>:</td>
            <td>{{ $bank->description ?? '-' }}</td>
        </tr>
    </table>

    <!-- Student Responses Main Table -->
    <table class="student-table">
        <thead>
            <tr>
                <th style="width: 4%;" rowspan="2">No.<br>Urut</th>
                <th style="width: 25%;" rowspan="2">NAMA/KODE PESERTA</th>
                <th style="width: 4%;" rowspan="2">L/P</th>
                <th style="width: 35%;" rowspan="2">URAIAN JAWABAN SISWA DAN HASIL PEMERIKSAAN</th>
                <th style="width: 10%;" colspan="2">JUMLAH</th>
                <th style="width: 6%;" rowspan="2">SKOR<br>PG</th>
                <th style="width: 6%;" rowspan="2">NILAI</th>
                <th style="width: 10%;" rowspan="2">CATATAN</th>
            </tr>
            <tr>
                <th style="width: 5%;">BENAR</th>
                <th style="width: 5%;">SALAH</th>
            </tr>
        </thead>
        <tbody>
            @forelse($student_rows as $idx => $row)
            <tr>
                <td class="text-center">{{ $idx + 1 }}</td>
                <td>{{ strtoupper($row['name']) }}</td>
                <td class="text-center">{{ $row['gender'] }}</td>
                <td class="answers-code">{{ $row['answer_string'] }}</td>
                <td class="text-center">{{ $row['correct_count'] }}</td>
                <td class="text-center">{{ $row['wrong_count'] }}</td>
                <td class="text-center">{{ $row['score'] }}</td>
                <td class="text-center">{{ $row['nilai'] }}</td>
                <td class="text-center">{{ $row['ket'] }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="9" class="text-center">Belum ada data pengerjaan siswa untuk bank soal ini.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <!-- REKAPITULASI Table (Bottom) -->
    @if(count($student_rows) > 0)
    <table class="rekap-table">
        <tr>
            <td class="rekap-header-vert" rowspan="5">
                <div>REKAPITULASI</div>
            </td>
            <td style="width: 23.5%;">- Jumlah peserta test</td>
            <td style="width: 1.5%;">:</td>
            <td style="width: 38%;">{{ $stats['peserta'] }} orang</td>
            
            <td style="width: 14%;" class="text-right font-bold">JUMLAH :</td>
            <td style="width: 9%;" class="text-center font-bold">{{ $stats['sum_score'] }}</td>
            <td style="width: 10%;" class="text-center font-bold">{{ $stats['sum_nilai'] }}</td>
        </tr>
        <tr>
            <td>- Jumlah yang lulus</td>
            <td>:</td>
            <td>{{ $stats['lulus'] }} orang</td>
            
            <td class="text-right font-bold">TERKECIL :</td>
            <td class="text-center">{{ number_format($stats['min_score'], 2) }}</td>
            <td class="text-center">{{ number_format($stats['min_nilai'], 2) }}</td>
        </tr>
        <tr>
            <td>- Jumlah yang tidak lulus</td>
            <td>:</td>
            <td>{{ $stats['tidak_lulus'] }} orang</td>
            
            <td class="text-right font-bold">TERBESAR :</td>
            <td class="text-center">{{ number_format($stats['max_score'], 2) }}</td>
            <td class="text-center">{{ number_format($stats['max_nilai'], 2) }}</td>
        </tr>
        <tr>
            <td>- Jumlah yang di atas rata-rata</td>
            <td>:</td>
            <td>{{ $stats['di_atas_rata'] }} orang</td>
            
            <td class="text-right font-bold">RATA-RATA :</td>
            <td class="text-center">{{ number_format($stats['avg_score'], 3) }}</td>
            <td class="text-center">{{ number_format($stats['avg_nilai'], 3) }}</td>
        </tr>
        <tr>
            <td>- Jumlah yang di bawah rata-rata</td>
            <td>:</td>
            <td>{{ $stats['di_bawah_rata'] }} orang</td>
            
            <td class="text-right font-bold">SIMPANGAN BAKU :</td>
            <td class="text-center">{{ number_format($stats['std_score'], 3) }}</td>
            <td class="text-center">{{ number_format($stats['std_nilai'], 3) }}</td>
        </tr>
    </table>
    @endif

    <!-- Signature Footer -->
    <table class="footer-sig-table">
        <tr>
            <td style="width: 50%; text-align: center;">
                Mengetahui,<br>
                Kepala Sekolah<br><br><br><br>
                <strong><u>{{ $headmaster_name }}</u></strong><br>
                NIP. {{ $headmaster_nip }}
            </td>
            <td style="width: 50%; text-align: center;">
                {{ $school_city ?? 'Semarang' }}, {{ now()->translatedFormat('d F Y') }}<br>
                Guru {{ $bank->subject->name ?? '' }}<br><br><br><br>
                <strong><u>{{ $bank->teacher->full_name ?? auth()->user()->name }}</u></strong><br>
                NIP. {{ $bank->teacher->nip ?? auth()->user()->nip ?? '-' }}
            </td>
        </tr>
    </table>

    <script>
        window.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                window.print();
            }, 500);
        });
    </script>
</body>
</html>
