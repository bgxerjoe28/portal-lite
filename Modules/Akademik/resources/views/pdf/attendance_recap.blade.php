<!DOCTYPE html>
<html lang="id">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Rekap Presensi - {{ $subject->name }} - {{ $classroom->name }}</title>
    <style>
        @page {
            margin: 1.2cm 1.5cm 1.5cm 1.5cm;
            size: A4 landscape;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 8.5pt;
            line-height: 1.3;
            color: #111;
            margin: 0;
            padding: 0;
        }

        /* KOP SURAT */
        .kop-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2px;
        }
        .kop-table td {
            vertical-align: middle;
            text-align: center;
        }
        .kop-table .logo-col {
            width: 65px;
        }
        .kop-table .text-col {
            padding: 0 10px;
        }
        .kop-pemprov {
            font-size: 11pt;
            font-weight: normal;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0;
        }
        .kop-dinas {
            font-size: 11pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 1px 0;
        }
        .kop-sekolah {
            font-size: 13pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 2px 0;
        }
        .kop-kontak {
            font-size: 7.5pt;
            margin-top: 2px;
            color: #333;
            line-height: 1.2;
        }
        .kop-divider-thick {
            border-top: 2px solid #000;
            margin-top: 4px;
            margin-bottom: 1px;
        }
        .kop-divider-thin {
            border-top: 1px solid #000;
            margin-bottom: 12px;
        }

        /* TITLE & METADATA */
        .report-title {
            text-align: center;
            margin-bottom: 12px;
        }
        .report-title h2 {
            margin: 0;
            font-size: 12pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            font-size: 8.5pt;
        }
        .meta-table td {
            padding: 2px 4px;
            vertical-align: top;
        }

        /* MAIN TABLE */
        .main-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .main-table th, .main-table td {
            border: 1px solid #222;
            padding: 4px 3px;
            font-size: 7.5pt;
        }
        .main-table th {
            background-color: #f1f5f9;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
            vertical-align: middle;
        }
        .main-table td.text-center {
            text-align: center;
        }
        .main-table td.text-left {
            text-align: left;
            padding-left: 6px;
        }

        .bg-hadir {
            background-color: #e6f4ea;
            color: #137333;
            font-weight: bold;
        }
        .bg-sakit {
            background-color: #e8f0fe;
            color: #1a73e8;
            font-weight: bold;
        }
        .bg-izin {
            background-color: #fef7e0;
            color: #b06000;
            font-weight: bold;
        }
        .bg-alpa {
            background-color: #fce8e6;
            color: #c5221f;
            font-weight: bold;
        }

        /* LEGEND & SIGNATURE */
        .footer-container {
            width: 100%;
            margin-top: 15px;
            page-break-inside: avoid;
        }
        .footer-table {
            width: 100%;
            border-collapse: collapse;
        }
        .footer-table td {
            vertical-align: top;
        }

        .legend-box {
            font-size: 7.5pt;
            color: #333;
            line-height: 1.4;
        }
        .legend-title {
            font-weight: bold;
            margin-bottom: 3px;
            text-transform: uppercase;
        }

        .signature-box {
            width: 250px;
            float: right;
            text-align: center;
            font-size: 8.5pt;
        }
        .signature-spacer {
            height: 60px;
        }
        .signature-name {
            font-weight: bold;
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <!-- KOP SURAT RESMI -->
    <table class="kop-table">
        <tr>
            <td class="logo-col">
                @if(!empty($kop['logo_pemda_base64']))
                    <img src="{{ $kop['logo_pemda_base64'] }}" width="55" alt="Logo Pemda" />
                @endif
            </td>
            <td class="text-col">
                <div class="kop-pemprov">{{ strtoupper($kop['kop_pemprov'] ?? 'PEMERINTAH PROVINSI JAWA TENGAH') }}</div>
                <div class="kop-dinas">{{ strtoupper($kop['kop_dinas'] ?? 'DINAS PENDIDIKAN DAN KEBUDAYAAN') }}</div>
                <div class="kop-sekolah">{{ strtoupper($kop['school_name'] ?? 'SMA NEGERI 16 SEMARANG') }}</div>
                <div class="kop-kontak">
                    {{ $kop['school_address'] ?? '' }} {{ $kop['school_city'] ?? '' }} {{ $kop['school_province'] ?? '' }} {{ !empty($kop['school_postal_code']) ? 'Kode Pos ' . $kop['school_postal_code'] : '' }}
                    @if(!empty($kop['school_phone'])) | Telp: {{ $kop['school_phone'] }} @endif
                    @if(!empty($kop['school_email'])) | Pos-el: {{ $kop['school_email'] }} @endif
                </div>
            </td>
            <td class="logo-col">
                @if(!empty($kop['logo_sekolah_base64']))
                    <img src="{{ $kop['logo_sekolah_base64'] }}" width="55" alt="Logo Sekolah" />
                @endif
            </td>
        </tr>
    </table>
    <div class="kop-divider-thick"></div>
    <div class="kop-divider-thin"></div>

    <!-- JUDUL LAPORAN -->
    <div class="report-title">
        <h2>REKAPITULASI PRESENSI MATA PELAJARAN {{ strtoupper($subject->name) }}</h2>
    </div>

    <!-- METADATA -->
    <table class="meta-table">
        <tr>
            <td style="width: 14%; font-weight: bold;">Kelas</td>
            <td style="width: 2%;">:</td>
            <td style="width: 34%;">{{ $classroom->name }}</td>
            <td style="width: 14%; font-weight: bold;">Guru Pengampu</td>
            <td style="width: 2%;">:</td>
            <td style="width: 34%;">{{ $teacher->full_name }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Mata Pelajaran</td>
            <td>:</td>
            <td>{{ $subject->name }}</td>
            <td style="font-weight: bold;">Cakupan Periode</td>
            <td>:</td>
            <td>{{ $periodLabel }}</td>
        </tr>
    </table>

    <!-- TABEL UTAMA REKAP PRESENSI -->
    <table class="main-table">
        <thead>
            <tr>
                <th rowspan="2" style="width: 25px;">No</th>
                <th rowspan="2" style="min-width: 160px;">Nama Siswa</th>
                <th rowspan="2" style="width: 65px;">NISN</th>
                @forelse($agendaHeaders as $h)
                    <th rowspan="2" style="width: 28px;">
                        P{{ $h['meeting_no'] }}<br>
                        <span style="font-size: 6.5pt; font-weight: normal;">{{ $h['formatted_date'] }}</span>
                    </th>
                @empty
                    <th rowspan="2" style="font-size: 7pt; color: #666;">Belum Ada Pertemuan</th>
                @endforelse
                <th rowspan="2" style="width: 25px; background-color: #d1e7dd;">H</th>
                <th colspan="3" style="background-color: #f8d7da;">TH</th>
                <th rowspan="2" style="width: 28px; background-color: #cff4fc;">Jml</th>
                <th rowspan="2" style="width: 45px;">Ket</th>
            </tr>
            <tr>
                <th style="width: 22px; background-color: #fce8e6;">S</th>
                <th style="width: 22px; background-color: #fce8e6;">I</th>
                <th style="width: 22px; background-color: #fce8e6;">A</th>
            </tr>
        </thead>
        <tbody>
            @forelse($students as $idx => $student)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td class="text-left">{{ $student['full_name'] }}</td>
                    <td class="text-center">{{ $student['nisn'] }}</td>
                    @forelse($agendaHeaders as $h)
                        @php
                            $st = $student['presensi'][$h['id']] ?? '-';
                            $cellClass = 'text-center';
                            if (in_array($st, ['H', 'T'])) $cellClass .= ' bg-hadir';
                            elseif ($st === 'S') $cellClass .= ' bg-sakit';
                            elseif (in_array($st, ['I', 'D'])) $cellClass .= ' bg-izin';
                            elseif ($st === 'A') $cellClass .= ' bg-alpa';
                        @endphp
                        <td class="{{ $cellClass }}">{{ $st }}</td>
                    @empty
                        <td class="text-center">-</td>
                    @endforelse
                    <td class="text-center" style="font-weight: bold; color: green;">{{ $student['h_count'] }}</td>
                    <td class="text-center" style="{{ $student['s_count'] > 0 ? 'font-weight: bold; color: #1a73e8;' : '' }}">{{ $student['s_count'] }}</td>
                    <td class="text-center" style="{{ $student['i_count'] > 0 ? 'font-weight: bold; color: #b06000;' : '' }}">{{ $student['i_count'] }}</td>
                    <td class="text-center" style="{{ $student['a_count'] > 0 ? 'font-weight: bold; color: #c5221f;' : '' }}">{{ $student['a_count'] }}</td>
                    <td class="text-center" style="font-weight: bold;">{{ $student['total_count'] }}</td>
                    <td class="text-center" style="font-size: 7pt;">{{ $student['keterangan'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ 8 + max(1, count($agendaHeaders)) }}" class="text-center" style="padding: 15px; color: #666;">
                        Tidak ada data siswa ditemukan di kelas ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if(count($students) > 0 && count($agendaHeaders) > 0)
        <tfoot>
            <tr style="background-color: #f8fafc; font-weight: bold;">
                <td colspan="3" class="text-center">Jumlah Hadir (H)</td>
                @foreach($agendaSummary as $sum)
                    <td class="text-center" style="color: green;">{{ $sum['hadir'] }}</td>
                @endforeach
                <td colspan="6" class="text-center" style="font-size: 7pt; color: #555;">
                    Total: {{ count($students) }} Siswa
                </td>
            </tr>
            <tr style="background-color: #f8fafc; font-weight: bold;">
                <td colspan="3" class="text-center">Jumlah Tidak Hadir (TH)</td>
                @foreach($agendaSummary as $sum)
                    <td class="text-center" style="color: red;">{{ $sum['tidak_hadir'] }}</td>
                @endforeach
                <td colspan="6"></td>
            </tr>
        </tfoot>
        @endif
    </table>

    <!-- FOOTER: KETERANGAN & TANDA TANGAN -->
    <div class="footer-container">
        <table class="footer-table">
            <tr>
                <td style="width: 50%;">
                    <div class="legend-box">
                        <div class="legend-title">Keterangan Singkatan:</div>
                        <table>
                            <tr>
                                <td style="padding: 1px 8px 1px 0;"><strong>H</strong> = Hadir</td>
                                <td style="padding: 1px 8px 1px 0;"><strong>S</strong> = Sakit</td>
                                <td style="padding: 1px 8px 1px 0;"><strong>I</strong> = Izin</td>
                            </tr>
                            <tr>
                                <td style="padding: 1px 8px 1px 0;"><strong>A</strong> = Alpa (Tanpa Keterangan)</td>
                                <td style="padding: 1px 8px 1px 0;"><strong>TH</strong> = Tidak Hadir (S + I + A)</td>
                                <td style="padding: 1px 8px 1px 0;"><strong>Jml</strong> = Total Pertemuan Terjadwal</td>
                            </tr>
                        </table>
                    </div>
                </td>
                <td style="width: 50%;">
                    <div class="signature-box">
                        <div>{{ $city }}, {{ $printDate }}</div>
                        <div>Guru Mata Pelajaran {{ $subject->name }}</div>
                        <div class="signature-spacer"></div>
                        <div class="signature-name">{{ $teacher->full_name }}</div>
                        <div>NIP. {{ $teacher->nip ?? '-' }}</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

</body>
</html>
