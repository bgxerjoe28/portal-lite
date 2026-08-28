<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>LAPORAN LENGKAP HASIL UJIAN - {{ $bank->name ?? 'CBT' }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 8mm;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8.5pt;
            color: #000;
            background-color: #fff;
            margin: 0;
            padding: 8px;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
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
            font-size: 11pt;
            padding: 5px;
            text-transform: uppercase;
        }
        .header-subtitle {
            border: 2px solid #000;
            border-top: none;
            background-color: #fff;
            text-align: center;
            font-weight: bold;
            font-size: 9.5pt;
            padding: 3px;
            margin-bottom: 5px;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
            border: 2px solid #000;
            margin-bottom: 6px;
            font-size: 8pt;
        }
        .meta-table td {
            padding: 2px 4px;
            vertical-align: middle;
            white-space: nowrap;
        }
        .meta-table .side-header {
            width: 10%;
            background-color: #d9d9d9;
            font-weight: bold;
            text-align: center;
            border-right: 2px solid #000;
            white-space: normal;
        }

        .key-box-table {
            width: 100%;
            border-collapse: collapse;
            border: 2px solid #000;
            margin-bottom: 6px;
            font-size: 8pt;
        }
        .key-box-table th, .key-box-table td {
            border: 1px solid #000;
            padding: 3px;
            text-align: center;
        }
        .key-string {
            font-family: 'Courier New', Courier, monospace;
            font-weight: bold;
            letter-spacing: 2px;
            color: #002060;
            font-size: 9pt;
        }

        .student-table {
            width: 100%;
            border-collapse: collapse;
            border: 2px solid #000;
            font-size: 8pt;
        }
        .student-table th {
            background-color: #d9d9d9;
            border: 1px solid #000;
            padding: 3px 2px;
            text-align: center;
            font-weight: bold;
        }
        .student-table td {
            border: 1px solid #000;
            padding: 2px 3px;
            vertical-align: middle;
        }
        .answers-code {
            font-family: 'Courier New', Courier, monospace;
            letter-spacing: 1.5px;
            font-size: 8pt;
            word-break: break-all;
        }
        .dichotomous-cell {
            font-family: 'Courier New', Courier, monospace;
            font-size: 7.5pt;
            text-align: center;
        }

        .summary-row {
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .footer-sig-table {
            width: 100%;
            margin-top: 15px;
            font-size: 8.5pt;
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .footer-sig-table td {
            vertical-align: top;
        }

        .page-break {
            page-break-after: always;
            break-after: page;
        }

        @media print {
            .no-print { display: none !important; }
            body { padding: 0; }
        }
    </style>
</head>
<body>

    <!-- Floating Print Button -->
    <div class="no-print" style="position: fixed; top: 15px; right: 15px; z-index: 9999; display: flex; gap: 8px;">
        <button onclick="window.print()" style="background-color: #2563eb; color: #fff; border: none; padding: 10px 18px; border-radius: 6px; font-weight: bold; cursor: pointer; box-shadow: 0 4px 6px rgba(0,0,0,0.2);">
            🖨️ Cetak / Simpan PDF (Semua Halaman)
        </button>
    </div>

    <!-- ========================================================================= -->
    <!-- BAGIAN 1: LAPORAN DATA JAWABAN SISWA -->
    <!-- ========================================================================= -->
    <div class="header-title">REPORT DATA JAWABAN SISWA</div>
    <div class="header-subtitle">UJIAN BERBASIS KOMPUTER (CBT)</div>

    <!-- Data Umum Header Table -->
    <table class="meta-table">
        <tr>
            <td class="side-header" rowspan="3">DATA UMUM</td>
            <td style="width: 15%;">NAMA SEKOLAH</td>
            <td style="width: 1%;">:</td>
            <td style="width: 34%;">{{ $school_name }}</td>
            <td style="width: 15%;">SEMESTER / T.A.</td>
            <td style="width: 1%;">:</td>
            <td style="width: 34%;">{{ $semester }} - {{ $academic_year }}</td>
        </tr>
        <tr>
            <td>NAMA TES</td>
            <td>:</td>
            <td>{{ $bank->name }}</td>
            <td>TANGGAL TES</td>
            <td>:</td>
            <td>{{ $exam_date }}</td>
        </tr>
        <tr>
            <td>MATA PELAJARAN</td>
            <td>:</td>
            <td>{{ $bank->subject->name ?? '-' }}</td>
            <td>KELAS / PROGRAM</td>
            <td>:</td>
            <td>{{ $classroom_names }}</td>
        </tr>
    </table>

    <!-- Kunci Jawaban Table -->
    <table class="key-box-table">
        <tr class="bg-gray-header">
            <th style="width: 50%;">KUNCI JAWABAN ({{ count($questions) }} SOAL)</th>
            <th style="width: 25%;">JUMLAH OPSI</th>
            <th style="width: 25%;">JUMLAH PESERTA</th>
        </tr>
        <tr>
            <td class="key-string">{{ $answer_keys_string ?: '-' }}</td>
            <td class="font-bold">{{ $max_option_count }} OPSI (A-E)</td>
            <td class="font-bold">{{ count($student_rows) }} SISWA</td>
        </tr>
    </table>

    <!-- Tabel Siswa Bagian 1 -->
    <table class="student-table">
        <thead>
            <tr>
                <th style="width: 3%;">NO</th>
                <th style="width: 20%;">NAMA SISWA</th>
                <th style="width: 4%;">L/P</th>
                <th style="width: 45%;">JAWABAN SISWA</th>
                <th style="width: 6%;">BENAR</th>
                <th style="width: 6%;">SALAH</th>
                <th style="width: 8%;">NILAI</th>
                <th style="width: 8%;">KET</th>
            </tr>
        </thead>
        <tbody>
            @forelse($student_rows as $idx => $row)
            <tr>
                <td class="text-center">{{ $idx + 1 }}</td>
                <td style="font-weight: bold;">{{ $row['name'] }}</td>
                <td class="text-center">{{ $row['gender'] }}</td>
                <td class="answers-code">{{ $row['answer_string'] }}</td>
                <td class="text-center">{{ $row['correct_count'] }}</td>
                <td class="text-center">{{ $row['wrong_count'] }}</td>
                <td class="text-center font-bold">{{ number_format($row['nilai'], 1) }}</td>
                <td class="text-center {{ $row['is_tuntas'] ? 'font-bold' : 'text-red' }}">
                    {{ $row['ket'] }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="text-center">Belum ada data pengerjaan siswa.</td>
            </tr>
            @endforelse

            @if(count($student_rows) > 0)
            <tr class="summary-row bg-gray-header font-bold">
                <td colspan="4" class="text-right" style="padding-right: 10px;">JUMLAH :</td>
                <td class="text-center">{{ $stats['sum_correct'] }}</td>
                <td class="text-center">{{ $stats['sum_wrong'] }}</td>
                <td class="text-center">{{ number_format($stats['sum_nilai'], 1) }}</td>
                <td></td>
            </tr>
            <tr class="summary-row font-bold">
                <td colspan="4" class="text-right" style="padding-right: 10px;">TERKECIL :</td>
                <td class="text-center">{{ $stats['min_correct'] }}</td>
                <td class="text-center">{{ $stats['min_wrong'] }}</td>
                <td class="text-center">{{ number_format($stats['min_nilai'], 1) }}</td>
                <td></td>
            </tr>
            <tr class="summary-row font-bold">
                <td colspan="4" class="text-right" style="padding-right: 10px;">TERBESAR :</td>
                <td class="text-center">{{ $stats['max_correct'] }}</td>
                <td class="text-center">{{ $stats['max_wrong'] }}</td>
                <td class="text-center">{{ number_format($stats['max_nilai'], 1) }}</td>
                <td></td>
            </tr>
            <tr class="summary-row bg-yellow-accent font-bold">
                <td colspan="4" class="text-right" style="padding-right: 10px;">RATA-RATA :</td>
                <td class="text-center">{{ number_format($stats['avg_correct'], 1) }}</td>
                <td class="text-center">{{ number_format($stats['avg_wrong'], 1) }}</td>
                <td class="text-center">{{ number_format($stats['avg_nilai'], 1) }}</td>
                <td></td>
            </tr>
            <tr class="summary-row font-bold">
                <td colspan="4" class="text-right" style="padding-right: 10px;">STANDAR DEVIASI :</td>
                <td colspan="2"></td>
                <td class="text-center">{{ number_format($stats['std_nilai'], 2) }}</td>
                <td></td>
            </tr>
            @endif
        </tbody>
    </table>

    <div style="font-size: 7.5pt; color: #555; margin-top: 4px;">
        * Halaman 1 dari 3 - Laporan Data Jawaban Siswa
    </div>

    <!-- PEMISAH HALAMAN 1 -> 2 -->
    <div class="page-break"></div>

    <!-- ========================================================================= -->
    <!-- BAGIAN 2: MATRIKS DIKOTOMI JAWABAN (0 & 1) -->
    <!-- ========================================================================= -->
    <div class="header-title">REPORT MATRIKS DIKOTOMI JAWABAN (0 & 1)</div>
    <div class="header-subtitle">{{ $school_name }} - {{ $bank->name }} ({{ $classroom_names }})</div>

    <table class="student-table" style="margin-top: 6px;">
        <thead>
            <tr>
                <th style="width: 3%;" rowspan="2">NO</th>
                <th style="width: 18%;" rowspan="2">NAMA SISWA</th>
                <th style="width: 3%;" rowspan="2">L/P</th>
                <th colspan="{{ count($questions) }}">NOMOR BUTIR SOAL</th>
                <th style="width: 5%;" rowspan="2">BENAR</th>
                <th style="width: 5%;" rowspan="2">SALAH</th>
                <th style="width: 6%;" rowspan="2">NILAI</th>
            </tr>
            <tr>
                @foreach($questions as $qIdx => $q)
                <th style="padding: 1px 2px; font-size: 7pt; width: {{ 60 / max(1, count($questions)) }}%;">{{ $qIdx + 1 }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($student_rows as $idx => $row)
            <tr>
                <td class="text-center">{{ $idx + 1 }}</td>
                <td style="font-weight: bold; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 150px;">{{ $row['name'] }}</td>
                <td class="text-center">{{ $row['gender'] }}</td>
                @foreach($row['dichotomous_arr'] as $dVal)
                <td class="dichotomous-cell {{ $dVal === '1' ? 'font-bold text-blue' : 'text-red' }}">{{ $dVal }}</td>
                @endforeach
                <td class="text-center font-bold">{{ $row['correct_count'] }}</td>
                <td class="text-center">{{ $row['wrong_count'] }}</td>
                <td class="text-center font-bold">{{ number_format($row['nilai'], 1) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="{{ 6 + count($questions) }}" class="text-center">Belum ada data pengerjaan siswa.</td>
            </tr>
            @endforelse

            @if(count($student_rows) > 0)
            <tr class="summary-row bg-gray-header font-bold">
                <td colspan="3" class="text-right" style="padding-right: 5px;">RATA-RATA:</td>
                @foreach($questions as $qIdx => $q)
                @php
                    $qCorrects = 0;
                    foreach($student_rows as $sr) {
                        if (($sr['dichotomous_arr'][$qIdx] ?? '0') === '1') $qCorrects++;
                    }
                    $qRate = count($student_rows) > 0 ? round(($qCorrects / count($student_rows)), 2) : 0;
                @endphp
                <td class="dichotomous-cell font-bold" style="font-size: 6.5pt;">{{ $qRate }}</td>
                @endforeach
                <td class="text-center">{{ number_format($stats['avg_correct'], 1) }}</td>
                <td class="text-center">{{ number_format($stats['avg_wrong'], 1) }}</td>
                <td class="text-center">{{ number_format($stats['avg_nilai'], 1) }}</td>
            </tr>
            @endif
        </tbody>
    </table>

    <div style="font-size: 7.5pt; color: #555; margin-top: 4px;">
        * Halaman 2 dari 3 - Matriks Dikotomi Jawaban (1 = Benar, 0 = Salah)
    </div>

    <!-- PEMISAH HALAMAN 2 -> 3 -->
    <div class="page-break"></div>

    <!-- ========================================================================= -->
    <!-- BAGIAN 3: DAFTAR NILAI & STATISTIK REKAPITULASI RESMI -->
    <!-- ========================================================================= -->
    <div class="header-title">DAFTAR NILAI DAN KETUNTASAN SISWA</div>
    <div class="header-subtitle">{{ $school_name }} - {{ $bank->name }}</div>

    <table class="meta-table" style="margin-top: 6px;">
        <tr>
            <td style="width: 15%;">MATA PELAJARAN</td>
            <td style="width: 1%;">:</td>
            <td style="width: 34%;">{{ $bank->subject->name ?? '-' }}</td>
            <td style="width: 15%;">BATAS KELULUSAN (KKM)</td>
            <td style="width: 1%;">:</td>
            <td style="width: 34%; font-weight: bold; color: #002060;">70 (Tujuh Puluh)</td>
        </tr>
        <tr>
            <td>KELAS / PROGRAM</td>
            <td>:</td>
            <td>{{ $classroom_names }}</td>
            <td>TANGGAL UJIAN</td>
            <td>:</td>
            <td>{{ $exam_date }}</td>
        </tr>
    </table>

    <table class="student-table">
        <thead>
            <tr>
                <th style="width: 3%;">NO</th>
                <th style="width: 25%;">NAMA SISWA</th>
                <th style="width: 4%;">L/P</th>
                <th style="width: 10%;">JML BENAR</th>
                <th style="width: 10%;">JML SALAH</th>
                <th style="width: 12%;">NILAI AKHIR</th>
                <th style="width: 15%;">STATUS KELULUSAN</th>
                <th style="width: 21%;">KETERANGAN</th>
            </tr>
        </thead>
        <tbody>
            @forelse($student_rows as $idx => $row)
            <tr>
                <td class="text-center">{{ $idx + 1 }}</td>
                <td style="font-weight: bold;">{{ $row['name'] }}</td>
                <td class="text-center">{{ $row['gender'] }}</td>
                <td class="text-center font-bold">{{ $row['correct_count'] }}</td>
                <td class="text-center">{{ $row['wrong_count'] }}</td>
                <td class="text-center font-bold" style="font-size: 9pt;">{{ number_format($row['nilai'], 1) }}</td>
                <td class="text-center font-bold {{ $row['is_tuntas'] ? 'text-blue' : 'text-red' }}">
                    {{ $row['is_tuntas'] ? 'LULUS / TUNTAS' : 'BELUM TUNTAS' }}
                </td>
                <td>{{ $row['is_tuntas'] ? 'Mencapai KKM' : 'Perlu Remedial' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="text-center">Belum ada data nilai siswa.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <!-- Rekapitulasi Statistik Kelulusan -->
    <table class="student-table" style="margin-top: 8px; font-size: 8pt;">
        <tr class="bg-gray-header font-bold">
            <td colspan="4" class="text-center">REKAPITULASI HASIL EVALUASI</td>
        </tr>
        <tr>
            <td style="width: 25%;">Jumlah Peserta Ujian</td>
            <td style="width: 25%;" class="font-bold">: {{ count($student_rows) }} Siswa</td>
            <td style="width: 25%;">Rata-rata Nilai</td>
            <td style="width: 25%;" class="font-bold">: {{ number_format($stats['avg_nilai'], 2) }}</td>
        </tr>
        <tr>
            <td>Jumlah Siswa Tuntas (>= 70)</td>
            <td class="font-bold text-blue">: {{ $stats['lulus'] ?? collect($student_rows)->where('is_tuntas', true)->count() }} Siswa</td>
            <td>Nilai Tertinggi / Terendah</td>
            <td class="font-bold">: {{ number_format($stats['max_nilai'], 1) }} / {{ number_format($stats['min_nilai'], 1) }}</td>
        </tr>
        <tr>
            <td>Jumlah Siswa Belum Tuntas (< 70)</td>
            <td class="font-bold text-red">: {{ $stats['tidak_lulus'] ?? collect($student_rows)->where('is_tuntas', false)->count() }} Siswa</td>
            <td>Standar Deviasi</td>
            <td class="font-bold">: {{ number_format($stats['std_nilai'], 2) }}</td>
        </tr>
    </table>

    <!-- Tanda Tangan Footer -->
    <table class="footer-sig-table">
        <tr>
            <td style="width: 50%; padding-left: 20px;">
                Mengetahui,<br>
                Kepala Sekolah {{ $school_name }}<br><br><br><br><br>
                <b><u>{{ $headmaster_name }}</u></b><br>
                NIP. {{ $headmaster_nip }}
            </td>
            <td style="width: 50%; text-align: right; padding-right: 20px;">
                {{ $school_city }}, {{ $exam_date }}<br>
                Guru Mata Pelajaran,<br><br><br><br><br>
                <b><u>{{ $exam->teacher->user->name ?? $exam->teacher->name ?? 'Guru Pengampu' }}</u></b><br>
                NIP. {{ $exam->teacher->nip ?? '-' }}
            </td>
        </tr>
    </table>

    <div style="font-size: 7.5pt; color: #555; margin-top: 6px;">
        * Halaman 3 dari 3 - Daftar Nilai & Lembar Pengesahan
    </div>

</body>
</html>
