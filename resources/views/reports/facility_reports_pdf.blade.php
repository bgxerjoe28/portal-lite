<!DOCTYPE html>
<html>
<head>
    <title>Rekapitulasi Sarana dan Prasarana</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            text-align: center;
        }
        .text-center {
            text-align: center;
        }
        .signature-section {
            width: 100%;
            margin-top: 50px;
        }
        .signature-box {
            width: 50%;
            float: left;
            text-align: center;
        }
        .signature-space {
            height: 80px;
        }
        .clear {
            clear: both;
        }
    </style>
</head>
<body>

    <h2>Laporan Rekapitulasi Sarana dan Prasarana</h2>

    <table>
        <thead>
            <tr>
                <th style="width: 5%">No</th>
                <th style="width: 25%">Ruang / Gedung</th>
                <th style="width: 30%">Nama Barang</th>
                <th style="width: 15%">Level Kerusakan</th>
                <th style="width: 25%">Keterangan / Tindakan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $item->room_name ?: ($item->facilityReport ? $item->facilityReport->location : '-') }}</td>
                <td>{{ $item->item_name }}</td>
                <td class="text-center">{{ strtoupper($item->severity) }}</td>
                <td>
                    @if($item->recommendation)
                        <strong>Saran:</strong> {{ $item->recommendation }}<br>
                    @endif
                    @if($item->notes)
                        <strong>Ket:</strong> {{ $item->notes }}
                    @endif
                    @if(!$item->recommendation && !$item->notes)
                        -
                    @endif
                </td>
            </tr>
            @endforeach
            
            @if($items->isEmpty())
            <tr>
                <td colspan="5" class="text-center">Tidak ada data.</td>
            </tr>
            @endif
        </tbody>
    </table>

    <div class="signature-section">
        <div class="signature-box">
            <p>Mengetahui,</p>
            <p><strong>Waka Sarpras</strong></p>
            <div class="signature-space"></div>
            <p>( ................................................. )</p>
            <p>NIP. </p>
        </div>
        <div class="signature-box">
            <p>Mengesahkan,</p>
            <p><strong>Kepala Sekolah</strong></p>
            <div class="signature-space"></div>
            <p>( ................................................. )</p>
            <p>NIP. </p>
        </div>
        <div class="clear"></div>
    </div>

</body>
</html>
