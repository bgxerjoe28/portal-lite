<?php

namespace Modules\Cbt\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Cbt\Models\CbtRoom;
use Modules\Cbt\Models\CbtRoomStudent;
use Modules\Akademik\Models\Student;
use Modules\Cbt\Models\CbtSession;
use Modules\Cbt\Imports\SeatingImport;
use Modules\Cbt\Exports\SeatingTemplateExport;

class CbtRoomController extends Controller
{
    public function index()
    {
        $rooms = CbtRoom::orderBy('name')
            ->withCount('students')
            ->paginate(10);

        return Inertia::render('Cbt/Room/Index', [
            'rooms' => $rooms,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1|max:100',
        ]);

        $room = CbtRoom::create([
            'name' => $request->name,
            'capacity' => $request->capacity,
        ]);

        ActivityLogger::log(
            'CBT_ROOM_CREATE',
            "Menambahkan Ruang Ujian CBT: {$room->name} (Kapasitas: {$room->capacity})",
            $room,
            null,
            $room->toArray()
        );

        return redirect()->back()->with('success', 'Ruang ujian berhasil dibuat.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1|max:100',
        ]);

        $room = CbtRoom::findOrFail($id);
        $old = $room->toArray();
        $room->update([
            'name' => $request->name,
            'capacity' => $request->capacity,
        ]);

        ActivityLogger::log(
            'CBT_ROOM_UPDATE',
            "Memperbarui data Ruang Ujian CBT: {$room->name}",
            $room,
            $old,
            $room->toArray()
        );

        return redirect()->back()->with('success', 'Ruang ujian berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $room = CbtRoom::findOrFail($id);
        $name = $room->name;
        $old = $room->toArray();
        $room->delete();

        ActivityLogger::log(
            'CBT_ROOM_DELETE',
            "Menghapus Ruang Ujian CBT: {$name}",
            $room,
            $old,
            null
        );

        return redirect()->back()->with('success', 'Ruang ujian berhasil dihapus.');
    }

    public function seating(Request $request, $id)
    {
        $room = CbtRoom::findOrFail($id);

        // Ambil semua kursi yang sudah terisi beserta data siswanya untuk ruangan ini
        $seating = CbtRoomStudent::where('cbt_room_id', $id)
            ->with(['student.classrooms'])
            ->get()
            ->keyBy('seat_number');

        // Ambil daftar siswa untuk dropdown pencarian / pilihan kursi
        // Filter siswa yang sudah punya kursi di seluruh ruangan CBT
        $assignedStudentIds = CbtRoomStudent::pluck('student_id')->toArray();

        $students = Student::with(['classrooms' => function ($q) {
                $q->where('classroom_students.status', 'aktif');
            }])
            ->whereNotIn('id', $assignedStudentIds)
            ->orderBy('full_name')
            ->get()
            ->map(function ($s) {
                return [
                    'id' => $s->id,
                    'name' => $s->full_name,
                    'nisn' => $s->nisn,
                    'classroom_name' => $s->classrooms->first()->name ?? '-',
                ];
            });

        // Format map tempat duduk untuk dikirim ke Inertia
        $seatingMap = [];
        for ($i = 1; $i <= 36; $i++) {
            $seat = $seating->get($i);
            $seatingMap[$i] = $seat ? [
                'id' => $seat->id,
                'student_id' => $seat->student_id,
                'student_name' => $seat->student->full_name,
                'nisn' => $seat->student->nisn,
                'classroom_name' => $seat->student->classrooms->first()->name ?? '-',
            ] : null;
        }

        return Inertia::render('Cbt/Room/Seating', [
            'room' => $room,
            'seatingMap' => $seatingMap,
            'students' => $students,
        ]);
    }

    public function assignSeat(Request $request, $id)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'seat_number' => 'required|integer|min:1|max:36',
        ]);

        $room = CbtRoom::findOrFail($id);

        // Validasi double assignment kursi di ruangan ini
        $existingSeat = CbtRoomStudent::where('cbt_room_id', $id)
            ->where('seat_number', $request->seat_number)
            ->first();
            
        if ($existingSeat) {
            return redirect()->back()->with('error', 'Nomor kursi tersebut sudah terisi.');
        }

        // Validasi double assignment siswa (siswa tidak boleh ditempatkan di ruangan berbeda)
        $existingStudent = CbtRoomStudent::where('student_id', $request->student_id)->first();

        if ($existingStudent) {
            return redirect()->back()->with('error', 'Siswa tersebut sudah terdaftar di ruangan lain.');
        }

        CbtRoomStudent::create([
            'cbt_room_id' => $id,
            'student_id' => $request->student_id,
            'seat_number' => $request->seat_number,
        ]);

        return redirect()->back()->with('success', 'Siswa berhasil ditempatkan di kursi nomor ' . $request->seat_number . '.');
    }

    public function clearSeat(Request $request, $id, $seatNumber)
    {
        CbtRoomStudent::where('cbt_room_id', $id)
            ->where('seat_number', $seatNumber)
            ->delete();

        return redirect()->back()->with('success', 'Kursi nomor ' . $seatNumber . ' berhasil dikosongkan.');
    }

    public function importSeating(Request $request, $id)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt',
        ]);

        $room = CbtRoom::findOrFail($id);
        $file = $request->file('file');

        try {
            $import = new SeatingImport($room->id);
            Excel::import($import, $file);

            $errors = $import->getErrors();
            if (!empty($errors)) {
                return redirect()->back()->with('error', 'Gagal mengimpor denah tempat duduk:<br>' . implode('<br>', $errors));
            }

            return redirect()->back()->with('success', 'Denah tempat duduk berhasil di-impor.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal import denah tempat duduk: ' . $e->getMessage());
        }
    }

    public function downloadSeatingTemplate()
    {
        return Excel::download(new SeatingTemplateExport, 'template_seating.xlsx');
    }
}
