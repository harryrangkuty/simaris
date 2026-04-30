<?php

namespace App\Models\Iis;

use App\Models\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class IisMovementApproval extends Model
{
    use HasFactory;

    protected $table = 'iis_movement_approvals';

    protected $fillable = [
        'movement_id',
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

    public function movement()
    {
        return $this->belongsTo(IisMovement::class, 'movement_id');
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
