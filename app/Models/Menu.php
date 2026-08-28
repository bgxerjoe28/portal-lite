<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Permission\Models\Role;
class Menu extends Model
{
    use HasFactory;
    protected $fillable = ['parent_id', 'label', 'icon', 'to', 'sort_order', 'is_separator','is_active'];
    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function roles() {
        return $this->belongsToMany(Role::class);
    }

    public function children() {
        return $this->hasMany(Menu::class, 'parent_id')
                    ->with(['children', 'roles'])
                    ->orderBy('sort_order', 'asc');
    }

    public function scopeParentOnly($query) {
        return $query->whereNull('parent_id');
    }
}