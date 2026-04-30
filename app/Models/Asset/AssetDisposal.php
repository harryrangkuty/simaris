<?php

namespace App\Models\Asset;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Model;
use App\Models\MasterData\TransactionType;

class AssetDisposal extends Model
{
    use HasFactory, SoftDeletes;

    protected $casts = [
    	'etc' => 'object',
    ];

    public function jenisTransaksi()
    {
        return $this->belongsTo(TransactionType::class, 'jenis_transaksi_id', 'kode');
    }

}
