<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use App\Models\MasterData\Category;

class CategoryController extends AuthController
{
    public function __invoke(Request $request)
    {
        $constant = [];

        $title = 'Manajemen Kategori Barang';

        $vue = "<category-page :title='" . json_encode($title) . "' :constant='" . json_encode($constant) . "' />";

        return view('layouts.antd', compact('vue', 'title'));
    }

    public function read(Request $request)
    {
        if ($request->req == 'table') {
            $data = Category::where(function ($q) use ($request) {
                    if ($request->search)
                        $q->where('name', 'LIKE', "%{$request->search}%")
                            ->orWhere('code', 'LIKE', "%{$request->search}%");
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
