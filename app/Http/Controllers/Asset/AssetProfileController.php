<?php

namespace App\Http\Controllers\Asset;

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Aset\AssetProfile;
use App\Models\Aset\AsetPerubahan;
use App\Models\Aset\AssetTransaction;
use App\Models\Aset\AsetRuang;
use App\Models\Aset\AsetMasaManfaat;
use App\Models\Aset\AsetPenyusutan;
use App\Models\Satker;
use App\Models\CodeRegister;
use App\Models\JenisTransaksi;
use App\Http\Middleware\Sso;
use Illuminate\Contracts\Encryption\DecryptException;
use DB;
use PDF;
use DateTimeImmutable;
use DateInterval;
use App\Traits\ValidationData;

class AssetProfileController extends AuthController
{
    use ValidationData;

    public function __invoke(Request $request)
    {
        if ($request->req == 'open') {
            $satker = Satker::getSatkers();

            $constant = [
                'SATKER' => $satker,
                'DISTRIBUTESATKER' => Satker::distributeSatkers(),
                'PEMANFAATAN' => setting('pemanfaatan'),
                'RUANG' => AsetRuang::whereIn('satker_id', collect($satker)->pluck('id')->toArray())->get(),
            ];

            if ($request->has('xid')) {
                if (request()->user()->hasRole('Administrator')) {
                    $data = AssetProfile::select('id', 'satker_id', 'kategori_id', 'nup', 'jenis_kib', 'etc', 'tgl_penghapusan', 'henti_guna')
                        ->where('id', $request->xid)
                        ->first();
                } else {
                    abort(403);
                }
            } else {
                $data = AssetProfile::select('id', 'satker_id', 'kategori_id', 'nup', 'jenis_kib', 'etc', 'tgl_penghapusan', 'henti_guna')
                    ->where('kategori_id', substr($request->id, 0, 10))
                    ->where('nup', substr($request->id, 10))
                    ->first();
            }

            if (!$data) {
                abort(404);
            }

            if (!$data->etc) {
                $data->etc = (object)[
                    'kondisi_awal' => $data->kondisi
                ];
                $data->save();
            }

            $this->validateSatkerPermission(request()->user(), $data->satker_id);

            $vue = "<aset-profil-detail-page :constant='" . json_encode($constant) . "' :parent='" . json_encode($data) . "' />";
        } elseif ($request->req == 'single_label_print') {
            $data = AssetProfile::find($request->id);
            if (!$data)
                return response()->json("Data Tidak Ditemukan", 403);

            $encryptId = encrypt($data->kategori_id . str_pad($data->nup, 6, 0, STR_PAD_LEFT));

            $url = route('access.profil', ['id' => $encryptId]);

            $views = [
                'standard' => 'print.aset_label_single_standard',
                'mini' => 'print.aset_label_single_mini',
            ];

            if (isset($views[$request->mode])) {
                return view($views[$request->mode] . ($request->tipe_kertas == 'stiker' ? '_stiker' : ''), compact('data', 'url'));
            }
        } elseif ($request->req == 'multi_label_print') {
            $data = AssetProfile::where('kategori_id', $request->kategori_id)
                ->whereBetween('nup', [$request->nup_start, $request->nup_end])
                ->get()
                ->map(function ($item) {
                    $item->url = route('access.profil', ['id' => encrypt($item->kategori_id . str_pad($item->nup, 6, 0, STR_PAD_LEFT))]);
                    return $item;
                });
            if ($data->isEmpty())
                return response()->json("Data Tidak Ditemukan", 403);

            $views = [
                'standard' => 'print.aset_label_multi_standard',
                'mini' => 'print.aset_label_multi_mini',
            ];

            if (isset($views[$request->mode])) {
                return view($views[$request->mode] . ($request->tipe_kertas == 'stiker' ? '_stiker' : ''), compact('data'));
            }
        } elseif ($request->req == 'print_pdf' || $request->req == 'generate_pdf') {

            $data = AssetProfile::with('satker')->find($request->id);

            $url = route('access.profil', ['id' => encrypt($data->kategori_id . str_pad($data->nup, 6, 0, STR_PAD_LEFT))]);

            if (!$data)
                return response()->json('Data Tidak Ditemukan', 403);

            if (!$data->kib)
                return response()->json('KIB Belum Diisi', 403);

            // Detail Kategori
            $detailKategori = $this->get_jenjang_kategori($data->kategori_id);
            $data->kd_gol = $detailKategori['kd_gol'];
            $data->kd_bid = $detailKategori['kd_bid'];
            $data->kd_kel = $detailKategori['kd_kel'];
            $data->kd_skel = $detailKategori['kd_skel'];
            $data->kd_sskel = $detailKategori['kd_sskel'];

            if ($data->jenis_kib == 'Gedung Bangunan') {
                $pdf = PDF::loadView('print.kib_gedung_bangunan', compact('data', 'url'));
            } elseif ($data->jenis_kib == 'Bangunan Air') {
                $pdf = PDF::loadView('print.kib_bangunan_air', compact('data', 'url'));
            } elseif ($data->jenis_kib == 'Alat Besar') {
                $pdf = PDF::loadView('print.kib_alat_besar', compact('data', 'url'));
            } elseif ($data->jenis_kib == 'Alat Angkutan') {
                $pdf = PDF::loadView('print.kib_alat_angkutan', compact('data', 'url'));
            } elseif ($data->jenis_kib == 'Alat Laboratorium') {
                $pdf = PDF::loadView('print.kib_alat_laboratorium', compact('data', 'url'));
            } else {
                return response()->json('Jenis KIB Tidak Valid', 403);
            }

            if ($request->req == 'print_pdf') {
                return $pdf->setPaper('f4', 'potrait')->stream();
            } elseif ($request->req == 'generate_pdf') {
                return $pdf->download();
            }
        } else {
            $constant = [
                'SATKER' => Satker::getSatkers()->keyBy('id'),
                'DISTRIBUTESATKER' => Satker::distributeSatkers(),
                'RUANG' => AsetRuang::all(),
            ];

            // REVIEWER ASET TIDAK BISA AKSES ROUTE PROFIL TANPA PARAM
            if (request()->user()->activeRole->name == "Reviewer Aset") {
                abort(403);
            }

            $vue = "<aset-profil-page :constant='" . json_encode($constant) . "' />";
        }

        return response()->view('layouts.antd', compact('vue'));
    }

