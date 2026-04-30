<?php

namespace App\Http\Controllers\Persediaan;

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use App\Models\Persediaan\PersediaanKeluar;
use App\Models\Persediaan\PersediaanKeluarDetail;
use App\Models\Persediaan\Barang;
use App\Models\JenisTransaksi;
use App\Models\CodeRegister;
use App\Models\Persediaan\PersediaanTransaksi;
use App\Models\Persediaan\PersediaanMutasi;
use App\Models\Persediaan\PersediaanMutasiDetail;
use DB;

class PersediaanKeluarController extends AuthController
{
    public function __invoke(Request $request)
    {

        if ($request->req == 'open') {
            $constant = [];

            $data = PersediaanKeluar::joinGudang()
                ->with('jenisTransaksi')
                ->where('persediaan_keluar.id', $request->id)
                ->orWhere('persediaan_keluar.kode', $request->kode)
                ->select('persediaan_keluar.*', 'persediaan_gudang.nama as nama_gudang')
                ->first();

            $vue = "<persediaan-keluar-detail-page :constant='" . json_encode($constant) . "' :parent='" . json_encode($data) . "' />";
        } else {

            $constant = [
                'GUDANG_OPT' => $this->list_gudang(),
                'JENIS_TRANSAKSI_OPT' => JenisTransaksi::persediaanKeluar()->get()
            ];

            $vue = "<persediaan-keluar-page :constant='" . json_encode($constant) . "' />";
        }

        return response()->view('layouts.antd', compact('vue'));
    }

    public function read(Request $request)
    {
        if ($request->req == 'table') {
            $data = PersediaanKeluar::whereDateBetween('persediaan_keluar.created_at', $request->date_start, $request->date_end)
                ->joinGudang()
                ->with('jenisTransaksi')
                ->where(function ($q) use ($request) {
                    if ($request->status) {
                        $q->where('status', $request->status);
                    }
                })
                ->where(function ($q) use ($request) {
                    if ($request->search) {
                        $q->where('persediaan_keluar.kode', 'like', "%{$request->search}%")
                            ->orWhere('konsumen', 'like', "%$request->search%");
                    }
                })
                ->select('persediaan_keluar.*', 'persediaan_gudang.nama as nama_gudang')
                ->withCount(['details'])
                ->withSum('details', 'kuantitas')
                ->paginate($this->per_page());

            $dataStatusCount = PersediaanKeluar::get();

            $dataPending = $dataStatusCount->filter(function ($y){
                return $y->status === 'O';
            })->count();

            $dataFinish = $dataStatusCount->filter(function ($x){
                return $x->status === 'F';
            })->count();

            return response()->json(['models' => $data, 'pending_count' => $dataPending, 'finish_count' => $dataFinish]); 

        } else if ($request->req == 'table_detail') {
            $data = PersediaanKeluarDetail::where('reference_id', $request->id)
                ->selectRaw('barang_id, barang_nama, kategori_id, kategori_nama, SUM(kuantitas) as kuantitas')
                ->groupBy('barang_id', 'barang_nama', 'kategori_id', 'kategori_nama')
                ->get();

            return response()->json(['models' => $data, 'jumlah_barang' => $data->sum('kuantitas')]);
        }
    }

