<?php

namespace App\Models\Iis;

use App\Models\Model;

class IisMovementItem extends Model
{
    protected $table = 'iis_movement_items';

    protected $fillable = [
        'movement_id',
        'asset_id',
        'qr_code_no',
        'description',
        'condition',

        // ASAL & TUJUAN
        'from_type',
        'from_location',
        'to_type',
        'to_location',
    ];


    protected $casts = [
        // SNAPSHOT JSON
        'from_location' => 'array',
        'to_location' => 'array',
    ];

    /* ================= RELATIONS ================= */

    public function movement()
    {
        return $this->belongsTo(IisMovement::class, 'movement_id');
    }


     /* ================= SAFETY ================= */

    /**
     * Cek apakah movement sudah pakai schema JSON baru
     */
    public function usesSnapshot(): bool
    {
        return ! empty($this->from_location) || ! empty($this->to_location);
    }

    /* ================= SNAPSHOT HELPERS ================= */

    public function getFromBranchNameAttribute(): ?string
    {
        return data_get($this->from_location, 'branch_name');
    }

    public function getFromWarehouseNameAttribute(): ?string
    {
        return data_get($this->from_location, 'warehouse_name');
    }

    public function getToBranchNameAttribute(): ?string
    {
        return data_get($this->to_location, 'branch_name');
    }

    public function getToBuildingNameAttribute(): ?string
    {
        return data_get($this->to_location, 'building_name');
    }

    public function getToUnitNameAttribute(): ?string
    {
        return data_get($this->to_location, 'unit_name');
    }

    public function getToRoomNameAttribute(): ?string
    {
        return data_get($this->to_location, 'room_name');
    }

    public function getToFloorAttribute(): ?string
    {
        return data_get($this->to_location, 'floor');
    }
}