    public function read(Request $request)
    {
        if ($request->req == 'table') {

            $data = AssetProfile::with('jenisPerolehan', 'satker')
                ->whereNull('tgl_penghapusan')
                ->where(function ($q) use ($request) {
                    if ($request->search)
                        $q->where('deskripsi', 'like', "%{$request->search}%");
                })
                ->where(function ($q) use ($request) {
                    if ($request->kategori_id)
                        $q->where('kategori_id', 'LIKE', $request->kategori_id  . '%');
                })
                ->where(function ($q) use ($request) {
                    if ($request->kib == 1) {
                        $q->WhereNotNull('jenis_kib');
                    } elseif ($request->kib == 2) {
                        $q->WhereNull('jenis_kib');
                    }
                })
                ->filterSatker($request)
                ->paginate($this->per_page());

            return response()->json(['models' => $data]);
        } elseif ($request->req == 'data_profil' || $request->req == 'data_profil_all') {
            $data = AssetProfile::with('jenisPerolehan')
                ->whereNull('tgl_penghapusan')
                // filter search
                ->where(function ($q) use ($request) {
                    if ($request->search)
                        $q->where('deskripsi', 'like', "%{$request->search}%");
                })
                // filter ruang
                ->where(function ($q) use ($request) {
                    if ($request->ruang_id)
                        $q->where('ruang_id', $request->ruang_id);
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
                // filter henti_guna
                ->where(function ($q) use ($request) {
                    $isHentiGuna = filter_var($request->henti_guna, FILTER_VALIDATE_BOOLEAN);

                    if ($isHentiGuna) {
                        $q->where('henti_guna', 1);
                    }
                })
                // filter status_distribusi
                ->where(function ($q) use ($request) {
                    if ($request->status_distribusi == 1) {
                        $q->WhereNotNull('tipe_ruang');
                    } elseif ($request->status_distribusi == 2) {
                        $q->whereNull('tipe_ruang');
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
                // filter satker
                ->filterSatker($request);

            if ($request->req == 'data_profil') {
                $data = $data->paginate($this->per_page());
            } elseif ($request->req == 'data_profil_all') {
                $data = $data->select('id')->get();
            }

            return response()->json(['models' => $data]);
        } elseif ($request->req == 'view_checked_data') {

            $selectedItems = $request->input('id');

            $data = AssetProfile::whereIn('id', $selectedItems)->paginate($this->per_page());

            return response()->json(['models' => $data]);
        } elseif ($request->req == 'pending_distribution') {
            $data = AssetProfile::with('jenisPerolehan', 'satker')
                ->where(function ($q) use ($request) {
                    if ($request->search)
                        $q->where('deskripsi', 'like', "%{$request->search}%");
                    $q->orWhere('kategori_nama', 'like', "%{$request->search}%");
                })
                ->where(function ($q) use ($request) {
                    if ($request->kib == 1) {
                        $q->WhereNotNull('jenis_kib');
                    } elseif ($request->kib == 2) {
                        $q->WhereNull('jenis_kib');
                    }
                })
                ->WhereNull('tipe_ruang')
                ->filterSatker($request)
                ->get();

            return response()->json(['undistributedModels' => $data]);
        } elseif ($request->req == 'distribution_history') {
            $data = AsetPerubahan::distribusi()->with('satker')
                ->where('profil_id', $request->id)
                ->orderByDesc('created_at')
                ->get();
            return response()->json(['models' => $data]);
        } elseif ($request->req == 'distributed') {
            $data = AssetProfile::with('jenisPerolehan', 'satker')
                ->where(function ($q) use ($request) {
                    if ($request->search)
                        $q->where('deskripsi', 'like', "%{$request->search}%");
                    $q->orWhere('kategori_nama', 'like', "%{$request->search}%");
                })
                ->where(function ($q) use ($request) {
                    if ($request->kib == 1) {
                        $q->WhereNotNull('jenis_kib');
                    } elseif ($request->kib == 2) {
                        $q->WhereNull('jenis_kib');
                    }
                })
                ->WhereNotNull('tipe_ruang')
                ->filterSatker($request)
                ->get();

            return response()->json(['distributedModels' => $data]);
        } elseif ($request->req == 'room_distribution_history') {
            $data = AsetPerubahan::distribusiRuang()
                ->where(function ($q) use ($request) {
                    if ($request->search)
                        $q->where('deskripsi', 'like', "%{$request->search}%");
                })
                ->orderByDesc('created_at')
                ->filterSatker($request)
                ->paginate($this->per_page());

            return response()->json(['models' => $data]);
        } elseif ($request->req == 'info_profil') {
            $data = AssetProfile::where('id', $request->id)
                ->with('jenisPerolehan', 'satker', 'perolehan', 'penghapusan')
                ->first();

            // Detail Kategori
            $detailKategori = $this->get_jenjang_kategori($data->kategori_id);
            $data->kd_gol = $detailKategori['kd_gol'];
            $data->kd_bid = $detailKategori['kd_bid'];
            $data->kd_kel = $detailKategori['kd_kel'];
            $data->kd_skel = $detailKategori['kd_skel'];

            return response()->json(['models' => $data]);
        } elseif ($request->req == 'log_kondisi') {
            $logKondisi = AsetPerubahan::where('profil_id', $request->id)
                ->where('type', 'kondisi')
                ->orderByDesc('created_at')
                ->get();

            return response()->json(['models' => $logKondisi]);
        } elseif ($request->req == 'log_pemanfaatan') {

            $data = AssetProfile::find($request->id);

            $logPemanfaatan = AsetPerubahan::where('profil_id', $request->id)
                ->where('type', 'pemanfaatan')
                ->orderByDesc('created_at')
                ->get()
                ->map(function ($item) {
                    $item->file = null;
                    return $item;
                });

            foreach ($logPemanfaatan as $log) {

                $directory = storage_path('app/documents/profiles/' . $data->id . '/perubahan/pemanfaatan/');

                $matchingFile = glob($directory . '*' . $log->code . '*');

                if (!empty($matchingFile)) {
                    $fileName = basename($matchingFile[0]);
                    $log->file = [
                        'name' => $fileName,
                        'code' => $data->id,
                    ];
                }
            }

            return response()->json(['models' => $logPemanfaatan]);
        } elseif ($request->req == 'log_pemeliharaan') {

            $data = AssetProfile::find($request->id);

            $logPemeliharaan = AsetPerubahan::where('profil_id', $request->id)
                ->where(function ($w) {
                    $w->where('type', 'pemeliharaan-kapitalisasi')
                        ->orWhere('type', 'pemeliharaan-non-kapitalisasi');
                })
                ->orderByDesc('created_at')
                ->get()
                ->map(function ($item) {
                    $item->file = null;
                    return $item;
                });

            foreach ($logPemeliharaan as $log) {

                $directory = storage_path('app/documents/profiles/' . $data->id . '/perubahan/pemeliharaan/');

                $matchingFile = glob($directory . '*' . $log->code . '*');

                if (!empty($matchingFile)) {
                    $fileName = basename($matchingFile[0]);
                    $log->file = [
                        'name' => $fileName,
                        'code' => $data->id,
                    ];
                }
            }

            return response()->json(['models' => $logPemeliharaan]);
        } elseif ($request->req == 'log_henti_guna') {

            $data = AssetProfile::find($request->id);

            $logHentiGuna = AsetPerubahan::where('profil_id', $request->id)
                ->where(function ($w) {
                    $w->where('type', 'henti-guna')
                        ->orWhere('type', 'aktif-guna-kembali');
                })
                ->orderByDesc('created_at')
                ->get()
                ->map(function ($item) {
                    $item->file = null;
                    return $item;
                });

            foreach ($logHentiGuna as $log) {

                $directory = storage_path('app/documents/profiles/' . $data->id . '/perubahan/penghentigunaan/');

                $matchingFile = glob($directory . '*' . $log->code . '*');

                if (!empty($matchingFile)) {
                    $fileName = basename($matchingFile[0]);
                    $log->file = [
                        'name' => $fileName,
                        'code' => $data->id,
                    ];
                }
            }

            return response()->json(['models' => $logHentiGuna]);
        } elseif ($request->req == 'aset_transaksi') {

            $data = AssetProfile::where('id', $request->id)
                ->with('jenisPerolehan', 'satker', 'penyusutan.jenisTransaksi', 'transaksi.jenisTransaksi', 'transaksi.reference')
                ->first();

            $transaksi = $data->transaksi->map(function ($item) {
                $item['type'] = 'Transaksi';
                return $item;
            })->toArray();

            $penyusutan = $data->penyusutan->map(function ($item) {
                $item['type'] = 'Penyusutan';
                $item['created_at'] = $item->tgl_penyusutan;
                $item['nilai'] = $item['nilai'] * -1;
                return $item;
            })->toArray();

            $models = collect(array_merge($transaksi, $penyusutan))->sortBy('created_at')->values();

            $saldo = 0;
            $models = $models->map(function ($item) use (&$saldo) {
                $saldo += $item['nilai'];
                $res = collect($item);
                $res['saldo'] = $saldo;
                return $res;
            });

            return response()->json(['models' => $models]);
        } elseif ($request->req == 'data_kib') {
            $data = AssetProfile::select('id', 'deskripsi', 'kib')->where('id', $request->id)->first();

            return response()->json(['models' => $data]);
        } elseif ($request->req == 'hitung_pertambahan_masa_manfaat') {
            $data = AssetProfile::find($request->id);

            $pertambahan_masa_manfaat = round($request->pemeliharaan_biaya / $data->nilai_penyusutan);

            return response()->json(['pertambahan_masa_manfaat' => $pertambahan_masa_manfaat]);
        } elseif ($request->req == 'table_list_image') {

            $directoryPath = storage_path('app/documents/profiles/' . $request->id . '/photo/');;

            if (is_dir($directoryPath)) {

                $files = array_diff(scandir($directoryPath), array('.', '..'));

                $data = [];
                foreach ($files as $idx => $f) {
                    $data[] = [
                        'no' => $idx - 1,
                        'name' => $f,
                        'code' => $request->id
                    ];
                }

                return response()->json($data);
            } else {
                return response()->json(['message' => 'Direktori tidak ditemukan'], 404);
            }
        } elseif ($request->req == 'table_list_document') {

            $directoryPath = storage_path('app/documents/profiles/' . $request->id . '/document/');;

            if (is_dir($directoryPath)) {

                $files = array_diff(scandir($directoryPath), array('.', '..'));

                $data = [];
                foreach ($files as $idx => $f) {
                    $data[] = [
                        'no' => $idx - 1,
                        'name' => $f,
                        'code' => $request->id
                    ];
                }

                return response()->json($data);
            } else {
                return response()->json(['message' => 'Direktori tidak ditemukan'], 404);
            }
        } elseif ($request->req == 'table_list_files_perolehan') {

            $data = AssetProfile::select('id', 'perolehan_id')
                ->with(['perolehan:id,kode'])
                ->where('id', $request->id)->first();

            $code = $data->perolehan->kode;

            $directoryPath = storage_path('app/documents/' . $code . '/');

            if (is_dir($directoryPath)) {

                $files = array_diff(scandir($directoryPath), array('.', '..'));

                $data = [];
                foreach ($files as $idx => $f) {
                    $data[] = [
                        'no' => $idx - 1,
                        'code' => $code,
                        'name' => $f,
                    ];
                }
            }
            return response()->json($data);
        } elseif ($request->req == 'table_list_files_perubahan') {

            $profil = AssetProfile::find($request->id);

            $baseDirectory = storage_path('app/documents/profiles/' . $profil->id . '/perubahan/');

            $folders = ['pemeliharaan', 'penghentigunaan', 'pemanfaatan'];

            $data = [];

            foreach ($folders as $folder) {
                $directoryPath = $baseDirectory . $folder . '/';

                if (is_dir($directoryPath)) {
                    $files = array_diff(scandir($directoryPath), ['.', '..']);

                    foreach ($files as $idx => $file) {
                        $data[] = [
                            'no' => $idx + 1,
                            'code' => $profil->id,
                            'name' => $file,
                            'type' => 'file_' . $folder
                        ];
                    }
                }
            }

            return response()->json($data);
        } elseif ($request->req == 'preview_file') {

            $extension = strtolower(pathinfo($request->filename, PATHINFO_EXTENSION));

            if ($request->mode == "file_perolehan") {
                $path = storage_path('app/documents/' . $request->code . '/' . $request->filename);
            } elseif ($request->mode == "file_profil") {
                if ($extension === 'pdf') {
                    $path = storage_path('app/documents/profiles/' . $request->code . '/document/' . $request->filename);
                } else {
                    $path = storage_path('app/documents/profiles/' . $request->code . '/photo/' . $request->filename);
                }
            } elseif ($request->mode == "file_pemeliharaan") {
                $path = storage_path('app/documents/profiles/' . $request->code . '/perubahan/pemeliharaan/' . $request->filename);
            } elseif ($request->mode == "file_pemanfaatan") {
                $path = storage_path('app/documents/profiles/' . $request->code . '/perubahan/pemanfaatan/' . $request->filename);
            } elseif ($request->mode == "file_penghentigunaan") {
                $path = storage_path('app/documents/profiles/' . $request->code . '/perubahan/penghentigunaan/' . $request->filename);
            }

            if (!file_exists($path)) {
                return response()->json(['message' => 'File tidak ditemukan'], 404);
            }

            if ($extension === 'pdf') {

                return response()->file($path);
            } elseif (in_array($extension, ['jpg', 'jpeg', 'png'])) {

                $arrContextOptions = array(
                    "ssl" => array(
                        "verify_peer" => false,
                        "verify_peer_name" => false,
                    ),
                );

                $base64Image = base64_encode(file_get_contents($path, false, stream_context_create($arrContextOptions)));

                $data = 'data:image/png;base64,' . $base64Image;

                $pdf = PDF::loadView('print.convert_image', compact('data'))->output();

                return new Response($pdf, 200, array(
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="preview.pdf"',
                ));
            }
        } elseif ($request->req == 'selected_categories_by_satker') {

            $data = AssetProfile::select('kategori_id', 'kategori_nama', DB::raw('COUNT(*) as total'))
                ->where('satker_id', $request->satker_id)
                ->groupBy('kategori_id')
                ->havingRaw('COUNT(*) > 1')
                ->get();

            return response()->json(['models' => $data]);
        } elseif ($request->req == 'check_nup_range') {

            $data = AssetProfile::where('satker_id', $request->satker_id)
                ->where('kategori_id', $request->kategori_id)
                ->selectRaw('MIN(nup) as min, MAX(nup) as max')
                ->first();

            return response()->json(['models' => $data]);
        }
    }
    public function write(Request $request)
    {
        if ($request->req == 'write_kib') {
            $data = AssetProfile::find($request->id);
            if (!$data)
                return response()->json('Data Tidak Ditemukan', 403);

            $data->kib = $request->form;

            return response()->json($data->save());
        } elseif ($request->req == 'write_catatan_profil') {
            $this->validate($request, [
                'notes' => 'string|max:255',
            ]);
            $data = AssetProfile::find($request->id);
            if (!$data)
                return response()->json('Data Tidak Ditemukan', 403);

            $data->notes = $request->notes;

            return response()->json($data->save());
        } elseif ($request->req == 'distribute_bulk') {

            if ($this->dynamic_config('INPUT-DISTRIBUSI') !== '1') {
                return response()->json([
                    'message' => $this->dynamic_config('INPUT-DISTRIBUSI', 'message') ?? 'Masa Pendistribusian telah berakhir.',
                ], 422);
            }

            $this->validate($request, [
                'ruang_id' => 'required_if:tipe_ruang,DBR',
                'keterangan_lokasi' => 'required_if:tipe_ruang,DBL'
            ]);

            $selectedItems = $request->input('id');

            $ruang = AsetRuang::find($request->ruang_id);

            DB::transaction(function () use ($selectedItems, $ruang, $request) {

                $dataToInsert = [];

                foreach ($selectedItems as $item) {
                    $data = AssetProfile::find($item);
                    if ($data) {

                        //JIKA TIDAK ADA PERUBAHAN DATA MAKA SKIP KE PERULANGAN BERIKUTNYA
                        if (($request->tipe_ruang == 'DBR' && $data->ruang_id == $request->ruang_id)
                            || ($request->tipe_ruang == 'DBL' && $data->ruang_nama == $request->keterangan_lokasi)
                        ) {
                            continue;
                        }

                        //UPDATE PROFIL
                        if ($request->tipe_ruang !== null) {
                            $data->tipe_ruang = $request->tipe_ruang;
                            if ($request->ruang_id) {
                                $data->ruang_id = $request->ruang_id;
                                $data->ruang_nama = ($ruang->kode ?? '') . ' - ' . ($ruang->nama ?? '');
                            } elseif ($request->keterangan_lokasi) {
                                $data->ruang_nama = $request->keterangan_lokasi;
                            }
                            $data->editor_id = request()->user()->id;
                            $data->save();

                            // INSERT KE ASET PERUBAHAN
                            $dataToInsert[] = [
                                'profil_id' => $data->id,
                                'satker_id' => $data->satker_id,
                                'type' => 'distribusi-ruang',
                                'code' => CodeRegister::next('aset_perubahan'),
                                'deskripsi' => $data->deskripsi,
                                'tipe_ruang' => $request->tipe_ruang,
                                'ruang_id' => $request->ruang_id,
                                'ruang_nama' => $request->ruang_id ? ($ruang->kode ?? '') . ' - ' . ($ruang->nama ?? '') : null,
                                'keterangan_lokasi' => $request->keterangan_lokasi,
                                'catatan' => $request->catatan,
                                'editor_id' => request()->user()->id,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        } else {
                            //UPDATE PROFIL
                            $data->satker_id = $request->satker_id;
                            $data->tipe_ruang = null;
                            $data->ruang_id = null;
                            $data->ruang_nama = null;
                            $data->editor_id = request()->user()->id;
                            $data->save();

                            // INSERT KE ASET PERUBAHAN
                            $dataToInsert[] = [
                                'profil_id' => $data->id,
                                'satker_id' => $data->satker_id,
                                'type' => 'distribusi-satker',
                                'code' => CodeRegister::next('aset_perubahan'),
                                'deskripsi' => $data->deskripsi,
                                'editor_id' => request()->user()->id,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ];
                        }
                    }
                }

                if (AsetPerubahan::insert($dataToInsert))
                    CodeRegister::update_number('aset_perubahan');
            });

            return response()->json(['message' => 'Data berhasil didistribusikan'], 200);
        } elseif ($request->req == 'update_condition_bulk') {

            $this->validate($request, [
                'kondisi' => 'required'
            ]);

            $selectedItems = $request->input('id');

            DB::transaction(function () use ($selectedItems, $request) {
                $dataToInsert = [];

                foreach ($selectedItems as $item) {
                    $data = AssetProfile::find($item);
                    if ($data) {

                        //JIKA TIDAK ADA PERUBAHAN DATA MAKA SKIP KE PERULANGAN BERIKUTNYA
                        if ($data->kondisi == $request->kondisi) {
                            continue;
                        }

                        //UPDATE PROFIL   
                        $data->kondisi = $request->kondisi;
                        $data->editor_id = request()->user()->id;
                        $data->save();

                        // INSERT KE ASET PERUBAHAN
                        $dataToInsert[] = [
                            'profil_id' => $data->id,
                            'satker_id' => $data->satker_id,
                            'type' => 'kondisi',
                            'kondisi' => $request->kondisi,
                            'code' => CodeRegister::next('aset_perubahan'),
                            'deskripsi' => $data->deskripsi,
                            'editor_id' => request()->user()->id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }

                if (AsetPerubahan::insert($dataToInsert))
                    CodeRegister::update_number('aset_perubahan');
            });

            return response()->json(['message' => 'Kondisi berhasil diubah'], 200);
        } elseif ($request->req == 'asset_deactivate_bulk') {

            if ($this->dynamic_config('UBAH-STATUS-KEAKTIFAN') !== '1') {
                return response()->json([
                    'message' => $this->dynamic_config('UBAH-STATUS-KEAKTIFAN', 'message') ?? 'Tidak bisa mengubah status keaktifan, silahkan hubungi Pihak Biro Aset.',
                ], 422);
            }

            $this->validate($request, [
                'henti_guna' => 'required'
            ]);

            $selectedItems = $request->input('id');

            DB::transaction(function () use ($selectedItems, $request) {

                foreach ($selectedItems as $item) {
                    $data = AssetProfile::find($item);
                    if ($data) {

                        // SUM NILAI TRANSAKSI
                        $sum_transaksi = AssetTransaction::where('profil_id', $data->id)->get()->sum('nilai');

                        // SUM NILAI PENYUSUTAN
                        $sum_penyusutan = AsetPenyusutan::where('profil_id', $data->id)->get()->sum('nilai');

                        //JIKA TIDAK ADA PERUBAHAN DATA MAKA SKIP KE PERULANGAN BERIKUTNYA
                        if ($data->henti_guna == $request->henti_guna) {
                            continue;
                        }

                        // UPDATE PROFIL
                        $data->henti_guna = $request->henti_guna;
                        $data->editor_id = request()->user()->id;
                        $data->save();

                        // INSERT KE ASET PERUBAHAN
                        $asetPerubahan = new AsetPerubahan();
                        $asetPerubahan->code = CodeRegister::next('aset_perubahan');
                        $asetPerubahan->profil_id = $data->id;
                        $asetPerubahan->henti_guna = $request->henti_guna;
                        $asetPerubahan->type = $request->henti_guna == 1 ? 'henti-guna' : 'aktif-guna-kembali';
                        $asetPerubahan->deskripsi = $data->deskripsi;
                        $asetPerubahan->satker_id = $data->satker_id;
                        $asetPerubahan->editor_id = request()->user()->id;
                        $asetPerubahan->created_at = now();

                        if ($asetPerubahan->save()) {
                            CodeRegister::update_number('aset_perubahan');
                        }

                        $date = new DateTimeImmutable('now');

                        // INSERT KE ASET TRANSAKSI DAN PENYUSUTAN
                        if ($request->henti_guna == 1) {
                            $this->insertTransaksiHentiGuna($data, $asetPerubahan->id, '401', 'ASET-PERUBAHAN', $date, $sum_transaksi);
                            $date = $date->add(new DateInterval('PT2S'));
                            $this->insertTransaksiHentiGuna($data, $asetPerubahan->id, '188', 'ASET-PERUBAHAN', $date, -abs($sum_transaksi));
                            $date = $date->add(new DateInterval('PT2S'));
                            $this->insertPenyusutanHentiGuna($data, 'S03', $date, -abs($sum_penyusutan));
                            $date = $date->add(new DateInterval('PT2S'));
                            $this->insertPenyusutanHentiGuna($data, 'S13', $date, $sum_penyusutan);
                        } else {
                            $this->insertTransaksiHentiGuna($data, $asetPerubahan->id, '177', 'ASET-PERUBAHAN', $date, $sum_transaksi);
                            $date = $date->add(new DateInterval('PT2S'));
                            $this->insertTransaksiHentiGuna($data, $asetPerubahan->id, '402', 'ASET-PERUBAHAN', $date, -abs($sum_transaksi));
                            $date = $date->add(new DateInterval('PT2S'));
                            $this->insertPenyusutanHentiGuna($data, 'S13', $date, -abs($sum_penyusutan));
                            $date = $date->add(new DateInterval('PT2S'));
                            $this->insertPenyusutanHentiGuna($data, 'S03', $date, $sum_penyusutan);
                        }
                    }
                }
            });

            return response()->json(['message' => 'Status Henti Guna berhasil diubah'], 200);
        } elseif ($request->req == 'update_condition_single') {

            $data = AssetProfile::find($request->id);

            // JIKA TIDAK ADA PERUBAHAN DATA
            if ($data->kondisi == $request->kondisi) {
                return response()->json('Tidak ada perubahan data', 403);
            }

            DB::transaction(function () use ($data, $request) {
                // UPDATE PROFIL
                $data->kondisi = $request->kondisi;
                $data->save();

                // INSERT KE ASET PERUBAHAN
                $asetPerubahan = new AsetPerubahan();
                $asetPerubahan->code = CodeRegister::next('aset_perubahan');
                $asetPerubahan->profil_id = $data->id;
                $asetPerubahan->type = 'kondisi';
                $asetPerubahan->kondisi = $request->kondisi;
                $asetPerubahan->deskripsi = $data->deskripsi;
                $asetPerubahan->satker_id = $data->satker_id;
                $asetPerubahan->editor_id = request()->user()->id;
                $asetPerubahan->created_at = now();

                if ($asetPerubahan->save()) {
                    CodeRegister::update_number('aset_perubahan');
                }
            });

            return response()->json(true);
        } elseif ($request->req == 'assign_utilization_single') {

            $this->validate($request, [
                'pemanfaatan_id' => 'required',
                'pemanfaatan_catatan' => 'required',
                'file' => 'required|file|mimes:jpg,jpeg,png,pdf'
            ]);

            $pemanfaatanList = setting('pemanfaatan');

            $data = AssetProfile::find($request->id);

            // JIKA TIDAK ADA PERUBAHAN DATA
            if (($data->pemanfaatan_id == $request->pemanfaatan_id) && ($data->pemanfaatan_catatan == $request->pemanfaatan_catatan)) {
                return response()->json('Tidak ada perubahan data', 403);
            }

            DB::transaction(function () use ($data, $pemanfaatanList, $request) {

                // INSERT KE ASET PERUBAHAN
                $asetPerubahan = new AsetPerubahan();
                $asetPerubahan->code = CodeRegister::next('aset_perubahan');
                $asetPerubahan->profil_id = $data->id;
                $asetPerubahan->type = 'pemanfaatan';
                $asetPerubahan->deskripsi = $data->deskripsi;
                $asetPerubahan->satker_id = $data->satker_id;
                $asetPerubahan->pemanfaatan_id = $request->pemanfaatan_id;
                $asetPerubahan->pemanfaatan_nama = $pemanfaatanList[$request->pemanfaatan_id] ?? null;
                $asetPerubahan->pemanfaatan_catatan = $request->pemanfaatan_catatan;
                $asetPerubahan->editor_id = request()->user()->id;
                $asetPerubahan->created_at = now();

                if ($asetPerubahan->save()) {
                    // HANDLE FILE UPLOAD
                    $file = $request->file('file');
                    $dirname = storage_path('app/documents/profiles/' . $data->id . '/perubahan/pemanfaatan/');
                    $filename = $file->getClientOriginalName();
                    $filename = $asetPerubahan->code . '_' . $file->getClientOriginalName();
                    $fileinfo = pathinfo($dirname . $filename);

                    $x = 1;
                    while (file_exists($dirname . $filename)) {
                        $filename = $fileinfo['filename'] . " ($x)." . $fileinfo['extension'];
                        $x++;
                    }

                    $file->move($dirname, $filename);

                    CodeRegister::update_number('aset_perubahan');

                    // UPDATE PROFIL
                    $data->pemanfaatan_id = $request->pemanfaatan_id;
                    $data->pemanfaatan_nama = $pemanfaatanList[$request->pemanfaatan_id] ?? null;
                    $data->pemanfaatan_catatan = $request->pemanfaatan_catatan;
                    $data->save();
                }
            });

            return response()->json(true);
        } elseif ($request->req == 'record_maintenance_single') {

            if ($this->dynamic_config('INPUT-PEMELIHARAAN') !== '1') {
                return response()->json([
                    'message' => $this->dynamic_config('INPUT-PEMELIHARAAN', 'message') ?? 'Masa Penginputan pemeliharaan telah berakhir.',
                ], 422);
            }

            $this->validate($request, [
                'pemeliharaan_catatan' => 'required',
                'pemeliharaan_biaya' => 'required|numeric|min:1',
                'file' => 'required|file|mimes:jpg,jpeg,png,pdf',
                'type' => 'required'
            ]);

            $data = AssetProfile::find($request->id);

            // INSERT KE ASET PERUBAHAN
            DB::transaction(function () use ($data, $request) {
                $asetPerubahan = new AsetPerubahan();
                $asetPerubahan->code = CodeRegister::next('aset_perubahan');
                $asetPerubahan->profil_id = $data->id;
                if ($request->type == 'kapitalisasi') {
                    $asetPerubahan->type = 'pemeliharaan-kapitalisasi';
                } elseif ($request->type == 'non-kapitalisasi') {
                    $asetPerubahan->type = 'pemeliharaan-non-kapitalisasi';
                }
                $asetPerubahan->deskripsi = $data->deskripsi;
                $asetPerubahan->satker_id = $data->satker_id;
                $asetPerubahan->editor_id = request()->user()->id;
                $asetPerubahan->pemeliharaan_catatan = $request->pemeliharaan_catatan;
                $asetPerubahan->pemeliharaan_biaya = $request->pemeliharaan_biaya;

                if ($asetPerubahan->save()) {

                    // HANDLE FILE UPLOAD
                    $file = $request->file('file');
                    $dirname = storage_path('app/documents/profiles/' . $data->id . '/perubahan/pemeliharaan/');
                    $filename = $file->getClientOriginalName();
                    $filename = $asetPerubahan->code . '_' . $file->getClientOriginalName();
                    $fileinfo = pathinfo($dirname . $filename);

                    $x = 1;
                    while (file_exists($dirname . $filename)) {
                        $filename = $fileinfo['filename'] . " ($x)." . $fileinfo['extension'];
                        $x++;
                    }

                    $file->move($dirname, $filename);

                    CodeRegister::update_number('aset_perubahan');

                    // INSERT KE ASET TRANSAKSI
                    $y = new AssetTransaction();
                    $y->editor_id = request()->user()->id;
                    $y->profil_id = $data->id;
                    $y->satker_id = $data->satker_id;
                    $y->satker_nama = Satker::find($data->satker_id)->nama;
                    $y->jenis_transaksi_id = 202;
                    $y->jenis_transaksi_nama = 'Pengembangan Nilai Aset';
                    $y->kategori_id = $data->kategori_id;
                    $y->kategori_nama = $data->kategori_nama;
                    $y->nup = $data->nup;
                    $y->kondisi = $data->kondisi;
                    $y->kuantitas = 1;
                    $y->nilai = $request->pemeliharaan_biaya;
                    $y->tipe_ruang = $data->tipe_ruang;
                    $y->ruang_id = $data->ruang_id;
                    $y->ruang_nama = $data->ruang_nama;
                    $y->jenis_kib = $data->jenis_kib;
                    $y->komptabel = $data->komptabel;
                    $y->reference_id = $asetPerubahan->id;
                    $y->reference_type = 'ASET-PERUBAHAN';
                    $y->jenis_kib = $data->jenis_kib;
                    $y->save();

                    $date = new DateTimeImmutable($y->created_at);

                    //UPDATE PROFIL
                    $data->nilai_perubahan = $data->nilai_perubahan + $asetPerubahan->pemeliharaan_biaya;
                    $data->nilai_buku = $data->nilai_buku + $asetPerubahan->pemeliharaan_biaya;
                    $data->save();

                    // $kelompok = substr($data->kategori_id, 0, 5);
                    // $masa_manfaat = AsetMasaManfaat::where('kdkbrg', $kelompok)->first();
                    // $data->nilai_perubahan = $data->nilai_perubahan + $asetPerubahan->pemeliharaan_biaya;

                    // $old_akum = $data->akumulasi_penyusutan;
                    // $perbandingan = ($data->akumulasi_penyusutan / $data->nilai_penyusutan);

                    // $data->nilai_penyusutan = $masa_manfaat ? round((1 / $masa_manfaat->umur_bulan) * ($data->nilai_perolehan + $data->nilai_perubahan), 2) : 0;
                    // $data->akumulasi_penyusutan = $data->nilai_penyusutan * $perbandingan;

                    // if ($data->nilai_buku == 0) {
                    //     $data->nilai_buku = 0;
                    //     $data->akumulasi_penyusutan = $data->nilai_perolehan + $data->nilai_perubahan;
                    // } else {
                    //     $data->nilai_buku = $data->nilai_perolehan + $data->nilai_perubahan - $data->akumulasi_penyusutan;
                    // }

                    //BALANCING PENYUSUTAN
                    // $z = new AsetPenyusutan();
                    // $z->profil_id = $data->id;
                    // $z->editor_id = 1;
                    // $z->satker_id = $data->satker_id;
                    // $z->jenis_transaksi_id = 'S03';
                    // $z->kategori_id = $data->kategori_id;
                    // $z->kategori_nama = $data->kategori_nama;
                    // $z->nup = $data->nup;
                    // $z->nilai = $data->nilai_buku == 0 ? ($data->nilai_perolehan + $data->nilai_perubahan - $old_akum) : ($data->akumulasi_penyusutan - $old_akum);
                    // $z->komptabel = $data->komptabel;
                    // $z->tgl_penyusutan = $date->add(new DateInterval('PT2S'));
                    // $z->bulan = (int)$date->format('m');
                    // $z->tahun = (int)$date->format('Y');
                    // $z->save();
                }
            });

            return response()->json(true);
        } elseif ($request->req == 'asset_deactivate_single') {

            if ($this->dynamic_config('UBAH-STATUS-KEAKTIFAN') !== '1') {
                return response()->json([
                    'message' => $this->dynamic_config('UBAH-STATUS-KEAKTIFAN', 'message') ?? 'Tidak bisa mengubah status keaktifan, silahkan hubungi Pihak Biro Aset.',
                ], 422);
            }

            $data = AssetProfile::find($request->id);

            // JIKA TIDAK ADA PERUBAHAN DATA
            if ($data->henti_guna == $request->henti_guna) {
                return response()->json('Tidak ada perubahan data', 403);
            }

            // SUM NILAI TRANSAKSI
            $sum_transaksi = AssetTransaction::where('profil_id', $data->id)->get()->sum('nilai');

            // SUM NILAI PENYUSUTAN
            $sum_penyusutan = AsetPenyusutan::where('profil_id', $data->id)->get()->sum('nilai');

            DB::transaction(function () use ($data, $request, $sum_transaksi, $sum_penyusutan) {

                // INSERT KE ASET PERUBAHAN
                $asetPerubahan = new AsetPerubahan();
                $asetPerubahan->code = CodeRegister::next('aset_perubahan');
                $asetPerubahan->profil_id = $data->id;
                $asetPerubahan->henti_guna = $request->henti_guna;
                $asetPerubahan->type = $request->henti_guna == 1 ? 'henti-guna' : 'aktif-guna-kembali';
                $asetPerubahan->deskripsi = $data->deskripsi;
                $asetPerubahan->satker_id = $data->satker_id;
                $asetPerubahan->editor_id = request()->user()->id;
                $asetPerubahan->created_at = now();

                if ($asetPerubahan->save()) {

                    // HANDLE FILE UPLOAD
                    $file = $request->file('file');
                    $dirname = storage_path('app/documents/profiles/' . $data->id . '/perubahan/penghentigunaan/');
                    $filename = $file->getClientOriginalName();
                    $filename = $asetPerubahan->code . '_' . $file->getClientOriginalName();
                    $fileinfo = pathinfo($dirname . $filename);

                    $x = 1;
                    while (file_exists($dirname . $filename)) {
                        $filename = $fileinfo['filename'] . " ($x)." . $fileinfo['extension'];
                        $x++;
                    }

                    $file->move($dirname, $filename);

                    CodeRegister::update_number('aset_perubahan');

                    // UPDATE PROFIL
                    $data->henti_guna = $request->henti_guna;
                    $data->editor_id = request()->user()->id;
                    $data->save();

                    $date = new DateTimeImmutable('now');

                    // INSERT KE ASET TRANSAKSI DAN PENYUSUTAN
                    if ($request->henti_guna == 1) {
                        $this->insertTransaksiHentiGuna($data, $asetPerubahan->id, '401', 'ASET-PERUBAHAN', $date, $sum_transaksi);
                        $date = $date->add(new DateInterval('PT2S'));
                        $this->insertTransaksiHentiGuna($data, $asetPerubahan->id, '188', 'ASET-PERUBAHAN', $date, -abs($sum_transaksi));
                        $date = $date->add(new DateInterval('PT2S'));
                        $this->insertPenyusutanHentiGuna($data, 'S03', $date, -abs($sum_penyusutan));
                        $date = $date->add(new DateInterval('PT2S'));
                        $this->insertPenyusutanHentiGuna($data, 'S13', $date, $sum_penyusutan);
                    } else {
                        $this->insertTransaksiHentiGuna($data, $asetPerubahan->id, '177', 'ASET-PERUBAHAN', $date, $sum_transaksi);
                        $date = $date->add(new DateInterval('PT2S'));
                        $this->insertTransaksiHentiGuna($data, $asetPerubahan->id, '402', 'ASET-PERUBAHAN', $date, -abs($sum_transaksi));
                        $date = $date->add(new DateInterval('PT2S'));
                        $this->insertPenyusutanHentiGuna($data, 'S13', $date, -abs($sum_penyusutan));
                        $date = $date->add(new DateInterval('PT2S'));
                        $this->insertPenyusutanHentiGuna($data, 'S03', $date, $sum_penyusutan);
                    }
                }
            });

            return response()->json(true);
        } elseif ($request->req == 'distribute_single') {

            if ($this->dynamic_config('INPUT-DISTRIBUSI') !== '1') {
                return response()->json([
                    'message' => $this->dynamic_config('INPUT-DISTRIBUSI', 'message') ?? 'Masa Pendistribusian telah berakhir.',
                ], 422);
            }

            $this->validate($request, [
                'tipe_ruang' => 'required',
                'ruang_id' => 'required_if:tipe_ruang,DBR',
                'keterangan_lokasi' => 'required_if:tipe_ruang,DBL'
            ]);

            $data = AssetProfile::find($request->id);

            // JIKA TIDAK ADA PERUBAHAN DATA
            if (($request->tipe_ruang == 'DBR' && $data->ruang_id == $request->ruang_id)
                || ($request->tipe_ruang == 'DBL' && $data->ruang_nama == $request->keterangan_lokasi)
            ) {
                return response()->json('Tidak ada perubahan data', 403);
            }

            DB::transaction(function () use ($data, $request) {
                if ($request->tipe_ruang !== null) {

                    $data->tipe_ruang = $request->tipe_ruang;
                    if ($request->ruang_id) {
                        $ruang = AsetRuang::find($request->ruang_id);
                        $data->ruang_id = $request->ruang_id;
                        $data->ruang_nama = ($ruang->kode ?? '') . ' - ' . ($ruang->nama ?? '');
                    } elseif ($request->keterangan_lokasi) {
                        $data->ruang_nama = $request->keterangan_lokasi;
                    }
                    $data->editor_id = request()->user()->id;
                    $data->save();

                    // INSERT KE ASET PERUBAHAN
                    $asetPerubahan = new AsetPerubahan();
                    $asetPerubahan->profil_id = $data->id;
                    $asetPerubahan->satker_id = $data->satker_id;
                    $asetPerubahan->type = 'distribusi-ruang';
                    $asetPerubahan->code = CodeRegister::next('aset_perubahan');
                    $asetPerubahan->deskripsi = $data->deskripsi;
                    $asetPerubahan->tipe_ruang = $request->tipe_ruang;
                    $asetPerubahan->ruang_id = $request->ruang_id;
                    $asetPerubahan->ruang_nama = $request->ruang_id ? ($ruang->kode ?? '') . ' - ' . ($ruang->nama ?? '') : null;
                    $asetPerubahan->keterangan_lokasi = $request->keterangan_lokasi;
                    $asetPerubahan->catatan = $request->catatan;
                    $asetPerubahan->editor_id = request()->user()->id;
                    $asetPerubahan->created_at = now();
                    $asetPerubahan->save();
                } else {
                    $data->satker_id = $request->satker_id;
                    $data->tipe_ruang = null;
                    $data->ruang_id = null;
                    $data->ruang_nama = null;
                    $data->editor_id = request()->user()->id;
                    $data->save();

                    // INSERT KE ASET PERUBAHAN
                    $asetPerubahan = new AsetPerubahan();
                    $asetPerubahan->profil_id = $data->id;
                    $asetPerubahan->satker_id = $data->satker_id;
                    $asetPerubahan->type = 'distribusi-satker';
                    $asetPerubahan->code = CodeRegister::next('aset_perubahan');
                    $asetPerubahan->deskripsi = $data->deskripsi;
                    $asetPerubahan->editor_id = request()->user()->id;
                    $asetPerubahan->created_at = now();
                    $asetPerubahan->save();
                }

                if ($asetPerubahan->save()) {
                    CodeRegister::update_number('aset_perubahan');
                }
            });

            return response()->json(['message' => 'Data berhasil didistribusikan'], 200);
        } elseif ($request->req == 'image_upload') {
            $this->validate($request, [
                'file' => 'required|file|mimes:jpg,jpeg,png'
            ]);

            $file = $request->file('file');
            $dirname = storage_path('app/documents/profiles/' . $request->id . '/photo/');
            $filename = $file->getClientOriginalName();
            $fileinfo = pathinfo($dirname . $filename);

            $x = 1;
            while (file_exists($dirname . $filename)) {
                $filename = $fileinfo['filename'] . " ($x)." . $fileinfo['extension'];
                $x++;
            }

            $file->move($dirname, $filename);

            return response()->json(true);
        } elseif ($request->req == 'document_upload') {
            $this->validate($request, [
                'file' => 'required|file|mimes:pdf'
            ]);

            $file = $request->file('file');
            $dirname = storage_path('app/documents/profiles/' . $request->id . '/document/');
            $filename = $file->getClientOriginalName();
            $fileinfo = pathinfo($dirname . $filename);

            $x = 1;
            while (file_exists($dirname . $filename)) {
                $filename = $fileinfo['filename'] . " ($x)." . $fileinfo['extension'];
                $x++;
            }

            $file->move($dirname, $filename);

            return response()->json(true);
        } elseif ($request->req == 'delete_image') {
            $filename = $request->filename;
            $dirname = storage_path('app/documents/profiles/' .  $request->id . '/photo/');

            $filepath = $dirname . $filename;

            if (file_exists($filepath)) {
                unlink($filepath);
                return response()->json(true);
            } else {
                return response()->json('Gambar Tidak Ditemukan!', 403);
            }
        } elseif ($request->req == 'delete_document') {
            $filename = $request->filename;
            $dirname = storage_path('app/documents/profiles/' . $request->id . '/document/');

            $filepath = $dirname . $filename;

            if (file_exists($filepath)) {
                unlink($filepath);
                return response()->json(true);
            } else {
                return response()->json('Dokumen Tidak Ditemukan!', 403);
            }
        }
    }

    public function insertTransaksiHentiGuna($data, $referenceId, $jenisTransaksiId, $type, $date, $sum_transaksi)
    {
        $transaksiHentiGuna = JenisTransaksi::asetHentiGuna()->get()->keyBy('kode')->toArray();

        $x = new AssetTransaction();
        $x->profil_id = $data->id;
        $x->editor_id = request()->user()->id;
        $x->satker_id = $data->satker_id;
        $x->satker_nama = $data->satker->nama;
        $x->jenis_transaksi_id = $jenisTransaksiId;
        $x->jenis_transaksi_nama = $transaksiHentiGuna[$jenisTransaksiId]['uraian'];
        $x->kategori_id = $data->kategori_id;
        $x->kategori_nama = $data->kategori_nama;
        $x->nup = $data->nup;
        $x->kondisi = $data->kondisi;
        $x->kuantitas = 1;
        $x->nilai = $sum_transaksi;
        $x->tipe_ruang = $data->tipe_ruang;
        $x->ruang_id = $data->ruang_id;
        $x->ruang_nama = $data->ruang_nama;
        $x->jenis_kib = $data->jenis_kib;
        $x->komptabel = $data->komptabel;
        $x->reference_id = $referenceId;
        $x->reference_type = $type;
        $x->created_at = $date;
        $x->updated_at = $date;
        $x->save();
    }

    public function insertPenyusutanHentiGuna($data, $jenisTransaksiId, $date, $sum_penyusutan)
    {
        $peny = new AsetPenyusutan();
        $peny->editor_id = request()->user()->id;
        $peny->profil_id = $data->id;
        $peny->satker_id = $data->satker_id;
        $peny->jenis_transaksi_id = $jenisTransaksiId;
        $peny->kategori_id = $data->kategori_id;
        $peny->kategori_nama = $data->kategori_nama;
        $peny->nup = $data->nup;
        $peny->nilai = $sum_penyusutan;
        $peny->komptabel = 'Intra';
        $peny->tahun = (int)$date->format('Y');
        $peny->bulan = (int)$date->format('m');
        $peny->tgl_penyusutan = $date;
        $peny->save();
    }

    public function profil(Request $request)
    {
        try {
            $decryptId = decrypt($request->id);

            $login = Sso::setLogin();

            if ($login) {
                return redirect()->route('aset.profil', ['req' => 'open', 'id' => $decryptId]);
            } else {

                $data = AssetProfile::where('kategori_id', substr($decryptId, 0, 10))
                    ->where('nup', substr($decryptId, 10))
                    ->with('satker')
                    ->first();

                // Detail Kategori
                $detailKategori = $this->get_jenjang_kategori($data->kategori_id);
                $data->kd_gol = $detailKategori['kd_gol'];
                $data->kd_bid = $detailKategori['kd_bid'];
                $data->kd_kel = $detailKategori['kd_kel'];
                $data->kd_skel = $detailKategori['kd_skel'];

                return view('layouts.aset_profil_guest', compact('data'));
            }
        } catch (DecryptException $e) {
            abort(404);
        }
    }
}
