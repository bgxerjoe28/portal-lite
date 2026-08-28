<?php

namespace Modules\Cbt\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Modules\Cbt\Models\CbtProctorSchedule;
use Modules\Cbt\Models\CbtSession;
use Modules\Cbt\Models\CbtRoom;
use Modules\Akademik\Models\Teacher;

class CbtProctorScheduleController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = CbtProctorSchedule::with(['room', 'session', 'teacher']);

        // Jika bukan admin dan merupakan guru, batasi hanya jadwal pengawas miliknya
        if (!$user->hasRole('admin') && $user->hasRole('guru')) {
            $teacherId = $user->teacher?->id;
            $query->where('teacher_id', $teacherId);
        }

        if ($request->search) {
            $search = $request->search;
            $query->whereHas('teacher', function($q) use ($search) {
                $q->where('full_name', 'ILIKE', "%{$search}%");
            });
        }

        if ($request->date) {
            $query->where('date', $request->date);
        } else {
            $query->where('date', now()->toDateString());
        }

        $schedules = $query->orderBy('date', 'desc')
                           ->orderBy('cbt_session_id')
                           ->orderBy('cbt_room_id')
                           ->paginate(10)
                           ->withQueryString();

        $teachersQuery = Teacher::orderBy('full_name');
        
        if (!$user->hasRole('admin') && $user->hasRole('guru')) {
            $teachersQuery->where('id', $user->teacher?->id);
        }
        
        $teachers = $teachersQuery->get();

        return Inertia::render('Cbt/ProctorSchedule/Index', [
            'schedules' => $schedules,
            'filters' => [
                'search' => $request->search,
                'date' => $request->date ?? now()->toDateString(),
            ],
            'rooms' => CbtRoom::orderBy('name')->get(),
            'sessions' => CbtSession::orderBy('name')->get(),
            'teachers' => $teachers,
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasRole('admin')) {
            return redirect()->back()->with('error', 'Hanya Admin yang memiliki hak untuk menambah jadwal pengawas.');
        }

        $request->validate([
            'date' => 'required|date',
            'cbt_room_id' => 'required|exists:cbt_rooms,id',
            'cbt_session_id' => [
                'required',
                'exists:cbt_sessions,id',
                Rule::unique('cbt_proctor_schedules')->where(function ($query) use ($request) {
                    return $query->where('date', $request->date)
                                 ->where('cbt_room_id', $request->cbt_room_id);
                }),
            ],
            'teacher_id' => 'required|exists:teachers,id',
        ], [
            'cbt_session_id.unique' => 'Jadwal untuk ruangan dan sesi ini pada tanggal tersebut sudah ada.',
        ]);

        CbtProctorSchedule::create($request->all());

        return redirect()->back()->with('success', 'Jadwal pengawas berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->hasRole('admin')) {
            return redirect()->back()->with('error', 'Hanya Admin yang memiliki hak untuk mengubah jadwal pengawas.');
        }

        $request->validate([
            'date' => 'required|date',
            'cbt_room_id' => 'required|exists:cbt_rooms,id',
            'cbt_session_id' => [
                'required',
                'exists:cbt_sessions,id',
                Rule::unique('cbt_proctor_schedules')->where(function ($query) use ($request) {
                    return $query->where('date', $request->date)
                                 ->where('cbt_room_id', $request->cbt_room_id);
                })->ignore($id),
            ],
            'teacher_id' => 'required|exists:teachers,id',
        ], [
            'cbt_session_id.unique' => 'Jadwal untuk ruangan dan sesi ini pada tanggal tersebut sudah ada.',
        ]);

        $schedule = CbtProctorSchedule::findOrFail($id);
        $schedule->update($request->all());

        return redirect()->back()->with('success', 'Jadwal pengawas berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $user = Auth::user();
        if (!$user->hasRole('admin')) {
            return redirect()->back()->with('error', 'Hanya Admin yang memiliki hak untuk menghapus jadwal pengawas.');
        }

        $schedule = CbtProctorSchedule::findOrFail($id);
        $schedule->delete();

        return redirect()->back()->with('success', 'Jadwal pengawas berhasil dihapus.');
    }
}
