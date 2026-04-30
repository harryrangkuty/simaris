<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as BaseModel;
use App\Models\User;
use DateTimeInterface;
use DateTime;
use App\Traits\ModelAppend;

class Model extends BaseModel
{
    use HasFactory, ModelAppend;

    protected $casts = [
        'created_at' => 'datetime'
    ];

    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function getCreatedAtAttribute($date)
    {
        $d = new DateTime($date);
        return $d->format('Y-m-d H:i:s');
    }

    public function getUpdatedAtAttribute($date)
    {
        $d = new DateTime($date);
        return $d->format('Y-m-d H:i:s');
    }

    public function scopeWhereDateBetween($q, $column, $start, $end)
    {

        if (!$start || !$end)
            return;

        $q->whereDate($column, '>=', $start)
            ->whereDate($column, '<=', $end);
    }

    public function getMorphClassAttribute()
    {
        return $this->getMorphClass();
    }

    public function editor()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

}
