<?php

namespace App\Models\MasterData;

use App\Models\Model;

class ItemSequence extends Model
{
    /**
     * Primary key bukan integer
     */
    protected $primaryKey = 'prefix';

    public $incrementing = false;

    protected $keyType = 'string';

    /**
     * Mass assignable
     */
    protected $fillable = [
        'prefix',
        'last_number',
        'last_inventory_number',
    ];
}
