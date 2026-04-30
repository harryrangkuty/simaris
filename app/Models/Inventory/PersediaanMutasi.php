<?php

namespace App\Models\Persediaan;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Model;
use App\Models\JenisTransaksi;
use App\Models\Persediaan\Gudang;

class PersediaanMutasi extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'persediaan_mutasi';

     public function details() {
        return $this->hasMany(PersediaanMutasiDetail::class, 'reference_id');
    }

    public function gudangAsal() {
        return $this->belongsTo(Gudang::class, 'gudang_asal_id');
    }

    public function gudangTujuan() {
        return $this->belongsTo(Gudang::class, 'gudang_tujuan_id');
    }
}