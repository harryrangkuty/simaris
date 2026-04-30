<?php

namespace App\Http\Controllers\Persediaan;

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use App\Models\Persediaan\Gudang;
use App\Models\Persediaan\Satuan;
use App\Models\Persediaan\Barang;
use App\Models\Persediaan\PersediaanMutasi;
use App\Models\Persediaan\PersediaanMutasiDetail;
use App\Models\Persediaan\PersediaanTransaksi;
use App\Models\Persediaan\PersediaanKeluar;
use App\Models\Persediaan\PersediaanKeluarDetail;
use App\Models\CodeRegister;
use DB;

class PersediaanMutasiController extends AuthController
{
    public function __invoke(Request $request)
    {   
        if($request->req == 'open'){
            $constant = [
                'SATUAN' => Satuan::all(),
            ];

            $data = PersediaanMutasi::with(['gudangAsal', 'gudangTujuan'])->where('kode', $request->kode)->first();
            
            $vue = "<persediaan-mutasi-detail-page :constant='" . json_encode($constant) . "' :parent='" . json_encode($data) . "' />";

        }
        else{   
            $constant = [
                'GUDANG_OPT' => $this->list_gudang()
            ];

            $vue = "<persediaan-mutasi-page :constant='" . json_encode($constant) . "' />";
        }
        
        return response()->view('layouts.antd', compact('vue'));
    }
    
    public function read(Request $request)
    {
        if($request->req == 'table'){
            $data = PersediaanMutasi::whereDateBetween('created_at', $request->date_start, $request->date_end)
                                    ->with(['gudangAsal:id,nama', 'gudangTujuan:id,nama'])
                                    ->where(function($q) use($request){
                                        if($request->status){
                                            $q->where('status', $request->status);
                                        }
                                    })
                                    ->where(function($q) use($request){
                                        if($request->search){
                                            $q->where('kode', 'like', "%{$request->search}%")   
                                              ->orWhere('nomor_invoice', 'like', "%$request->search%");
                                        }
                                    })
                                    ->withCount(['details'])
                                    ->withSum('details', 'kuantitas')
                                    ->paginate($this->per_page());
            
            $dataStatusCount = PersediaanMutasi::get();

            $dataPending = $dataStatusCount->filter(function ($y){
                return $y->status === 'O';
            })->count();

            $dataFinish = $dataStatusCount->filter(function ($x){
                return $x->status === 'F';
            })->count();

            return response()->json(['models' => $data, 'pending_count' => $dataPending, 'finish_count' => $dataFinish]); 
        } elseif ($request->req == 'table_detail') {
            $data = PersediaanMutasiDetail::where('kuantitas', '<', 0)->where('reference_id', $request->id)
                ->selectRaw('barang_id, barang_nama, kategori_id, kategori_nama, SUM(kuantitas) as kuantitas')
                ->groupBy('barang_id', 'barang_nama', 'kategori_id', 'kategori_nama')
                ->get();

            return response()->json(['models' => $data, 'jumlah_barang' => $data->sum('kuantitas')]);
        }
    }

