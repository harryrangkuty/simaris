<?php

namespace App\Models\Persediaan;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Model;
use App\Models\JenisTransaksi;
use App\Models\MasterData\Category;

class PersediaanTransaksi extends Model
{
	use HasFactory;

	protected $table = 'persediaan_transaksi';

	protected $appends = [
		'sub_total'
	];

	protected $casts = [
		'is_completed' => 'boolean',
		'habis' => 'boolean',
	];

	public function reference()
	{
		return $this->morphTo();
	}

	public function kategori()
	{
		return $this->belongsTo(Kategori::class)->withTrashed();
	}

	public function gudang()
	{
		return $this->belongsTo(Gudang::class)->withTrashed();
	}

	public function jenisTransaksi()
	{
		return $this->belongsTo(JenisTransaksi::class)->withTrashed();
	}

	public function getSubTotalAttribute()
	{
		return ($this->kuantitas * $this->harga);
	}

	public function scopeCompleted($q)
	{
		$q->where('is_completed', true);
	}

	public function scopeBatchMasuk($q, $gudang_id = null)
	{
		$q->completed()
			->where('reference_type', 'PERSEDIAAN-MASUK')
			->where('habis', false)
			->where(function ($q) use ($gudang_id) {
				if ($gudang_id)
					$q->where('gudang_id', $gudang_id);
			})
			->selectRaw("id, barang_id, sum( kuantitas ) as total, reference_id, harga, (sum( kuantitas ) * harga) as sub_total_batch")
			->groupBy('id', 'barang_id', 'reference_id', 'harga');
	}

	public function scopeTotalStock($q, $gudang_id = null)
	{
		$q->completed()
			->where(function ($q) use ($gudang_id) {
				if ($gudang_id)
					$q->where('gudang_id', $gudang_id);
			})
			->selectRaw("barang_id, sum( kuantitas ) AS total")
			->groupBy('barang_id');
	}
}
