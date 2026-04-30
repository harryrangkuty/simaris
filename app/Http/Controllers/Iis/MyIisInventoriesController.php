<?php

namespace App\Http\Controllers\Iis;

use App\Http\Controllers\AuthController;
use App\Models\Iis\IisInventory;
use App\Models\Iis\IisMaintenance;
use App\Models\MasterData\Room;
use App\Models\MasterData\Unit;
use App\Models\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;

class MyIisInventoriesController extends AuthController
{
    public function __invoke(Request $request)
    {
        if ($request->req === 'open') {

            $constant = [
                'USERS' => User::select('id', 'identifier', 'name', 'position')->get(),
                'UNITS' => Unit::select('id', 'name', 'department')->get(),
                'ROOMS' => Room::select('id', 'legacy_id', 'code', 'name', 'building_id', 'floor')->get(),
            ];

            try {
                $barcodeNo = Crypt::decryptString($request->code);
            } catch (DecryptException $e) {
                abort(403, 'Kode tidak valid');
            }

            $data = IisInventory::with(['branch:id,name', 'lastPrintBy:id,identifier,name', 'bUser:id,identifier,name', 'item:code,name'])
                ->select('id', 'branch_id', 'qr_code_no', 'item_code', 'category_name', 'description', 'pj_nik', 'print_count', 'last_print_at', 'last_print_by', 'is_handed_over')
                ->where('qr_code_no', $barcodeNo)
                ->firstOrFail();

            $title = 'Data Inventaris '.$data->qr_code_no;
            $vue = "<iis-inventory-detail-page :title='".json_encode($title)."' :constant='".json_encode($constant)."' :parent='".json_encode($data)."' />";
        } else {
            $constant = [];

            $title = 'Daftar Inventarisku (IIS)';

            $vue = "<my-iis-inventories-page :title='".json_encode($title)."' :constant='".json_encode($constant)."' />";
        }

        return response()->view('layouts.antd', compact('vue', 'title'));
    }

    public function read(Request $request)
    {
        if ($request->req == 'table') {

            $user = Auth::user();

            $data = IisInventory::with([
                'bUser',
                'lastPrintBy',
                'unit',
                'room',
                'item',
                'building',
            ])
                ->where('pj_nik', $user->identifier)
                ->where(function ($q) use ($request) {
                    if ($request->search) {
                        $q->where('description', 'like', "%{$request->search}%")
                            ->orWhere('category_name', 'like', "%{$request->search}%")
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
                // Query untuk ambil status terakhir movement
                ->select(
                    'iis_inventories_list.*',
                    \DB::raw('(SELECT status 
                    FROM iis_movement_items AS mi
                    JOIN iis_movements AS m ON m.id = mi.movement_id
                    WHERE mi.asset_id = iis_inventories_list.id
                    ORDER BY mi.id DESC
                    LIMIT 1) AS latest_movement_status'),
                            \DB::raw('(SELECT movement_type 
                    FROM iis_movement_items AS mi
                    JOIN iis_movements AS m ON m.id = mi.movement_id
                    WHERE mi.asset_id = iis_inventories_list.id
                    ORDER BY mi.id DESC
                    LIMIT 1) AS latest_movement_type')
                )
                // Filter by latest movement status
                ->when($request->movement_status, function ($q) use ($request) {
                    $q->whereExists(function ($query) use ($request) {
                        $query->select(\DB::raw(1))
                            ->from('iis_movement_items as mi')
                            ->join('iis_movements as m', 'm.id', '=', 'mi.movement_id')
                            ->whereColumn('mi.asset_id', 'iis_inventories_list.id')
                            ->whereRaw('mi.id = (SELECT MAX(id) FROM iis_movement_items WHERE asset_id = mi.asset_id)')
                            ->where('m.status', $request->movement_status);
                    });
                })
                ->paginate($this->per_page());

            $data = $data->through(function ($item) {
                $item->encrypt_code = Crypt::encryptString($item->qr_code_no);

                return $item;
            });

            return response()->json(['models' => $data]);
        } elseif ($request->req == 'info_inventory') {
            $data = IisInventory::with(['bUser', 'unit', 'room'])->findOrFail($request->id);

            return response()->json(['models' => $data]);
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
}
