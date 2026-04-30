<?php

namespace App\Models\Persediaan;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Model;
use App\Models\Persediaan\PersediaanTransaksi;

class Barang extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'persediaan_barang';

    protected $appends = [
        'stock',
    ];

    protected $casts = [
    	'etc' => 'json',
        'status_masuk' => 'boolean',
        'status_keluar' => 'boolean'
    ];

    public static $gudang_id = null;

    public function transaksi() {
        return $this->hasMany(PersediaanTransaksi::class, 'barang_id')->where('persediaan_transaksi.is_completed', 1);
    }

    public function stockBatch() {
        return $this->hasMany(PersediaanTransaksi::class, 'barang_id')
                    ->batchMasuk(static::$gudang_id);
    }

    public function scopeWithStockBatch($q, $gudang_id = null) {
        static::$gudang_id = $gudang_id;
        $q->with('stockBatch');
    }

    public function sumTransaksi() {
        return $this->hasMany(PersediaanTransaksi::class, 'barang_id')
                    ->totalStock(static::$gudang_id);
    }

    public function scopeWithSumTransaksi($q, $gudang_id = null) {
        static::$gudang_id = $gudang_id;
        $q->with('sumTransaksi');
    }

    public function getStockAttribute() {        
        if($this->relationLoaded('sumTransaksi') && $this->sumTransaksi)
           return $this->sumTransaksi->sum('total');

        return 0;
    }
}
