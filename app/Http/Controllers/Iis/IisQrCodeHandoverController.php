<?php

namespace App\Http\Controllers\Iis;

use App\Http\Controllers\AuthController;
use App\Models\Iis\IisAlkes;
use App\Models\Iis\IisInventory;
use App\Models\Iis\IisQrCodeHandover;
use App\Models\Iis\IisQrCodeHandoverItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class IisQrCodeHandoverController extends AuthController
{
    public function __invoke(Request $request)
    {
        $constant = [];

        $title = 'Daftar Serah Terima Pendataan Ulang Data QR Code Barang IIS';
        $vue = "<iis-qr-code-handover-page :title='".json_encode($title)."' :constant='".json_encode($constant)."' />";

        return response()->view('layouts.antd', compact('vue', 'title'));
    }

    public function read(Request $request)
    {
        if ($request->req == 'table') {

            $user = auth()->user();

            $canViewAll = $user->can('iis.qrcode-handover.view_all');

            $data = IisQrCodeHandover::with([
                'pj:id,identifier,name',
                'operator:id,identifier,name',
                'verifier:id,identifier,name',
                'rejector:id,identifier,name,position',
                'approvals' => function ($q) {
                    $q->select(
                        'id',
                        'handover_id',
                        'user_id',
                        'position',
                        'approval_order',
                        'status',
                        'approved_at',
                        'rejected_at',
                        'note'
                    )->orderBy('approval_order');
                },
                'approvals.user:id,identifier,name,photo',
            ])
                ->withCount('items')
                ->where(function ($q) use ($request) {
                    if ($request->search) {
                        $q->where('code', 'like', "%{$request->search}%")
                            ->orWhere('notes', 'like', "%{$request->search}%");
                    }
                })
                ->where(function ($q) use ($request) {
                    if ($request->pj_id) {
                        $q->where('pj_id', $request->pj_id);
                    }
                })
                ->where(function ($q) use ($request) {
                    if ($request->status) {
                        $q->where('status', $request->status);
                    }
                })
                // ROLE FILTER
                ->when(! $canViewAll, function ($q) use ($user) {
                    $q->where(function ($query) use ($user) {

                        // Kalau dia approver yang sedang pending
                        $query->whereHas('approvals', function ($sub) use ($user) {
                            $sub->where('user_id', $user->id)
                                ->where('status', 'pending');
                        });

                        // Atau dia PJ tapi sudah fully approved
                        $query->orWhere(function ($sub) use ($user) {
                            $sub->where('pj_id', $user->id)
                                ->where(function ($q) {
                                    $q->where('status', 'approved')
                                        ->orWhere('status', 'verified');
                                });
                        });
                    });
                })
                ->orderBy('created_at', 'desc')
                ->paginate($this->per_page());

            // ambil items untuk modal edit
            $data->getCollection()->transform(function ($handover) {
                $handover->setRelation(
                    'items',
                    $handover->items->pluck('asset_id')
                );

                return $handover;
            });

            return response()->json([
                'models' => $data,
            ]);
        } elseif ($request->req === 'handover_items') {

            $items = IisQrCodeHandoverItem::where('qr_code_handover_id', $request->id)
                ->select([
                    'id',
                    'asset_id',
                    'qr_code_no',
                    'description',
                    'condition',
                ])
                ->get();

            return response()->json([
                'models' => $items,
            ]);
        } elseif ($request->req === 'pdf') {

            $data = IisQrCodeHandover::with([
                'items',
                'operator:id,identifier,name,position',
                'pj:id,identifier,name,position',
                'approvals' => function ($q) {
                    $q->select(
                        'id',
                        'handover_id',
                        'user_id',
                        'position',
                        'approval_order',
                        'status',
                        'approved_at',
                        'rejected_at',
                        'note'
                    )->orderBy('approval_order');
                },
                'approvals.user:id,identifier,name',
            ])->findOrFail($request->id);

            $logoPath = public_path('images/logo-rsbt.png');

            // ===============================
            // QR OPERATOR
            // ===============================
            $qrOperatorPayload = json_encode([
                'type' => 'handover_submission',
                'handover_id' => $data->id,
                'code' => $data->code,
                'operator' => [
                    'id' => $data->operator->id,
                    'nik' => $data->operator->identifier,
                    'name' => $data->operator->name,
                    'position' => $data->operator->position,
                ],
                'submitted_at' => $data->submitted_at
                    ? \Carbon\Carbon::parse($data->submitted_at)->format('Y-m-d H:i:s')
                    : null,
            ], JSON_UNESCAPED_UNICODE);

            $qrOperator = $this->makeQrBase64WithLogo(
                $qrOperatorPayload,
                $logoPath
            );

            // ===============================
            // QR APPROVALS
            // ===============================
            $qrApprovals = [];

            foreach ($data->approvals as $approval) {

                // hanya generate QR kalau sudah approved
                if ($approval->approved_at) {

                    $payload = json_encode([
                        'type' => 'handover_approval',
                        'handover_id' => $data->id,
                        'code' => $data->code,
                        'approval_order' => $approval->approval_order,
                        'position' => $approval->position,
                        'approved_by' => [
                            'id' => $approval->user->id,
                            'nik' => $approval->user->identifier,
                            'name' => $approval->user->name,
                        ],
                        'approved_at' => $approval->approved_at
                            ? \Carbon\Carbon::parse($approval->approved_at)
                                ->format('Y-m-d H:i:s')
                            : null,
                    ], JSON_UNESCAPED_UNICODE);

                    $qrApprovals[] = [
                        'approval' => $approval,
                        'qr' => $this->makeQrBase64WithLogo($payload, $logoPath),
                    ];
                }
            }

            // ===============================
            // QR VERIFIKASI (PJ)
            // ===============================
            $qrVerifyPayload = json_encode([
                'type' => 'handover_verification',
                'handover_id' => $data->id,
                'code' => $data->code,
                'pj' => [
                    'id' => $data->pj->id,
                    'nik' => $data->pj->identifier,
                    'name' => $data->pj->name,
                    'position' => $data->pj->position,
                ],
                'verified_at' => $data->verified_at
                    ? \Carbon\Carbon::parse($data->verified_at)->format('Y-m-d H:i:s')
                    : null,
            ], JSON_UNESCAPED_UNICODE);

            $qrVerify = $this->makeQrBase64WithLogo(
                $qrVerifyPayload,
                $logoPath
            );

            $pdf = \PDF::loadView(
                'print.iis.serah_terima',
                compact('data', 'qrOperator', 'qrVerify', 'qrApprovals')
            );

            return $pdf->stream('Serah_Terima_'.$data->code.'.pdf');
        }
    }

    public function write(Request $request)
    {
        if ($request->req == 'write') {

            $this->validate(
                $request,
                [
                    'asset_type' => 'required|in:inventory,alkes',
                    'pj_id' => 'required|exists:users,id',
                    'approvers' => 'required|array|min:1',
                    'approvers.*' => 'distinct|exists:users,id',
                ],
                [
                    'asset_type.required' => 'Jenis barang wajib dipilih.',
                    'pj_id.required' => 'Penanggung jawab wajib dipilih.',
                    'approvers.required' => 'Minimal pilih 1 approver.',
                    'approvers.*.distinct' => 'Approver tidak boleh duplikat.',
                ]
            );

            // ======================
            // VALIDASI DUPLIKASI
            // ======================
            $duplicate = IisQrCodeHandover::where('pj_id', $request->pj_id)
                ->whereIn('status', ['draft', 'submitted', 'verified'])
                ->when($request->id, function ($q) use ($request) {
                    $q->where('id', '!=', $request->id);
                })
                ->whereHas('items', function ($q) use ($request) {
                    $q->whereIn('asset_id', $request->items);
                })
                ->with('items')
                ->first();

            if ($duplicate) {
                $item = $duplicate->items
                    ->whereIn('asset_id', $request->items)
                    ->first();

                throw ValidationException::withMessages([
                    'items' => "Asset barcode {$item->qr_code_no} sudah pernah diserahterimakan ke PJ yang sama",
                ]);
            }

            try {
                return DB::transaction(function () use ($request) {
                    // ======================
                    // CREATE ATAU UPDATE
                    // ======================
                    if ($request->id) {
                        $handover = IisQrCodeHandover::findOrFail($request->id);

                        // proteksi status
                        if ($handover->status !== 'draft') {
                            abort(403, 'Data sudah disubmit / diverifikasi');
                        }

                        $handover->update([
                            'pj_id' => $request->pj_id,
                            'notes' => $request->notes,
                            'asset_type' => $request->asset_type,
                        ]);

                        // HAPUS ITEM LAMA
                        $handover->items()->delete();

                        // hapus approval lama
                        $handover->approvals()->delete();
                    } else {
                        $handover = IisQrCodeHandover::create([
                            'code' => $this->generateCode(),
                            'pj_id' => $request->pj_id,
                            'operator_id' => auth()->id(),
                            'notes' => $request->notes,
                            'asset_type' => $request->asset_type,
                            'status' => 'draft',
                        ]);
                    }

                    // ======================
                    // INSERT ITEM BARU
                    // ======================
                    $assetModel = $this->resolveAssetModel($request->asset_type);

                    $items = $assetModel::whereIn('id', $request->items)->get();

                    foreach ($items as $item) {

                        if ($item->is_handed_over) {
                            throw ValidationException::withMessages([
                                'items' => "Inventory barcode {$item->qr_code_no} sudah pernah diserahterimakan",
                            ]);
                        }

                        $item->update([
                            'condition' => 'Baik',
                        ]);

                        $handover->items()->create([
                            'asset_id' => $item->id,
                            'qr_code_no' => $item->qr_code_no,
                            'description' => $item->description,
                            'condition' => $item->condition,
                        ]);
                    }

                    // ======================
                    // INSERT APPROVALS
                    // ======================
                    $users = User::whereIn('id', $request->approvers)->get()->keyBy('id');

                    foreach ($request->approvers as $index => $userId) {

                        $user = $users[$userId];

                        $handover->approvals()->create([
                            'user_id' => $user->id,
                            'position' => $user->position,
                            'approval_order' => $index + 1,
                            'status' => 'pending',
                        ]);
                    }

                    return response()->json([
                        'success' => true,
                        'message' => $request->id
                            ? 'Serah terima berhasil diperbarui'
                            : 'Serah terima berhasil dibuat',
                        'data' => $handover->load('items'),
                    ]);
                });
            } catch (ValidationException $e) {
                throw $e;
            } catch (\Throwable $e) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 500);
            }
        } elseif ($request->req === 'authorize') {

            $request->validate([
                'id' => 'required|exists:iis_qr_code_handovers,id',
                'action' => 'required|in:submit,approve,verify,reject',
            ]);

            $handover = IisQrCodeHandover::findOrFail($request->id);

            if ($request->action === 'submit') {

                if (! auth()->user()->can('iis.qrcode-handover.submit')) {
                    abort(403, 'Tidak punya hak submit');
                }

                if ($handover->status !== 'draft') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Hanya data draft yang bisa disubmit',
                    ], 422);
                }

                $handover->update([
                    'status' => 'submitted',
                    'submitted_at' => now(),
                ]);
            }

            /* ---------- APPROVE ---------- */
            if ($request->action === 'approve') {

                if ($handover->status !== 'submitted') {
                    abort(422, 'Hanya submitted yang bisa di-approve');
                }

                $approval = $handover->approvals()
                    ->where('user_id', auth()->id())
                    ->whereNull('approved_at')
                    ->whereNull('rejected_at')
                    ->first();

                if (! $approval) {
                    abort(403, 'Bukan giliran Anda atau sudah diproses');
                }

                // cek apakah ini urutan terkecil yg belum approve
                $nextApproval = $handover->approvals()
                    ->whereNull('approved_at')
                    ->whereNull('rejected_at')
                    ->orderBy('approval_order')
                    ->first();

                if ($nextApproval->id !== $approval->id) {
                    abort(403, 'Belum giliran Anda');
                }

                DB::transaction(function () use ($handover, $approval) {

                    $approval->update([
                        'status' => 'approved',
                        'approved_at' => now(),
                    ]);

                    // cek apakah masih ada approval tersisa
                    $remaining = $handover->approvals()
                        ->whereNull('approved_at')
                        ->whereNull('rejected_at')
                        ->count();

                    if ($remaining === 0) {
                        $handover->update([
                            'status' => 'approved',
                            'approved_at' => now(),
                        ]);
                    }
                });

                return response()->json([
                    'success' => true,
                    'message' => 'Berhasil approve',
                ]);
            }

            /* ---------- VERIFY ---------- */
            if ($request->action === 'verify') {

                if (! auth()->user()->can('iis.qrcode-handover.verify')) {
                    abort(403, 'Tidak punya hak verifikasi');
                }

                if ($handover->status !== 'approved') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Hanya data yang telah di approve yang bisa diverifikasi',
                    ], 422);
                }

                DB::transaction(function () use ($handover) {

                    // ======================
                    // RESOLVE MODEL ASSET
                    // ======================
                    $assetModel = $this->resolveAssetModel($handover->asset_type);

                    // ======================
                    // LOCK ASSET
                    // ======================
                    foreach ($handover->items as $item) {

                        $updated = $assetModel::where('id', $item->asset_id)
                            ->where('is_handed_over', false)
                            ->update([
                                'is_handed_over' => true,
                            ]);

                        if ($updated === 0) {
                            throw ValidationException::withMessages([
                                'asset' => "Asset barcode {$item->qr_code_no} sudah pernah diserahterimakan",
                            ]);
                        }
                    }

                    // ======================
                    // UPDATE STATUS HANDOVER
                    // ======================
                    $handover->update([
                        'status' => 'verified',
                        'verified_at' => now(),
                    ]);
                });
            }

            /* ---------- REJECT ---------- */
            if ($request->action === 'reject') {

                if (! auth()->user()->can('iis.qrcode-handover.reject')) {
                    abort(403);
                }

                $request->validate([
                    'notes' => 'required|string|min:3',
                ]);

                DB::transaction(function () use ($handover, $request) {

                    // ============================
                    // REJECT SAAT SUBMITTED (Approver)
                    // ============================
                    if ($handover->status === 'submitted') {

                        $approval = $handover->approvals()
                            ->where('user_id', auth()->id())
                            ->whereNull('approved_at')
                            ->whereNull('rejected_at')
                            ->first();

                        if (! $approval) {
                            abort(403, 'Bukan giliran Anda atau sudah diproses');
                        }

                        $approval->update([
                            'status' => 'rejected',
                            'rejected_at' => now(),
                        ]);

                        $handover->update([
                            'status' => 'rejected',
                            'rejection_note' => $request->notes,
                            'rejected_by' => auth()->id(),
                            'rejected_at' => now(),
                        ]);
                    }

                    // ============================
                    // REJECT SAAT APPROVED (PJ)
                    // ============================
                    elseif ($handover->status === 'approved') {

                        if ($handover->pj_id !== auth()->id()) {
                            abort(403, 'Hanya PJ yang bisa menolak');
                        }

                        $handover->update([
                            'status' => 'rejected',
                            'rejection_note' => $request->notes,
                            'rejected_by' => auth()->id(),
                            'rejected_at' => now(),
                        ]);
                    } else {
                        abort(422, 'Status tidak valid untuk reject');
                    }
                });

                return response()->json([
                    'success' => true,
                    'message' => 'Serah terima ditolak',
                ]);
            }

            return response()->json(['success' => true]);
        } elseif ($request->req === 'delete') {

            $handover = IisQrCodeHandover::findOrFail($request->id);

            if (! $handover->isDraft()) {
                abort(403, 'Hanya draft yang bisa dihapus');
            }

            DB::transaction(function () use ($handover) {
                $handover->items()->delete();
                $handover->approvals()->delete();
                $handover->delete();
            });

            return response()->json([
                'success' => true,
                'message' => 'Data serah terima berhasil dihapus',
            ]);
        }
    }

    private function generateCode(): string
    {
        $year = now()->year;

        $last = IisQrCodeHandover::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $number = $last
            ? ((int) substr($last->code, -4)) + 1
            : 1;

        return sprintf('RB-IIS-%s-%04d', $year, $number);
    }

    private function resolveAssetModel(string $type)
    {
        return match ($type) {
            'inventory' => IisInventory::class,
            'alkes' => IisAlkes::class,
            default => throw ValidationException::withMessages([
                'asset_type' => 'Tipe aset tidak valid',
            ]),
        };
    }
}
