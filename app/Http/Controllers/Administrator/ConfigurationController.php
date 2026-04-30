<?php

namespace App\Http\Controllers\Administrator;

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use App\Models\Configuration;

class ConfigurationController extends AuthController
{
    public function __invoke()
    {
        $constant = [];
        $title = 'Konfigurasi Sistem';

        $vue = "<dynamic-config-page :title='" . json_encode($title) . "' :constant='" . json_encode($constant) . "'/>";

        return view('layouts.antd', compact('vue', 'title'));
    }

    public function read(Request $request)
    {
        if ($request->req == 'table') {
            $data = Configuration::where(function ($q) use ($request) {
                if ($request->search)
                    $q->where('key', 'like', "%{$request->search}%");
            })
                ->get();

            return response()->json(['models' => $data]);
        } elseif ($request->req == 'available_fiscal_year') {
            $data = Configuration::where('key', 'available_fiscal_year')->value('value');

            return response()->json(['models' => $data]);
        }
    }

    public function write(Request $request)
    {
        if ($request->req == 'write') {

            $this->validate($request, [
                'key' => 'required|unique:config,key,' . $request->key . ',key',
            ]);

            $data = Configuration::find($request->key);

            if (!$data) {
                $data = new Configuration();
            }

            $data->key = $request->key;
            $data->value = $request->value;
            $data->message = $request->message;
            $data->save();

            return response()->json(true);
        } elseif ($request->req == 'delete') {
            $data = Configuration::findOrFail($request->key)->delete();
            return response()->json(true);
        }
    }
}
