<?php

namespace App\Http\Controllers\Iis;

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use App\Models\Iis\IisCategory;

class IisCategoriesController extends AuthController
{
    public function __invoke(Request $request)
    {
        $constant = [];

        $title = 'Kategori Inventaris IIS';

        $vue = "<iis-categories-page :title='" . json_encode($title) . "' :constant='" . json_encode($constant) . "' />";
        return response()->view('layouts.antd', compact('vue', 'title'));
    }

    public function read(Request $request)
    {
        if ($request->req == 'table') {
            $data = IisCategory::where(function ($q) use ($request) {
                    $q->where('category_name', 'like', "%{$request->search}%");
                })
                ->paginate($this->per_page());

            return response()->json(['models' => $data]);
        }
    }

    public function write(Request $request)
    {
        if ($request->req === 'delete') {
            $data = IisCategory::find($request->id);
            $data->delete();

            return response()->json(['status' => 'deleted']);
        }

        $request->validate([
            'category_name' => 'required|string|unique:iis_categories_list,category_name,'.$request->id,
        ]);

        IisCategory::updateOrCreate(
            ['id' => $request->id],
            ['category_name' => $request->category_name]
        );

        return response()->json(true);
    }
}
