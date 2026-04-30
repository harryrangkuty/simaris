<?php

namespace App\Http\Controllers\Administrator;

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use App\Models\Unit;

class UnitController extends AuthController
{
    public function __invoke(Request $request)
    {
        $constant = [
            'TYPES' => Unit::getTypes(),
        ];

        $title = 'Manajemen Unit';
        $vue = "<unit-page :title='" . json_encode($title) . "' :constant='" . json_encode($constant) . "' />";
        return response()->view('layouts.antd', compact('vue', 'title'));
    }
    public function read(Request $request)
    {
        if ($request->req == 'table') {
            $data = Unit::where(function ($q) use ($request) {
                if($request->search)
                $q->where('name', 'like', "%{$request->search}%");
        })
            ->where(function ($q) use ($request) {
                if($request->unit_type)
                $q->where('type', $request->unit_type);
        })
        ->paginate($this->per_page());
        
        return response()->json(['models' => $data]);
        } 
    }
    public function write(Request $request)
    {
        if ($request->req == 'write') {
            
            $data = Unit::find($request->id) ?? new Unit();
            
            $data->name = $request->name;
            $data->code = $request->code;
            $data->type = $request->type;
            $data->level = $request->level;
            $data->nim_label = $request->nim_label;
            $data->is_active = $request->is_active ? true : false;
            $data->is_satker = $request->is_satker ? true : false;
            $data->is_fakultas = $request->is_fakultas ? true : false;
            $data->is_prodi = $request->is_prodi ? true : false;
            $data->save();
            return response()->json(true);
        }
        elseif ($request->req == 'delete'){
            $data = Unit::find($request->id);
            $data->delete();
            return response()->json($data);
        }
        elseif ($request->req == 'restore'){
            $data = Unit::onlyTrashed()->where('id',$request->id);
            $data->restore();
            return response()->json($data);
        }
    }
}
