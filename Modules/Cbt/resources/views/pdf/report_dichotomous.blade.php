<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>REPORT MATRIKS DIKOTOMI JAWABAN - {{ $bank->name }}</title>
    <style>
        @page {
            size: A4 landscape; /* using landscape because of many columns */
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
        .font-bold { font-weight: bold; }
        .bg-gray-header { background-color: #d9d9d9; }
        .bg-yellow-accent { background-color: #fff2cc; }
        .text-blue { color: #002060; }
        .text-red { color: #c00000; }
        
        .header-title {
            border: 2px solid #000;
            background-color: #d9d9d9;
            text-align: center;
            font-weight: bold;
            font-size: 12pt;
            padding: 6px;
            text-transform: uppercase;
        }
        .header-subtitle {
            border: 2px solid #000;
            border-top: none;
            background-color: #fff;
            text-align: center;
            font-weight: bold;
            font-size: 10pt;
            padding: 4px;
            margin-bottom: 5px;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
            border: 2px solid #000;
            margin-bottom: 8px;
            font-size: 8.5pt;
        }
        .meta-table td {
            padding: 3px 5px;
            vertical-align: middle;
            white-space: nowrap;
        }
        .meta-table .side-header {
            width: 12%;
            background-color: #d9d9d9;
            font-weight: bold;
            text-align: center;
            border-right: 2px solid #000;
            white-space: normal;
        }

        .student-table {
            width: 100%;
            border-collapse: collapse;
            border: 2px solid #000;
            font-size: 8.5pt;
        }
        .student-table th {
            background-color: #d9d9d9;
            border: 1px solid #000;
            padding: 4px 2px;
            text-align: center;
            font-weight: bold;
        }
        .student-table td {
            border: 1px solid #000;
            padding: 3px 4px;
            vertical-align: middle;
        }
        .answers-code {
            font-family: 'Courier New', Courier, monospace;
            letter-spacing: 1.5px;
            font-size: 8.5pt;
            word-break: break-all;
        }
        .dichotomous-cell {
            font-family: 'Courier New', Courier, monospace;
            font-size: 8.5pt;
            text-align: center;
        }

        .summary-row {
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .footer-sig-table {
            width: 100%;
            margin-top: 25px;
            font-size: 9pt;
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

    <!-- Title Banner -->
    <div class="header-title">MATRIKS DIKOTOMI BINER (HASIL UJIAN)</div>
    <div class="header-subtitle">1: BENAR, 0 : SALAH / KOSONG</div>

    <!-- Data Umum Header Table -->
    <table class="meta-table">
        <tr>
            <td class="side-header" rowspan="6">DATA UMUM</td>
            <td style="width: 15%;"><strong>NAMA SEKOLAH</strong></td>
            <td style="width: 1%;">:</td>
            <td style="width: 34%;" class="text-blue font-bold">{{ $school_name }}</td>
            <td style="width: 18%;"><strong>SEMESTER</strong></td>
            <td style="width: 1%;">:</td>
            <td style="width: 31%;" class="text-blue">{{ $semester }}</td>
        </tr>
        <tr>
            <td><strong>MATA PELAJARAN</strong></td>
            <td>:</td>
            <td class="text-blue font-bold">{{ $bank->subject->name ?? '-' }}</td>
            <td><strong>TAHUN PELAJARAN</strong></td>
            <td>:</td>
            <td class="text-blue">{{ $academic_year }}</td>
        </tr>
        <tr>
            <td><strong>KELAS</strong></td>
            <td>:</td>
            <td class="text-blue font-bold">{{ $classroom_names }}</td>
            <td><strong>TANGGAL TES</strong></td>
            <td>:</td>
            <td>{{ $exam_date }}</td>
        </tr>
        <tr>
            <td><strong>NAMA TES</strong></td>
            <td>:</td>
            <td class="text-blue font-bold">{{ $bank->name }}</td>
            <td><strong>TANGGAL DIPERIKSA</strong></td>
            <td>:</td>
            <td>{{ now()->translatedFormat('l, d F Y') }}</td>
        </tr>
        <tr>
            <td><strong>MATERI POKOK</strong></td>
            <td>:</td>
            <td>{{ $bank->description ?? '-' }}</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
        </tr>
        <tr>
            <td><strong>NAMA PENGAJAR</strong></td>
            <td>:</td>
            <td class="text-blue font-bold">{{ $bank->teacher->full_name ?? 'Admin / Pengajar' }}</td>
            <td><strong>NIM / NIP</strong></td>
            <td>:</td>
            <td>{{ $bank->teacher->nip ?? '-' }}</td>
        </tr>
    </table>

    <!-- Student Responses Main Table -->
    <table class="student-table">
        <thead>
            <tr>
                <th style="width: 3%;" rowspan="2">No.</th>
                <th style="width: 15%;" rowspan="2">Nama Siswa</th>
                <th style="width: 3%;" rowspan="2">L/P</th>
                <th style="width: 15%;" rowspan="2">Rincian Jawaban Siswa</th>
                <th colspan="{{ count($questions) }}">Nomor Soal</th>
                <th style="width: 5%;" rowspan="2">BENAR</th>
                <th style="width: 5%;" rowspan="2">SALAH</th>
                <th style="width: 5%;" rowspan="2">SKOR</th>
                <th style="width: 5%;" rowspan="2">NILAI</th>
            </tr>
            <tr>
                @for($i = 1; $i <= count($questions); $i++)
                <th style="width: 2%;">{{ $i }}</th>
                @endfor
            </tr>
        </thead>
        <tbody>
            @forelse($student_rows as $idx => $row)
            <tr>
                <td class="text-center">{{ $idx + 1 }}</td>
                <td><strong>{{ $row['name'] }}</strong></td>
                <td class="text-center">{{ $row['gender'] }}</td>
                <td class="answers-code text-center">{{ $row['answer_string'] }}</td>
                
                @foreach($row['dichotomous_arr'] as $val)
                <td class="dichotomous-cell">{{ $val }}</td>
                @endforeach
                
                <td class="text-center font-bold">{{ $row['correct_count'] }}</td>
                <td class="text-center font-bold text-red">{{ $row['wrong_count'] }}</td>
                <td class="text-center font-bold">{{ $row['score'] }}</td>
                <td class="text-center font-bold" style="background-color: {{ $row['is_tuntas'] ? '#e2efda' : '#fce4d6' }};">
                    {{ $row['nilai'] }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="{{ count($questions) + 8 }}" class="text-center">Belum ada data pengerjaan siswa untuk bank soal ini.</td>
            </tr>
            @endforelse

            <!-- Summary Rows (Renders ONCE at the end of the table on the final page) -->
            <tr class="summary-row" style="background-color: #f2f2f2; font-weight: bold;">
                <td colspan="{{ count($questions) + 4 }}" class="text-right">JUMLAH :</td>
                <td class="text-center">{{ $stats['sum_correct'] }}</td>
                <td class="text-center">{{ $stats['sum_wrong'] }}</td>
                <td class="text-center">{{ $stats['sum_score'] }}</td>
                <td class="text-center">{{ $stats['sum_nilai'] }}</td>
            </tr>
            <tr class="summary-row" style="background-color: #f2f2f2; font-weight: bold;">
                <td colspan="{{ count($questions) + 4 }}" class="text-right">TERKECIL :</td>
                <td class="text-center">{{ $stats['min_correct'] }}</td>
                <td class="text-center">{{ $stats['min_wrong'] }}</td>
                <td class="text-center">{{ number_format($stats['min_score'], 2) }}</td>
                <td class="text-center">{{ number_format($stats['min_nilai'], 2) }}</td>
            </tr>
            <tr class="summary-row" style="background-color: #f2f2f2; font-weight: bold;">
                <td colspan="{{ count($questions) + 4 }}" class="text-right">TERBESAR :</td>
                <td class="text-center">{{ $stats['max_correct'] }}</td>
                <td class="text-center">{{ $stats['max_wrong'] }}</td>
                <td class="text-center">{{ number_format($stats['max_score'], 2) }}</td>
                <td class="text-center">{{ number_format($stats['max_nilai'], 2) }}</td>
            </tr>
            <tr class="summary-row" style="background-color: #f2f2f2; font-weight: bold;">
                <td colspan="{{ count($questions) + 4 }}" class="text-right">RATA-RATA :</td>
                <td class="text-center">{{ number_format($stats['avg_correct'], 2) }}</td>
                <td class="text-center">{{ number_format($stats['avg_wrong'], 2) }}</td>
                <td class="text-center">{{ number_format($stats['avg_score'], 2) }}</td>
                <td class="text-center">{{ number_format($stats['avg_nilai'], 2) }}</td>
            </tr>
            <tr class="summary-row" style="background-color: #f2f2f2; font-weight: bold;">
                <td colspan="{{ count($questions) + 4 }}" class="text-right">SIMPANGAN BAKU :</td>
                <td class="text-center">-</td>
                <td class="text-center">-</td>
                <td class="text-center">{{ number_format($stats['std_score'], 2) }}</td>
                <td class="text-center">{{ number_format($stats['std_nilai'], 2) }}</td>
            </tr>
        </tbody>
    </table>

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
        // Auto open print dialog on page load
        window.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                window.print();
            }, 500);
        });
    </script>
</body>
</html>
