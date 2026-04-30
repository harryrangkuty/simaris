<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\AuthController;
use App\Models\MasterData\Branch;
use App\Models\MasterData\Building;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BuildingController extends AuthController
{
    public function __invoke(Request $request)
    {
        $constant = [
            'BRANCHES' => Branch::select('id', 'code', 'name')->get(),
        ];

        $title = 'Manajemen Gedung';

        $vue = "<building-page :title='".json_encode($title)."' :constant='".json_encode($constant)."' />";

        return response()->view('layouts.antd', compact('vue', 'title'));
    }

    public function read(Request $request)
    {
        if ($request->req == 'table') {
            $data = Building::with(['branch:id,name'])
                ->where(function ($q) use ($request) {
                    if ($request->branch_id) {
                        $q->where('branch_id', $request->branch_id);
                    }
                })
                ->where(function ($q) use ($request) {
                    $q->where('name', 'like', "%{$request->search}%");
                })
                ->paginate($this->per_page());

            return response()->json(['models' => $data]);
        }
    }

    public function write(Request $request)
    {
        if ($request->req === 'write') {

            $this->validate(
                $request,
                [
                    'branch_id' => 'required|exists:branches,id',
                    'name' => 'required|string|max:100|unique:buildings,name,'.$request->id.',id,branch_id,'.$request->branch_id,
                    'floors_count' => 'required|integer|min:1',
                ],
                [
                    'branch_id.required' => 'Cabang wajib dipilih.',
                    'branch_id.exists' => 'Cabang tidak valid.',
                    'name.required' => 'Nama gedung wajib diisi.',
                    'name.unique' => 'Nama gedung sudah terdaftar di cabang ini.',
                    'floors_count.required' => 'Jumlah lantai wajib diisi.',
                    'floors_count.integer' => 'Jumlah lantai harus berupa angka.',
                    'floors_count.min' => 'Jumlah lantai minimal 1.',
                ]
            );

            DB::transaction(function () use ($request) {

                Building::updateOrCreate(
                    ['id' => $request->id],
                    [
                        'branch_id' => $request->branch_id,
                        'name' => $request->name,
                        'floors_count' => $request->floors_count,
                        'created_by' => $request->id ? null : $this->user()->id,
                        'updated_by' => $this->user()->id,
                    ]
                );
            });

            return response()->json(true);
        } elseif ($request->req === 'delete') {
            $data = Building::find($request->id);

            if (! $data) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data tidak ditemukan',
                ], 404);
            }

            $data->editor_id = $this->user()->id;
            $data->delete();

            return response()->json([
                'status' => 'success',
                'action' => 'delete',
                'data' => $data,
            ]);
        }
    }
}