    public function write(Request $request)
    {
        if ($request->req == 'write') {
            $this->validate($request, [
                'konsumen' => 'required',
                'gudang_id' => 'required',
                'jenis_transaksi_id' => 'required'
            ]);

            $data = PersediaanKeluar::find($request->id);
            $isNew = false;

            if (!$data) {
                $data = new PersediaanKeluar();
                $data->kode = CodeRegister::next('persediaan_keluar');
                $data->created_at = date('Y-m-d H:i:s');
                $isNew = true;
            }

            $data->jenis_transaksi_id = $request->jenis_transaksi_id;
            $data->gudang_id = $request->gudang_id;
            $data->konsumen = $request->konsumen;
            $data->status = "O";
            $data->notes = $request->notes;
            $data->editor_id = $this->user()->id;

            if ($data->save() && $isNew) {
                CodeRegister::update_number('persediaan_keluar');
            }

            return response()->json(['kode' => $data->kode]);
        }

        if ($request->req == 'write_detail') {
            $this->validate($request, [
                'barang_id' => 'required',
                'kuantitas' => 'required|numeric|min:1',
                'reference_id' => 'required',
            ]);

            $reference = PersediaanKeluar::with('jenisTransaksi', 'gudang')->find($request->reference_id);
            $barang = Barang::withStockBatch($reference->gudang_id)->withSumTransaksi($reference->gudang_id)->find($request->barang_id);

            if (!$reference || !$barang)
                return response()->json('Data Not Found', 404);

            // Cek Duplikasi
            $checkResult = duplicateCheck(
            $request->barang_id,
            $request->reference_id,
            $reference->gudang_id,
                [
                    [
                        'type' => 'keluar',
                        'mainModel' => PersediaanKeluar::class,
                        'detailModel' => PersediaanKeluarDetail::class,
                        'isSelf' => true,
                    ],
                    [
                        'type' => 'mutasi',
                        'mainModel' => PersediaanMutasi::class,
                        'detailModel' => PersediaanMutasiDetail::class,
                    ],
                ]
            );

            if ($checkResult) return $checkResult;

            if ($barang->stock < $request->kuantitas)
                return response()->json(['message' => 'Stok Tidak Mencukupi'], 403);

            $needs = $request->kuantitas;
            foreach ($barang->stockBatch as $bs) {
                $data = PersediaanKeluarDetail::find($request->id) ?? new PersediaanKeluarDetail();
                //check-sisa
                $used = PersediaanKeluarDetail::where('reference_in', $bs->id)->sum('kuantitas');

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
                $data->jenis_transaksi_id = $reference->jenisTransaksi->kode;
                $data->jenis_transaksi_nama = $reference->jenisTransaksi->uraian;
                $data->gudang_id = $reference->gudang->id;
                $data->gudang_nama = $reference->gudang->nama;
                $data->kuantitas = ($available - $lefts) * -1;
                $data->harga = $bs->harga ?? 0;
                $data->created_at = $reference->created_at;
                $data->reference_in = $bs->id;
                $data->save();

                // Better for authorize!
                // if ($lefts == 0)
                //     PersediaanTransaksi::where('id', $bs->id)->update(['habis' => true]);

                if ($needs == 0)
                    break;
            }

            return response()->json(true);
        } elseif ($request->req == 'authorize') {
            $data = PersediaanKeluar::with('details')->find($request->id);
            if (!$data)
                return response()->json('Data Not Found', 403);

            DB::transaction(function () use ($data) {
                $lock = $data->status == 'O';

                $data->editor_id = $this->user()->id;
                $data->status = $lock ? 'F' : 'O';
                $data->details->each(function ($d) use ($lock) {
                    $d->is_completed = $lock;
                    $d->save();
                    $x = PersediaanTransaksi::where('id', $d->reference_in)->first();
                    $all = PersediaanKeluarDetail::where('reference_in', $d->reference_in)->sum('kuantitas');
                    if ($x->kuantitas + $all == 0) {
                        $x->habis = $lock;
                    }
                    $x->save();
                });
                $data->save();
            });

            return response()->json(true);
        } elseif ($request->req == 'delete_detail') {
            $data = PersediaanKeluarDetail::where('reference_id', $request->parent_id)
                ->where('barang_id', $request->id)
                ->get();

            if (!$data)
                return response()->json('Data Not Found', 403);

            return response()->json($data->each->delete());
        } elseif ($request->req == 'delete') {
            $data = PersediaanKeluar::find($request->id);
            if (!$data)
                return response()->json('Data Not Found', 403);

            $data->editor_id = $this->user()->id;
            $data->save();
            return response()->json($data->delete());
        }
    }
}
