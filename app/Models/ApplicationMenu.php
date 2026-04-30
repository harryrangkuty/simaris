<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Models\Permission;

class ApplicationMenu extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'key',
        'title',
        'icon',
        'url',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /* =====================
     | RELATIONS
     ===================== */

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')
            ->orderBy('order');
    }

    public function permissions()
    {
        return $this->belongsToMany(
            Permission::class,
            'menu_has_permissions',
            'application_menu_id',
            'permission_id'
        );
    }

    /* =====================
     | SCOPES
     ===================== */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
