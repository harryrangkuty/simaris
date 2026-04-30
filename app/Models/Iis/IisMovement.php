<?php

namespace App\Models\Iis;

use App\Models\Model;
use App\Models\User;

class IisMovement extends Model
{
    protected $table = 'iis_movements';

    protected $fillable = [
        'code',
        'asset_type',
        'movement_type',

        // PJ
        'from_pj_id',
        'to_pj_id',

        'approver_id',
        'approver_role',

        // META
        'operator_id',
        'movement_date',
        'notes',

        // STATUS
        'status',

        // SPESIAL ACTION PEMINJAMAN
        'borrowed_at',
        'borrowed_due_at',
        'is_external',

        // WORKFLOW META
        'submitted_by',
        'submitted_at',
        'approved_by',
        'approved_at',
        'verified_by',
        'verified_at',
        'rejected_by',
        'rejected_at',
        'rejection_note',
    ];

    protected $casts = [
        'movement_date' => 'datetime',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'verified_at' => 'datetime',
        'rejected_at' => 'datetime',
        'borrowed_at' => 'datetime',
        'borrowed_due_at' => 'datetime',
    ];

    /* ================= RELATIONS ================= */

    public function items()
    {
        return $this->hasMany(IisMovementItem::class, 'movement_id');
    }

    public function operator()
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approvals()
    {
        return $this->hasMany(IisMovementApproval::class, 'movement_id')
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

    public function fromPj()
    {
        return $this->belongsTo(User::class, 'from_pj_id');
    }

    public function toPj()
    {
        return $this->belongsTo(User::class, 'to_pj_id');
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
