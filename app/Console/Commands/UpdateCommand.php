<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Permission;
use App\Models\Role;

class UpdateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command untuk segala jenis update sistem';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Menjalankan migration...');
        $this->call('migrate');
        $this->info('Migration selesai.');


        // ===== Tambah permissions baru =====

        $this->info('Menambahkan permissions baru...');

        $newPermissions = [
            'krs-digital',
        ];

        // Bikin atau ambil permissions
        $permissions = [];
        foreach ($newPermissions as $perm) {
            $permission = Permission::firstOrCreate(
                ['name' => $perm],
                [
                    'display_name' => implode(' - ', array_map(
                        fn($part) => ucwords(str_replace(['-', '_'], ' ', $part)),
                        explode('/', $perm)
                    )),
                ]
            );
            $permissions[$perm] = $permission;
        }

        // Assign ke role
        $roleOperator = Role::where('name', 'operator')->first();
        $roleKaprodi  = Role::where('name', 'kaprodi')->first();
        $roleSekprodi = Role::where('name', 'sekprodi')->first();
        $roleLecturer = Role::where('name', 'lecturer')->first();

        if (!$roleKaprodi || !$roleSekprodi || !$roleLecturer || !$roleOperator) {
            $this->error('Beberapa role tidak ditemukan!');
            return;
        }

        // Kaprodi, Sekprodi, Lecturer dapat validasi
        $krsDigitalPerm = $permissions['krs-digital']->id;

        $roleOperator->permissions()->syncWithoutDetaching([$krsDigitalPerm]);
        $roleKaprodi->permissions()->syncWithoutDetaching([$krsDigitalPerm]);
        $roleSekprodi->permissions()->syncWithoutDetaching([$krsDigitalPerm]);
        $roleLecturer->permissions()->syncWithoutDetaching([$krsDigitalPerm]);


        $this->info('Permissions baru berhasil ditambahkan dan diassign ke role sesuai aturan.');

        // $this->info('Menambahkan permissions baru...');

        // $newPermissions = [
        //     'krs-digital',
        // ];

        // // Bikin atau ambil permissions
        // $permissions = [];
        // foreach ($newPermissions as $perm) {
        //     $permission = Permission::firstOrCreate(
        //         ['name' => $perm],
        //         [
        //             'display_name' => implode(' - ', array_map(
        //                 fn($part) => ucwords(str_replace(['-', '_'], ' ', $part)),
        //                 explode('/', $perm)
        //             )),
        //         ]
        //     );
        //     $permissions[$perm] = $permission;
        // }

        // // Assign ke role
        // $roleKaprodi  = Role::where('name', 'kaprodi')->first();
        // $roleSekprodi = Role::where('name', 'sekprodi')->first();
        // $roleLecturer = Role::where('name', 'lecturer')->first();

        // if (!$roleKaprodi || !$roleSekprodi || !$roleLecturer) {
        //     $this->error('Beberapa role tidak ditemukan!');
        //     return;
        // }

        // // Kaprodi, Sekprodi, Lecturer dapat validasi
        // $validasiPerms = [
        //     $permissions['pengajuan-krs']->id,
        //     $permissions['validasi-krs']->id,
        //     $permissions['validasi-krs/write']->id,
        // ];
        // $roleKaprodi->permissions()->syncWithoutDetaching($validasiPerms);
        // $roleSekprodi->permissions()->syncWithoutDetaching($validasiPerms);
        // $roleLecturer->permissions()->syncWithoutDetaching($validasiPerms);

        // // Kaprodi tambahan: pengesahan
        // $pengesahanPerms = [
        //     $permissions['pengesahan-krs']->id,
        //     $permissions['pengesahan-krs/write']->id,
        // ];
        // $roleKaprodi->permissions()->syncWithoutDetaching($pengesahanPerms);

        // $this->info('Permissions baru berhasil ditambahkan dan diassign ke role sesuai aturan.');
    }
}
