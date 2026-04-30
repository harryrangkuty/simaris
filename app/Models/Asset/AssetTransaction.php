<?php

namespace App\Models\Asset;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Model;
use App\Models\Satker;
use App\Models\MasterData\Category;
use App\Models\JenisTransaksi;

class AssetTransaction extends Model
{
    use HasFactory;

    protected $table = 'aset_transaksi';

    protected $fillable = [
        'profil_id',
        'editor_id',
        'satker_id',
        'satker_nama',
        'jenis_transaksi_id',
        'jenis_transaksi_nama',
        'kategori_id',
        'kategori_nama',
        'nup',
        'kondisi',
        'kuantitas',
        'nilai',
        'tipe_ruang',
        'ruang_id',
        'ruang_nama',
        'jenis_kib',
        'reference_id',
        'reference_type',
    ];

    public function reference()
    {
        return $this->morphTo();
    }

    public function profil()
    {
        return $this->belongsTo(AssetProfile::class);
    }

    public function satker()
    {
        return $this->belongsTo(Satker::class)->withTrashed();
    }

    public function kategori()
    {
        return $this->belongsTo(Kategori::class, 'kategori_id', 'kd_brg');
    }

    public function jenisTransaksi()
    {
        return $this->belongsTo(JenisTransaksi::class, 'jenis_transaksi_id', 'kode');
    }

    public function ruang()
    {
        return $this->belongsTo(AsetRuang::class);
    }
}
