<?php

namespace App\Models\Iis;

use App\Models\Model;

class IisItem extends Model
{
    protected $table = 'iis_items_list';

    // Primary key
    protected $primaryKey = 'item_no';

    // PK bukan auto increment
    public $incrementing = false;

    // PK bertipe string
    protected $keyType = 'string';

    // Tidak pakai timestamps
    public $timestamps = false;

    /**
     * Mass assignable
     */
    protected $fillable = [
        'item_no',
        'name',
        'buying_price',
        'is_sell',
        'is_buy',
        'stock',
        'onhand',
        'hpp',
        'asset',
    ];
}
