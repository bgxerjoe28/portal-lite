<table>
    <thead>
        <tr>
            <th colspan="{{ 8 + max(1, count($agendaHeaders)) }}" style="font-size: 14pt; font-weight: bold; text-align: center;">
                REKAPITULASI PRESENSI MATA PELAJARAN
            </th>
        </tr>
        <tr>
            <th colspan="{{ 8 + max(1, count($agendaHeaders)) }}" style="font-size: 12pt; font-weight: bold; text-align: center;">
                {{ strtoupper($kop['school_name'] ?? 'SMA NEGERI 16 SEMARANG') }}
            </th>
        </tr>
        <tr>
            <th colspan="{{ 8 + max(1, count($agendaHeaders)) }}" style="font-size: 10pt; text-align: center;">
                Tahun Ajaran {{ $activeYear->name ?? '-' }}
            </th>
        </tr>
        <tr></tr>

        <tr>
            <th style="font-weight: bold;">Kelas</th>
            <td colspan="2">: {{ $classroom->name ?? '-' }}</td>
            <th style="font-weight: bold;">Guru Pengampu</th>
            <td colspan="4">: {{ $teacher->full_name ?? '-' }}</td>
        </tr>
        <tr>
            <th style="font-weight: bold;">Mata Pelajaran</th>
            <td colspan="2">: {{ $subject->name ?? '-' }}</td>
            <th style="font-weight: bold;">Cakupan Periode</th>
            <td colspan="4">: {{ $periodLabel ?? '-' }}</td>
        </tr>
        <tr></tr>

        <tr>
            <th rowspan="2" style="background-color: #e2e8f0; font-weight: bold; text-align: center; vertical-align: middle; border: 1px solid #000;">NO</th>
            <th rowspan="2" style="background-color: #e2e8f0; font-weight: bold; text-align: center; vertical-align: middle; border: 1px solid #000; min-width: 250px;">NAMA SISWA</th>
            <th rowspan="2" style="background-color: #e2e8f0; font-weight: bold; text-align: center; vertical-align: middle; border: 1px solid #000;">NISN</th>
            @forelse($agendaHeaders as $h)
                <th rowspan="2" style="background-color: #f1f5f9; font-weight: bold; text-align: center; vertical-align: middle; border: 1px solid #000;">
                    P{{ $h['meeting_no'] }} ({{ $h['formatted_date'] }})
                </th>
            @empty
                <th rowspan="2" style="background-color: #f1f5f9; font-weight: bold; text-align: center; vertical-align: middle; border: 1px solid #000;">-</th>
            @endforelse
            <th rowspan="2" style="background-color: #d1e7dd; font-weight: bold; text-align: center; vertical-align: middle; border: 1px solid #000;">H</th>
            <th colspan="3" style="background-color: #f8d7da; font-weight: bold; text-align: center; vertical-align: middle; border: 1px solid #000;">TH</th>
            <th rowspan="2" style="background-color: #cff4fc; font-weight: bold; text-align: center; vertical-align: middle; border: 1px solid #000;">Jml</th>
            <th rowspan="2" style="background-color: #e2e8f0; font-weight: bold; text-align: center; vertical-align: middle; border: 1px solid #000;">Keterangan</th>
        </tr>
        <tr>
            <th style="background-color: #fce8e6; font-weight: bold; text-align: center; border: 1px solid #000;">S</th>
            <th style="background-color: #fce8e6; font-weight: bold; text-align: center; border: 1px solid #000;">I</th>
            <th style="background-color: #fce8e6; font-weight: bold; text-align: center; border: 1px solid #000;">A</th>
        </tr>
    </thead>
    <tbody>
        @forelse($students as $idx => $student)
            <tr>
                <td style="text-align: center; border: 1px solid #000;">{{ $idx + 1 }}</td>
                <td style="border: 1px solid #000;">{{ $student['full_name'] }}</td>
                <td style="text-align: center; border: 1px solid #000;">'{{ $student['nisn'] }}</td>
                @forelse($agendaHeaders as $h)
                    <td style="text-align: center; border: 1px solid #000;">{{ $student['presensi'][$h['id']] ?? '-' }}</td>
                @empty
                    <td style="text-align: center; border: 1px solid #000;">-</td>
                @endforelse
                <td style="text-align: center; font-weight: bold; color: #137333; border: 1px solid #000;">{{ $student['h_count'] }}</td>
                <td style="text-align: center; border: 1px solid #000;">{{ $student['s_count'] }}</td>
                <td style="text-align: center; border: 1px solid #000;">{{ $student['i_count'] }}</td>
                <td style="text-align: center; border: 1px solid #000; {{ $student['a_count'] > 0 ? 'font-weight: bold; color: #c5221f;' : '' }}">{{ $student['a_count'] }}</td>
                <td style="text-align: center; font-weight: bold; border: 1px solid #000;">{{ $student['total_count'] }}</td>
                <td style="text-align: center; border: 1px solid #000;">{{ $student['keterangan'] }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="{{ 8 + max(1, count($agendaHeaders)) }}" style="text-align: center; border: 1px solid #000;">Tidak ada data siswa.</td>
            </tr>
        @endforelse
    </tbody>
    @if(count($students) > 0 && count($agendaHeaders) > 0)
    <tfoot>
        <tr>
            <td colspan="3" style="font-weight: bold; text-align: center; border: 1px solid #000;">Jumlah Hadir (H)</td>
            @foreach($agendaSummary as $sum)
                <td style="text-align: center; font-weight: bold; color: #137333; border: 1px solid #000;">{{ $sum['hadir'] }}</td>
            @endforeach
            <td colspan="6" style="border: 1px solid #000;"></td>
        </tr>
        <tr>
            <td colspan="3" style="font-weight: bold; text-align: center; border: 1px solid #000;">Jumlah Tidak Hadir (TH)</td>
            @foreach($agendaSummary as $sum)
                <td style="text-align: center; font-weight: bold; color: #c5221f; border: 1px solid #000;">{{ $sum['tidak_hadir'] }}</td>
            @endforeach
            <td colspan="6" style="border: 1px solid #000;"></td>
        </tr>
    </tfoot>
    @endif
</table>

<table>
    <tr></tr>
    <tr>
        <td colspan="3" style="font-weight: bold;">Keterangan Singkatan:</td>
        <td colspan="{{ max(1, count($agendaHeaders)) }}"></td>
        <td colspan="4" style="text-align: center;">{{ $city ?? 'Semarang' }}, {{ $printDate ?? date('d F Y') }}</td>
    </tr>
    <tr>
        <td colspan="3">H = Hadir</td>
        <td colspan="{{ max(1, count($agendaHeaders)) }}"></td>
        <td colspan="4" style="text-align: center;">Guru Mata Pelajaran {{ $subject->name ?? '' }}</td>
    </tr>
    <tr>
        <td colspan="3">S = Sakit</td>
    </tr>
    <tr>
        <td colspan="3">I = Izin</td>
    </tr>
    <tr>
        <td colspan="3">A = Alpa (Tanpa Keterangan)</td>
    </tr>
    <tr>
        <td colspan="3">TH = Tidak Hadir (S + I + A)</td>
        <td colspan="{{ max(1, count($agendaHeaders)) }}"></td>
        <td colspan="4" style="text-align: center; font-weight: bold; text-decoration: underline;">{{ $teacher->full_name ?? '' }}</td>
    </tr>
    <tr>
        <td colspan="3">Jml = Total Pertemuan Terjadwal</td>
        <td colspan="{{ max(1, count($agendaHeaders)) }}"></td>
        <td colspan="4" style="text-align: center;">NIP. {{ $teacher->nip ?? '-' }}</td>
    </tr>
</table>
