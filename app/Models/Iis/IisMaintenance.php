<?php

namespace App\Models\Iis;

use App\Models\Model;
use App\Models\User;
use App\Models\MasterData\Supplier;
use App\Traits\HandleFileStorage;

class IisMaintenance extends Model
{
    use HandleFileStorage;

    protected $table = 'iis_maintenance';

    protected $guarded = [];

    protected $casts = [
        'maintenance_date' => 'datetime',
        'completed_date' => 'datetime',
        'attachments' => 'array',
    ];

    protected $appends = [
        'attachments_object',
    ];

    public function getFileStorageColumns(): array
    {
        return [
            'attachments',
        ];
    }

    public static function boot()
    {
        parent::boot();
        self::bootHandlesFileStorage();
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->created_by = auth()->id();
        });

        static::updating(function ($model) {
            $model->updated_by = auth()->id();
        });
    }

    // Relasi ke user
    public function operator()
    {
        return $this->belongsTo(User::class, 'operator_id', 'id');
    }

    // Relasi ke inventaris atau alkes
    public function inventory()
    {
        return $this->belongsTo(IisInventory::class, 'qr_code_no', 'qr_code_no');
    }

    public function alkes()
    {
        return $this->belongsTo(IisAlkes::class, 'qr_code_no', 'qr_code_no');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'id');
    }
}
