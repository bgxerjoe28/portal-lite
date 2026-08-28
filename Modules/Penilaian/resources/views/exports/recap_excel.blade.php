<table>
    <thead>
        <tr><th colspan="5" style="font-weight: bold; font-size: 14pt;">REKAPITULASI NILAI SISWA</th></tr>
        <tr><th>Mata Pelajaran</th><td colspan="4">: {{ $subject->name }}</td></tr>
        <tr><th>Kelas</th><td colspan="4">: {{ $classroom->name }}</td></tr>
        <tr><th>Guru Pengampu</th><td colspan="4">: {{ $teacher->full_name ?? $teacher->name ?? '-' }}</td></tr>
        <tr></tr>

        <tr>
            <th rowspan="2" style="background-color: #f2f2f2; border: 1px solid #000;">NO</th>
            <th rowspan="2" style="background-color: #f2f2f2; border: 1px solid #000;">NAMA SISWA</th>
            @foreach($components as $comp)
                <th colspan="{{ count($comp->gradingItems) + 1 }}" style="text-align: center; border: 1px solid #000;">
                    {{ strtoupper($comp->name) }} (KKM: {{ $comp->passing_grade }})
                </th>
            @endforeach
            <th rowspan="2" style="background-color: #d1f2eb; border: 1px solid #000;">NILAI AKHIR</th>
        </tr>
        <tr>
            @foreach($components as $comp)
                @foreach($comp->gradingItems as $item)
                    <th style="border: 1px solid #000; {{ !$item->is_posted ? 'background-color: #fce4ec; color: #c2185b;' : '' }}">
                        {{ $item->title }}{{ !$item->is_posted ? ' [UNPOST]' : '' }}
                    </th>
                @endforeach
                <th style="background-color: #ebf5fb; border: 1px solid #000;">Rerata</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach($recapData as $index => $data)
        <tr>
            <td style="text-align: center; border: 1px solid #000;">{{ $index + 1 }}</td>
            <td style="border: 1px solid #000;">{{ $data['student_name'] }}</td>
            @foreach($components as $comp)
                @foreach($comp->gradingItems as $item)
                    <td style="text-align: center; border: 1px solid #000;">
                        {{ $data['categories'][$comp->id]['items']['item_'.$item->id] }}
                    </td>
                @endforeach
                <td style="text-align: center; font-weight: bold; background-color: #f4f6f7; border: 1px solid #000;">
                    {{ number_format($data['categories'][$comp->id]['avg'], 1) }}
                </td>
            @endforeach
            <td style="text-align: center; font-weight: bold; background-color: #d1f2eb; border: 1px solid #000;">
                {{ number_format($data['final_grade'], 1) }}
            </td>
        </tr>
        @endforeach
    </tbody>
</table>