<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Rekap Laporan Peminjaman Sarana & Prasarana</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 8.5pt; color: #222; margin: 15px; }
        .text-center { text-align: center; }
        .header { border-bottom: 2px solid #000; padding-bottom: 8px; margin-bottom: 15px; }
        .header h2 { margin: 0; font-size: 13pt; text-transform: uppercase; }
        .header h3 { margin: 2px 0; font-size: 10.5pt; color: #1e3a8a; }
        .header p { margin: 0; font-size: 8pt; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #6b7280; padding: 5px 6px; }
        th { background-color: #f3f4f6; font-weight: bold; text-transform: uppercase; font-size: 7.5pt; }
        .badge { display: inline-block; padding: 2px 5px; font-size: 7.5pt; border-radius: 3px; font-weight: bold; }
        .badge-approved { background-color: #dbeafe; color: #1e40af; }
        .badge-in_use { background-color: #ede9fe; color: #6b21a8; }
        .badge-completed { background-color: #d1fae5; color: #065f46; }
        .badge-rejected { background-color: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <div class="header text-center">
        <h2>LAPORAN REKAPITULASI PEMINJAMAN RUANG & ASET</h2>
        <h3>SMA NEGERI 16 SEMARANG</h3>
        <p>Dicetak pada: {{ date('d F Y H:i') }} | Total Data: {{ count($reservations) }} Peminjaman</p>
    </div>

    <table>
        <thead>
            <tr>
                <th width="4%">No</th>
                <th width="14%">Kode / Waktu</th>
                <th width="16%">Pemohon / Eskul</th>
                <th width="20%">Nama Kegiatan</th>
                <th width="18%">Ruang & Aset</th>
                <th width="16%">Jadwal Penggunaan</th>
                <th width="12%">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($reservations as $index => $res)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>
                    <strong>{{ $res->reservation_code }}</strong><br>
                    <span style="font-size: 7.5pt; color: #666;">{{ $res->created_at->format('d/m/Y') }}</span>
                </td>
                <td>
                    <strong>{{ $res->user->name ?? '-' }}</strong><br>
                    <span style="color: #4b5563;">{{ $res->extracurricular ? $res->extracurricular->name : 'Pribadi' }}</span>
                </td>
                <td>
                    <strong>{{ $res->title }}</strong><br>
                    <span style="font-size: 7.5pt; color: #555;">{{ \Illuminate\Support\Str::limit($res->purpose, 50) }}</span>
                </td>
                <td>
                    @if($res->room)
                        <div><strong>Ruang:</strong> {{ $res->room->name }}</div>
                    @endif
                    @if($res->assets->isNotEmpty())
                        <div style="font-size: 7.5pt; color: #4b5563;">
                            <strong>Aset:</strong> {{ $res->assets->pluck('name')->implode(', ') }}
                        </div>
                    @endif
                </td>
                <td>
                    <div>{{ \Carbon\Carbon::parse($res->start_time)->format('d/m/Y H:i') }}</div>
                    <div style="font-size: 7.5pt; color: #666;">s/d {{ \Carbon\Carbon::parse($res->end_time)->format('d/m/Y H:i') }}</div>
                </td>
                <td class="text-center">
                    <span class="badge badge-{{ $res->status }}">{{ strtoupper(str_replace('_', ' ', $res->status)) }}</span>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center" style="padding: 20px;">Tidak ada data peminjaman.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
