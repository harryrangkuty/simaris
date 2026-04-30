<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use App\Models\MasterData\Supplier;

class SupplierController extends AuthController
{
    public function __invoke(Request $request)
    {
        $constant = [];

        $title = 'Manajemen Supplier Barang';

        $vue = "<supplier-page :title='" . json_encode($title) . "' :constant='" . json_encode($constant) . "' />";
        return response()->view('layouts.antd', compact('vue', 'title'));
    }

    public function read(Request $request)
    {
        if ($request->req == 'table') {
            $data = Supplier::withTrashed()
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
        if ($request->req == 'write') {
            $this->validate(
                $request,
                [
                    'name' => 'required|unique:suppliers,name,' . $request->id,
                    'gl_code' => 'required|unique:suppliers,gl_code,' . $request->id,
                    'address' => 'required',
                    'phone' => 'required|string|max:25',
                    'is_ppn' => 'required',
                ],
                [
                    'name.required' => 'Nama supplier wajib diisi.',
                    'name.unique' => 'Nama supplier sudah terdaftar.',
                    'gl_code.required' => 'Kode GL wajib diisi.',
                    'gl_code.unique' => 'Kode GL sudah terdaftar.',
                    'address.required' => 'Alamat wajib diisi.',
                    'phone.required' => 'Nomor telepon wajib diisi.',
                    'phone.max' => 'Nomor telepon tidak boleh lebih dari 25 karakter.',
                    'is_ppn.required' => 'Status PPN wajib dipilih.',
                ]
            );

            $data = Supplier::find($request->id) ?? new Supplier();

            $data->name = $request->name;
            $data->gl_code = $request->gl_code;
            $data->address = $request->address;
            $data->phone = $request->phone;
            $data->is_ppn = $request->is_ppn;
            $data->notes = $request->notes;
            $data->pic_name = $request->pic_name;
            $data->editor_id = $this->user()->id;
            return response()->json($data->save());
        } elseif ($request->req === 'delete') {
            $data = Supplier::find($request->id);

            if (!$data) {
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
            $data = Supplier::onlyTrashed()->find($request->id);

            if (!$data) {
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
