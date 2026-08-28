<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\ActivityLogger;

class ImpersonateController extends Controller
{
    /**
     * Admin masuk sebagai user lain.
     */
    public function take(Request $request, int $id)
    {
        $admin  = $request->user();
        $target = User::findOrFail($id);

        // Guard 1: hanya admin
        if (! $admin->canImpersonate()) {
            abort(403, 'Anda tidak memiliki izin untuk melakukan impersonation.');
        }

        // Guard 2: target tidak boleh admin
        if (! $target->canBeImpersonated()) {
            return back()->with('error', 'Tidak dapat login sebagai sesama admin.');
        }

        // Guard 3: tidak boleh nested impersonation
        if (app('impersonate')->isImpersonating()) {
            return back()->with('error', 'Anda sedang dalam mode impersonation. Keluar dulu sebelum masuk ke akun lain.');
        }

        // Audit log
        Log::channel('stack')->info('[IMPERSONATE] MULAI', [
            'admin_id'    => $admin->id,
            'admin_name'  => $admin->name,
            'target_id'   => $target->id,
            'target_name' => $target->name,
            'target_role' => $target->getRoleNames()->first(),
            'ip'          => $request->ip(),
            'at'          => now()->toDateTimeString(),
        ]);

        ActivityLogger::log(
            'IMPERSONATE',
            "Admin {$admin->name} login sebagai {$target->name} ({$target->getRoleNames()->first()})",
            $target,
            null,
            null,
            $admin
        );

        $admin->impersonate($target);
        $request->session()->regenerateToken();

        return redirect('/')->with('success', "Anda sekarang login sebagai {$target->name}.");
    }

    /**
     * Kembali ke akun admin asli.
     */
    public function leave(Request $request)
    {
        $manager = app('impersonate');

        if (! $manager->isImpersonating()) {
            return redirect('/')->with('error', 'Tidak ada sesi impersonation aktif.');
        }

        // Ambil data untuk log sebelum leave
        $impersonator = User::find($manager->getImpersonatorId(), ['*']);
        $current      = $request->user();

        Log::channel('stack')->info('[IMPERSONATE] SELESAI', [
            'admin_id'    => $impersonator?->id,
            'admin_name'  => $impersonator?->name,
            'was_as_id'   => $current?->id,
            'was_as_name' => $current?->name,
            'ip'          => $request->ip(),
            'at'          => now()->toDateTimeString(),
        ]);

        ActivityLogger::log(
            'IMPERSONATE',
            "Admin {$impersonator?->name} selesai login sebagai {$current?->name}",
            $current,
            null,
            null,
            $impersonator
        );

        $manager->leave();
        $request->session()->regenerateToken();

        return redirect('/admin/dashboard')->with('success', 'Anda kembali ke akun admin.');
    }
}
