<?php

namespace App\Http\Controllers\Iis;

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use App\Models\Iis\IisItem;

class IisItemsController extends AuthController
{
    public function __invoke(Request $request)
    {
        $constant = [];

        $title = 'Data Master Item IIS';

        $vue = "<iis-items-page :title='" . json_encode($title) . "' :constant='" . json_encode($constant) . "' />";
        return response()->view('layouts.antd', compact('vue', 'title'));
    }

    public function read(Request $request)
    {
        if ($request->req == 'table') {
            $data = IisItem::where(function ($q) use ($request) {
                    $q->where('item_no', 'like', "%{$request->search}%")
                    ->orWhere('name', 'like', "%{$request->search}%");
                })
                ->paginate($this->per_page());
            return response()->json(['models' => $data]);
        }
    }
}
