<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use App\Models\MasterData\Uom;

class UomController extends AuthController
{
    public function __invoke(Request $request)
    {
        $constant = [];

        $title = 'Manajemen Satuan Barang';

        $vue = "<uom-page :title='" . json_encode($title) . "' :constant='" . json_encode($constant) . "' />";

        return response()->view('layouts.antd', compact('vue', 'title'));
    }

    public function read(Request $request)
    {
        if ($request->req == 'table') {
            $data = Uom::where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%");
            })
                ->where(function ($q) use ($request) {
                    if ($request->status == 'aktif') {
                        $q->where('is_active', true);
                    } else {
                        $q->where('is_active', false);
                    }
                })
                ->paginate($this->per_page());

            return response()->json(['models' => $data]);
        }
    }

    public function write(Request $request)
    {
        //
    }
}
