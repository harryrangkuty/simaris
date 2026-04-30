<?php

namespace App\Models\Iis;

use App\Models\Model;

class IisCategory extends Model
{
    protected $table = 'iis_categories_list';

    // Tidak pakai timestamps
    public $timestamps = false;

    /**
     * Mass assignable
     */
    protected $fillable = [
        'category_name',
    ];

    /**
     * Relasi ke inventaris IIS (optional)
     */
    public function inventories()
    {
        return $this->hasMany(
            IisInventory::class,
            'category_name',
            'category_name'
        );
    }
}
