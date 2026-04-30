<?php

namespace App\Models\Asset;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Model;
use App\Models\Satker;

class AssetChange extends Model
{
    use HasFactory;

    protected $table = 'aset_perubahan';

    public function profil(){
        return $this->belongsTo(AssetProfile::class, 'profil_id');
    }

    public function scopeDistribusi($q){
        $q->where('type', 'distribusi-ruang')
          ->orWhere('type', 'distribusi-satker');
    }
    public function scopeDistribusiRuang($q){
        $q->where('type', 'distribusi-ruang');
    }

    public function scopeDistribusiSatker($q){
        $q->where('type', 'distribusi-satker');
    }

    public function scopeKondisi($q){
        $q->where('type', 'kondisi');
    }

    public function scopePemanfaatan($q){
        $q->where('type', 'pemanfaatan');
    }

    public function scopePenghentigunaan($q){
        $q->where('type', 'henti-guna');
    }

    public function scopePengaktigunaan($q){
        $q->where('type', 'aktif-guna-kembali');
    }

    public function scopePemeliharaan($q){
        $q->where('type', 'pemeliharaan');
    }
}
