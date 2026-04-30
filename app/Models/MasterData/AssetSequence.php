<?php

namespace App\Models\MasterData;

use Illuminate\Database\Eloquent\Model;

class AssetSequence extends Model
{
    protected $fillable = [
        'prefix',
        'last_number',
    ];
}