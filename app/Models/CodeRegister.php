<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use DateTime;

class CodeRegister extends Model
{
    public $timestamps = false;

    protected $daily_number = [
        'inventory_out',
        'inventory_mutation',
        'asset_disposal',
    ];

    protected $monthly_number = [
        'procurement',
        'asset_change',
    ];

    protected function update_number($name)
    {
        $number = $this->where('name', $name)->first();
        if (!$number) return;
        $number->last_number = $number->last_number + 1;
        $number->last_date = date('Y-m-d');
        return $number->save();
    }

    protected function next($name)
    {
        $register = $this->where('name', $name)->first();
        if (!$register) {
            $register = new self();
            $register->name = $name;
            $register->last_number = 0;
            $register->last_date = date('Y-m-d');
            $register->save();
        }

        if (in_array($name, $this->daily_number)) {
            $today = date('Y-m-d');
            if ($register->last_date != $today) {
                $register->last_date = $today;
                $register->last_number = 0;
                $register->save();
            }

            $number = $register->last_number + 1;
            $str_number = str_pad($number, 4, "0", STR_PAD_LEFT);
            return setting("{$name}_prefix", config("setting.prefix_number.{$name}")) . '-' . date('ymd') . '-' . $str_number;
        } elseif (in_array($name, $this->monthly_number)) {
            $last_date = DateTime::createFromFormat('Y-m-d', $register->last_date);
            $today = new DateTime();
            if ($today->format('Y-m') != $last_date->format('Y-m')) {
                $register->last_date = $today->format('Y-m') . '-01';
                $register->last_number = 0;
                $register->save();
            }

            $number = $register->last_number + 1;
            $str_number = str_pad($number, 4, "0", STR_PAD_LEFT);
            return setting("{$name}_prefix", config("setting.prefix_number.{$name}")) . '-' . date('ymd') . '-' . $str_number;
        }
    }
}
