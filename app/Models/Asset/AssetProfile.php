<?php

namespace App\Models\Asset;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Model;
use App\Models\JenisTransaksi;
use App\Models\Satker;
use App\Models\MasterData\Category;

class AssetProfile extends Model
{
    use HasFactory;

    protected $table = 'aset_profil';

    protected $fillable = [
        'deskripsi',
        'perolehan_id',
        'jenis_perolehan_id',
        'tgl_perolehan',
        'tgl_buku',
        'satker_id',
        'kategori_id',
        'kategori_nama',
        'nup',
        'kondisi',
        'nilai_perolehan',
        'nilai_buku',
        'komptabel',
        'akumulasi_penyusutan',
        'editor_id',
        'tipe_ruang',
        'ruang_id',
        'ruang_nama',
        'jenis_kib',
        'kib',
        'penghapusan_id',
        'jenis_penghapusan_id',
        'tgl_penghapusan',
        'notes',
        'pemanfaatan_id',
        'pemanfaatan_nama',
        'pemanfaatan_catatan',
        'masa_manfaat',
        'etc',
        'henti_guna'
    ];

    protected $casts = [
        'kib' => 'object',
        'etc' => 'object'
    ];

    public $timestamps = false;

    public function scopeAktif($q)
    {
        $q->whereNull('penghapusan_id');
    }

    public function scopeHentiGuna($q) {}

    public function kategori()
    {
        return $this->belongsTo(Kategori::class)->withTrashed();
    }

    public function satker()
    {
        return $this->belongsTo(Satker::class);
    }

    public function ruang()
    {
        return $this->belongsTo(AsetRuang::class);
    }

    public function transaksi()
    {
        return $this->hasMany(AssetTransaction::class, 'profil_id');
    }

    public function perolehan()
    {
        return $this->belongsTo(AssetAcquisition::class, 'perolehan_id');
    }

    public function jenisPerolehan()
    {
        return $this->belongsTo(JenisTransaksi::class, 'jenis_perolehan_id', 'kode');
    }

    public function penyusutan()
    {
        return $this->hasMany(AsetPenyusutan::class, 'profil_id');
    }

    public function penghapusan()
    {
        return $this->belongsTo(AsetPenghapusan::class, 'penghapusan_id');
    }

    public function jenisPenghapusan()
    {
        return $this->belongsTo(JenisTransaksi::class, 'jenis_penghapusan_id', 'kode');
    }

    //TODO
    public function pemanfaatan()
    {
        // JENIS PEMANFAATAN
    }

    protected function getLastNumber($kategori)
    {
        $last = $this->where('kategori_id', $kategori)->orderBy('nup', 'desc')->first();
        return $last->nup ?? 0;
    }

    public function getDeskripsiAttribute()
    {
        return str_replace("'", "`", $this->attributes['deskripsi']);
    }
}