    public function write(Request $request)
    {
        if($request->req == 'write'){
            $this->validate($request, [
                'gudang_asal_id' => 'required',
                'gudang_tujuan_id' => 'required',
                'nomor_invoice' => 'required|unique:persediaan_mutasi,nomor_invoice,' . $request->id,
            ]);

            $data = PersediaanMutasi::find($request->id);
            $isNew = false;
            
            if (!$data) {
                $data = new PersediaanMutasi();
                $data->kode = CodeRegister::next('persediaan_mutasi');
                $data->created_at = date('Y-m-d H:i:s');
                $isNew = true; 
            }

            $data->gudang_asal_id = $request->gudang_asal_id;
            $data->gudang_tujuan_id = $request->gudang_tujuan_id;
            $data->nomor_invoice = $request->nomor_invoice;
            $data->status = "O";
            $data->notes = $request->notes;
            $data->editor_id = request()->user()->id;

            if ($data->save() && $isNew) {
                CodeRegister::update_number('persediaan_mutasi');
            }

            return response()->json(['kode' => $data->kode]);
        } elseif ($request->req == 'write_detail') {
            $this->validate($request, [
                'barang_id' => 'required',
                'kuantitas' => 'required|numeric|min:1',
                'reference_id' => 'required',
            ]);

            $reference = PersediaanMutasi::with(['gudangAsal', 'gudangTujuan'])->find($request->reference_id);
            $barang = Barang::withStockBatch($reference->gudang_asal_id)->withSumTransaksi($reference->gudang_asal_id)->find($request->barang_id);

            if (!$reference || !$barang)
                return response()->json('Data Not Found', 404);

            // Cek Duplikasi
            $checkResult = duplicateCheck(
            $request->barang_id,
            $request->reference_id,
            $reference->gudang_asal_id,
                [
                    [
                        'type' => 'mutasi',
                        'mainModel' => PersediaanMutasi::class,
                        'detailModel' => PersediaanMutasiDetail::class,
                        'isSelf' => true,
                    ],
                    [
                        'type' => 'keluar',
                        'mainModel' => PersediaanKeluar::class,
                        'detailModel' => PersediaanKeluarDetail::class,
                    ],
                ]
            );

            if ($checkResult) return $checkResult;

            if ($barang->stock < $request->kuantitas)
                return response()->json(['message' => 'Stok Tidak Mencukupi'], 403);

            DB::transaction(function () use ($request, $reference, $barang) {
                $needs = $request->kuantitas;
                $harga_total = 0;
                $qty_total = 0;

                foreach ($barang->stockBatch as $bs) {
                    $data = new PersediaanMutasiDetail();

                    $used = PersediaanMutasiDetail::where('reference_in', $bs->id)->sum('kuantitas');
                    $available = $bs->total + $used;

                    if ($needs > $available) {
                        $lefts = 0;
                        $needs -= $available;
                    } else {
                        $lefts = $available - $needs;
                        $needs = 0;
                    }

                    $data->reference_id = $request->reference_id;
                    $data->barang_id = $request->barang_id;
                    $data->barang_nama = $barang->nama;
                    $data->kategori_id = $barang->kategori_id;
                    $data->kategori_nama = $barang->kategori_nama;
                    $data->jenis_transaksi_id = 'K02';
                    $data->jenis_transaksi_nama = 'Transfer Keluar';
                    $data->gudang_id = $reference->gudang_asal_id;
                    $data->gudang_nama = $reference->gudangAsal->nama;
                    $data->kuantitas = ($available - $lefts) * -1;
                    $data->harga = $bs->harga ?? 0;
                    $data->created_at = $reference->created_at;
                    $data->reference_in = $bs->id;
                    $data->save();

                    $harga_total += ($bs->harga ?? 0) * ($available - $lefts);
                    $qty_total += ($available - $lefts);

                    if ($needs == 0)
                        break;
                }

                // Tambahkan Baris untuk Masuk ke Gudang Tujuan
                if ($qty_total > 0) {
                    $data_in = new PersediaanMutasiDetail();
                    $data_in->reference_id = $request->reference_id;
                    $data_in->barang_id = $request->barang_id;
                    $data_in->barang_nama = $barang->nama;
                    $data_in->kategori_id = $barang->kategori_id;
                    $data_in->kategori_nama = $barang->kategori_nama;
                    $data_in->jenis_transaksi_id = 'M03';
                    $data_in->jenis_transaksi_nama = 'Transfer Masuk';
                    $data_in->gudang_id = $reference->gudang_tujuan_id;
                    $data_in->gudang_nama = $reference->gudangTujuan->nama;
                    $data_in->kuantitas = $qty_total;
                    $data_in->harga = $qty_total > 0 ? $harga_total / $qty_total : 0;
                    $data_in->created_at = $reference->created_at;
                    $data_in->save();
                }
            });

        return response()->json(true);

        } elseif ($request->req == 'authorize') {
            $data = PersediaanMutasi::with('details')->find($request->id);

            if (!$data)
                return response()->json('Data Not Found', 403);

            DB::transaction(function () use ($data) {
                $lock = $data->status == 'O';
                $data->editor_id = request()->user()->id;
                $data->status = $lock ? 'F' : 'O';
                $data->details->each(function ($d) use ($lock) {
                    $d->is_completed = $lock;
                    $d->save();
                    if ($d->reference_in) {
                        $x = PersediaanTransaksi::where('id', $d->reference_in)->first();
                        $all = PersediaanMutasiDetail::where('reference_in', $d->reference_in)->sum('kuantitas');
                        if ($x->kuantitas + $all == 0) {
                            $x->habis = $lock;
                        }
                        $x->save();
                    }
                });
                $data->save();
            });

            return response()->json(true);
        } elseif ($request->req == 'delete_detail') {
            $data = PersediaanMutasiDetail::where('reference_id', $request->parent_id)
                ->where('barang_id', $request->id)
                ->get();

            if (!$data)
                return response()->json('Data Not Found', 403);

            return response()->json($data->each->delete());
        } elseif($request->req == 'delete'){
            $data = PersediaanMutasi::find($request->id);
            if(!$data)
                return response()->json('Data Not Found', 403);
            
            $data->editor_id = request()->user()->id;
            $data->save();
            return response()->json($data->delete());
        }
    }
}