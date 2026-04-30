<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Procurement\Procurement;
use App\Models\Procurement\ProcurementDetail;
use App\Models\Asset\AssetProfile;
use App\Models\Asset\AssetTransaction;
use App\Models\MasterData\TransactionType;
use App\Models\MasterData\Branch;
use App\Models\CodeRegister;
use App\Models\Aset\AsetPenyusutan;
use DateInterval;
use DB;
use PDF;
use DateTimeImmutable;

class ProcurementController extends AuthController
{
    public function __invoke(Request $request)
    {
        if ($request->req == 'open') {

            Procurement::removeAllAppends();

            $data = Procurement::with(['branch:id,name', 'supplier:id,gl_code,name,is_ppn'])->select('id', 'code', 'status', 'branch_id', 'supplier_id')
                ->where('code', $request->code)
                ->first();

            $title = 'Detail Pengadaan Barang';
            $vue = "<procurement-detail-page :title='" . json_encode($title) . "' :parent='" . json_encode($data) . "' />";
        } else {
            $constant = [
                'TRANSACTION_TYPE_OPTIONS' => TransactionType::asetAcquisitions()->get(),
                'BRANCH_RECEIVE_OPTIONS' => Branch::all(),
            ];

            $title = 'Pengadaan Barang';
            $vue = "<procurement-page :title='" . json_encode($title) . "' :constant='" . json_encode($constant) . "' />";
        }

        return response()->view('layouts.antd', compact('vue', 'title'));
    }

