<?php

use App\Services\MenuService;


if (! function_exists('menu_list')) {
    function menu_list($user = null)
    {
        return MenuService::forUser($user ?? auth()->user());
    }
}

if (! function_exists('setting')) {
    function setting($key, $default = null)
    {

        if (isset($value)) {
            return $value;
        }

        if ($default) {
            return $default;
        }

        return config('setting.'.$key);
    }
}

if (! function_exists('stringToArray')) {
    function stringToArray($string)
    {
        // Remove whole spaces
        $string = str_replace(' ', '', $string);

        // Split string by comma (,)
        $array_by_comma = explode(',', $string);

        // Set $array as the return array
        $array = $array_by_comma;

        // Each elements in array $array
        foreach ($array as $key => $value) {

            // Check if contains minus (-)
            if (str_contains($value, '-')) {

                // Remove elements by $value from array $array
                $array = array_diff($array, [$value]);

                // Split string from $value by minus (-)
                $array_by_minus = explode('-', $value);

                // If first element greater than second element then return
                if ($array_by_minus[0] > $array_by_minus[1]) {
                    abort(403, "$value (Parameter kedua lebih kecil dari parameter pertama)");
                }

                // Create array with elements from $array_by_minus[0] to $array_by_minus[1]
                $range = range($array_by_minus[0], $array_by_minus[1]);

                // Merge array in $range to $array_by_comma
                $array = array_merge($array, $range);
            }
        }

        // Convert elements in $array from string to integer
        $array = array_map('intval', $array);

        // Return converted string to array
        return $array;
    }
}

if (! function_exists('checkDiff')) {
    function checkDiff($array_id, $response)
    {
        // Array add to collection
        $array_id = collect($array_id);

        // Keys of collection $response become value of ids
        $response_id = $response->keyBy('id')->keys();

        // Elements from $array_id those not inside $response_id
        $diff = $array_id->diff($response_id);

        // If elements from $array_id those not inside $response_id exist
        if ($diff->isNotEmpty()) {

            // Convert collection to array then to string
            $diff = implode(', ', $diff->toArray());
            abort(403, "$diff tidak terdaftar");
        } else {

            // Return $response if all ids exist
            return response()->json(response()->json($response));
        }
    }
}

if (! function_exists('idCurrency')) {
    function idCurrency($number)
    {
        if (! is_numeric($number)) {
            return $number;
        }

        $number = str_replace('.', '#', $number);
        $number = preg_replace('/(\d)(?=(\d{3})+(?!\d))/', '$1.', $number);
        $number = str_replace('#', ',', $number);

        return $number;
    }
}

if (! function_exists('auto_nup_generator')) {
    function auto_nup_generator()
    {
        $last = \App\Models\Asset\AssetProfile::orderBy('asset_number', 'DESC')->first();

        if (! $last || ! $last->asset_number) {
            return '0001';
        }

        // ambil angka dari asset_number
        $num = intval($last->asset_number) + 1;

        // selalu jadi 4 digit
        return str_pad($num, 4, '0', STR_PAD_LEFT);
    }
}
