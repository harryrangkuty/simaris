<?php

namespace App\Http\Controllers\Persediaan;

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use App\Models\Persediaan\Barang;
use App\Models\Persediaan\PersediaanTransaksi;
use App\Models\MasterData\Category;

class BarangController extends AuthController
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
    }

    public function __invoke(Request $request)
    {

        if ($request->req == 'open') {
            $constant = [
                'GUDANG_OPT' => $this->list_gudang()->keyBy('id')
            ];

            $data = Barang::withTrashed()
                            ->withSumTransaksi()
                            ->where('id', $request->id)
                            ->first();

            $vue = "<barang-detail-page  :constant='" . json_encode($constant) . "' :parent='" . json_encode($data) . "' />";
        }else{
            $constant = [
                'GUDANG_OPT' => $this->list_gudang()->keyBy('id')
            ];
    
            $vue = "<barang-page :constant='" . json_encode($constant) . "' />";
        }
        return response()->view('layouts.antd', compact('vue'));
    }

    public function read(Request $request)
    {
        if($request->req == 'table'){
            $data = Barang::withTrashed()
                ->withSumTransaksi($request->gudang_id)
                ->where(function ($q) use ($request) {
                    $q->where('persediaan_barang.nama', 'like', "%{$request->search}%");
                })
                ->paginate($this->per_page());

            return response()->json(['models' => $data]);
        }
        elseif($request->req == 'data_transaksi_barang'){
            $data = PersediaanTransaksi::with('reference')
                                        ->whereDateBetween('persediaan_transaksi.created_at', $request->date_start, $request->date_end)
                                        ->where('barang_id', $request->id)
                                        ->where('is_completed', 1)
                                        ->where(function ($q) use ($request) {
                                            if( $request->gudang_id)
                                                $q->where('gudang_id', $request->gudang_id);
                                        })
                                        ->get();

            $stock = 0;
            $saldo = 0;

            foreach ($data as $item) {
                $stock += $item->kuantitas;
                $item->stock_setelah = $stock;
                $saldo += $item->sub_total;
                $item->saldo_setelah = $saldo;
            }
                        
            $data = $data->sortByDesc('created_at')->values();

            return response()->json(['models' => $data]);
        }
    }

    public function write(Request $request)
    {
        if($request->req == 'write'){
            $this->validate($request, [
                'kategori_id' => 'required',
                'nama' => 'required|unique:persediaan_barang,nama,' . $request->id,
                'status_keluar' => 'required',
                'status_masuk' => 'required',
                'stok_minimum' => 'required|numeric',
            ]);

            $data = Barang::find($request->id) ?? new Barang();
            $data->kategori_id = $request->kategori_id;
            $data->kategori_nama = $request->kategori_nama;
            $data->nama = $request->nama;
            $data->satuan_id = $request->satuan_id;
            $data->satuan_nama = $request->satuan_nama;
            $data->status_keluar = $request->status_keluar;
            $data->status_masuk = $request->status_masuk;
            $data->stok_minimum = $request->stok_minimum;
            $data->stok_maksimum = $request->stok_maksimum;
            $data->editor_id = $this->user()->id;
            $data->save();

            return response()->json(true);
        }

        elseif($request->req == 'toggle'){
            $data = Barang::withTrashed()->find($request->id);
            if(!$data)
                return response()->json('Data Not Found', 403);
            
            $data->editor_id = $this->user()->id;
            $data->save();
            return response()->json($data->deleted_at ? $data->restore() : $data->delete());
        }

        elseif($request->req == 'delete'){
            $data = Barang::withTrashed()->find($request->id);
            if(!$data)
                return response()->json('Data Not Found', 403);
            
            $data->editor_id = $this->user()->id;
            $data->save();
            return response()->json($data->forceDelete());
        }
    }
}
