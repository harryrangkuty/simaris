<?php

namespace App\Http\Controllers\Persediaan;

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use App\Models\MasterData\Category;
use App\Models\Persediaan\Gudang;
use App\Models\Persediaan\Satuan;
use DB;
use PDF;

class StockOpname2025Controller extends AuthController
{
    public function __invoke(Request $request)
    {   
        $constant = [
            'GUDANG_OPT' => $this->list_gudang()->values()
        ];
        
        $vue = "<stock-opname-2025-page :constant='" . json_encode($constant) . "' />";
        
        return response()->view('layouts.antd', compact('vue'));
    }
    
    public function read(Request $request)
    {
        if ($request->req == "table") {

            // $data = Kategori::select('kd_brg', 'uraian')
            //         ->where('kd_brg', 'like', '1%')
            //         ->whereRaw('LENGTH(kd_brg) = 10')
            //         ->where(function ($q) use ($request) {
            //             if($request->search)
            //                 $q->where('uraian', 'LIKE', "%{$request->search}%");
            //         })
            //         ->get();

            // Jika mau digrouping dulu
            $data = [];

            $kategori_id = $request->kategori_id;

            $kategori7 = Kategori::select('kd_brg', 'uraian')
                ->whereRaw('LENGTH(kd_brg) = 7')
                ->where('kd_brg', 'like', '1%')
                ->when($kategori_id && strlen($kategori_id) <= 7, function ($q) use ($kategori_id) {
                    $q->where('kd_brg', 'like', "{$kategori_id}%");
                })
                ->when($kategori_id && strlen($kategori_id) == 10, function ($q) use ($kategori_id) {
                    $q->where('kd_brg', substr($kategori_id, 0, 7));
                })
                ->orderBy('kd_brg')
                ->get();

                $summary = DB::table('stock_opname_2025')
                        ->select(
                            'kategori_id',
                            DB::raw('SUM(kuantitas) as total_kuantitas'),
                            DB::raw('SUM(sub_total) as total_harga')
                        )
                        ->where('gudang_id', $request->gudang_id)
                        ->groupBy('kategori_id')
                        ->get()
                        ->keyBy('kategori_id');

                foreach ($kategori7 as $kat7) {
                    $total_kat7_kuantitas = 0;
                    $total_kat7_harga = 0;
                    $kat7_index = count($data);

                    $data[] = [
                        'kd_brg' => $kat7->kd_brg,
                        'uraian' => $kat7->uraian,
                        'total_kuantitas' => 0,
                        'total_harga' => 0,
                    ];

                    $kategori10 = Kategori::select('kd_brg', 'uraian')
                        ->where('kd_brg', 'like', $kat7->kd_brg . '%')
                        ->whereRaw('LENGTH(kd_brg) = 10')
                        ->when($kategori_id && strlen($kategori_id) == 10, function ($q) use ($kategori_id) {
                            $q->where('kd_brg', $kategori_id);
                        })
                        ->orderBy('kd_brg')
                        ->get();

                    foreach ($kategori10 as $child) {
                        $child_kuantitas = $summary[$child->kd_brg]->total_kuantitas ?? 0;
                        $child_harga = $summary[$child->kd_brg]->total_harga ?? 0;

                        $data[] = [
                            'kd_brg' => $child->kd_brg,
                            'uraian' => $child->uraian,
                            'total_kuantitas' => $child_kuantitas,
                            'total_harga' => $child_harga,
                        ];

                        $total_kat7_kuantitas += $child_kuantitas;
                        $total_kat7_harga += $child_harga;
                    }

                    $data[$kat7_index]['total_kuantitas'] = $total_kat7_kuantitas;
                    $data[$kat7_index]['total_harga'] = $total_kat7_harga;
                }
            

            return response()->json(['models' => $data]);

        } elseif ($request->req == "detail_stock_opname"){

            $data = DB::table('stock_opname_2025')
                ->select('id', 'nama_barang', 'kuantitas', 'harga', 'sub_total', 'kategori_id', 'kategori_nama')
                ->where('kategori_id', $request->kat_id)
                ->where('gudang_id', $request->gudang_id)
                ->where(function ($q) use ($request) {
                    if($request->search)
                        $q->where('nama_barang', 'LIKE', "%{$request->search}%");
                })
                ->get();

            return response()->json(['models' => $data]);
        } elseif ($request->req === 'search_nama_barang') {

            $keyword = $request->keyword;

            // Suggestion dari database
            $fromDB = DB::table('stock_opname_2025')
                ->select('nama_barang')
                ->where('nama_barang', 'like', '%' . $keyword . '%')
                ->groupBy('nama_barang')
                ->pluck('nama_barang');

            // Suggestion data statis
            $staticSuggestion = collect([
                'HVS',
                'Kabel',
                'Kabel',
                'Lampu LED 12W',
                'Stop Kontak',
                'Pipa PVC 3/4 inch',
                'Elbow PVC 90°',
                'Cat Tembok Dulux 5L',
                'Plafon Gypsum 9mm',
                'Sekrup Gypsum 6x1 inch',
            ]);

            $data = $fromDB->merge($staticSuggestion)
                ->unique()
                ->filter(function ($item) use ($keyword) {
                    return stripos($item, $keyword) !== false;
                })
                ->values();


            return response()->json([
                'models' => $data,
            ]);
        }  elseif ($request->req == 'print_data') {

                $gudang = DB::table('persediaan_gudang')->where('id', $request->gudang_id)->first();
                $satker_nama = optional($gudang)->nama ?? 'N/A';

                $kategori_id = $request->kategori_id;

                $kategori7 = Kategori::select('kd_brg', 'uraian')
                    ->whereRaw('LENGTH(kd_brg) = 7')
                    ->where('kd_brg', 'like', '1%')
                    ->when($kategori_id && strlen($kategori_id) <= 7, function ($q) use ($kategori_id) {
                        $q->where('kd_brg', 'like', "{$kategori_id}%");
                    })
                    ->when($kategori_id && strlen($kategori_id) == 10, function ($q) use ($kategori_id) {
                        $q->where('kd_brg', substr($kategori_id, 0, 7));
                    })
                    ->orderBy('kd_brg')
                    ->get();

                $summary = DB::table('stock_opname_2025')
                    ->select(
                        'kategori_id',
                        DB::raw('SUM(kuantitas) as total_kuantitas'),
                        DB::raw('SUM(sub_total) as total_harga')
                    )
                    ->where('gudang_id', $request->gudang_id)
                    ->groupBy('kategori_id')
                    ->get()
                    ->keyBy('kategori_id');

                $data = [];

                foreach ($kategori7 as $kat7) {
                    $kategori7_item = [
                        'kd_brg' => $kat7->kd_brg,
                        'uraian' => $kat7->uraian,
                        'total_kuantitas' => 0,
                        'total_harga' => 0,
                        'children' => []
                    ];

                    $total_kat7_kuantitas = 0;
                    $total_kat7_harga = 0;

                    $kategori10 = Kategori::select('kd_brg', 'uraian')
                        ->where('kd_brg', 'like', $kat7->kd_brg . '%')
                        ->whereRaw('LENGTH(kd_brg) = 10')
                        ->when($kategori_id && strlen($kategori_id) == 10, function ($q) use ($kategori_id) {
                            $q->where('kd_brg', $kategori_id);
                        })
                        ->orderBy('kd_brg')
                        ->get();

                    foreach ($kategori10 as $child) {
                        $child_kuantitas = $summary[$child->kd_brg]->total_kuantitas ?? 0;
                        $child_harga = $summary[$child->kd_brg]->total_harga ?? 0;

                        $details = DB::table('stock_opname_2025')
                            ->select('id', 'nama_barang', 'kuantitas', 'harga', 'sub_total')
                            ->where('kategori_id', $child->kd_brg)
                            ->where('gudang_id', $request->gudang_id)
                            ->get();

                        $kategori7_item['children'][] = [
                            'kd_brg' => $child->kd_brg,
                            'uraian' => $child->uraian,
                            'total_kuantitas' => $child_kuantitas,
                            'total_harga' => $child_harga,
                            'details' => $details,
                        ];

                        $total_kat7_kuantitas += $child_kuantitas;
                        $total_kat7_harga += $child_harga;
                    }

                    $kategori7_item['total_kuantitas'] = $total_kat7_kuantitas;
                    $kategori7_item['total_harga'] = $total_kat7_harga;

                    $data[] = $kategori7_item;
                }

                if (empty($data)) {
                    return response()->json('Data tidak ditemukan');
                }

                ini_set('memory_limit', '2G');

                return PDF::loadView('print.stock_opname_2025', compact('data', 'gudang'))
                    ->setPaper('f4', 'landscape')->stream();
            }
    }

