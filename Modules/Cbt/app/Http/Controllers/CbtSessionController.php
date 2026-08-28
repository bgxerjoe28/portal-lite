<?php

namespace Modules\Cbt\Http\Controllers;

use App\Http\Controllers\Controller;
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

        CbtSession::create($request->all());

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
        $session->update($request->all());

        return redirect()->back()->with('success', 'Sesi ujian berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $session = CbtSession::findOrFail($id);
        $session->delete();

        return redirect()->back()->with('success', 'Sesi ujian berhasil dihapus.');
    }
}
