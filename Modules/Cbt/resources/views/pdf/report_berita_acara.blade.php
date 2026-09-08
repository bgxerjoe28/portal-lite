<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>BERITA ACARA & REKAP PELAKSANAAN CBT - {{ $exam->title }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 10mm 12mm;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10pt;
            color: #000;
            background-color: #fff;
            margin: 0;
            padding: 5px;
            line-height: 1.3;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }

        .doc-title {
            text-align: center;
            font-weight: bold;
            font-size: 13pt;
            text-transform: uppercase;
            margin-top: 5px;
            margin-bottom: 3px;
        }
        .doc-subtitle {
            text-align: center;
            font-weight: bold;
            font-size: 10pt;
            text-transform: uppercase;
            margin-bottom: 15px;
            text-decoration: underline;
        }

        .section-desc {
            margin-bottom: 12px;
            text-align: justify;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 9.5pt;
        }
        .meta-table td {
            padding: 3px 4px;
            vertical-align: top;
        }

        .rekap-summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 9.5pt;
        }
        .rekap-summary-table th,
        .rekap-summary-table td {
            border: 1px solid #000;
            padding: 5px 8px;
        }
        .rekap-summary-table th {
            background-color: #f2f2f2;
            font-weight: bold;
            text-align: center;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 9pt;
        }
        .data-table th,
        .data-table td {
            border: 1px solid #000;
            padding: 4px 6px;
        }
        .data-table th {
            background-color: #f2f2f2;
            font-weight: bold;
            text-align: center;
        }

        .notes-box {
            border: 1px solid #000;
            padding: 8px 10px;
            min-height: 55px;
            margin-bottom: 20px;
            font-size: 9.5pt;
            background-color: #fafafa;
        }

        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
            page-break-inside: avoid;
            font-size: 9.5pt;
        }
        .signature-table td {
            vertical-align: top;
            padding: 0 10px;
        }

        .page-break {
            page-break-before: always;
        }

        .no-print {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 10px;
            gap: 8px;
        }
        .print-btn {
            background-color: #2563eb;
            color: #fff;
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            font-size: 13px;
        }
        .close-btn {
            background-color: #64748b;
            color: #fff;
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            font-size: 13px;
        }

        @media print {
            .no-print {
                display: none !important;
            }
            body {
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button class="print-btn" onclick="window.print()">🖨️ Cetak / Unduh PDF</button>
        <button class="close-btn" onclick="window.close()">Tutup</button>
    </div>

    <!-- ================= HALAMAN 1: BERITA ACARA PELAKSANAAN ================= -->
    @include('components.kop-surat', ['kop' => $kop])

    <div class="doc-title">BERITA ACARA PELAKSANAAN</div>
    <div class="doc-subtitle">ASESMEN / UJIAN COMPUTER BASED TEST (CBT)</div>

    <div class="section-desc">
        Pada hari ini <strong>{{ $exam_date }}</strong>, telah diselenggarakan pelaksanaan ujian Computer Based Test (CBT) untuk:
    </div>

    <table class="meta-table">
        <tr>
            <td style="width: 25%;"><strong>Nama Ujian / Asesmen</strong></td>
            <td style="width: 2%;">:</td>
            <td style="width: 73%;"><strong>{{ $exam->title }}</strong></td>
        </tr>
        <tr>
            <td><strong>Mata Pelajaran</strong></td>
            <td>:</td>
            <td>{{ $bank->subject->name ?? ($bank->title ?? 'Mata Pelajaran Umum') }}</td>
        </tr>
        <tr>
            <td><strong>Kelas / Rombongan Belajar</strong></td>
            <td>:</td>
            <td>{{ $classroom_names }}</td>
        </tr>
        <tr>
            <td><strong>Alokasi Waktu & Durasi</strong></td>
            <td>:</td>
            <td>{{ $exam_time_range }} (Durasi: {{ $exam->duration }} Menit)</td>
        </tr>
        <tr>
            <td><strong>Guru Pengampu / Pelaksana</strong></td>
            <td>:</td>
            <td>{{ $teacher_name }}</td>
        </tr>
    </table>

    <div class="font-bold" style="margin-bottom: 6px;">I. REKAPITULASI KEHADIRAN PESERTA UJIAN</div>
    <table class="rekap-summary-table">
        <thead>
            <tr>
                <th style="width: 25%;">Peserta Terdaftar</th>
                <th style="width: 25%;">Peserta Hadir</th>
                <th style="width: 25%;">Peserta Tidak Hadir</th>
                <th style="width: 25%;">Persentase Kehadiran</th>
            </tr>
        </thead>
        <tbody>
            <tr class="text-center font-bold">
                <td style="font-size: 11pt;">{{ $total_students }} Siswa</td>
                <td style="font-size: 11pt; color: #15803d;">{{ $present_count }} Siswa</td>
                <td style="font-size: 11pt; color: {{ $absent_count > 0 ? '#b91c1c' : '#000' }};">{{ $absent_count }} Siswa</td>
                <td style="font-size: 11pt; color: #1d4ed8;">{{ $present_percentage }}%</td>
            </tr>
        </tbody>
    </table>

    @if($absent_count > 0)
        <div class="font-bold" style="margin-bottom: 6px; margin-top: 10px;">II. DAFTAR SISWA YANG TIDAK HADIR / BELUM MENGERJAKAN</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 35px;">No</th>
                    <th style="width: 90px;">NIS</th>
                    <th>Nama Siswa</th>
                    <th style="width: 110px;">Kelas</th>
                    <th style="width: 150px;">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($absent_students as $idx => $abs)
                    <tr>
                        <td class="text-center">{{ $idx + 1 }}</td>
                        <td class="text-center">{{ $abs['nis'] }}</td>
                        <td>{{ $abs['name'] }}</td>
                        <td class="text-center">{{ $abs['classroom'] }}</td>
                        <td class="text-center font-bold" style="color: #b91c1c;">{{ $abs['status_label'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="font-bold" style="margin-bottom: 6px; margin-top: 10px;">II. CATATAN KEHADIRAN</div>
        <div style="font-style: italic; margin-bottom: 15px; color: #15803d;">
            ✓ Seluruh peserta yang terdaftar (100%) hadir dan telah mengikuti ujian ini.
        </div>
    @endif

    <div class="font-bold" style="margin-bottom: 6px;">III. CATATAN KEJADIAN SELAMA PELAKSANAAN UJIAN</div>
    <div class="notes-box">
        {!! nl2br(e($notes ?? 'Ujian Computer Based Test (CBT) telah dilaksanakan secara tertib, lancar, dan sesuai dengan petunjuk teknis pelaksanaan asesmen sekolah.')) !!}
    </div>

    <table class="signature-table">
        <tr>
            <td style="width: 50%; text-align: center;">
                Mengetahui,<br>
                Kepala Sekolah
                <br><br><br><br><br>
                <strong><u>{{ $headmaster_name }}</u></strong><br>
                NIP. {{ $headmaster_nip }}
            </td>
            <td style="width: 50%; text-align: center;">
                {{ $school_city }}, {{ $exam_date }}<br>
                Guru Pengampu / Proktor
                <br><br><br><br><br>
                <strong><u>{{ $teacher_name }}</u></strong><br>
                NIP. {{ $teacher_nip }}
            </td>
        </tr>
    </table>

    <!-- ================= HALAMAN 2: LAMPIRAN DAFTAR HADIR PESERTA ================= -->
    <div class="page-break"></div>

    @include('components.kop-surat', ['kop' => $kop])

    <div class="doc-title">LAMPIRAN DAFTAR HADIR & STATUS PESERTA</div>
    <div class="doc-subtitle">{{ $exam->title }}</div>

    <table class="meta-table" style="margin-bottom: 10px;">
        <tr>
            <td style="width: 18%;"><strong>Mata Pelajaran</strong></td>
            <td style="width: 2%;">:</td>
            <td style="width: 40%;">{{ $bank->subject->name ?? ($bank->title ?? '-') }}</td>
            <td style="width: 15%;"><strong>Hari / Tanggal</strong></td>
            <td style="width: 2%;">:</td>
            <td style="width: 23%;">{{ $exam_date }}</td>
        </tr>
        <tr>
            <td><strong>Kelas / Rombel</strong></td>
            <td>:</td>
            <td>{{ $classroom_names }}</td>
            <td><strong>Alokasi Waktu</strong></td>
            <td>:</td>
            <td>{{ $exam_time_range }}</td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 25px;">No</th>
                <th style="width: 70px;">NIS</th>
                <th>Nama Siswa</th>
                <th style="width: 65px;">Kelas</th>
                <th style="width: 100px;">Status</th>
                <th style="width: 65px;">Masuk</th>
                <th style="width: 65px;">Selesai</th>
                <th style="width: 50px;">Nilai</th>
                <th style="width: 70px;">Paraf</th>
            </tr>
        </thead>
        <tbody>
            @foreach($all_student_rows as $row)
                <tr>
                    <td class="text-center">{{ $row['no'] }}</td>
                    <td class="text-center">{{ $row['nis'] }}</td>
                    <td>{{ $row['name'] }}</td>
                    <td class="text-center">{{ $row['classroom'] }}</td>
                    <td class="text-center" style="font-size: 8pt; color: {{ $row['is_present'] ? '#15803d' : '#b91c1c' }}; font-weight: bold;">
                        {{ $row['status_label'] }}
                    </td>
                    <td class="text-center" style="font-size: 8pt;">{{ $row['started_at'] }}</td>
                    <td class="text-center" style="font-size: 8pt;">{{ $row['submitted_at'] }}</td>
                    <td class="text-center font-bold">{{ $row['score'] }}</td>
                    <td class="text-center" style="font-size: 8pt; color: #64748b;">
                        {{ $row['no'] % 2 == 1 ? $row['no'] . '. .......' : '   ' . $row['no'] . '. .......' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="signature-table" style="margin-top: 15px;">
        <tr>
            <td style="width: 60%;"></td>
            <td style="width: 40%; text-align: center;">
                {{ $school_city }}, {{ $exam_date }}<br>
                Guru Pengampu / Proktor
                <br><br><br><br>
                <strong><u>{{ $teacher_name }}</u></strong><br>
                NIP. {{ $teacher_nip }}
            </td>
        </tr>
    </table>
</body>
</html>
