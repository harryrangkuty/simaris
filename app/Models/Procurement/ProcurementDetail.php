<?php

namespace App\Models\Procurement;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Model;
use App\Models\MasterData\Item;

class ProcurementDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'procurement_id',
        'description',
        'item_code',
        'condition',
        'quantity',
        'unit_price',
        'discount_value',
        'commission_value',
        'shipping_value',
        'ppn_value',
    ];

    public $timestamps = false;

    public function procurement()
    {
        return $this->belongsTo(Procurement::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

}
