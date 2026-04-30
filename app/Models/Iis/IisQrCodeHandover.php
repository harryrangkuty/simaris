<?php

namespace App\Models\Iis;

use App\Models\Model;
use App\Models\User;

class IisQrCodeHandover extends Model
{
    protected $table = 'iis_qr_code_handovers';

    protected $fillable = [
        'code',
        'asset_type',
        'pj_id',
        'operator_id',
        'status',
        'submitted_at',
        'verified_at',
        'created_by',
        'updated_at',
        'verified_by',
        'notes',
        'rejection_note',
        'rejected_by',
        'rejected_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->created_by = auth()->id();
        });

        static::updating(function ($model) {
            $model->updated_by = auth()->id();
        });
    }

    /* ================= RELATIONS ================= */

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function items()
    {
        return $this->hasMany(
            IisQrCodeHandoverItem::class,
            'qr_code_handover_id'
        );
    }

    public function pj()
    {
        return $this->belongsTo(User::class, 'pj_id');
    }

    public function operator()
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    public function approvals()
    {
        return $this->hasMany(IisQrCodeHandoverApproval::class, 'handover_id')
            ->orderBy('approval_order');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function rejector()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    /* ================= STATUS HELPERS ================= */

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isSubmitted(): bool
    {
        return $this->status === 'submitted';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isVerified(): bool
    {
        return $this->status === 'verified';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }
}
