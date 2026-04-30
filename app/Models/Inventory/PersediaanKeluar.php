<?php

namespace App\Models\Persediaan;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Model;
use App\Models\JenisTransaksi;
use App\Models\Persediaan\Gudang;

class PersediaanKeluar extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'persediaan_keluar';

    public function details() {
        return $this->hasMany(PersediaanKeluarDetail::class, 'reference_id');
    }

    public function gudang() {
        return $this->belongsTo(Gudang::class);
    }

    public function jenisTransaksi() {
        return $this->belongsTo(JenisTransaksi::class, 'jenis_transaksi_id', 'kode');
    }

    public function scopeJoinGudang($q) {
        $q->join('persediaan_gudang','persediaan_gudang.id','gudang_id');
    }
}
