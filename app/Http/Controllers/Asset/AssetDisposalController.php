<?php

namespace App\Http\Controllers\Asset;

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Aset\AsetPenghapusan;
use App\Models\Aset\AssetProfile;
use App\Models\Aset\AsetPenyusutan;
use App\Models\Aset\AssetTransaction;
use App\Models\Satker;
use App\Models\JenisTransaksi;
use App\Models\CodeRegister;
use DB;
use PDF;
use DateInterval;
use DateTimeImmutable;
use App\Traits\ValidationData;

class AssetDisposalController extends AuthController
{
    public function __invoke(Request $request)
    {
        if ($request->req == 'open') {

            $data = AsetPenghapusan::select('id', 'kode', 'status', 'satker_id', 'jenis_transaksi_id')
                ->where('kode', $request->kode)
                ->first();

            $vue = "<aset-penghapusan-detail-page :parent='" . json_encode($data) . "' />";

            $this->validateSatkerPermission(request()->user(), $data->satker_id);
        } else {
            $constant = [
                'JENIS_TRANSAKSI_OPT' => JenisTransaksi::asetPenghapusan()->get(),
                'SATKER' => Satker::getSatkers()
            ];

            $vue = "<aset-penghapusan-page :constant='" . json_encode($constant) . "' />";
        }


        return response()->view('layouts.antd', compact('vue'));
    }

    public function read(Request $request)
    {
        if ($request->req == 'table') {
            $data = AsetPenghapusan::whereDateBetween('aset_penghapusan.created_at', $request->date_start, $request->date_end)
                ->with('jenisTransaksi', 'satker')
                ->where(function ($q) use ($request) {
                    if ($request->status)
                        $q->where('status', $request->status);
                })
                ->filterSatker($request)
                ->paginate($this->per_page());

                $dataStatusCount = AsetPenghapusan::filterSatker($request)->get();

                $dataOpen = $dataStatusCount->filter(function ($y) {
                    return $y->status === 'OPEN';
                })->count();
    
                $dataClose = $dataStatusCount->filter(function ($x) {
                    return $x->status === 'CLOSE';
                })->count();
    
                $dataFinish = $dataStatusCount->filter(function ($x) {
                    return $x->status === 'FINISH';
                })->count();

            return response()->json(['models' => $data, 'open_count' => $dataOpen, 'close_count' => $dataClose, 'finish_count' => $dataFinish]);
        } elseif ($request->req == 'info_penghapusan') {

            $data = AsetPenghapusan::with('jenisTransaksi', 'satker')->where('id', $request->id)->first();

            return response()->json(['models' => $data]);
        } elseif ($request->req == 'list_penghapusan') {

            $data = AssetProfile::with('jenisPerolehan')->where('penghapusan_id', $request->id)->get();

            return response()->json(['models' => $data]);
        } elseif ($request->req == 'pilih_aset') {

            $data = AssetProfile::with('jenisPerolehan')
                ->whereNull('penghapusan_id')
                ->where('satker_id', $request->satker_id)
                ->where(function ($x) use ($request) {
                    if ($request->jenis_transaksi_id === '301' || $request->jenis_transaksi_id === '303') {
                        $x->where('henti_guna', 0);
                    } else {
                        $x->where('henti_guna', 1);
                    }
                })
                ->where(function ($q) use ($request) {
                    if ($request->search)
                        $q->where('deskripsi', 'like', "%{$request->search}%");
                    $q->orWhere('kategori_nama', 'like', "%{$request->search}%");
                })
                ->get();

            return response()->json(['models' => $data]);
        } elseif ($request->req == 'document_detail') {

            $data = AsetPenghapusan::select('id', 'tgl_surat', 'no_surat')->where('id', $request->id)->first();

            return response()->json(['models' => $data]);
        }
         elseif ($request->req == 'table_list_files') {
            $directoryPath = storage_path('app/documents/' . $request->code . '/');

            if (is_dir($directoryPath)) {

                $files = array_diff(scandir($directoryPath), array('.', '..'));

                $data = [];
                foreach ($files as $idx => $f) {
                    $data[] = [
                        'no' => $idx - 1,
                        'code' => $request->code,
                        'name' => $f,
                    ];
                }

                return response()->json($data);
            } else {
                return response()->json(['message' => 'Direktori tidak ditemukan'], 404);
            }
        } elseif ($request->req == 'preview_file') {

            $extension = strtolower(pathinfo($request->filename, PATHINFO_EXTENSION));
            $path = storage_path('app/documents/' . $request->code . '/' . $request->filename);

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
        }
    }

