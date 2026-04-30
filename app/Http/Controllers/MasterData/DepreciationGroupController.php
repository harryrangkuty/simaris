<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use App\Models\MasterData\DepreciationGroup;

class DepreciationGroupController extends AuthController
{
    public function __invoke(Request $request)
    {
        $constant = [];

        $title = 'Manajemen Kelompok Penyusutan';
        
        $vue = "<depreciation-group-page :title='" . json_encode($title) . "' :constant='" . json_encode($constant) . "' />";

        return view('layouts.antd', compact('vue', 'title'));
    }

    public function read(Request $request)
    {
        if($request->req == 'table'){
            $data = DepreciationGroup::where(function ($q) use ($request) {
                    $q->where('code', 'like', "%{$request->search}%")
                    ->orWhere('name', 'like', "%{$request->search}%");
                })
                ->paginate($this->per_page());
                
            return response()->json(['models' => $data]);
        }
    }

    public function write(Request $request)
    {
        if($request->req == 'write'){
            //
        }
        elseif ($request->req == 'delete') {
            //
        }
    }
}
