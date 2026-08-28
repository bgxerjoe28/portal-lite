<?php

namespace Modules\Akademik\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Akademik\Models\TkaSubject;
use Modules\Akademik\Models\TkaSession;

class TkaSessionController extends Controller
{
    public function index(Request $request, TkaSubject $tka_subject)
    {
        $sessions = TkaSession::where('tka_subject_id', $tka_subject->id)
            ->withCount('attendances')
            ->orderBy('date', 'desc')
            ->orderBy('start_time', 'desc')
            ->paginate(15);

        return Inertia::render('Akademik/Tka/Sessions/Index', [
            'subject' => $tka_subject,
            'sessions' => $sessions
        ]);
    }

    public function store(Request $request, TkaSubject $tka_subject)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'date' => 'required|date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'description' => 'nullable|string',
        ]);

        $validated['tka_subject_id'] = $tka_subject->id;

        TkaSession::create($validated);

        return back()->with('success', 'Sesi TKA berhasil ditambahkan.');
    }

    public function update(Request $request, TkaSession $tka_session)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'date' => 'required|date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'description' => 'nullable|string',
        ]);

        $tka_session->update($validated);

        return back()->with('success', 'Sesi TKA berhasil diperbarui.');
    }

    public function destroy(TkaSession $tka_session)
    {
        $tka_session->delete();

        return back()->with('success', 'Sesi TKA berhasil dihapus.');
    }
}
