<?php

namespace App\Models\MasterData;

use App\Models\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Warehouse extends Model
{
    use HasFactory, SoftDeletes;

    protected $casts = [
        'can_receive' => 'boolean',
        'can_dispatch' => 'boolean',
    ];

    protected $fillable = [
        'branch_id',
        'name',
        'code',
        'address',
        'location',
        'can_receive',
        'can_dispatch',
        'person_in_charge_id',
        'editor_id',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function personInCharge()
    {
        return $this->belongsTo(User::class, 'person_in_charge_id');
    }

    public function stockCodes()
    {
        return $this->belongsToMany(
            StockCode::class,
            'warehouse_stock_codes',
            'warehouse_id',
            'stock_code'
        )->withTimestamps();
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'editor_id');
    }

    public function scopeCanReceive($query)
    {
        return $query->where('can_receive', true);
    }

    public function scopeCanDispatch($query)
    {
        return $query->where('can_dispatch', true);
    }
}
