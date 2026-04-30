<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\AuthController;
use App\Models\MasterData\Category;
use App\Models\MasterData\DepreciationGroup;
use App\Models\MasterData\Item;
use App\Models\MasterData\ItemSequence;
use App\Models\MasterData\StockCode;
use App\Models\MasterData\Uom;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ItemController extends AuthController
{
    public function __invoke(Request $request)
    {
        $constant = [
            'STOCK_CODES' => StockCode::where('is_active', true)->get(),
            'CATEGORIES' => Category::where('is_active', true)->get(),
            'DEPRECIATION_GROUPS' => DepreciationGroup::all(),
            'UOMS' => Uom::all(),
            'PREFIXES' => ItemSequence::where('prefix', 'not like', 'REG-%')->get(),
        ];

        $title = 'Manajemen Master Item Barang';

        $vue = "<item-page :title='".json_encode($title)."' :constant='".json_encode($constant)."' />";

        return response()->view('layouts.antd', compact('vue', 'title'));
    }

    public function read(Request $request)
    {
        if ($request->req == 'table') {
            $data = Item::with(['editor', 'stock', 'category', 'uom', 'depreciationGroup:code,name,lifespan_months'])
                ->where(function ($q) use ($request) {
                    $q->where('name', 'like', "%{$request->search}%")
                        ->orWhere('code', 'like', "%{$request->search}%")
                        ->orWhere('code_legacy', 'like', "%{$request->search}%");
                })
                ->where(function ($q) use ($request) {
                    if ($request->type) {
                        $q->where('type', $request->type);
                    }
                })
                ->where(function ($q) use ($request) {
                    if ($request->status == 'aktif') {
                        $q->whereNull('deleted_at');
                    } else {
                        $q->whereNotNull('deleted_at');
                    }
                })
                ->paginate($this->per_page());

            return response()->json(['models' => $data]);
        }
    }

    public function write(Request $request)
    {
        if ($request->req === 'write') {

            $item = $request->code ? Item::find($request->code) : null;

            $rules = [
                'code_legacy' => [
                    'nullable',
                    'string',
                    'max:50',
                    Rule::unique('items', 'code_legacy')->ignore($item?->code, 'code'),
                ],
                'name' => 'required|string|max:255',
                'uom_code' => 'required|exists:uoms,code',
                'stock_code' => 'required|exists:stock_codes,code',
                'category_code' => 'required|exists:categories,code',
                'depreciation_group_code' => 'nullable|exists:depreciation_groups,code',
                'type' => 'required|string|in:inventory,asset',
                'min_stock' => 'nullable|numeric|min:0',
                'max_stock' => 'nullable|numeric|min:0',
                'notes' => 'nullable|string|max:500',
                'is_active' => 'required|boolean',
            ];

            // Hanya wajib saat insert
            if (! $item) {
                $rules['prefix'] = 'required|string|max:10';
            }

            $messages = [
                'prefix.required' => 'Prefix wajib diisi.',
                'name.required' => 'Nama item wajib diisi.',
                'uom_code.required' => 'Satuan wajib dipilih.',
                'uom_code.exists' => 'Satuan tidak valid.',
                'stock_code.required' => 'Stock code wajib dipilih.',
                'stock_code.exists' => 'Stock code tidak valid.',
                'category_code.required' => 'Kategori wajib dipilih.',
                'category_code.exists' => 'Kategori tidak valid.',
                'type.required' => 'Tipe item wajib dipilih.',
                'type.in' => 'Tipe item tidak valid.',
                'is_active.required' => 'Status aktif wajib dipilih.',
            ];

            $this->validate($request, $rules, $messages);

            // jika insert baru, generate code
            if (! $item) {

                $basePrefix = strtoupper($request->prefix ?? '');

                $regUsers = [151202, 211209];
                $isRegUser = in_array($this->user()->identifier, $regUsers);

                // Tentukan prefix sequence
                $sequencePrefix = $isRegUser
                    ? 'REG-'.$basePrefix
                    : $basePrefix;

                $sequence = ItemSequence::lockForUpdate()->firstOrCreate(
                    ['prefix' => $sequencePrefix],
                    ['last_number' => 0]
                );

                $nextNumber = $sequence->last_number + 1;

                // Code final
                $generatedCode = $sequencePrefix
                    .str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

                $sequence->update(['last_number' => $nextNumber]);

                $request->merge([
                    'code' => $generatedCode,
                    'is_reg' => $isRegUser,
                ]);
            }

            // insert / update data
            $data = array_merge(
                $request->only([
                    'code',
                    'code_legacy',
                    'name',
                    'uom_code',
                    'stock_code',
                    'category_code',
                    'depreciation_group_code',
                    'type',
                    'min_stock',
                    'max_stock',
                    'notes',
                    'is_active',
                    'is_reg',
                ]),
                ['editor_id' => $this->user()->id]
            );

            if ($item) {
                // update
                $item->update($data);
            } else {
                // insert baru
                $item = Item::create($data);
            }

            return response()->json(true);

        } elseif ($request->req === 'delete') {
            $item = Item::find($request->id);

            if (! $item) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Item tidak ditemukan',
                ], 404);
            }

            $item->editor_id = $this->user()->id;
            $item->delete();

            return response()->json([
                'status' => 'success',
                'action' => 'delete',
                'data' => $item,
            ]);

        } elseif ($request->req === 'restore') {
            $item = Item::onlyTrashed()->find($request->id);

            if (! $item) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Item tidak ditemukan atau belum terhapus',
                ], 404);
            }

            $item->editor_id = $this->user()->id;
            $item->restore();

            return response()->json([
                'status' => 'success',
                'action' => 'restore',
                'data' => $item,
            ]);
        }
    }
}
