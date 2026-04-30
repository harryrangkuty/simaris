<?php

namespace App\Http\Controllers\Iis;

use App\Http\Controllers\AuthController;
use App\Models\Iis\IisAlkes;
use App\Models\Iis\IisInventory;
use App\Models\Iis\IisMovement;
use App\Models\Iis\IisMovementItem;
use App\Models\MasterData\Branch;
use App\Models\MasterData\Building;
use App\Models\MasterData\Unit;
use App\Models\MasterData\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class IisReturnController extends AuthController
{
    public function __invoke(Request $request)
    {
        $constant = [
            'BRANCHES' => Branch::select('id', 'code', 'name')->get(),
            'WAREHOUSES' => Warehouse::canReceive()
                ->with(['personInCharge:id,identifier,name,position', 'branch:id,name'])
                ->select('id', 'branch_id', 'code', 'name', 'can_receive', 'person_in_charge_id')
                ->get(),
            'BUILDINGS' => Building::select('id', 'branch_id', 'name', 'floors_count')->get(),
            'UNITS' => Unit::select('id', 'name', 'department')->get(),
        ];

        $title = 'Daftar Retur Barang IIS';
        $vue = "<iis-return-page :title='".json_encode($title)."' :constant='".json_encode($constant)."' />";

        return response()->view('layouts.antd', compact('vue', 'title'));
    }

    public function read(Request $request)
    {
        if ($request->req === 'table') {

            $user = auth()->user();

            $canViewAll = $user->can('iis.return.view_all');

            $canVerify  = $user->can('iis.return.verify');

            $data = IisMovement::with([
                'items',
                'operator:id,identifier,name,position',
                'verifier:id,identifier,name,position',
                'rejector:id,identifier,name,position',
                'toPj:id,identifier,name,position',
            ])
                ->where('movement_type', 'return')
                ->withCount('items')
                ->when($request->search, function ($q) use ($request) {
                    $q->where('code', 'like', "%{$request->search}%")
                        ->orWhere('notes', 'like', "%{$request->search}%");
                })
                ->when($request->status, function ($q) use ($request) {
                    $q->where('status', $request->status);
                })
                ->when(! $canViewAll, function ($q) use ($user, $canVerify) {

                    $q->where(function ($qq) use ($user, $canVerify) {
                        // USER BIASA → hanya punya sendiri
                        $qq->where('from_pj_id', $user->id);

                        // GUDANG → bisa lihat yang perlu diverifikasi
                        if ($canVerify) {
                            $qq->orWhere('status', 'submitted');
                        }
                    });

                })
                ->orderByDesc('created_at')
                ->paginate($this->per_page());

            $data->getCollection()->transform(function ($movement) {
                $movement->setRelation(
                    'items',
                    $movement->items->pluck('asset_id')
                );

                return $movement;
            });

            return response()->json([
                'models' => $data,
            ]);
        } elseif ($request->req === 'movement_items') {

            $items = IisMovementItem::where('movement_id', $request->id)
                ->select([
                    'id',
                    'asset_id',
                    'qr_code_no',
                    'description',
                    'condition',
                    'from_type',
                    'from_location',
                    'to_location',
                    'to_type',
                ])
                ->get();

            return response()->json([
                'models' => $items,
            ]);
        } elseif ($request->req === 'pdf') {

            $data = IisMovement::with([
                'items',
                'operator:id,identifier,name,position',
                'toPj:id,identifier,name,position',
            ])->findOrFail($request->id);

            $logoPath = public_path('images/logo-rsbt.png');

            // ===============================
            // QR OPERATOR
            // ===============================
            $qrOperatorPayload = json_encode([
                'type' => 'return_submission',
                'handover_id' => $data->id,
                'code' => $data->code,
                'operator' => [
                    'id' => $data->operator->id,
                    'nik' => $data->operator->identifier,
                    'name' => $data->operator->name,
                    'position' => $data->operator->position,
                ],
                'submitted_at' => \Carbon\Carbon::parse($data->created_at)
                    ->format('Y-m-d H:i:s'),
            ], JSON_UNESCAPED_UNICODE);

            $qrOperatorBase64 = $this->makeQrBase64WithLogo(
                $qrOperatorPayload,
                $logoPath
            );

            // ===============================
            // QR VERIFIKASI (PJ)
            // ===============================
            $qrVerifyPayload = json_encode([
                'type' => 'return_verification',
                'handover_id' => $data->id,
                'code' => $data->code,
                'pj' => [
                    'id' => $data->verifier?->id,
                    'nik' => $data->verifier?->identifier,
                    'name' => $data->verifier?->name,
                    'position' => $data->verifier?->position,
                ],
                'verified_at' => $data->verified_at
                    ? \Carbon\Carbon::parse($data->verified_at)->format('Y-m-d H:i:s')
                    : null,
            ], JSON_UNESCAPED_UNICODE);

            $qrVerifyBase64 = $this->makeQrBase64WithLogo(
                $qrVerifyPayload,
                $logoPath
            );

            $pdf = \PDF::loadView(
                'print.iis.serah_terima_retur',
                compact('data', 'qrOperatorBase64', 'qrVerifyBase64')
            );

            return $pdf->stream('Serah_Terima_'.$data->code.'.pdf');
        } elseif ($request->req === 'movement_detail') {

            $movement = IisMovement::with([
                'items',
                'approvals.user:id,identifier,name',
                'operator:id,identifier,name,position',
            ])->findOrFail($request->id);

            return response()->json([
                'model' => $movement,
            ]);
        }
    }

    public function write(Request $request)
    {
        if ($request->req === 'write') {

            $request->validate(
                [
                    'asset_type' => 'required|in:inventory,alkes',
                    'to_location.branch_id' => 'required|exists:branches,id',
                    'to_location.warehouse_id' => 'required|exists:warehouses,id',
                    'items' => 'required|array|min:1',
                    'items.*' => 'integer',
                ],
                [
                    'asset_type.required' => 'Jenis barang wajib dipilih.',
                    'asset_type.in' => 'Jenis barang tidak valid.',
                    'to_location.branch_id.required' => 'Cabang tujuan wajib dipilih.',
                    'to_location.branch_id.exists' => 'Cabang tujuan tidak valid.',
                    'to_location.warehouse_id.required' => 'Gudang tujuan wajib dipilih.',
                    'to_location.warehouse_id.exists' => 'Gudang tujuan tidak valid.',
                    'items.required' => 'Barang distribusi wajib dipilih.',
                    'items.array' => 'Format barang distribusi tidak valid.',
                    'items.min' => 'Minimal harus memilih 1 barang.',
                    'items.*.integer' => 'Item barang tidak valid.',
                ]
            );

            // ======================
            // VALIDASI DUPLIKASI
            // ======================

            $movementLabels = [
                'distribution' => 'distribusi',
                'mutation' => 'mutasi',
                'return' => 'retur',
            ];

            $duplicates = IisMovement::whereIn('status', ['draft', 'submitted'])
                ->when($request->id, function ($q) use ($request) {
                    $q->where('id', '!=', $request->id);
                })
                ->whereHas('items', function ($q) use ($request) {
                    $q->whereIn('asset_id', $request->items);
                })
                ->with(['items' => function ($q) use ($request) {
                    $q->whereIn('asset_id', $request->items);
                }])
                ->get();

            if ($duplicates->isNotEmpty()) {

                $conflictedItems = $duplicates
                    ->flatMap(fn ($m) => $m->items->map(function ($item) use ($m) {
                        return [
                            'qr_code_no' => $item->qr_code_no,
                            'status' => $m->status,
                            'code' => $m->code,
                            'movement_type' => $m->movement_type,
                        ];
                    }))
                    ->unique('qr_code_no')
                    ->values();

                $message = $conflictedItems
                    ->map(function ($i) use ($movementLabels) {
                        $type = $movementLabels[$i['movement_type']] ?? 'pergerakan';

                        return "Barang dengan no {$i['qr_code_no']} sedang diproses {$type} "
                            ."dengan status {$i['status']} (Kode: {$i['code']})";
                    })
                    ->implode(', ');

                throw ValidationException::withMessages([
                    'items' => $message,
                ]);
            }

            try {
                return DB::transaction(function () use ($request) {

                    $to = $request->to_location;

                    $toBranch = Branch::find($to['branch_id']);
                    $warehouse = Warehouse::find($to['warehouse_id']);

                    $toLocationSnapshot = [
                        'branch_id' => $toBranch->id,
                        'branch_name' => $toBranch->name,
                        'warehouse_id' => $warehouse->id,
                        'warehouse_name' => $warehouse->name,
                    ];

                    if ($request->id) {
                        $movement = IisMovement::findOrFail($request->id);

                        if (! $movement->isDraft()) {
                            abort(403, 'Data sudah disubmit');
                        }

                        $movement->update([
                            'asset_type' => $request->asset_type,
                            'notes' => $request->notes,
                        ]);

                        $movement->items()->delete();
                    } else {
                        $movement = IisMovement::create([
                            'code' => $this->generateCode(),
                            'movement_type' => 'return',
                            'asset_type' => $request->asset_type,
                            'from_pj_id' => auth()->id(),
                            'operator_id' => auth()->id(),
                            'movement_date' => now(),
                            'status' => 'draft',
                            'notes' => $request->notes,
                        ]);
                    }

                    // ======================
                    // INSERT ITEMS
                    // ======================
                    $assetModel = $this->resolveAssetModel($request->asset_type);

                    $assets = $assetModel::whereIn('id', $request->items)->get();

                    foreach ($assets as $asset) {
                        $fromLocationSnapshot = $this->snapshotFromAsset($asset);
                        $movement->items()->create([
                            'asset_id' => $asset->id,
                            'qr_code_no' => $asset->qr_code_no,
                            'description' => $asset->description ?? null,
                            'condition' => $asset->condition,
                            'from_type' => 'user',
                            'from_location' => $fromLocationSnapshot,
                            'to_type' => 'user',
                            'to_location' => $toLocationSnapshot,
                        ]);
                    }

                    return response()->json([
                        'success' => true,
                        'message' => 'Distribusi berhasil disimpan',
                        'data' => $movement->load('items'),
                    ]);
                });
            } catch (\Throwable $e) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 500);
            }
        } elseif ($request->req === 'authorize') {

            $request->validate([
                'id' => 'required|exists:iis_movements,id',
                'action' => 'required|in:submit,approve,verify,reject',
            ]);

            $movement = IisMovement::with('items')->findOrFail($request->id);

            /* ---------- SUBMIT ---------- */
            if ($request->action === 'submit') {

                if (! $movement->isDraft()) {
                    abort(422, 'Hanya draft yang bisa disubmit');
                }

                $movement->update([
                    'status' => 'submitted',
                    'submitted_by' => auth()->id(),
                    'submitted_at' => now(),
                ]);
            }

            /* ---------- VERIFY (PENERIMA) ---------- */
            if ($request->action === 'verify') {

                DB::transaction(function () use ($movement) {

                    $assetModel = $this->resolveAssetModel($movement->asset_type);

                    $unitGudang = Unit::whereRaw('LOWER(TRIM(name)) = ?', ['gudang perbekalan'])
                        ->first();
                    if (! $unitGudang) {
                        throw new \Exception('Unit Gudang Perbekalan tidak ditemukan');
                    }

                    foreach ($movement->items as $item) {

                        $to = $item->to_location;
                        $assetModel::where('id', $item->asset_id)->update([
                            'branch_id' => $to['branch_id'] ?? null,
                            'warehouse_id' => $to['warehouse_id'] ?? null,
                            'unit_id' => $unitGudang->id,
                            'building_id' => null,
                            'floor' => null,
                            'room_id' => null,
                            'pj_nik' => null,
                        ]);
                    }

                    $movement->update([
                        'status' => 'verified',
                        'verified_by' => auth()->id(),
                        'verified_at' => now(),
                    ]);
                });
            }

            /* ---------- REJECT ---------- */
            if ($request->action === 'reject') {

                $request->validate([
                    'notes' => 'required|string|min:3',
                ]);

                $movement->update([
                    'status' => 'rejected',
                    'rejection_note' => $request->notes,
                    'rejected_by' => auth()->id(),
                    'rejected_at' => now(),
                ]);
            }

            return response()->json(['success' => true]);
        } elseif ($request->req === 'delete') {

            $movement = IisMovement::findOrFail($request->id);

            if (! $movement->isDraft()) {
                abort(403, 'Hanya draft yang bisa dihapus');
            }

            DB::transaction(function () use ($movement) {
                $movement->items()->delete();
                $movement->delete();
            });

            return response()->json([
                'success' => true,
                'message' => 'Distribusi berhasil dihapus',
            ]);
        }
    }

    /* ========================================================= */

    private function generateCode(): string
    {
        $year = now()->year;

        $last = IisMovement::whereYear('created_at', $year)
            ->where('movement_type', 'return')
            ->orderByDesc('id')
            ->first();

        $number = $last ? ((int) substr($last->code, -4)) + 1 : 1;

        return sprintf('RET-IIS-%s-%04d', $year, $number);
    }

    private function resolveAssetModel(string $type)
    {
        return match ($type) {
            'inventory' => IisInventory::class,
            'alkes' => IisAlkes::class,
            default => throw ValidationException::withMessages([
                'asset_type' => 'Tipe asset tidak valid',
            ]),
        };
    }

    private function snapshotFromAsset($asset): array
    {
        return [
            'branch_id' => $asset->branch_id,
            'branch_name' => optional($asset->branch)->name,

            'building_id' => $asset->building_id,
            'building_name' => optional($asset->building)->name,

            'floor' => $asset->floor,

            'unit_id' => $asset->unit_id,
            'unit_name' => optional($asset->unit)->name,

            'room_id' => $asset->room_id,
            'room_name' => optional($asset->room)->name,

            'warehouse_id' => $asset->warehouse_id,
            'warehouse_name' => optional($asset->warehouse)->name,
        ];
    }
}
