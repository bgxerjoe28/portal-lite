<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Jurnal Mengajar - {{ $teacher->full_name }}</title>
    <style>
        @page {
            margin: 0.8cm 1cm 1cm 1cm;
            size: A4 portrait;
        }
        body { 
            font-family: 'Helvetica', 'Arial', sans-serif; 
            font-size: 8pt; 
            color: #222; 
            margin: 0;
            padding: 0;
            line-height: 1.25;
        }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
        
        .header { 
            text-align: center;
            margin-bottom: 10px; 
            border-bottom: 2px solid #000; 
            padding-bottom: 6px; 
        }
        .header h2 { margin: 0; font-size: 12pt; font-weight: bold; letter-spacing: 0.5px; }
        .header h3 { margin: 2px 0; font-size: 10pt; font-weight: bold; }
        .header p { margin: 3px 0 0 0; font-size: 8.5pt; color: #333; }
        
        table.agenda-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 12px; 
            page-break-inside: avoid;
        }
        table.agenda-table th, table.agenda-table td { 
            border: 1px solid #444; 
            padding: 4px 5px; 
            vertical-align: top;
        }
        table.agenda-table th { 
            background-color: #f1f3f5; 
            font-weight: bold; 
            text-transform: uppercase; 
            font-size: 7pt; 
            text-align: center;
            vertical-align: middle;
        }
        
        .day-header { 
            background-color: #e3e8ee; 
            font-weight: bold; 
            padding: 3px 6px; 
            border: 1px solid #444; 
            border-bottom: none;
            font-size: 8pt;
            color: #1a202c;
        }
        
        .footer-table {
            width: 100%;
            border: none !important;
            margin-top: 20px;
            page-break-inside: avoid;
        }
        .footer-table td {
            border: none !important;
            width: 50%;
            vertical-align: top;
            text-align: center;
            font-size: 8.5pt;
        }
        .spacer {
            height: 55px;
        }
        
        .badge-s { color: #1e40af; font-weight: bold; }
        .badge-i { color: #b45309; font-weight: bold; }
        .badge-d { color: #6b21a8; font-weight: bold; }
        .badge-a { color: #b91c1c; font-weight: bold; }
        .badge-h { color: #15803d; font-weight: bold; }

        .absent-list {
            margin: 0;
            padding-left: 10px;
            font-size: 7pt;
            line-height: 1.25;
        }
        .absent-list li {
            margin-bottom: 1px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>JURNAL MENGAJAR GURU</h2>
        <h3>SMA NEGERI 16 SEMARANG</h3>
        <p>Bulan: <strong>{{ $monthName }} {{ $year }}</strong> | Guru: <strong>{{ $teacher->full_name }}</strong></p>
    </div>

    @foreach($reports as $date => $dayAgendas)
        <div class="day-header">
            Hari/Tanggal: {{ \Carbon\Carbon::parse($date)->translatedFormat('l, d F Y') }}
        </div>
        <table class="agenda-table">
            <thead>
                <tr>
                    <th rowspan="2" width="6%">Jam</th>
                    <th rowspan="2" width="10%">Kelas</th>
                    <th rowspan="2" width="16%">Mapel</th>
                    <th rowspan="2" width="26%">Materi & Tujuan Pembelajaran</th>
                    <th colspan="6" width="24%">Presensi</th>
                    <th rowspan="2" width="18%">Siswa Tidak Hadir</th>
                </tr>
                <tr>
                    <th width="4%">Jml</th>
                    <th width="4%" class="badge-h">H</th>
                    <th width="4%" class="badge-s">S</th>
                    <th width="4%" class="badge-i">I</th>
                    <th width="4%" class="badge-d">D</th>
                    <th width="4%" class="badge-a">A</th>
                </tr>
            </thead>
            <tbody>
                @foreach($dayAgendas as $agenda)
                <tr>
                    <td class="text-center" style="font-weight: bold;">
                        Ke-{{ $agenda->start_slot ?? $agenda->scheduleDetail?->start_slot ?? '-' }}
                    </td>
                    <td class="text-center font-bold">{{ $agenda->classroom?->name }}</td>
                    <td>{{ $agenda->subject?->name }}</td>
                    <td>
                        @if($agenda->tp?->kode_tp)
                            <div style="font-size: 7.5pt; color: #0284c7; font-weight: bold;">{{ $agenda->tp->kode_tp }}</div>
                        @endif
                        <div>{{ $agenda->materi_pembelajaran }}</div>
                    </td>
                    <td class="text-center font-bold">{{ $agenda->attendances_count }}</td>
                    <td class="text-center badge-h">{{ $agenda->hadir_count }}</td>
                    <td class="text-center {{ $agenda->sakit_count > 0 ? 'badge-s' : '' }}">{{ $agenda->sakit_count }}</td>
                    <td class="text-center {{ $agenda->izin_count > 0 ? 'badge-i' : '' }}">{{ $agenda->izin_count }}</td>
                    <td class="text-center {{ $agenda->dispen_count > 0 ? 'badge-d' : '' }}">{{ $agenda->dispen_count }}</td>
                    <td class="text-center {{ $agenda->alpa_count > 0 ? 'badge-a' : '' }}">{{ $agenda->alpa_count }}</td>
                    <td>
                        @if(!empty($agenda->absent_students))
                            <ul class="absent-list">
                                @foreach($agenda->absent_students as $absent)
                                    <li>{{ $absent }}</li>
                                @endforeach
                            </ul>
                        @else
                            <span style="color: #15803d; font-style: italic; font-size: 7.5pt;">- (Hadir Semua)</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach

    <table class="footer-table">
        <tr>
            <td>
                <br>
                Guru Mata Pelajaran,
                <div class="spacer"></div>
                <strong>{{ $teacher->full_name }}</strong><br>
                NIP. {{ $teacher->nip ?? '-' }}
            </td>
            <td>
                Semarang, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}<br>
                Kepala SMA Negeri 16 Semarang
                <div class="spacer"></div>
                <strong>Tarisno, S.Pd., M.Pd.</strong><br>
                NIP. 197509242007011009
            </td>
        </tr>
    </table>
</body>
</html>