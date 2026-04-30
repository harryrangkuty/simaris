<?php

namespace App\Http\Controllers\Iis;

use App\Http\Controllers\AuthController;
use App\Models\Iis\IisAlkes;
use App\Models\Iis\IisCategory;
use App\Models\Iis\IisMaintenance;
use App\Models\Iis\IisQrCodeHandover;
use App\Models\MasterData\AssetSequence;
use App\Models\MasterData\Branch;
use App\Models\MasterData\Building;
use App\Models\MasterData\Room;
use App\Models\MasterData\Unit;
use App\Models\MasterData\Warehouse;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class IisAlkesController extends AuthController
{
    public function __invoke(Request $request)
    {
        if ($request->req === 'open') {

            $constant = [
                'BUILDINGS' => Building::select('id', 'branch_id', 'name', 'floors_count')->get(),
                'UNITS' => Unit::select('id', 'name', 'department')->get(),
            ];

            if ($request->has('xid')) {
                if ($request->user()->hasRole('superadmin')) {
                    $data = IisAlkes::where('id', $request->xid)
                        ->first();
                } else {
                    abort(403);
                }
            } else {
                try {
                    $barcodeNo = Crypt::decryptString($request->code);
                } catch (DecryptException $e) {
                    abort(403, 'Kode tidak valid');
                }

                $data = IisAlkes::with(['branch:id,name', 'lastPrintBy:id,identifier,name', 'bUser:id,identifier,name', 'item:code,name'])
                    ->select('id', 'branch_id', 'qr_code_no', 'item_no', 'description', 'item_no', 'item_no_legacy', 'pj_nik', 'print_count', 'last_print_at', 'last_print_by', 'is_handed_over')
                    ->where('qr_code_no', $barcodeNo)
                    ->firstOrFail();

                $handover = IisQrCodeHandover::whereIn('status', ['draft', 'submitted', 'approved', 'verified'])
                    ->whereHas('items', function ($q) use ($data) {
                        $q->where('qr_code_no', $data->qr_code_no);
                    })
                    ->select('id', 'code', 'status')
                    ->first();

                $data->is_handover_active = (bool) $handover;
                $data->handover_code = $handover?->code;
                $data->handover_status = $handover?->status;
            }

            if (! $data) {
                abort(404, 'Data tidak ditemukan.');
            }

            $title = 'Data ALKES '.$data->qr_code_no;
            $vue = "<iis-alkes-detail-page :title='".json_encode($title)."' :constant='".json_encode($constant)."' :parent='".json_encode($data)."' />";
        } else {
            $constant = [
                'BRANCHES' => Branch::select('id', 'code', 'name')->get(),
                'BUILDINGS' => Building::select('id', 'branch_id', 'name', 'floors_count')->get(),
                'UNITS' => Unit::select('id', 'name', 'department')->get(),
                'CATEGORIES' => IisCategory::all(),
                'WAREHOUSES' => Warehouse::canReceive()
                    ->with(['personInCharge:id,identifier,name,position', 'branch:id,name'])
                    ->select('id', 'branch_id', 'code', 'name', 'can_receive', 'person_in_charge_id')
                    ->get(),
            ];

            $title = 'Daftar ALKES IIS';
            $vue = "<iis-alkes-page :title='".json_encode($title)."' :constant='".json_encode($constant)."' />";
        }

        return response()->view('layouts.antd', compact('vue', 'title'));
    }

    public function read(Request $request)
    {
        if ($request->req == 'table') {

            $data = IisAlkes::with(['bUser', 'lastPrintBy', 'unit', 'room', 'item', 'bItemIis', 'building'])
                ->where(function ($q) use ($request) {
                    if ($request->branch_id) {
                        $q->where('branch_id', $request->branch_id);
                    }
                })
                ->where(function ($q) use ($request) {
                    if ($request->search) {
                        $q->where('description', 'like', "%{$request->search}%")
                            ->orWhere('qr_code_no', 'like', "%{$request->search}%");
                    }
                })
                ->where(function ($q) use ($request) {
                    if ($request->status == 'active') {
                        $q->where('is_deactivated', false);
                    } else {
                        $q->where('is_deactivated', true);
                    }
                })
                ->where(function ($q) use ($request) {
                    if ($request->unit) {
                        $q->whereHas('unit', function ($uq) use ($request) {
                            $uq->where('name', 'like', "%{$request->unit}%");
                        });
                    }
                })
                ->where(function ($q) use ($request) {
                    if ($request->user) {
                        $q->where('pj_nik', $request->user);
                    }
                })
                ->where(function ($q) use ($request) {
                    if ($request->data_source) {
                        $q->where('data_source', $request->data_source);
                    }
                })
                ->where(function ($q) use ($request) {
                    if ($request->handed_over == 'n') {
                        $q->where('is_handed_over', false);
                    } elseif (($request->handed_over == 'y')) {
                        $q->where('is_handed_over', true);
                    }
                })
                ->where(function ($q) use ($request) {
                    if ($request->printed === 'n') {
                        $q->where('print_count', 0);
                    } elseif ($request->printed === 'y') {
                        $q->where('print_count', '>', 0);
                    }
                })
                ->when($request->has('distributed'), function ($q) use ($request) {
                    if ((int) $request->distributed === 1) {
                        $q->whereNull('warehouse_id');
                    } else {
                        $q->whereNotNull('warehouse_id');
                    }
                })
                ->paginate($this->per_page());

            $data = $data->through(function ($item) {
                $item->encrypt_code = Crypt::encryptString($item->qr_code_no);

                return $item;
            });

            return response()->json(['models' => $data]);
        } elseif ($request->req == 'info_alkes') {
            $data = IisAlkes::with(['bUser', 'unit', 'room', 'building', 'creator:id,identifier,name', 'updater:id,identifier,name'])->findOrFail($request->id);

            return response()->json(['models' => $data]);
        } elseif ($request->req === 'single_label_preview') {

            $data = IisAlkes::findOrFail($request->id);

            // Barcode tanpa logo
            $qrBase64 = $this->makeQrBase64(
                $data->qr_code_no,
            );

            $views = [
                'standard' => 'print.iis.iis_alkes_label_single_standard',
                'mini' => 'print.iis.iis_alkes_label_single_mini',
            ];

            if (! isset($views[$request->mode])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mode tidak valid.',
                ]);
            }

            $view = $views[$request->mode];

            if ($request->tipe_kertas === 'stiker') {
                $view .= '_stiker';
            }

            $pdf = \PDF::loadView($view, compact('data', 'qrBase64'));

            if ($request->tipe_kertas === 'stiker' && $request->mode === 'mini') {
                $pdf->setPaper([0, 0, 141.73, 85.04]); // 50mm x 30mm
            }

            $barcode_safe = str_replace(['/', '\\'], '-', $data->qr_code_no);
            $filename = 'Label_Aset_'.preg_replace('/\s+/', '_', $barcode_safe).'.pdf';

            return $pdf->stream($filename);
        } elseif ($request->req === 'single_label_print') {
            $user = Auth::user();
            $data = IisAlkes::findOrFail($request->id);

            if ($data->print_count >= 2 && ! $user->can('iis.alkes.print.unlimited')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Label sudah pernah dicetak maksimal 2 kali. Harap hubungi IT!!',
                ], 403);
            }

            $data->increment('print_count');
            $data->update([
                'last_print_at' => now(),
                'last_print_by' => auth()->user()->id ?? null,
            ]);

            return response()->json([
                'success' => true,
                'print_count' => $data->print_count,
            ]);
        } elseif ($request->req === 'multi_label_preview') {

            $ids = explode(',', $request->ids);

            $data = IisAlkes::whereIn('qr_code_no', $ids)->get();

            if ($data->isEmpty()) {
                abort(404, 'Data tidak ditemukan');
            }

            $logoPath = public_path('images/logo-rsbt.png');

            $items = $data->map(function ($item) {

                return [
                    'data' => $item,
                    // Barcode tanpa logo
                    'qrBase64' => $this->makeQrBase64($item->qr_code_no),
                ];
            });

            // ===============================
            // VIEW MAP
            // ===============================
            $views = [
                'standard' => 'print.iis.iis_alkes_label_multi_standard',
                'mini' => 'print.iis.iis_alkes_label_multi_mini',
            ];

            if (! isset($views[$request->mode])) {
                abort(400, 'Mode tidak valid');
            }

            $view = $views[$request->mode];

            if ($request->tipe_kertas === 'stiker') {
                $view .= '_stiker';
            }

            $pdf = \PDF::loadView($view, [
                'items' => $items,
            ]);

            if ($request->tipe_kertas === 'stiker' && $request->mode === 'mini') {
                $pdf->setPaper([0, 0, 141.73, 85.04]); // 50mm x 30mm
            }

            return $pdf->stream('Label_Aset_Multi.pdf');
        } elseif ($request->req === 'multi_label_print') {

            $user = Auth::user();

            $ids = explode(',', $request->ids);

            $items = IisAlkes::whereIn('qr_code_no', $ids)->get();

            if ($items->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data tidak ditemukan',
                ], 404);
            }

            // ===============================
            // VALIDASI LIMIT CETAK
            // ===============================
            foreach ($items as $item) {
                if ($item->print_count >= 2 && ! $user->can('iis.alkes.print.unlimited')) {
                    return response()->json([
                        'success' => false,
                        'message' => "Label {$item->qr_code_no} sudah dicetak maksimal 2 kali. Harap hubungi IT!!",
                    ], 403);
                }
            }

            // ===============================
            // UPDATE MASSAL
            // ===============================
            foreach ($items as $item) {
                $item->increment('print_count');
                $item->update([
                    'last_print_at' => now(),
                    'last_print_by' => $user->id ?? null,
                ]);
            }

            return response()->json([
                'success' => true,
                'total_printed' => count($items),
            ]);
        } elseif ($request->req === 'log_maintenance') {

            $maintenances = IisMaintenance::with(['operator:id,name,position', 'supplier:id,gl_code,name'])
                ->where('qr_code_no', $request->qr_code_no)
                ->orderBy('maintenance_date', 'desc')
                ->where(function ($q) use ($request) {
                    if ($request->maintenance_type) {
                        $q->where('maintenance_type', $request->maintenance_type);
                    }
                })
                ->paginate($this->per_page());

            return response()->json(['models' => $maintenances]);
        }
    }

    public function write(Request $request)
    {
        if ($request->req === 'write') {

            // ================= VALIDATION =================
            $this->validate($request, [
                'item_code' => 'required|string',
                'description' => 'required|string',
                'category_name' => 'required|string',
                'building_id' => 'nullable|required_unless:location_type,warehouse|integer',
                'floor' => 'nullable|required_unless:location_type,warehouse|string',
                'unit_id' => 'required|integer',
                'pj_nik' => 'nullable|required_unless:location_type,warehouse|string',
                'condition' => 'required|string',
                'purchase_year' => 'required|string',
                'po_number' => 'nullable|required_if:purchase_year,'.now()->year.'|string',
                'received_number' => 'nullable|required_if:purchase_year,'.now()->year.'|string',
                'unit_price' => 'nullable|required_if:purchase_year,'.now()->year.'|numeric',
            ], [
                'item_code.required' => 'Item wajib dipilih.',
                'description.required' => 'Deskripsi wajib diisi.',
                'category_name.required' => 'Kategori wajib dipilih.',
                'building_id.required_unless' => 'Gedung wajib dipilih.',
                'floor.required_unless' => 'Lantai wajib diisi.',
                'unit_id.required' => 'Unit wajib dipilih.',
                'pj_nik.required' => 'Penanggung Jawab (PJ) wajib dipilih.',
                'condition.required' => 'Kondisi barang wajib dipilih.',
                'po_number.required' => 'PO Number wajib diisi.',
                'received_number.required' => 'Received Number wajib diisi.',
                'unit_price.required' => 'Harga Satuan wajib diisi.',
                'purchase_year.required' => 'Tahun pembelian wajib diisi.',
            ]);

            $data = IisAlkes::find($request->id);

            // =====================================================
            // ================= CREATE NEW DATA ===================
            // =====================================================
            if (! $data) {

                DB::transaction(function () use ($request, &$data) {

                    $data = new IisAlkes;

                    // Default etc
                    $data->etc = [
                        ['key' => 'Serial Number', 'value' => null],
                        ['key' => 'Merek', 'value' => null],
                    ];

                    $prefix = $request->item_code;

                    if (! $prefix) {
                        abort(422, 'Prefix tidak ditemukan dari item_code.');
                    }

                    $sequence = AssetSequence::where('prefix', $prefix)
                        ->lockForUpdate()
                        ->first();

                    if (! $sequence) {
                        $sequence = AssetSequence::create([
                            'prefix' => $prefix,
                            'last_number' => 0,
                        ]);
                    }

                    $sequence->last_number += 1;
                    $sequence->save();

                    $number = str_pad($sequence->last_number, 4, '0', STR_PAD_LEFT);

                    $data->qr_code_no = "{$request->item_code}-{$number}";

                    // ================= FIELD ASSIGN =================
                    $data->branch_id = $request->branch_id;
                    $data->item_no = $request->item_code;
                    $data->description = $request->description;
                    $data->building_id = $request->building_id;
                    $data->floor = $request->floor;
                    $data->unit_id = $request->unit_id;
                    $data->room_id = $request->room_id;
                    $data->pj_nik = $request->pj_nik;
                    $data->condition = $request->condition;
                    $data->po_number = $request->po_number;
                    $data->category_name = $request->category_name;
                    $data->received_number = $request->received_number;
                    $data->unit_price = $request->unit_price;
                    $data->purchase_year = $request->purchase_year;
                    $data->notes = $request->notes;
                    $data->warehouse_id = $request->warehouse_id;
                    $data->etc = $request->etc ?? $data->etc;

                    $data->save();
                });

            } else {

                // =====================================================
                // ================= UPDATE EXISTING ===================
                // =====================================================

                $data->branch_id = $request->branch_id;
                $data->item_no = $request->item_code;
                $data->description = $request->description;
                $data->building_id = $request->building_id;
                $data->floor = $request->floor;
                $data->unit_id = $request->unit_id;
                $data->room_id = $request->room_id;
                $data->pj_nik = $request->pj_nik;
                $data->condition = $request->condition;
                $data->po_number = $request->po_number;
                $data->received_number = $request->received_number;
                $data->unit_price = $request->unit_price;
                $data->purchase_year = $request->purchase_year;
                $data->notes = $request->notes;
                $data->warehouse_id = $request->warehouse_id;
                $data->etc = $request->etc ?? $data->etc;

                $data->save();
            }

            return response()->json([
                'success' => true,
                'id' => $data->id,
            ]);
        } elseif ($request->req === 'write_detail') {

            $request->validate([
                'id' => 'required|integer',
                'field' => 'required|string',
                'value' => 'nullable',
            ]);

            $data = IisAlkes::find($request->id);
            if (! $data) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data tidak ditemukan',
                ], 404);
            }

            $data->{$request->field} = $request->value;
            $data->save();

            return response()->json([
                'success' => true,
            ]);
        } elseif ($request->req === 'bulk_action') {

            $request->validate([
                'ids' => 'required|array|min:1',
                'action' => 'required|string|in:change_condition,change_pj,change_location,toggle_status',
            ]);

            $query = IisAlkes::whereIn('qr_code_no', $request->ids);

            DB::transaction(function () use ($request, $query) {

                switch ($request->action) {

                    case 'toggle_status':

                        $request->validate([
                            'is_active' => 'required|boolean',
                            'is_deactivated_notes' => 'required_if:is_active,0|nullable|string',
                        ]);

                        $query->update([
                            'is_deactivated' => $request->is_active ? 0 : 1,
                            'is_deactivated_notes' => $request->is_active
                                ? null
                                : $request->is_deactivated_notes,
                        ]);

                        break;

                    case 'change_pj':

                        $request->validate([
                            'pj_nik' => 'required|string',
                        ]);

                        // ============================
                        // AMBIL DATA INVENTORY
                        // ============================
                        $items = IisInventory::whereIn('qr_code_no', $request->ids)->get();

                        // ============================
                        // VALIDASI: SUDAH HANDOVER
                        // ============================
                        $alreadyHandover = $items
                            ->where('is_handed_over', true)
                            ->pluck('qr_code_no')
                            ->values();

                        if ($alreadyHandover->isNotEmpty()) {
                            throw ValidationException::withMessages([
                                'ids' => 'Asset berikut sudah diserahterimakan, tidak bisa ubah PJ: '
                                    .$alreadyHandover->join(', '),
                            ]);
                        }

                        // ============================
                        // VALIDASI: ADA HANDOVER AKTIF
                        // ============================
                        $activeItems = IisQrCodeHandover::whereIn('status', ['draft', 'submitted', 'approved'])
                            ->whereHas('items', function ($q) use ($request) {
                                $q->whereIn('qr_code_no', $request->ids);
                            })
                            ->with(['items' => function ($q) use ($request) {
                                $q->whereIn('qr_code_no', $request->ids);
                            }])
                            ->get()
                            ->flatMap(fn ($h) => $h->items)
                            ->pluck('qr_code_no')
                            ->unique()
                            ->values();

                        if ($activeItems->isNotEmpty()) {
                            throw ValidationException::withMessages([
                                'ids' => 'Asset berikut sedang dalam proses serah terima: '
                                    .$activeItems->join(', '),
                            ]);
                        }

                        // ============================
                        // UPDATE
                        // ============================

                        $query->update([
                            'pj_nik' => $request->pj_nik,
                        ]);

                        break;

                    case 'change_location':

                        $request->validate([
                            'branch_id' => 'required|integer',
                            'building_id' => 'required|integer',
                            'floor' => 'required|string',
                            'unit_id' => 'nullable|integer',
                            'room_id' => 'nullable|integer',
                        ]);

                        $query->update([
                            'branch_id' => $request->branch_id,
                            'building_id' => $request->building_id,
                            'floor' => $request->floor,
                            'unit_id' => $request->unit_id,
                            'room_id' => $request->room_id,
                        ]);

                        break;

                    case 'change_condition':

                        $request->validate([
                            'condition' => 'required|string',
                        ]);

                        $query->update([
                            'condition' => $request->condition,
                        ]);

                        break;
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Bulk action berhasil dilakukan',
            ]);
        } elseif ($request->req === 'write_detail_location') {

            $request->validate([
                'id' => 'required|integer',
                'field' => 'required|string|in:building_id,floor,room_id,unit_id',
                'value' => 'nullable',
            ]);

            $data = IisAlkes::findOrFail($request->id);

            switch ($request->field) {

                case 'building_id':
                    $data->building_id = $request->value;
                    $data->floor = null;
                    $data->room_id = null;
                    break;

                case 'floor':
                    $data->floor = $request->value;
                    $data->room_id = null;
                    break;

                case 'room_id':
                    $room = Room::find($request->value);

                    if (! $room) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Ruangan tidak valid',
                        ], 422);
                    }

                    if (
                        $room->building_id != $data->building_id ||
                        strtolower($room->floor) != strtolower($data->floor)
                    ) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Ruangan tidak sesuai gedung/lantai',
                        ], 422);
                    }

                    $data->room_id = $room->id;
                    break;

                case 'unit_id':
                    $data->unit_id = $request->value;
                    break;
            }

            $data->save();

            return response()->json(['success' => true]);
        } elseif ($request->req === 'write_single_maintenance') {

            // ===== VALIDATION =====
            $rules = [
                'maintenance_type' => 'required|in:internal,external',
                'maintenance_date' => 'required|date',
                'completed_date' => 'required|date|after_or_equal:maintenance_date',
                'description' => 'required|string',
            ];

            if ($request->maintenance_type === 'external') {
                $rules['service_code'] = 'required|string|max:100';
                $rules['supplier_id'] = 'required';
            }

            $messages = [
                'maintenance_type.required' => 'Jenis pemeliharaan wajib dipilih.',
                'maintenance_type.in' => 'Jenis pemeliharaan tidak valid.',
                'maintenance_date.required' => 'Tanggal pemeliharaan wajib diisi.',
                'completed_date.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal pemeliharaan.',
                'description.required' => 'Deskripsi pemeliharaan wajib diisi.',
                'service_code.required' => 'Kode service wajib diisi untuk pemeliharaan eksternal.',
                'supplier_id.required' => 'Supplier wajib diisi untuk pemeliharaan eksternal.',
            ];

            $this->validate($request, $rules, $messages);

            $data = IisMaintenance::find($request->id) ?? new IisMaintenance;

            $data->qr_code_no = $request->qr_code_no;
            $data->object_type = 'inventory';
            $data->maintenance_type = $request->maintenance_type;
            $data->maintenance_date = $request->maintenance_date;
            $data->completed_date = $request->completed_date;
            $data->description = $request->description;

            // External only
            $data->service_code = $request->maintenance_type === 'external'
                ? $request->service_code
                : null;

            $data->supplier_id = $request->maintenance_type === 'external'
                ? $request->supplier_id
                : null;

            $data->operator_id = $this->user()->id;

            $data->save();

            // ATTACHMENTS (multiple files)
            if ($request->hasFile('attachments')) {
                $attachmentFiles = [];
                foreach ($request->file('attachments') as $f) {
                    $path = $data->storeFile($f, 'maintenances/'.$data->id, 'public');
                    $attachmentFiles[] = $path;
                }
                $data->attachments = $attachmentFiles;
            }

            $data->save();

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } elseif ($request->req == 'delete') {
            $data = IisAlkes::find($request->id);

            return response()->json($data->delete());
        }
    }
}
