<?php

namespace App\Models\Iis;

use App\Models\Model;

class IisQrCodeHandoverItem extends Model
{
    protected $table = 'iis_qr_code_handover_items';

    protected $fillable = [
        'qr_code_handover_id',
        'asset_id',
        'qr_code_no',
        'category_name',
        'description',
        'condition',
    ];

    /* ================= RELATIONS ================= */

    public function handover()
    {
        return $this->belongsTo(
            IisQrCodeHandover::class,
            'qr_code_handover_id'
        );
    }

    /**
     * Ambil model asset IIS secara dinamis
     */
    public function asset()
    {
        return match ($this->asset_type) {
            'inventory' => $this->belongsTo(
                IisInventory::class,
                'asset_id'
            ),
            'alkes' => $this->belongsTo(
                IisAlkes::class,
                'asset_id'
            ),
            default => null,
        };
    }
}
