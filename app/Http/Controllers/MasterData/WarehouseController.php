<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\AuthController;
use App\Models\MasterData\Branch;
use App\Models\MasterData\StockCode;
use App\Models\MasterData\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WarehouseController extends AuthController
{
    public function __invoke(Request $request)
    {
        $constant = [
            'BRANCHES' => Branch::select('id', 'code', 'name')->get(),
            'STOCK_CODE_OPTIONS' => StockCode::all(),
        ];

        $title = 'Manajemen Gudang';

        $vue = "<warehouse-page :title='".json_encode($title)."' :constant='".json_encode($constant)."' />";

        return response()->view('layouts.antd', compact('vue', 'title'));
    }

    public function read(Request $request)
    {
        if ($request->req == 'table') {
            $data = Warehouse::withTrashed()
                ->with(['personInCharge:id,identifier,name', 'stockCodes:code,name', 'branch:id,name'])
                ->where(function ($q) use ($request) {
                    if ($request->branch_id) {
                        $q->where('branch_id', $request->branch_id);
                    }
                })
                ->where(function ($q) use ($request) {
                    $q->where('name', 'like', "%{$request->search}%");
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
        if ($request->req === 'write') {

            $this->validate(
                $request,
                [
                    'branch_id' => 'required|exists:branches,id',
                    'code' => 'required|unique:warehouses,code,'.$request->id.',id,deleted_at,NULL',
                    'name' => 'required|unique:warehouses,name,'.$request->id.',id,branch_id,'.$request->branch_id.',deleted_at,NULL',
                    'address' => 'nullable|string|max:255',
                    'can_receive' => 'required|boolean',
                    'can_dispatch' => 'required|boolean',
                    'person_in_charge_id' => 'required',
                    'stock_code_ids' => 'required|array|min:1',
                    'stock_code_ids.*' => 'exists:stock_codes,code',
                ],
                [
                    'code.required' => 'Kode gudang wajib diisi.',
                    'code.unique' => 'Kode gudang sudah terdaftar.',
                    'name.required' => 'Nama gudang wajib diisi.',
                    'name.unique' => 'Nama gudang sudah terdaftar.',
                    'address.max' => 'Lokasi gudang tidak boleh lebih dari 255 karakter.',
                    'can_receive.required' => 'Status penerimaan barang wajib dipilih.',
                    'can_dispatch.required' => 'Status pengeluaran barang wajib dipilih.',
                    'person_in_charge_id.required' => 'PJ Gudang wajib dipilih.',
                    'stock_code_ids.required' => 'Jenis Stock wajib dipilih.',
                    'stock_code_ids.*.exists' => 'Stock code tidak valid.',
                ]
            );

            DB::transaction(function () use ($request) {

                $warehouse = Warehouse::updateOrCreate(
                    ['id' => $request->id],
                    [
                        'branch_id' => $request->branch_id,
                        'code' => $request->code,
                        'name' => $request->name,
                        'address' => $request->address,
                        'description' => $request->description,
                        'can_receive' => $request->can_receive,
                        'can_dispatch' => $request->can_dispatch,
                        'person_in_charge_id' => $request->person_in_charge_id,
                        'editor_id' => $this->user()->id,
                    ]
                );

                $warehouse->stockCodes()->sync($request->stock_code_ids);
            });

            return response()->json(true);
        } elseif ($request->req === 'delete') {
            $data = Warehouse::find($request->id);

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
        } elseif ($request->req === 'restore') {
            $data = Warehouse::onlyTrashed()->find($request->id);

            if (! $data) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data tidak ditemukan atau belum terhapus',
                ], 404);
            }

            $data->editor_id = $this->user()->id;
            $data->restore();

            return response()->json([
                'status' => 'success',
                'action' => 'restore',
                'data' => $data,
            ]);
        }
    }
}
