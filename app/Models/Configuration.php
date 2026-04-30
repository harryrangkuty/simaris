<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Configuration extends Model
{
    use HasFactory;

    protected $table = 'configurations';
    public $incrementing = false;
    protected $primaryKey = 'key';
    public $timestamps = false;
    protected $fillable = [
        'key', 
        'label', 
        'value', 
        'message'
    ];
}