    public function read(Request $request)
    {
        if ($request->req == 'table') {
            $baseQuery = Procurement::query()
                ->whereDateBetween('created_at', $request->date_start, $request->date_end)
                ->with(['transactionType:code,name', 'supplier:id,gl_code,name', 'branch:id,code,name'])
                ->when($request->status, fn($q) => $q->where('status', $request->status))
                ->when($request->branch_id, fn($q) => $q->where('branch_id', $request->branch_id))
                ->where(function ($q) use ($request) {
                    $q->where('code', 'like', "%{$request->search}%");
                });

            $data = (clone $baseQuery)
                ->withCount('detail')
                ->withSum('detail', 'quantity')
                ->paginate($this->per_page());

            $statusCounts = (clone $baseQuery)
                ->select('status', DB::raw('COUNT(*) as total_count'))
                ->groupBy('status')
                ->pluck('total_count', 'status');

            return response()->json([
                'models' => $data,
                'counts' => $statusCounts,
            ]);
        } elseif ($request->req == 'procurement_info') {
            $data = Procurement::with('TransactionType')->where('id', $request->id)->first();

            return response()->json(['models' => $data]);
        } elseif ($request->req == 'procurement_detail_list') {
            $query = ProcurementDetail::with('item')
                ->where('procurement_id', $request->id);

            $total = $query->get()->sum('sub_total');

            $data = $query->paginate($this->per_page());

            return response()->json([
                'models' => $data,
                'total' => $total,
            ]);
        } elseif ($request->req == 'document_detail') {
            Procurement::removeAllAppends();

            $data = Procurement::select('id', 'letter_date', 'letter_number')->where('id', $request->id)->first();

            return response()->json(['models' => $data]);
        } elseif ($request->req == 'table_list_files') {
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
        if ($this->dynamic_config('input_procurement') !== '1') {
            return response()->json([
                'message' => $this->dynamic_config('input_procurement', 'message') ?? 'Masa Penginputan aset telah berakhir.',
            ], 422);
        }

        if ($request->req == 'add_procurement') {
            $this->validate(
                $request,
                [
                    'branch_id' => 'required',
                    'transaction_type_code' => 'required',
                    'funding_source' => 'required_if:transaction_type_code,M01',
                ],
                [
                    'branch_id.required' => 'Branch wajib dipilih.',
                    'transaction_type_code.required' => 'Tanggal Perolehan wajib diisi.',
                ]
            );

            $data = Procurement::find($request->id);
            $isNew = false;

            if (!$data) {
                $data = new Procurement();
                $data->code = CodeRegister::next('procurement');

                $etc = (object)[];
                $isNew = true;
            } else {
                $etc = $data->etc;
            }

            if ($data->status == 'submitted') {
                return response()->json('Perolehan Telah Disubmit!', 403);
            }

            if ($data->status == 'approved') {
                return response()->json('Perolehan Telah Disetujui!', 403);
            }

            if ($data->status == 'registered') {
                return response()->json('Perolehan Telah Diregisterasi!', 403);
            }

            if ($request->created_at) {
                $data->created_at = $request->created_at;
            }

            $data->branch_id = $request->branch_id;
            $data->transaction_type_code = $request->transaction_type_code;
            $data->funding_source = $request->funding_source;
            $data->supplier_id = $request->supplier_id;
            $data->purchasing_officer_id = request()->user()->id;
            $data->notes = $request->notes;
            $data->updated_at = date('Y-m-d H:i:s');
            $data->etc = $etc;

            if ($data->save() && $isNew) {
                CodeRegister::update_number('procurement');
            }

            return response()->json(['code' => $data->code]);
        } elseif ($request->req == 'add_procurement_list') {
            // Ambil parent procurement
            $parent = Procurement::select('status')->find($request->procurement_id);

            if (!$parent) {
                return response()->json('Procurement tidak ditemukan!', 404);
            }

            if ($parent->status == 'validated') {
                return response()->json('Perolehan telah divalidasi!', 403);
            }

            if ($parent->status == 'registered') {
                return response()->json('Perolehan telah diregistrasi!', 403);
            }

            // Validasi request
            $rules = [
                'item_code'        => 'required',
                'procurement_id'   => 'required|integer',
                'description'      => 'required|string',
                'item_type'  => 'required|integer',
                'physical_condition'  => 'required|integer',
                'quantity'         => 'required|numeric|min:1',
                'unit_price'       => 'required|numeric|min:0',
                'discount_value'   => 'nullable|numeric|min:0',
                'shipping_value'   => 'nullable|numeric|min:0',
                'commission_value' => 'nullable|numeric|min:0',
                'ppn_value'        => 'nullable|numeric|min:0',
                'sub_total'        => 'required|numeric|min:0',
            ];

            $this->validate($request, $rules);

            // Cari data detail atau buat baru
            $data = ProcurementDetail::find($request->id) ?? new ProcurementDetail();

            // Set nilai
            $data->procurement_id   = $request->procurement_id;
            $data->item_code          = $request->item_code;
            $data->description      = $request->description;
            $data->item_type        = $request->item_type;
            $data->physical_condition        = $request->physical_condition;
            $data->quantity         = $request->quantity;
            $data->unit_price       = $request->unit_price;
            $data->discount_value   = $request->discount_value ?? 0;
            $data->shipping_value   = $request->shipping_value ?? 0;
            $data->commission_value = $request->commission_value ?? 0;
            $data->ppn_value        = $request->ppn_value ?? 0;
            $data->sub_total        = $request->sub_total;

            $data->save();

            return response()->json($data);
        } elseif ($request->req == 'write_dokumen') {
            $data = Procurement::find($request->id);

            if ($data->status == 'CLOSE') {
                return response()->json('Perolehan Terkunci!', 403);
            }

            if ($data->status == 'FINISH') {
                return response()->json('Perolehan Telah Diregisterasi!', 403);
            }

            if ($data->letter_date != $request->letter_date || $data->letter_number != $request->letter_number) {
                $data->letter_date = $request->letter_date;
                $data->letter_number = $request->letter_number;

                $data->save();
                return response()->json($data->save());
            } else {
                return response()->json('Tidak ada perubahan data', 403);
            }
        } elseif ($request->req == 'authorize') {
            $data = Procurement::with('detail')->find($request->id);
            if (!$data)
                return response()->json('Data Not Found', 403);

            DB::transaction(function () use ($data) {
                $lock = $data->status == 'OPEN';

                $data->purchasing_officer_id = $this->user()->id;
                $data->status = $lock ? 'CLOSE' : 'OPEN';
                $data->detail->each(function ($d) use ($lock) {
                    $d->save();
                });
                $data->save();
            });

            return response()->json(true);
        } elseif ($request->req == 'delete') {
            $data = Procurement::withCount('detail')->find($request->id);

            if ($data->detail_count > 0) {
                return response()->json('List Perolehan Tidak Kosong!', 403);
            }

            if ($data->status == 'CLOSE') {
                return response()->json('Perolehan Terkunci!', 403);
            }

            if ($data->status == 'FINISH') {
                return response()->json('Perolehan Telah Diregisterasi!', 403);
            }

            return response()->json($data->delete());
        } elseif ($request->req == 'delete_detail') {
            $data = ProcurementDetail::with('perolehan')->find($request->id);

            if ($data->perolehan->status == 'CLOSE') {
                return response()->json('Perolehan Terkunci!', 403);
            }

            if ($data->status == 'FINISH') {
                return response()->json('Perolehan Telah Diregisterasi!', 403);
            }

            return response()->json($data->delete());
        } elseif ($request->req == 'register_item') {
            $data = Procurement::with('detail.kategori', 'satker', 'TransactionType')->find($request->id);

            if ($data->status == 'OPEN') {
                return response()->json('Perolehan Masih Terbuka!', 403);
            }

            if (!$data->detail->count() > 0) {
                return response()->json('List Perolehan Kosong!', 403);
            }

            if (empty($data->letter_date)) {
                return response()->json('Tanggal Dokumen Belum Diisi!', 403);
            }

            if (empty($data->letter_number)) {
                return response()->json('Nomor Dokumen Belum Diisi!', 403);
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

            DB::transaction(function () use ($data) {
                foreach ($data->detail as $d) {
                    $last_number = AssetProfile::getLastNumber($d->kategori_id);

                    $insert = [];
                    $nup = [];

                    $now = ($data->notes == 'System 2023') ? $data->created_at : now();

                    $nilai_perolehan = $d->nilai_perolehan * (100 + $d->ppn) / 100;
                    $peny = AsetPenyusutan::getPenyusutanAwal($d->kategori_id, $nilai_perolehan, $now, $data->acquisition_date);

                    for ($x = $last_number + 1; $x - $last_number <= $d->kuantitas; $x++) {

                        $jenis_kib = null;

                        if (str_starts_with((string) $d->kategori_id, '4')) {
                            $jenis_kib = 'Gedung Bangunan';
                        } elseif (str_starts_with((string) $d->kategori_id, '502')) {
                            $jenis_kib = 'Bangunan Air';
                        } elseif (str_starts_with((string) $d->kategori_id, '30101') || str_starts_with((string) $d->kategori_id, '30102')) {
                            $jenis_kib = 'Alat Besar';
                        } elseif (str_starts_with((string) $d->kategori_id, '302')) {
                            $jenis_kib = 'Alat Angkutan';
                        } elseif (str_starts_with((string) $d->kategori_id, '2')) {
                            $jenis_kib = 'Tanah';
                        } elseif (str_starts_with((string) $d->kategori_id, '309')) {
                            $jenis_kib = 'Senjata';
                        } elseif (str_starts_with((string) $d->kategori_id, '308')) {
                            $jenis_kib = 'Alat Laboratorium';
                        }

                        $insert[] = [
                            'deskripsi' => $d->deskripsi,
                            'perolehan_id' => $d->perolehan_id,
                            'jenis_perolehan_id' => $data->transaction_type_code,
                            'acquisition_date' => $data->acquisition_date,
                            'tgl_buku' => $now,
                            'satker_id' => $data->satker_id,
                            'kategori_id' => $d->kategori_id,
                            'kategori_nama' => $d->kategori->uraian,
                            'kondisi' => $d->kondisi,
                            'nilai_perolehan' => $nilai_perolehan,
                            'nilai_buku' => $nilai_perolehan - $peny->akum,
                            'nilai_penyusutan' => $peny->single,
                            'komptabel' => $d->komptabel,
                            'jenis_kib' => $jenis_kib,
                            'akumulasi_penyusutan' => $peny->akum, //Sementara
                            'masa_manfaat' => $d->masa_manfaat,
                            'purchasing_officer_id' => request()->user()->id,
                            'nup' => $x,
                            'etc' => null
                        ];

                        $nup[] = $x;
                    }

                    AssetProfile::insert($insert);

                    $profils = AssetProfile::where('kategori_id', $d->kategori_id)
                        ->whereIn('nup', $nup)
                        ->select('id', 'nup')
                        ->get();


                    $date = new DateTimeImmutable($now);

                    $penyusutan = [];
                    $transaksi = [];

                    foreach ($profils as $profil) {

                        if ($peny->akum > 0) {
                            $penyusutan[] = [
                                'purchasing_officer_id' => request()->user()->id,
                                'profil_id' => $profil->id,
                                'satker_id' => $data->satker_id,
                                'transaction_type_code' => 'S01',
                                'kategori_id' => $d->kategori_id,
                                'kategori_nama' => $d->kategori->uraian,
                                'nup' => $profil->nup,
                                'nilai' => $peny->akum,
                                'komptabel' => $d->komptabel,
                                'tahun' => (int)$date->format('Y'),
                                'bulan' => (int)$date->format('m'),
                                'tgl_penyusutan' => $date->add(new DateInterval('PT2S')),
                            ];
                        }

                        $transaksi[] = [
                            'purchasing_officer_id' => request()->user()->id,
                            'profil_id' => $profil->id,
                            'satker_id' => $data->satker_id,
                            'satker_nama' => $data->satker->nama,
                            'transaction_type_code' => $data->transaction_type_code,
                            'jenis_transaksi_nama' => $data->TransactionType->uraian,
                            'kategori_id' => $d->kategori_id,
                            'kategori_nama' => $d->kategori->uraian,
                            'nup' => $profil->nup,
                            'kondisi' => $d->kondisi,
                            'kuantitas' => 1,
                            'nilai' => $nilai_perolehan,
                            'reference_id' => $data->id,
                            'komptabel' => $d->komptabel,
                            'reference_type' => 'ASET-PEROLEHAN',
                            'jenis_kib' => $jenis_kib ?? null,
                            'created_at' => $now,
                            'updated_at' => $now
                        ];
                    }

                    if ($peny->akum > 0) {
                        AsetPenyusutan::insert($penyusutan);
                    }

                    AssetTransaction::insert($transaksi);
                }

                $data->status = 'FINISH';
                $data->save();
            });

            return response()->json($data);
        } elseif ($request->req == 'file_upload') {

            $parent = Procurement::select('status')->where('kode', $request->code)->first();

            if ($parent->status == 'CLOSE') {
                return response()->json('Perolehan Terkunci!', 403);
            }

            if ($parent->status == 'FINISH') {
                return response()->json('Perolehan Telah Diregisterasi!', 403);
            }

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

            $parent = Procurement::select('status')->where('kode', $request->code)->first();

            if ($parent->status == 'CLOSE') {
                return response()->json('Perolehan Terkunci!', 403);
            }

            if ($parent->status == 'FINISH') {
                return response()->json('Perolehan Telah Diregisterasi!', 403);
            }

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
