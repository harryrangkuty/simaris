<?php

namespace App\Models\MasterData;

use App\Models\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Item extends Model
{
    use HasFactory;

    protected $primaryKey = 'code';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'code',
        'code_legacy',
        'name',
        'uom_code',
        'stock_code',
        'category_code',
        'depreciation_group_code',
        'type',
        'min_stock',
        'max_stock',
        'notes',
        'is_active',
        'editor_id',
        'is_reg',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function stock()
    {
        return $this->belongsTo(StockCode::class, 'stock_code', 'code');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_code', 'code');
    }

    public function uom()
    {
        return $this->belongsTo(Uom::class, 'uom_code', 'code');
    }

    public function depreciationGroup()
    {
        return $this->belongsTo(DepreciationGroup::class, 'depreciation_group_code', 'code');
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'editor_id', 'id');
    }
}
