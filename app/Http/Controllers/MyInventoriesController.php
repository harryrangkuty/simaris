<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Aset\AsetPenyusutan;
use App\Models\Aset\AssetTransaction;
use App\Models\Persediaan\Barang;
use App\Models\MasterData\Category;
use App\Models\Persediaan\PersediaanTransaksi;

class MyInventoriesController extends AuthController
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        $constant = [
            'user' => $user
        ];

        $title = "Dashboard";

        $vue = "<my-inventories-page :title='" . json_encode($title) . "' :constant='" . e(json_encode($constant)) . "' />";

        return view('layouts.antd', compact('vue', 'title'));
    }

    public function read(Request $request)
    {
        //
    }
}
