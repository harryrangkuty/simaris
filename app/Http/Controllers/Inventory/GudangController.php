<?php

namespace App\Http\Controllers\Persediaan;

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use App\Models\Persediaan\Gudang;
use App\Models\User;

class GudangController extends AuthController
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
    }

    public function __invoke(Request $request)
    {
        $constant = [
            
        ];

        $vue = "<gudang-page :constant='" . json_encode($constant) . "' />";
        return response()->view('layouts.antd', compact('vue'));
    }

    public function read(Request $request)
    {
        if($request->req == 'table'){
            $data = Gudang::withTrashed()
                ->where(function ($q) use ($request) {
                    $q->where('persediaan_gudang.nama', 'like', "%{$request->search}%");
                })
                ->paginate($this->per_page());

            return response()->json(['models' => $data]);
        }

        elseif($request->req == 'single'){
            $data = Gudang::find($request->id);
            if(!$data)
                return response()->json('Data Not Found', 403);

            $users = User::whereIn('id', $data->users_id)->select('id','name')->get();

            return response()->json(['models' => $data, 'users' => $users]);
        }

        elseif ($request->req == 'list_user') {
            $data = User::where('users.name', 'like', "%{$request->name}%")
                ->get();

            return response()->json($data);
        }
    }

    public function write(Request $request)
    {
        if($request->req == 'write'){
            $this->validate($request, [
                'nama' => 'required|unique:persediaan_gudang,nama,' . $request->id,
                'kode' => 'required|unique:persediaan_gudang,kode,' . $request->id,
                'status_masuk' => 'required',
                'status_keluar' => 'required'
            ]);

            $data = Gudang::find($request->id) ?? new Gudang();

            $data->nama = $request->nama;
            $data->kode = $request->kode;
            $data->status_keluar = $request->status_keluar;
            $data->status_masuk = $request->status_masuk;
            $data->users_id = $request->users_id;
            $data->editor_id = $this->user()->id;
            return response()->json($data->save());
        }

        elseif($request->req == 'toggle'){
            $data = Gudang::withTrashed()->find($request->id);

            if(!$data)
                return response()->json('Data Not Found', 403);
            
            $data->editor_id = $this->user()->id;
            $data->save();
            return response()->json($data->deleted_at ? $data->restore() : $data->delete());
        }

        elseif($request->req == 'delete'){
            $data = Gudang::withTrashed()->find($request->id);
            if(!$data)
                return response()->json('Data Not Found', 403);
            
            $data->save();  
            return response()->json($data->forceDelete());
        }   
    }
}