    public function write(Request $request)
    {
        if($request->req == 'write'){
            $this->validate($request, [
                'gudang_id' => 'required',
                'kategori_id' => 'required',
                'kategori_nama' => 'required',
                'nama_barang' => 'required',
                'kuantitas' => 'required|numeric',
                'harga' => 'required|numeric',
                'sub_total' => 'required|numeric',
            ]);

            $data = [
                'user_id'       => $request->user()->id,
                'gudang_id'     => $request->gudang_id,
                'kategori_id'   => $request->kategori_id,
                'kategori_nama' => $request->kategori_nama,
                'nama_barang'   => $request->nama_barang,
                'kuantitas'     => $request->kuantitas,
                'harga'         => $request->harga,
                'sub_total'     => $request->sub_total,
                'updated_at'    => now(),
            ];

            if ($request->id) {
                DB::table('stock_opname_2025')
                    ->where('id', $request->id)
                    ->update($data);
            } else {
                $data['created_at'] = now();
                DB::table('stock_opname_2025')->insert($data);
            }

            return response()->json(true);
            
        } elseif($request->req == 'delete'){
            $this->validate($request, [
                'id' => 'required|exists:stock_opname_2025,id',
            ]);

            DB::table('stock_opname_2025')->where('id', $request->id)->delete();

            return response()->json(true);
        }
    }
}