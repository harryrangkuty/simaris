<?php

namespace App\Models\MasterData;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Model;

class Supplier extends Model
{
    use HasFactory, SoftDeletes;

    protected $casts = [
        'is_ppn' => 'boolean',
    ];
}
