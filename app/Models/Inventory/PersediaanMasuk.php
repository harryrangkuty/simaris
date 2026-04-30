<?php

namespace App\Models\Persediaan;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Model;
use App\Models\JenisTransaksi;
use App\Models\Persediaan\Gudang;

class PersediaanMasuk extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'persediaan_masuk';

    public function details() {
        return $this->hasMany(PersediaanMasukDetail::class, 'reference_id');
    }

    public function gudang() {
        return $this->belongsTo(Gudang::class);
    }

    public function jenisTransaksi() {
        return $this->belongsTo(JenisTransaksi::class, 'jenis_transaksi_id', 'kode');
    }

    // public function order() {
    //     return $this->belongsTo(PersediaanOrderMasuk::class, 'order_id');
    // }

    public function scopeJoinSuplier($q) {
    	$q->join('persediaan_suplier','persediaan_suplier.id', 'suplier_id');
    }

    public function scopeJoinGudang($q) {
        $q->join('persediaan_gudang','persediaan_gudang.id','gudang_id');
    }
}
