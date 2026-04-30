<?php

namespace App\Models\Iis;

use App\Models\MasterData\Branch;
use App\Models\MasterData\Building;
use App\Models\MasterData\Item;
use App\Models\MasterData\Room;
use App\Models\MasterData\Unit;
use App\Models\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\SoftDeletes;

class IisInventory extends Model
{
    use SoftDeletes;

    protected $table = 'iis_inventories_list';

    /**
     * Mass assignable
     */
    protected $fillable = [
        'qr_code_no',
        'item_code',
        'category_name',
        'description',
        'position',
        'building',
        'floor',
        'unit',
        'room',
        'utilized_by',
        'utilization_note',
        'condition',
        'asset_number',
        'iis_operator',
        'etc',
        'disposal_id',
        'disposal_type_id',
        'disposal_date',
        'is_deactivated',
        'is_deactivated_notes',
        'notes',
        'is_handed_over',
        'print_count',
        'last_print_at',
        'last_print_by',
        'data_source',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * Casts
     */
    protected $casts = [
        'etc' => 'array',
        'is_deactivated' => 'boolean',
        'disposal_date' => 'datetime',
        'is_handed_over' => 'boolean',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->created_by = auth()->id();
        });

        static::updating(function ($model) {
            $model->updated_by = auth()->id();
        });
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    // --- Relasi ke Item (master) ---
    public function item()
    {
        return $this->belongsTo(Item::class, 'item_code', 'code');
        // 'item_code' di inventaris
        // 'code' di tabel items
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Belong to user / PJ
    public function bUser()
    {
        return $this->belongsTo(User::class, 'pj_nik', 'identifier');
    }

    public function building()
    {
        return $this->belongsTo(Building::class);
    }

    /**
     * Scope: hanya aset aktif
     */
    public function scopeActive($query)
    {
        return $query->where('is_deactivated', false);
    }

    /**
     * Scope: aset legacy IIS
     */
    public function scopeLegacy($query)
    {
        return $query->whereNotNull('qr_code_no');
    }

    /**
     * Helper: ambil value dari etc
     */
    public function getEtcValue(string $key, $default = null)
    {
        return $this->etc[$key] ?? $default;
    }

    /**
     * Helper: set value ke etc
     */
    public function setEtcValue(string $key, $value): void
    {
        $etc = $this->etc ?? [];
        $etc[$key] = $value;
        $this->etc = $etc;
    }

    public function lastPrintBy()
    {
        return $this->belongsTo(User::class, 'last_print_by');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }
    
}
