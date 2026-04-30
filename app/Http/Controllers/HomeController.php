<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Aset\AsetPenyusutan;
use App\Models\Aset\AssetTransaction;
use App\Models\Persediaan\Barang;
use App\Models\MasterData\Category;
use App\Models\Persediaan\PersediaanTransaksi;

class HomeController extends AuthController
{
     public function __invoke(Request $request)
    {
        $user = $request->user();

        $constant = [
            'user' => $user
        ];

        $title = "Dashboard";

        $vue = "<dashboard-page :title='" . json_encode($title) . "' :constant='" . e(json_encode($constant)) . "' />";

        return view('layouts.antd', compact('vue', 'title'));
    }

    public function read(Request $request)
    {
        if ($request->req == 'table') {

            $data = AssetTransaction::with('satker')
                ->select('satker_id', DB::raw('SUM(nilai) as total_nilai_perolehan'))
                ->where('reference_type', 'ASET-PEROLEHAN')
                ->whereYear('created_at', $request->year ?? now()->year)
                ->groupBy('satker_id')
                ->orderBy('total_nilai_perolehan', 'desc')
                ->get();

            return response()->json(['models' => $data, 'total_nilai_global' => $data->sum('total_nilai_perolehan')]);
        } elseif ($request->req == 'nilai_perolehan') {

            $kategori = Kategori::selectRaw('LEFT(kd_brg, 1)  as kode, uraian')
                ->whereNull('kd_bid')
                ->where('kd_brg', 'NOT LIKE', '1%')
                ->get()
                ->keyBy('kode');

            $chartData = AssetTransaction::selectRaw('LEFT(kategori_id, 1) as category_prefix, SUM(nilai) as total, COUNT(*) as count')
                ->groupBy('category_prefix')
                ->where('reference_type', 'ASET-PEROLEHAN')
                ->where(function ($q) use ($request) {
                    if ($request->year) {
                        $q->whereYear('created_at', $request->year);
                    } else {
                        $q->whereYear('created_at', now()->year);
                    }
                })
                ->where(function ($q) use ($request) {
                    if ($request->satker_id) {
                        $q->where('satker_id', $request->satker_id);
                    }
                })
                ->get()
                ->map(function ($item) use ($kategori, $request) {
                    $namaKategori = data_get($kategori, $item->category_prefix . '.uraian', '-');
                    $namaKategori = ucwords(strtolower($namaKategori));

                    return [
                        'value' => $item->total,
                        'name' => $namaKategori,
                        'item' => $item->count,
                        'label' => number_format($item->total, 0, '.', '.')
                    ];
                });

            return response()->json(['models' => $chartData]);
        } elseif ($request->req == 'nilai_buku') {

            $kategori = Kategori::selectRaw('LEFT(kd_brg, 1)  as kode, uraian')
                ->whereNull('kd_bid')
                ->where('kd_brg', 'NOT LIKE', '1%')
                ->get()
                ->keyBy('kode');

            $transaksi = AssetTransaction::selectRaw('LEFT(kategori_id, 1) as category_prefix, SUM(nilai) as total')
                ->groupBy('category_prefix')
                ->where(function ($q) use ($request) {
                    if ($request->satker_id) {
                        $q->where('satker_id', $request->satker_id);
                    }
                })
                ->where(function ($q) use ($request) {
                    if ($request->year) {
                        $q->whereYear('created_at', '<=',  $request->year);
                    } else {
                        $q->whereYear('created_at', '<=',  now()->year);
                    }
                })
                ->get()
                ->mapWithKeys(function ($item) use ($kategori) {
                    $namaKategori = data_get($kategori, $item->category_prefix . '.uraian', '-');
                    $namaKategori = ucwords(strtolower($namaKategori));

                    return [$namaKategori => $item->total];
                });

            $penyusutan = AsetPenyusutan::selectRaw('LEFT(kategori_id, 1) as category_prefix, SUM(nilai) as total')
                ->groupBy('category_prefix')
                ->where(function ($q) use ($request) {
                    if ($request->satker_id) {
                        $q->where('satker_id', $request->satker_id);
                    }
                })
                ->where(function ($q) use ($request) {
                    if ($request->year) {
                        $q->whereYear('tgl_penyusutan', '<=', $request->year);
                    } else {
                        $q->whereYear('tgl_penyusutan', '<=', now()->year);
                    }
                })
                ->get()
                ->mapWithKeys(function ($item) use ($kategori) {
                    $namaKategori = data_get($kategori, $item->category_prefix . '.uraian', '-');
                    $namaKategori = ucwords(strtolower($namaKategori));

                    return [$namaKategori => $item->total];
                });

            $chartData = $transaksi->map(function ($total, $namaKategori) use ($penyusutan) {
                $penyusutanTotal = $penyusutan->get($namaKategori, 0);
                return [
                    'value' => $total - $penyusutanTotal,
                    'name' => $namaKategori,
                    'label' => number_format($total - $penyusutanTotal, 0, '.', '.'),
                ];
            })->values();

            return response()->json(['models' => $chartData]);
        } elseif ($request->req == 'persediaan_barang') {

            $kategori = Kategori::selectRaw('SUBSTR(kd_brg, 1, 5) as kode, uraian')
                ->where('kd_brg', 'LIKE', '1%')
                ->whereNotNull('kd_kel')
                ->whereNull('kd_skel')
                ->get()->keyBy('kode');

            $chartData = PersediaanTransaksi::selectRaw('LEFT(kategori_id, 5) as category_prefix, SUM(kuantitas) as stock, SUM(kuantitas * harga) as total')
                ->where('kategori_id', 'LIKE', '1%')
                ->where('is_completed', 1)
                ->groupBy('category_prefix')
                ->where(function ($q) use ($request) {
                    if ($request->year) {
                        $q->whereYear('created_at', $request->year);
                    } else {
                        $q->whereYear('created_at', now()->year);
                    }
                })
                ->get()
                ->map(function ($item) use ($kategori) {
                    $namaKategori = data_get($kategori, $item->category_prefix . '.uraian', '-');
                    $namaKategori = ucwords(strtolower($namaKategori));
                    return [
                        'value' => $item->total,
                        'name' => $namaKategori,
                        'item' => $item->stock,
                        'label' => number_format($item->total, 0, '.', '.')
                    ];
                });

            return response()->json(['models' => $chartData]);
        } elseif ($request->req == 'nilai_persediaan') {

            $chartData = PersediaanTransaksi::selectRaw('reference_type, SUM(ABS(kuantitas) * harga) as total')
                ->groupBy('reference_type')
                ->where('is_completed', 1)
                ->where(function ($q) use ($request) {
                    if ($request->year) {
                        $q->whereYear('created_at', $request->year);
                    } else {
                        $q->whereYear('created_at', now()->year);
                    }
                })
                ->get()
                ->map(function ($item) {
                    $nameMapping = [
                        'PERSEDIAAN-MASUK' => 'Persediaan Masuk',
                        'PERSEDIAAN-KELUAR' => 'Persediaan Keluar',
                    ];

                    $name = $nameMapping[$item->reference_type] ?? $item->reference_type;

                    return [
                        'value' => $item->total,
                        'name' => $name,
                        'label' => number_format($item->total, 0, '.', '.')
                    ];
                });

            return response()->json(['models' => $chartData]);
        } elseif ($request->req == 'list_barang_kekurangan_stock') {

            $data = Barang::withTrashed()
                ->withSumTransaksi()
                ->where('stok_minimum', '!=', 0)
                ->get();

            $data_barang_kekurangan_stock = $data->filter(function ($item) {
                return isset($item->stock) && $item->stock < $item->stok_minimum;
            })->values();;

            return response()->json(['models' => $data_barang_kekurangan_stock]);
        }
    }
}
