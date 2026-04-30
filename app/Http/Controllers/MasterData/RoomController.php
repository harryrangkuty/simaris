<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\AuthController;
use App\Models\MasterData\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Models\MasterData\Building;
use App\Models\MasterData\Unit;
use App\Models\MasterData\Branch;
use App\Models\MasterData\Warehouse;

class RoomController extends AuthController
{
    public function __invoke(Request $request)
    {
        $constant = [
            'BRANCHES' => Branch::select('id', 'code', 'name')->get(),
            'BUILDINGS' => Building::select('id', 'branch_id', 'name', 'floors_count')->get(),
            'UNITS' => Unit::select('id', 'name', 'department')->get(),
            'WAREHOUSES' => Warehouse::canReceive()
                ->with(['personInCharge:id,identifier,name,position', 'branch:id,name'])
                ->select('id', 'branch_id', 'code', 'name', 'can_receive', 'person_in_charge_id')
                ->get(),
        ];

        $title = 'Manajemen Ruangan';
        $vue = "<room-page :title='".json_encode($title)."' :constant='".json_encode($constant)."' />";

        return response()->view('layouts.antd', compact('vue', 'title'));
    }

    public function read(Request $request)
    {
        if ($request->req == 'table') {
            $data = Room::withTrashed()
                ->with(['building.branch', 'personInCharge'])
                ->where(function ($q) use ($request) {
                    if ($request->search) {
                        $q->where('name', 'like', "%{$request->search}%")
                            ->orWhere('code', 'like', "%$request->search%");
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
        } elseif ($request->req == 'sync') {

            try {
                $response = Http::get('https://bunda-thamrin.com/api/sistem/barcode_ruangan.php');

                if (! $response->successful()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Gagal ambil data dari API.',
                    ], 500);
                }

                $roomList = $response->json('data');

                foreach ($roomList as $row) {
                    $existing = Room::withTrashed()
                        ->where('legacy_id', $row['id'])
                        ->first();

                    if ($existing) {
                        DB::table('rooms')
                            ->where('legacy_id', $row['id'])
                            ->update([
                                'code' => $row['no_barcode'],
                                'name' => $row['ruangan'],
                                'building_id' => $row['gedung'],
                                'floor' => $row['lantai'],
                                'registered_at' => $row['tanggal'],
                                'updated_at' => now(),
                            ]);
                    } else {
                        Room::create([
                            'legacy_id' => $row['id'],
                            'code' => $row['no_barcode'],
                            'name' => $row['ruangan'],
                            'building_id' => $row['gedung'],
                            'floor' => $row['lantai'],
                            'registered_at' => $row['tanggal'],
                        ]);
                    }
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Sinkronisasi Room selesai. Total data: '.count($roomList),
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal sinkronisasi: '.$e->getMessage(),
                ], 500);
            }
        }
    }

    public function write(Request $request)
    {
        if ($request->req === 'write') {
            $this->validate($request, [
                'name' => 'required',
                'building_id' => 'nullable|required_unless:location_type,warehouse|integer',
                'floor' => 'nullable|required_unless:location_type,warehouse|string',
                'person_in_charge_id' => 'required',
            ], [
                'name.required' => 'Nama wajib diisi.',
                'building_id.required_unless' => 'Gedung wajib dipilih.',
                'floor.required_unless' => 'Lantai wajib diisi.',
                'person_in_charge_id.required' => 'PJ wajib dipilih.',
            ]);

            DB::transaction(function () use ($request) {
                Room::updateOrCreate(
                    ['id' => $request->id],
                    [
                        'name' => $request->name,
                        'building_id' => $request->building_id,
                        'floor' => $request->floor,
                        'person_in_charge_id' => $request->person_in_charge_id,
                        'is_lab' => $request->is_lab,
                    ]
                );
            });

            return response()->json(true);
        } else if ($request->req === 'delete') {
            Room::findOrFail($request->id)->delete();
            return response()->json(['success' => true]);
        }
    }
}
