<?php

namespace App\Http\Controllers;

use App\Models\Iis\IisAlkes;
use App\Models\Iis\IisInventory;
use App\Models\MasterData\Item;
use App\Models\MasterData\Room;
use App\Models\MasterData\Supplier;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LookUpController extends Controller
{
    public function users(Request $request)
    {
        $query = User::query();

        // ===== FETCH BY ID =====
        if ($id = $request->get('id')) {
            return User::where('id', $id)->get(['id', 'identifier', 'name', 'position']);
        }

        // ===== FILTER KHUSUS LEADER =====
        if ($request->get('type') === 'leader') {
            $query->where(function ($q) {
                $q->where('position', 'like', '%Ka.Sub.Bid%')
                    ->orWhere('position', 'like', '%Ka.Bid%')
                    ->orWhere('position', 'like', '%Koordinator%')
                    ->orWhere('position', 'like', '%Kepala Ruangan%')
                    ->orWhere('position', 'like', '%Kasie%');
            });
        }

        // ===== FETCH BY IDENTIFIER =====
        if ($identifier = $request->get('identifier')) {
            return User::where('identifier', $request->identifier)
                ->get(['id', 'identifier', 'name', 'position']);
        }

        // ===== SEARCH =====
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                    ->orWhere('identifier', 'like', "%$search%")
                    ->orWhere('position', 'like', "%$search%");
            });
        }

        return $query->limit($request->get('limit', 10))
            ->get(['id', 'identifier', 'name', 'position']);
    }

    public function suppliers(Request $request)
    {
        $query = Supplier::query();

        if ($id = $request->get('id')) {
            return Supplier::where('id', $id)->get(['id', 'gl_code', 'name']);
        }

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                    ->orWhere('gl_code', 'like', "%$search%");
            });
        }

        return $query->limit($request->get('limit', 10))
            ->get(['id', 'gl_code', 'name']);
    }

    public function items(Request $request)
    {
        $query = Item::query();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                    ->orWhere('code', 'like', "%$search%")
                    ->orWhere('code_legacy', 'like', "%$search%");
            });
        }

        // ITEM TIPE ASSET DAN NON ALK
        if ($request->type === 'asset_non_alk') {
            $query->where('type', 'asset')
                ->where('code', 'not like', '%ALK%');
        }

        // ITEM TIPE ASSET DAN ALK
        if ($request->type === 'asset_alk') {
            $query->where('type', 'asset')
                ->where('code', 'like', '%ALK%');
        }

        return $query->limit($request->get('limit', 10))
            ->get(['code', 'name', 'code_legacy']);
    }

    public function iisAvailableHandoverAssets(Request $request)
    {
        $request->validate([
            'asset_type' => 'required|in:inventory,alkes',
            'pj_nik' => 'required|string',
            'handover_id' => 'nullable|integer',
        ]);

        $blockedStatuses = ['draft', 'submitted'];

        $query = match ($request->asset_type) {

            'inventory' => IisInventory::query()
                ->with(['bUser', 'unit', 'room', 'building'])
                ->where('pj_nik', $request->pj_nik)
                ->where('is_handed_over', false)
                ->where('is_deactivated', false)
                ->where(function ($q) {
                    $q->where('data_source', '!=', 'system')
                        ->orWhere(function ($qq) {
                            $qq->where('data_source', 'system')
                                ->where('print_count', '>', 0)
                                ->where('purchase_year', '<', 2026)
                                ->whereNull('po_number');
                        });
                })
                ->whereNotExists(function ($q) use ($blockedStatuses, $request) {
                    $q->selectRaw(1)
                        ->from('iis_qr_code_handover_items as hi')
                        ->join('iis_qr_code_handovers as h', 'h.id', '=', 'hi.qr_code_handover_id')
                        ->whereColumn('hi.asset_id', 'iis_inventories_list.id')
                        ->where('h.asset_type', 'inventory')
                        ->whereIn('h.status', $blockedStatuses)
                        ->when(
                            $request->handover_id,
                            fn ($qq) => $qq->where('h.id', '!=', $request->handover_id)
                        );
                }),

            'alkes' => IisAlkes::query()
                ->with(['bUser', 'unit', 'room', 'building'])
                ->where('pj_nik', $request->pj_nik)
                ->where('is_handed_over', false)
                ->where('is_deactivated', false)
                ->where(function ($q) {
                    $q->where('data_source', '!=', 'system')
                        ->orWhere(function ($qq) {
                            $qq->where('data_source', 'system')
                                ->where('print_count', '>', 0)
                                ->where('purchase_year', '<', 2026)
                                ->whereNull('po_number');
                        });
                })
                ->whereNotExists(function ($q) use ($blockedStatuses, $request) {
                    $q->selectRaw(1)
                        ->from('iis_qr_code_handover_items as hi')
                        ->join('iis_qr_code_handovers as h', 'h.id', '=', 'hi.qr_code_handover_id')
                        ->whereColumn('hi.asset_id', 'iis_alkes_list.id')
                        ->where('h.asset_type', 'alkes')
                        ->whereIn('h.status', $blockedStatuses)
                        ->when(
                            $request->handover_id,
                            fn ($qq) => $qq->where('h.id', '!=', $request->handover_id)
                        );
                })
        };

        return response()->json([
            'models' => $query->paginate($this->per_page()),
        ]);
    }

    public function iisAvailableDistributionAssets(Request $request)
    {
        $request->validate([
            'asset_type' => 'required|in:inventory,alkes',
            'warehouse_id' => 'required|integer',
            'movement_id' => 'nullable|integer',
        ]);

        $blockedStatuses = ['draft', 'submitted', 'approved'];

        $query = match ($request->asset_type) {

            'inventory' => IisInventory::query()
                ->with(['bUser', 'unit', 'room', 'building'])
                ->whereNotNull('warehouse_id')
                ->where('warehouse_id', $request->warehouse_id)
                ->where('is_deactivated', false)
                ->where(function ($q) {
                    $q->where('data_source', '!=', 'system')
                        ->orWhere(function ($qq) {
                            $qq->where('data_source', 'system')
                                ->where('print_count', '>', 0);
                        });
                })
                ->whereNotExists(function ($q) use ($blockedStatuses, $request) {
                    $q->selectRaw(1)
                        ->from('iis_movement_items as mi')
                        ->join('iis_movements as m', 'm.id', '=', 'mi.movement_id')
                        ->whereColumn('mi.asset_id', 'iis_inventories_list.id')
                        ->where('m.asset_type', 'inventory')
                        ->whereIn('m.status', $blockedStatuses)
                        ->when(
                            $request->movement_id,
                            fn ($qq) => $qq->where('m.id', '!=', $request->movement_id)
                        );
                }),

            'alkes' => IisAlkes::query()
                ->with(['bUser', 'unit', 'room', 'building'])
                ->whereNotNull('warehouse_id')
                ->where('warehouse_id', $request->warehouse_id)
                ->where('is_deactivated', false)
                ->where(function ($q) {
                    $q->where('data_source', '!=', 'system')
                        ->orWhere(function ($qq) {
                            $qq->where('data_source', 'system')
                                ->where('print_count', '>', 0);
                        });
                })
                ->whereNotExists(function ($q) use ($blockedStatuses, $request) {
                    $q->selectRaw(1)
                        ->from('iis_movement_items as mi')
                        ->join('iis_movements as m', 'm.id', '=', 'mi.movement_id')
                        ->whereColumn('mi.asset_id', 'iis_alkes_list.id')
                        ->where('m.asset_type', 'alkes')
                        ->whereIn('m.status', $blockedStatuses)
                        ->when(
                            $request->movement_id,
                            fn ($qq) => $qq->where('m.id', '!=', $request->movement_id)
                        );
                }),
        };

        return response()->json([
            'models' => $query->get(),
        ]);
    }

    public function iisAvailableMutationAndReturnAssets(Request $request)
    {
        $request->validate([
            'asset_type' => 'required|in:inventory,alkes',
            'movement_id' => 'nullable|integer',
        ]);

        $identifier = Auth::user()->identifier;

        $blockedStatuses = ['draft', 'submitted', 'approved'];

        $query = match ($request->asset_type) {

            'inventory' => IisInventory::query()
                ->with(['bUser', 'unit', 'room', 'building'])
                ->where('pj_nik', $identifier)
                ->where('is_handed_over', true)
                ->where('is_deactivated', false)
                ->where(function ($q) {
                    $q->where('data_source', '!=', 'system')
                        ->orWhere(function ($qq) {
                            $qq->where('data_source', 'system')
                                ->where('print_count', '>', 0);
                        });
                })
                ->whereNotExists(function ($q) use ($blockedStatuses, $request) {
                    $q->selectRaw(1)
                        ->from('iis_movement_items as mi')
                        ->join('iis_movements as m', 'm.id', '=', 'mi.movement_id')
                        ->whereColumn('mi.asset_id', 'iis_inventories_list.id')
                        ->where('m.asset_type', 'inventory')
                        ->whereIn('m.status', $blockedStatuses)
                        ->when(
                            $request->movement_id,
                            fn ($qq) => $qq->where('m.id', '!=', $request->movement_id)
                        );
                }),
            'alkes' => IisAlkes::query()
                ->with(['bUser', 'unit', 'room', 'building'])
                ->where('pj_nik', $identifier)
                ->where('is_handed_over', true)
                ->where('is_deactivated', false)
                ->where(function ($q) {
                    $q->where('data_source', '!=', 'system')
                        ->orWhere(function ($qq) {
                            $qq->where('data_source', 'system')
                                ->where('print_count', '>', 0);
                        });
                })
                ->whereNotExists(function ($q) use ($blockedStatuses, $request) {
                    $q->selectRaw(1)
                        ->from('iis_movement_items as mi')
                        ->join('iis_movements as m', 'm.id', '=', 'mi.movement_id')
                        ->whereColumn('mi.asset_id', 'iis_alkes_list.id')
                        ->where('m.asset_type', 'alkes')
                        ->whereIn('m.status', $blockedStatuses)
                        ->when(
                            $request->movement_id,
                            fn ($qq) => $qq->where('m.id', '!=', $request->movement_id)
                        );
                }),
        };

        return response()->json([
            'models' => $query->get(),
        ]);
    }

    public function rooms(Request $request)
    {
        $query = Room::query();

        if ($id = $request->get('id')) {
            return $query->where('id', $id)
                ->get(['id', 'building_id', 'floor', 'name']);
        }

        if ($buildingId = $request->get('building_id')) {
            $query->where('building_id', $buildingId);
        }

        if ($floor = $request->get('floor')) {
            $query->where('floor', $floor);
        }

        if ($search = $request->get('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        return $query
            ->limit($request->get('limit', 10))
            ->get(['id', 'building_id', 'floor', 'name']);
    }
}
