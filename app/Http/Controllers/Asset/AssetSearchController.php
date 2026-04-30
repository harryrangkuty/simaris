<?php

namespace App\Http\Controllers\Asset;

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use App\Models\Aset\AsetRuang;
use App\Models\Aset\AssetProfile;
use App\Models\Satker;
use App\Exports\CariAsetTetapExport;
use Maatwebsite\Excel\Facades\Excel;
use DB;
use PDF;

class AssetSearchController extends AuthController
{
    public function __invoke(Request $request)
    {
        $constant = [
            'SATKER' => Satker::getSatkers()->keyBy('id'),
            'RUANG' => AsetRuang::all(),
        ];

        $vue = "<aset-cari-page :constant='" . json_encode($constant) . "' />";
        
        return response()->view('layouts.antd', compact('vue'));
    }
    
    public function read(Request $request){
        if($request->req == 'table' || $request->req == 'print_data' || $request->req == 'data_in_excel'){
            $data = AssetProfile::with('jenisPerolehan','satker') 
                                // filter search
                                ->where(function ($q) use ($request) {
                                    if ($request->search)
                                        $q->where('deskripsi', 'like', "%{$request->search}%");
                                })
                                // filter kib
                                ->where(function ($q) use ($request) {
                                    if ($request->kib == 1){
                                        $q->WhereNotNull('jenis_kib');
                                    } elseif($request->kib == 2){
                                        $q->WhereNull('jenis_kib');
                                    }
                                })
                                // filter tipe
                                ->where(function ($q) use ($request) {
                                    if ($request->tipe_ruang)
                                        $q->where('tipe_ruang', $request->tipe_ruang);
                                })
                                 // filter ruang
                                 ->where(function ($q) use ($request) {
                                    if ($request->ruang_id)
                                        $q->where('ruang_id', $request->ruang_id);
                                })
                                // filter kondisi
                                ->where(function ($q) use ($request) {
                                    if ($request->kondisi)
                                        $q->where('kondisi', $request->kondisi);
                                })
                                // filter henti_guna
                                 ->where(function ($q) use ($request) {
                                    if ($request->henti_guna)
                                        $q->where('henti_guna', $request->henti_guna);
                                })
                                // filter komptabel
                                ->where(function ($q) use ($request) {
                                    if ($request->komptabel)
                                        $q->where('komptabel', $request->komptabel);
                                })
                                // filter kategori
                                ->where(function ($q) use ($request) {
                                    if ($request->kategori_id)
                                        $q->where('kategori_id', 'LIKE', $request->kategori_id  . '%');
                                })
                                // filter nilai_buku
                                  ->where(function ($q) use ($request) {
                                    $nilaiBukuNull = filter_var($request->nilai_buku_null, FILTER_VALIDATE_BOOLEAN);

                                    if ($nilaiBukuNull) {
                                        $q->where('nilai_buku', 0);
                                    }
                                })
                                // filter aset_yang_dihapus
                                ->where(function ($q) use ($request) {
                                    $asetDihapus = filter_var($request->aset_dihapus, FILTER_VALIDATE_BOOLEAN);
                                    
                                    if ($asetDihapus) {
                                        $q->whereNotNull('tgl_penghapusan');
                                    } else {
                                        $q->whereNull('tgl_penghapusan');
                                    }
                                })
                                // filter tahun_perolehan
                                  ->where(function ($q) use ($request) {
                                    if ($request->tahun_perolehan) {
                                        $q->whereRaw('YEAR(tgl_perolehan) = ?', $request->tahun_perolehan);
                                    }
                                })
                                // filter kode_perolehan
                                ->whereHas('perolehan', function ($q) use ($request) {
                                    if ($request->kode_perolehan)
                                        $q->where('kode', 'like', "%{$request->kode_perolehan}%");
                                })
                                // filter keterangan_simak
                                ->where(function ($q) use ($request) {
                                    if ($request->keterangan_simak)
                                        // Bagian ini masih perlu diperbaiki karna harusnya ambil etc->keterangan saja
                                        $q->where('etc', 'LIKE', "%{$request->keterangan_simak}%" );
                                })
                                // filter satker
                                ->filterSatker($request);

            if ($request->req == 'table') {
                $data = $data->paginate($this->per_page());
                return response()->json(['models' => $data]);
            } elseif ($request->req == 'print_data') {
                $data = $data->get();

                if($data->isEmpty()){
                    return response()->json('Data tidak ditemukan');
                };
                ini_set('memory_limit', '2G');
                return PDF::loadView('print.aset_cari', compact('data'))
                ->setPaper('f4', 'landscape')->stream();
            } elseif ($request->req == 'data_in_excel') {
                $data = $data->get();
                return Excel::download(new CariAsetTetapExport($data), 'data_aset_tetap.xlsx');
            }
        } 
    }
}
