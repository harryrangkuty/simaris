<?php

namespace App\Models\Iis;

use App\Models\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class IisQrCodeHandoverApproval extends Model
{
    use HasFactory;

    protected $table = 'iis_qr_code_handover_approvals';

    protected $fillable = [
        'handover_id',
        'user_id',
        'position',
        'approval_order',
        'status',
        'approved_at',
        'rejected_at',
        'note',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function handover()
    {
        return $this->belongsTo(IisQrCodeHandover::class, 'handover_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    public function isApproved()
    {
        return $this->status === 'approved';
    }

    public function isRejected()
    {
        return $this->status === 'rejected';
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }
}
