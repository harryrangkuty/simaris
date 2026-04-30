<?php

namespace App\Http\Controllers\Administrator;

use App\Http\Controllers\AuthController;
use App\Models\User;
use Illuminate\Http\Request;
// use App\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;

class UserController extends AuthController
{
    public function __invoke(Request $request)
    {
        if ($request->req == 'open') {
            $data = User::with(['roles'])->findOrFail($request->id);

            $title = $data->name;
            $vue = "<user-page-detail :title='".json_encode($title)."' :data='".json_encode($data)."'/>";
        } else {
            $constant = [
                'ROLE' => Role::select('id', 'name')->get(),
            ];

            $title = 'Manajemen Akun Pengguna';
            $vue = "<user-page :title='".json_encode($title)."' :constant='".json_encode($constant)."' />";
        }

        return response()->view('layouts.antd', compact('vue', 'title'));
    }

    public function read(Request $request)
    {
        if ($request->req == 'table') {
            $data = User::with(['roles' => function ($q) use ($request) {
                if ($request->roles) {
                    $q->whereIn('id', $request->roles);
                }
            }])
                ->withTrashed()
                ->where(function ($q) use ($request) {
                    if ($request->search) {
                        $q->where('users.name', 'like', "%{$request->search}%")
                            ->orWhere('identifier', 'like', "%$request->search%");
                    }
                })
                ->where(function ($q) use ($request) {
                    if ($request->status == 'aktif') {
                        $q->whereNull('deleted_at');
                    } else {
                        $q->whereNotNull('deleted_at');
                    }
                })
                ->when($request->roles, function ($q) use ($request) {
                    $q->whereHas('roles', function ($query) use ($request) {
                        $query->whereIn('id', $request->roles);
                    });
                })
                ->paginate($this->per_page());

            return response()->json(['models' => $data]);
        } elseif ($request->req == 'sync') {

            try {

                $response = Http::get('https://bunda-thamrin.com/api/sistem/data_karyawan.php');

                if (! $response->successful()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Gagal ambil data dari API.',
                    ], 500);
                }

                $karyawanList = $response->json('data');

                foreach ($karyawanList as $row) {

                    $isInactive = in_array($row['STATUS_KARYAWAN'], ['Berhenti', 'R.Resign']);

                    $user = User::withTrashed()
                        ->where('identifier', $row['NIK'])
                        ->first();

                    if ($user) {

                        // UPDATE DATA
                        // $user->update([
                        //     'division' => $row['DIVISI'],
                        //     'department' => $row['DEPARTMENT'],
                        //     'position' => $row['JABATAN'],
                        // ]);
                        $update = [];

                        if (empty($user->division)) {
                            $update['division'] = $row['DIVISI'];
                        }

                        if (empty($user->department)) {
                            $update['department'] = $row['DEPARTMENT'];
                        }

                        if (empty($user->position)) {
                            $update['position'] = $row['JABATAN'];
                        }

                        if (! empty($update)) {
                            $user->update($update);
                        }

                        if ($isInactive) {
                            $user->syncRoles([]); // hapus role
                            $user->delete();      // soft delete
                        }

                    } else {

                        // CREATE USER
                        $user = User::create([
                            'identifier' => $row['NIK'],
                            'name' => $row['NAMA'],
                            'email' => $row['NIK'].'@noemail.com',
                            'division' => $row['DIVISI'],
                            'department' => $row['DEPARTMENT'],
                            'position' => $row['JABATAN'],
                            'password' => bcrypt($row['NIK']),
                            'active_role_id' => 4,
                        ]);

                        if ($isInactive) {
                            $user->delete();
                        } else {
                            $user->syncRoles(['staff']);
                        }
                    }
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Sinkronisasi selesai. Total data: '.count($karyawanList),
                ]);

            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal sinkronisasi: '.$e->getMessage(),
                ], 500);
            }
        } elseif ($request->req == 'detail') {
            $data = User::with(['roles'])->findOrFail($request->id);

            return response()->json([
                'model' => $data,
            ]);
        }
    }

    public function write(Request $request)
    {
        if ($request->req == 'write') {
            $this->validate(
                $request,
                [
                    'identifier' => 'required',
                    'name' => 'required',
                    'roles' => 'required|min:1',
                    'password' => $request->id ? 'nullable' : 'required',
                ],
                [
                    'roles.required' => 'Wajib pilih Role User',
                ],
            );

            DB::transaction(function () use ($request) {
                $data = User::find($request->id) ?? new User;

                $data->identifier = $request->identifier;
                $data->name = $request->name;
                $data->division = $request->division;
                $data->department = $request->department;
                $data->position = $request->position;
                $data->email = $request->email;

                // Password Stuff
                if (! empty($request->password)) {
                    $data->password = bcrypt($request->password);
                } elseif (empty($data->password)) {
                    $generatedPassword = $request->identifier ?? 'rsbt123456';
                    $data->password = bcrypt($generatedPassword);
                }

                if ($request->hasFile('photo')) {
                    $file = $request->file('photo');
                    $path = $file->store('public/photos');
                    $data->photo = url(str_replace('public', 'storage', $path));
                }
                $data->save();

                $roleNames = Role::whereIn('id', $request->roles)->pluck('name')->toArray();
                $data->syncRoles($roleNames);
                $data->active_role_id = collect($request->roles)->last();
                $data->save();
            });

            return response()->json(true);
        } elseif ($request->req === 'delete') {
            $data = User::find($request->id);

            if (! $data) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data tidak ditemukan',
                ], 404);
            }

            $data->delete();

            return response()->json([
                'status' => 'success',
                'action' => 'delete',
                'data' => $data,
            ]);
        } elseif ($request->req === 'restore') {
            $data = User::onlyTrashed()->find($request->id);

            if (! $data) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data tidak ditemukan atau belum terhapus',
                ], 404);
            }

            $data->restore();

            return response()->json([
                'status' => 'success',
                'action' => 'restore',
                'data' => $data,
            ]);
        }
    }
}
