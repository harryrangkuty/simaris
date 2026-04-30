<?php

namespace App\Models\Asset;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Model;
use App\Models\MasterData\MasaManfaat;
use App\Models\MasterData\TransactionType;
use DateTime;

class AsetPenyusutan extends Model
{
    use HasFactory;

    protected $table = 'aset_penyusutan';

    protected $fillable = [
        'profil_id',
        'editor_id',
        'satker_id',
        'jenis_transaksi_id',
        'kategori_id',
        'kategori_nama',
        'nup',
        'nilai',
        'komptabel',
        'tgl_penyusutan',
        'bulan',
        'tahun',
    ];
    public $timestamps = false;

    protected function getPenyusutanAwal($kategori, $nilai,  $x, $y)
    {
        if (in_array(substr($kategori, 0, 1), ['1', '2', '7']))
            return (object)['akum' => 0, 'single' => 0];

        $kelompok = substr($kategori, 0, 5);
        $masa_manfaat = MasaManfaat::where('kdkbrg', $kelompok)->first();

        if (!$masa_manfaat || $masa_manfaat->umur_bulan <= 0 || $y > $x || (strlen($kategori) < 10))
            return (object)['akum' => 0, 'single' => 0];

        $awal = new DateTime($x);
        $perolehan = new DateTime($y);
        $diff = $awal->diff($perolehan);
        $bulan = ($diff->y * 12) + $diff->m;

        $single = round((1 / $masa_manfaat->umur_bulan) * $nilai, 2);
        $akum = $bulan * $single;

        return (object)['akum' => $akum, 'single' => $single];
    }

    public function jenisTransaksi()
    {
        return $this->belongsTo(TransactionType::class, 'jenis_transaksi_id', 'kode');
    }
}
