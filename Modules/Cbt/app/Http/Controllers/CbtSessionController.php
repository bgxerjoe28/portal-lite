<?php

namespace Modules\Cbt\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Cbt\Models\CbtSession;

class CbtSessionController extends Controller
{
    public function index(Request $request)
    {
        $query = CbtSession::query();

        if ($request->search) {
            $query->where('name', 'ILIKE', "%{$request->search}%");
        }

        $sessions = $query->orderBy('name')->paginate(10)->withQueryString();

        return Inertia::render('Cbt/Session/Index', [
            'sessions' => $sessions,
            'filters' => $request->only('search'),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
        ]);

        $session = CbtSession::create($request->all());

        $timeStr = ($session->start_time && $session->end_time) ? " ({$session->start_time} - {$session->end_time})" : '';
        ActivityLogger::log('CBT_SESSION_CREATE', "Menambahkan Sesi Ujian CBT baru: {$session->name}{$timeStr}", $session, null, $session->toArray());

        return redirect()->back()->with('success', 'Sesi ujian berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
        ]);

        $session = CbtSession::findOrFail($id);
        $old = $session->toArray();
        $session->update($request->all());

        ActivityLogger::log('CBT_SESSION_UPDATE', "Memperbarui data Sesi Ujian CBT: {$session->name}", $session, $old, $session->toArray());

        return redirect()->back()->with('success', 'Sesi ujian berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $session = CbtSession::findOrFail($id);
        $name = $session->name;
        $old = $session->toArray();
        $session->delete();

        ActivityLogger::log('CBT_SESSION_DELETE', "Menghapus Sesi Ujian CBT: {$name}", $session, $old, null);

        return redirect()->back()->with('success', 'Sesi ujian berhasil dihapus.');
    }
}
