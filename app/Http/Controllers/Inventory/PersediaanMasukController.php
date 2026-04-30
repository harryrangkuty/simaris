<?php

namespace App\Http\Controllers\Persediaan;

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use App\Models\Persediaan\Suplier;
use App\Models\Persediaan\Satuan;
use App\Models\Persediaan\Gudang;
use App\Models\Persediaan\PersediaanMasuk;
use App\Models\Persediaan\PersediaanMasukDetail;
use App\Models\Persediaan\PersediaanTransaksi;
use App\Models\Persediaan\Barang;
use App\Models\JenisTransaksi;
use App\Models\CodeRegister;
use DB;

class PersediaanMasukController extends AuthController
{
    public function __invoke(Request $request)
    {
        if($request->req == 'open'){
            $constant = [
                'SATUAN' => Satuan::all(),
            ];

            $data = PersediaanMasuk::joinSuplier()->joinGudang()
                                    ->with('jenisTransaksi')
                                    ->where('persediaan_masuk.id', $request->id)
                                    ->orWhere('persediaan_masuk.kode', $request->kode)
                                    ->select('persediaan_masuk.*', 'persediaan_suplier.nama as nama_suplier', 'persediaan_gudang.nama as nama_gudang')
                                    ->first();
            
            $vue = "<persediaan-masuk-detail-page :constant='" . json_encode($constant) . "' :parent='" . json_encode($data) . "' />";
        }
        else{
            $constant = [
                'SUPLIER_OPT' => Suplier::get(),
                'GUDANG_OPT' => $this->list_gudang(),
                'JENIS_TRANSAKSI_OPT' => JenisTransaksi::persediaanMasuk()->get()
            ];

            $vue = "<persediaan-masuk-page :constant='" . json_encode($constant) . "' />";
        }
        
        return response()->view('layouts.antd', compact('vue'));
    }

    public function read(Request $request)
    {
        if($request->req == 'table'){
            $data = PersediaanMasuk::whereDateBetween('persediaan_masuk.created_at', $request->date_start, $request->date_end)
                                    ->joinSuplier()->joinGudang()
                                    // ->with('order')
                                    ->with('jenisTransaksi')
                                    ->where(function($q) use($request){
                                        if($request->status){
                                            $q->where('status', $request->status);
                                        }
                                    })
                                    ->where(function($q) use($request){
                                        if($request->search){
                                            $q->where('persediaan_masuk.kode', 'like', "%{$request->search}%")   
                                              ->orWhere('nomor_invoice', 'like', "%$request->search%");
                                        }
                                    })
                                    ->select('persediaan_masuk.*', 'persediaan_suplier.nama as nama_suplier', 'persediaan_gudang.nama as nama_gudang')
                                    ->withCount(['details'])
                                    ->withSum('details', 'kuantitas')
                                    ->paginate($this->per_page());
            
            $dataStatusCount = PersediaanMasuk::get();

            $dataPending = $dataStatusCount->filter(function ($y){
                return $y->status === 'O';
            })->count();

            $dataFinish = $dataStatusCount->filter(function ($x){
                return $x->status === 'F';
            })->count();

            return response()->json(['models' => $data, 'pending_count' => $dataPending, 'finish_count' => $dataFinish]); 
        }
        elseif($request->req == 'table_detail'){
            $data = PersediaanMasukDetail::where('reference_id', $request->id)
                                            ->get();

            return response()->json(['models' => $data, 'jumlah_barang' => $data->sum('kuantitas'), 'total' => $data->sum('sub_total')]); 
        }
    }

    public function write(Request $request)
    {
        if($request->req == 'write'){
            $this->validate($request, [
                'nomor_invoice' => 'required|unique:persediaan_masuk,nomor_invoice,' . $request->id,
                'tanggal_invoice' => 'required|date',
                'suplier_id' => 'required',
                'gudang_id' => 'required',
                'jenis_transaksi_id' => 'required'
            ]);

            $data = PersediaanMasuk::find($request->id);
            $isNew = false;
            
            if (!$data) {
                $data = new PersediaanMasuk();
                $data->kode = CodeRegister::next('persediaan_masuk');
                $data->created_at = date('Y-m-d H:i:s');
                $isNew = true; 
            }

            $data->jenis_transaksi_id = $request->jenis_transaksi_id;
            $data->gudang_id = $request->gudang_id;
            $data->suplier_id = $request->suplier_id;
            $data->nomor_invoice = $request->nomor_invoice;
            $data->tanggal_invoice = $request->tanggal_invoice;
            $data->status = "O";
            $data->notes = $request->notes;
            $data->editor_id = request()->user()->id;

            if ($data->save() && $isNew) {
                CodeRegister::update_number('persediaan_masuk');
            }

            return response()->json(['kode' => $data->kode]);
        }

        elseif($request->req == 'write_detail'){
            $this->validate($request, [
                'barang_id' => 'required',
                'kuantitas' => 'required|numeric|min:1',
                'harga' => 'required|numeric|min:1',
                'reference_id' => 'required',
            ]);

            $reference = PersediaanMasuk::with('jenisTransaksi','gudang')->find($request->reference_id);
            $barang = Barang::find($request->barang_id);

            if(!$reference || !$barang)
                return response()->json('Data Not Found', 404);

            $check_dupe = PersediaanMasukDetail::where('reference_id', $request->reference_id)
                                                ->where('barang_id', $request->barang_id)
                                                ->where('harga', $request->harga)
                                                ->first();
            
            if($check_dupe)
                return response()->json($check_dupe->barang_nama . ' sudah ada dalam list dengan harga yang sama', 403);                                                

            $data = PersediaanMasukDetail::find($request->id) ?? new PersediaanMasukDetail();
            $data->reference_id = $request->reference_id;
            $data->barang_id = $request->barang_id;
            $data->barang_nama = $barang->nama;
            $data->kategori_id = $barang->kategori_id;
            $data->kategori_nama = $barang->kategori_nama;
            $data->jenis_transaksi_id = $reference->jenisTransaksi->kode;
            $data->jenis_transaksi_nama = $reference->jenisTransaksi->uraian;
            $data->gudang_id = $reference->gudang->id;
            $data->gudang_nama = $reference->gudang->nama;
            $data->kuantitas = $request->kuantitas;
            $data->harga = $request->harga;
            $data->created_at = $reference->created_at;

            return response()->json($data->save());
        }

        elseif($request->req == 'authorize'){
            $data = PersediaanMasuk::with('details')->find($request->id);
            if(!$data)
                return response()->json('Data Not Found', 403);

            DB::transaction(function () use($data) {
                $lock = $data->status == 'O'; 

                $data->editor_id = $this->user()->id;         
                $data->status = $lock ? 'F' : 'O';
                $data->details->each(function($d) use($lock){
                    $d->is_completed = $lock;
                    $d->save();
                });
                $data->save();
            });

            return response()->json(true);
        }

        elseif($request->req == 'delete'){
            $data = PersediaanMasuk::find($request->id);
            if(!$data)
                return response()->json('Data Not Found', 403);
            
            $data->editor_id = $this->user()->id;
            $data->save();
            return response()->json($data->delete());
        }

        elseif($request->req == 'delete_detail'){
            $data = PersediaanTransaksi::find($request->id);
            if(!$data)
                return response()->json('Data Not Found', 403);
            
            if ($data->is_completed == true) {
                return response()->json('Status Persediaan Telah Diselesaikan!', 403);
            }

            return response()->json($data->delete());
        }
    }
}
