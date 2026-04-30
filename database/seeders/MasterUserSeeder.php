<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class MasterUserSeeder extends Seeder
{
    public function run(): void
    {
        // ===============================
        // MASTER SUPERADMIN
        // ===============================
        $superAdmins = [
            [
                'identifier' => '250602',
                'name' => 'Harry Rahman Rangkuti',
                'email' => 'harryrahman2768@gmail.com',
                'division' => 'IT',
                'department' => 'Administrasi dan Umum',
                'position' => 'SIM RS',
                'password' => bcrypt('1234567890'),
            ],
            [
                'identifier' => '251203',
                'name' => 'Rafli Zocky Leonard',
                'email' => 'raflizocky@gmail.com',
                'division' => 'IT',
                'department' => 'Administrasi dan Umum',
                'position' => 'SIM RS',
                'password' => bcrypt('1234567890'),
            ],
            [
                'identifier' => '9055',
                'name' => 'Salim',
                'email' => '9055@gmail.com',
                'division' => 'IT',
                'department' => 'Administrasi dan Umum',
                'position' => 'Ka.Sub.Bid. Teknologi dan Informasi',
                'password' => bcrypt('#Bunda456852'),
            ],
        ];

        foreach ($superAdmins as $data) {
            $user = User::updateOrCreate(
                ['identifier' => $data['identifier']],
                array_merge($data, ['photo' => null])
            );

            $user->syncRoles([
                'superadmin',
                'administrator',
                'staff',
            ]);
        }

        // ===============================
        // MASTER ADMIN KEUANGAN
        // ===============================
        $financeAdmins = [
            [
                'identifier' => '3001',
                'name' => 'Hui Chien',
                'email' => '3001@gmail.com',
                'division' => 'Akuntansi',
                'department' => 'Bidang Keuangan dan Akuntansi',
                'position' => 'Ka.Bid. Keuangan dan Akuntansi',
                'password' => bcrypt('3001'),
            ],
            [
                'identifier' => '170701',
                'name' => 'SARTIKA SALIM',
                'email' => '170701@gmail.com',
                'division' => 'Keuangan',
                'department' => 'Bidang Keuangan dan Akuntansi',
                'position' => 'Ka.Sub.Bid. Akuntansi',
                'password' => bcrypt('170701'),
            ],
            [
                'identifier' => '120704',
                'name' => 'RUDYONO',
                'email' => '120704@noemail.com',
                'division' => 'SPI',
                'department' => 'DEWAN KOMISARIS',
                'position' => 'Ketua SPI',
                'password' => bcrypt('120704'),
            ],
            [
                'identifier' => '230711',
                'name' => 'HERLINA',
                'email' => '230711@noemail.com',
                'division' => 'Keuangan',
                'department' => 'Bidang Keuangan dan Akuntansi',
                'position' => 'Ka.Sub.Bid. Keuangan',
                'password' => bcrypt('230711'),
            ],
        ];

        foreach ($financeAdmins as $data) {
            $user = User::updateOrCreate(
                ['identifier' => $data['identifier']],
                array_merge($data, ['photo' => null])
            );

            $user->syncRoles([
                'superadmin',
                'finance_administrator',
                'staff',
            ]);
        }

        // ===============================
        // MASTER PEMBELIAN
        // ===============================
        $purchasers = [
            [
                'identifier' => '150101',
                'name' => 'FREDY WIJAYA',
                'email' => '150101@noemail.com',
                'division' => 'Keuangan',
                'department' => 'Bidang Keuangan dan Akuntansi',
                'position' => 'Pembelian',
                'password' => bcrypt('150101'),
            ],
            [
                'identifier' => '130724',
                'name' => 'MINARVI',
                'email' => '130724@noemail.com',
                'division' => 'Keuangan',
                'department' => 'Bidang Keuangan dan Akuntansi',
                'position' => 'Pembelian',
                'password' => bcrypt('130724'),
            ],
        ];

        foreach ($purchasers as $data) {
            $user = User::updateOrCreate(
                ['identifier' => $data['identifier']],
                array_merge($data, ['photo' => null])
            );

            $user->syncRoles([
                'purchasing_officer',
                'staff',
            ]);
        }

        // ===============================
        // ADMIN GUDANG
        // ===============================
        $warehousers = [
            [
                'identifier' => '211209',
                'name' => 'LISNAWATI',
                'email' => '211209@noemail.com',
                'division' => 'Keuangan',
                'department' => 'Bidang Keuangan dan Akuntansi',
                'position' => 'Koordinator Gudang Perbekalan',
                'password' => bcrypt('211209'),
            ],
            [
                'identifier' => '151202',
                'name' => 'SARTIKA MARIANA SITUMEANG',
                'email' => '151202@noemail.com',
                'division' => 'Keuangan',
                'department' => 'Bidang Keuangan dan Akuntansi',
                'position' => 'Gudang Perbekalan',
                'password' => bcrypt('151202'),
            ],
        ];

        foreach ($warehousers as $w) {
            $user = User::updateOrCreate(
                ['identifier' => $w['identifier']],
                array_merge($w, ['photo' => null])
            );

            $user->syncRoles([
                'warehouse_administrator',
                'staff',
            ]);
        }

        // ===============================
        // STAFF EKSTERNAL
        // ===============================
        $exStaff = [
            [
                'identifier' => 'EX-001',
                'name' => 'RUDYONO',
                'email' => 'EX-001@noemail.com',
                'division' => 'UBT',
                'department' => 'UBT',
                'position' => 'Koordinator UBT',
                'password' => bcrypt('EX-001'),
            ],
            [
                'identifier' => 'EX-002',
                'name' => 'MAXIMILLIAN',
                'email' => 'EX-002@noemail.com',
                'division' => 'Roemah54',
                'department' => 'Roemah54',
                'position' => 'Koordinator Roemah54',
                'password' => bcrypt('EX-002'),
            ],
            [
                'identifier' => 'EX-003',
                'name' => 'AYEN',
                'email' => 'EX-003@noemail.com',
                'division' => 'Lab Bunda Thamrin',
                'department' => 'Lab Bunda Thamrin',
                'position' => 'Koordinator Lab Bunda Thamrin',
                'password' => bcrypt('EX-003'),
            ],
            [
                'identifier' => 'EX-004',
                'name' => 'APHING',
                'email' => 'EX-004@noemail.com',
                'division' => 'Lab Bunda Thamrin',
                'department' => 'Lab Bunda Thamrin',
                'position' => 'Koordinator Lab Bunda Thamrin',
                'password' => bcrypt('EX-004'),
            ],
            [
                'identifier' => 'EX-005',
                'name' => 'DITA',
                'email' => 'EX-005@noemail.com',
                'division' => 'Lab Bunda Thamrin',
                'department' => 'Lab Bunda Thamrin',
                'position' => 'Operator Lab Bunda Thamrin',
                'password' => bcrypt('EX-005'),
            ],
            [
                'identifier' => 'EX-006',
                'name' => 'ISNAN SARWO PRASETYO',
                'email' => 'EX-006@noemail.com',
                'division' => 'UBT',
                'department' => 'UBT',
                'position' => 'Operator UBT',
                'password' => bcrypt('EX-006'),
            ],
            [
                'identifier' => 'EX-007',
                'name' => 'SYAFITRI ARIYA',
                'email' => 'EX-007@noemail.com',
                'division' => 'Roemah54',
                'department' => 'Roemah54',
                'position' => 'Operator Roemah54',
                'password' => bcrypt('EX-001'),
            ],
        ];

        foreach ($exStaff as $e) {
            $user = User::updateOrCreate(
                ['identifier' => $e['identifier']],
                array_merge($e, ['photo' => null])
            );

            $user->syncRoles([
                'staff-external',
            ]);
        }
    }
}
