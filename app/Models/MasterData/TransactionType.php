<?php

namespace App\Models\MasterData;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Model;

class TransactionType extends Model
{
    use HasFactory;

    protected $primaryKey = 'code';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeAsetAcquisitions($q)
    {
        $q->whereIn("code", ['S00', 'M01', 'M02', 'M03'])->where('is_active', true);
    }

    public function scopeAsetHentiGuna($q)
    {
        $q->whereIn("code", ['401', '402', '188', '177'])->where('is_active', true);
    }

    public function scopeAsetPenghapusan($q)
    {
        $q->whereIn("code", ['301', '303', '391', '393'])->where('is_active', true);
    }

    public function scopePersediaanMasuk($q)
    {
        $q->where("code", "like", "M%")->where('is_active', true);
    }

    public function scopePersediaanKeluar($q)
    {
        $q->where("code", "like", "K%")->where('is_active', true);
    }

    public function scopeKdpPerolehan($q)
    {
        $q->whereIn("code", ['501', '502'])->where('is_active', true);
    }
}
