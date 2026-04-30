<?php

namespace App\Models\MasterData;

use App\Models\Asset\AssetProfile;
use App\Models\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Room extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'legacy_id',
        'code',
        'name',
        'building_id',
        'floor',
        'person_in_charge_id',
        'registered_at',
        'is_lab',
    ];

    protected $casts = [
        'is_lab' => 'boolean',
    ];

    public function profil()
    {
        return $this->hasMany(AssetProfile::class, 'room_id');
    }

    public function getNamaAttribute()
    {
        return str_replace("'", '`', $this->attributes['name']);
    }

    public function building()
    {
        return $this->belongsTo(Building::class);
    }

    public function personInCharge()
    {
        return $this->belongsTo(User::class, 'person_in_charge_id');
    }
}
