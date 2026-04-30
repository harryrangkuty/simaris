<?php

namespace App\Models\Persediaan;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PersediaanMasukDetail extends PersediaanTransaksi
{
    use HasFactory;

    protected static function boot() {
        parent::boot();
        static::addGlobalScope('reference_type', function (Builder $builder) {
            $builder->where('reference_type', 'PERSEDIAAN-MASUK');
        });
    }
	
	public function __construct(array $attributes = []) {
		parent::__construct($attributes);
		$this->attributes['reference_type'] = 'PERSEDIAAN-MASUK';
	}

    public function persediaanMasuk() {
		return $this->belongsTo(PersediaanMasuk::class, 'reference_id');
	}
}
