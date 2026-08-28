<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MenuController extends Controller
{
    /**
     * Menampilkan halaman manajemen menu
     */
    public function index()
    {
        return Inertia::render('Admin/Menu/Index', [
                // Hanya ambil bapaknya, anaknya otomatis ikut karena relasi recursive
                'menus' => Menu::with(['children', 'roles'])
                                ->whereNull('parent_id') 
                                ->orderBy('sort_order')
                                ->get(),
                'roles' => Role::all(['id', 'name']),
                'parentOptions' => Menu::whereNull('parent_id')->get(['id', 'label'])
            ]);
    }        
    /**
     * Menyimpan menu baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'label'        => 'required|string|max:100',
            'icon'         => 'nullable|string',
            'to'           => 'nullable|string', // URL/Route tujuan
            'parent_id'    => 'nullable|exists:menus,id',
            'sort_order'   => 'integer',
            'is_separator' => 'boolean',
            'role_ids'     => 'required|array', // Role mana saja yang bisa akses
            'is_active'     => 'boolean'
        ]);

        $menu = Menu::create($validated);

        // Sinkronisasi tabel pivot menu_role
        $menu->roles()->sync($request->role_ids);

        return redirect()->back()->with('success', 'Menu berhasil ditambahkan');
    }

    /**
     * Update menu yang ada
     */
    public function update(Request $request, Menu $menu)
    {
        $validated = $request->validate([
            'label'        => 'required|string|max:100',
            'icon'         => 'nullable|string',
            'to'           => 'nullable|string',
            'parent_id'    => 'nullable|exists:menus,id',
            'sort_order'   => 'integer',
            'is_separator' => 'boolean',
            'role_ids'     => 'required|array',
            'is_active'     => 'boolean'
        ]);

        $menu->update($validated);
        
        // Update tabel pivot
        $menu->roles()->sync($request->role_ids);

        return redirect()->back()->with('success', 'Menu berhasil diperbarui');
    }

    /**
     * Hapus menu
     */
    public function destroy(Menu $menu)
    {
        // Karena di migration kita pakai onDelete('cascade'), 
        // anak-anak menu ini otomatis ikut terhapus di DB.
        $menu->delete();

        return redirect()->back()->with('success', 'Menu berhasil dihapus');
    }
    public function toggle(Menu $menu)
    {
        // Balikkan nilai is_active (jika true jadi false, jika false jadi true)
        $menu->update([
            'is_active' => !$menu->is_active
        ]);

        return back()->with('success', 'Status menu berhasil diubah.');
    }
}