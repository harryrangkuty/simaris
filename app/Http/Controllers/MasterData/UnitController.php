<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\AuthController;
use App\Models\MasterData\Unit;
use Illuminate\Http\Request;

class UnitController extends AuthController
{
    public function __invoke(Request $request)
    {
        $constant = [];

        $title = 'Manajemen Unit/Satuan Kerja RS';
        $vue = "<unit-page :title='".json_encode($title)."' :constant='".json_encode($constant)."' />";

        return response()->view('layouts.antd', compact('vue', 'title'));
    }

    public function read(Request $request)
    {
        if ($request->req == 'table') {
            $data = Unit::withTrashed()
                ->where(function ($q) use ($request) {
                    if ($request->search) {
                        $q->where('name', 'like', "%{$request->search}%")
                            ->orWhere('department', 'like', "%$request->search%");
                    }
                })
                ->where(function ($q) use ($request) {
                    if ($request->status == 'aktif') {
                        $q->whereNull('deleted_at');
                    } else {
                        $q->whereNotNull('deleted_at');
                    }
                })
                ->paginate($this->per_page());

            return response()->json(['models' => $data]);
        }
    }

    public function write(Request $request)
    {
        if ($request->req == 'write') {
            $this->validate(
                $request,
                [
                    'name' => 'required',
                    'department' => 'required',
                ],
                [
                    'name.required' => 'Nama Unit wajib diisi.',
                    'department.required' => 'Nama Departemen wajib diisi.'
                ]
            );

            $data = Unit::find($request->id) ?? new Unit;

            $data->name = $request->name;
            $data->department = $request->department;

            return response()->json($data->save());
        } elseif ($request->req === 'delete') {
            $data = Unit::find($request->id);

            if (! $data) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data tidak ditemukan',
                ], 404);
            }

            $data->delete();

            return response()->json([
                'status' => 'success',
                'action' => 'delete',
                'data' => $data,
            ]);
        } elseif ($request->req === 'restore') {
            $data = Unit::onlyTrashed()->find($request->id);

            if (! $data) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data tidak ditemukan atau belum terhapus',
                ], 404);
            }

            $data->restore();

            return response()->json([
                'status' => 'success',
                'action' => 'restore',
                'data' => $data,
            ]);
        }
    }
}