    public function write(Request $request)
    {
        if ($this->dynamic_config('INPUT-PENGHAPUSAN') !== '1') {
            return response()->json([
                'message' => $this->dynamic_config('INPUT-PENGHAPUSAN', 'message') ?? 'Tidak bisa menginput penghapusan, silahkan hubungi Pihak Biro Aset.',
            ], 422);
        }

        if ($request->req == 'write') {
            $this->validate($request, [
                'satker_id' => 'required',
                'jenis_transaksi_id' => 'required'
            ]);

            $data = AsetPenghapusan::find($request->id);

            if (!$data) {
                $data = new AsetPenghapusan();
                $data->kode = CodeRegister::next('aset_keluar');
                $data->created_at = date('Y-m-d H:i:s');
                $etc = (object)[];
            } else {
                $etc = $data->etc;
            }

            if ($data->status == 'CLOSE') {
                return response()->json('Penghapusan Terkunci!', 403);
            }

            $data->status = 'OPEN';
            $data->jenis_transaksi_id = $request->jenis_transaksi_id;
            $data->satker_id = $request->satker_id;
            $data->tgl_surat = $request->tgl_surat;
            $data->no_surat = $request->no_surat;
            $data->editor_id = request()->user()->id;
            $data->notes = $request->notes;
            $data->updated_at = date('Y-m-d H:i:s');
            $data->save();

            if ($data->save())
                CodeRegister::update_number('aset_keluar');

            return response()->json(['kode' => $data->kode]);
        } elseif ($request->req == 'tambah_list_penghapusan') {

            $selectedItems = $request->input('id');

            foreach ($selectedItems as $item) {
                $data = AssetProfile::find($item);
                if ($data) {
                    $data->penghapusan_id = $request->penghapusan_id;
                    $data->editor_id = request()->user()->id;
                    $data->save();
                }
            }

            return response()->json(true);
        } elseif ($request->req == 'authorize') {

            $data = AsetPenghapusan::find($request->id);

            if (!$data) {
                return response()->json('Data Not Found', 403);
            }

            $lock = $data->status == 'OPEN';
            $data->editor_id = request()->user()->id;
            $data->status = $lock ? 'CLOSE' : 'OPEN';
            $data->save();

            return response()->json(true);
        } elseif ($request->req == 'write_dokumen') {

            $data = AsetPenghapusan::find($request->id);

            if ($data->tgl_surat != $request->tgl_surat || $data->no_surat != $request->no_surat) {
                $data->tgl_surat = $request->tgl_surat;
                $data->no_surat = $request->no_surat;

                $data->save();
                return response()->json($data->save());
            } else {
                return response()->json('Tidak ada perubahan data', 403);
            }
        } elseif ($request->req == 'submit') {

            $data = AsetPenghapusan::find($request->id);

            if(!$data){
                return response()->json('Data Not Found', 403);
            }

            $AssetProfile = AssetProfile::where('penghapusan_id', $data->id)->get();

            if (!$AssetProfile->count() > 0) {
                return response()->json('List Penghapusan Kosong!', 403);
            }

            if (empty($data->tgl_surat)) {
                return response()->json('Tanggal Dokumen Penghapusan Belum Diisi!', 403);
            }

            if (empty($data->no_surat)) {
                return response()->json('Nomor Dokumen Penghapusan Belum Diisi!', 403);
            }

            $directoryPath = storage_path('app/documents/' . $data->kode . '/');

            if (!is_dir($directoryPath)) {
                return response()->json('Dokumen Belum di Upload!', 403);
            } else {
                $files = array_diff(scandir($directoryPath), array('.', '..'));

                if (empty($files)) {
                    return response()->json('Dokumen Belum di Upload!', 403);
                }
            }
            
            DB::transaction(function () use ($data, $AssetProfile) {
                $penyusutan = [];
                $transaksi = [];
                $date = new DateTimeImmutable(now());

                foreach ($AssetProfile as $profil) {
                    // SUM TRANSAKSI
                    $sum_transaksi = AssetTransaction::where('profil_id', $profil->id)->sum('nilai');

                    // SUM PENYUSUTAN
                    $sum_penyusutan = AsetPenyusutan::where('profil_id', $profil->id)->sum('nilai');

                    // UBAH DI PROFIL
                    $profil->tgl_penghapusan = now();
                    $profil->jenis_penghapusan_id = $data->jenis_transaksi_id;
                    $profil->save();

                    //TAMBAH DATA TRANSAKSI
                    $transaksi[] = [
                        'editor_id' => request()->user()->id,
                        'profil_id' => $profil->id,
                        'satker_id' => $data->satker_id,
                        'satker_nama' => $data->satker->nama,
                        'jenis_transaksi_id' => $data->jenis_transaksi_id,
                        'jenis_transaksi_nama' => $data->jenisTransaksi->uraian,
                        'kategori_id' => $profil->kategori_id,
                        'kategori_nama' => $profil->kategori_nama,
                        'nup' => $profil->nup,
                        'kondisi' => $profil->kondisi,
                        'kuantitas' => 1,
                        'nilai' => -abs($sum_transaksi),
                        'reference_id' => $data->id,
                        'reference_type' => 'ASET-PENGHAPUSAN',
                        'komptabel' => 'Intra',
                        'created_at' => now(),
                        'updated_at' => now()
                    ];

                    //TAMBAH DATA PENYUSUTAN
                    $penyusutan[] = [
                        'editor_id' => request()->user()->id,
                        'profil_id' => $profil->id,
                        'satker_id' => $data->satker_id,
                        'jenis_transaksi_id' => 'S03',
                        'kategori_id' => $profil->kategori_id,
                        'kategori_nama' => $profil->kategori_nama,
                        'nup' => $profil->nup,
                        'nilai' => -abs($sum_penyusutan),
                        'komptabel' => 'Intra',
                        'tahun' => (int)$date->format('Y'),
                        'bulan' => (int)$date->format('m'),
                        'tgl_penyusutan' => $date->add(new DateInterval('PT2S')),
                    ];
                }
                
                // INSERT TRANSAKSI
                AssetTransaction::insert($transaksi);

                // INSERT PENYUSUTAN
                AsetPenyusutan::insert($penyusutan);
                
                // UBAH DI ASET PENGHAPUSAN
                $data->status = 'FINISH';
                $data->tgl_penghapusan = now();
                $data->save();
            });

            return response()->json(true);
        } elseif ($request->req == 'delete') {

            $AssetProfile = AssetProfile::where('penghapusan_id', $request->id)->count();

            if ($AssetProfile > 0) {
                return response()->json('List Penghapusan Tidak Kosong!', 403);
            }

            $data = AsetPenghapusan::find($request->id);

            if ($data->status == 'CLOSE' || $data->status == 'FINISH') {
                return response()->json('Penghapusan Terkunci!', 403);
            }

            return response()->json($data->delete());
        } elseif ($request->req == 'delete_list') {
            $data = AssetProfile::find($request->id);

            $data->penghapusan_id = null;
            $data->save();

            return response()->json(true);
        } elseif ($request->req == 'file_upload') {
            $this->validate($request, [
                'file' => 'required|file|mimes:jpg,jpeg,png,pdf'
            ]);

            $file = $request->file('file');
            $dirname = storage_path('app/documents/' . $request->code . '/');
            $filename = $file->getClientOriginalName();
            $fileinfo = pathinfo($dirname . $filename);

            $x = 1;
            while (file_exists($dirname . $filename)) {
                $filename = $fileinfo['filename'] . " ($x)." . $fileinfo['extension'];
                $x++;
            }

            $file->move($dirname, $filename);

            return response()->json(true);
        } elseif ($request->req == 'delete_file') {
            $filename = $request->filename;
            $dirname = storage_path('app/documents/' . $request->code . '/');

            $filepath = $dirname . $filename;

            if (file_exists($filepath)) {
                unlink($filepath);
                return response()->json(true);
            } else {
                return response()->json('File Tidak Ditemukan!', 403);
            }
        }
    }
}
