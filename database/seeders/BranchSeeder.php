<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\MasterData\Branch;

class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $branches = [
            [
                'code' => 'RSBT-MDN',
                'name' => 'RSU Bunda Thamrin Medan',
                'address' => 'Jl. Sei Batang Hari No.28-30-42, Babura Sunggal, Kec. Medan Sunggal, Kota Medan, Sumatera Utara 20112',
            ],
            [
                'code' => 'RSBT-ACH',
                'name' => 'RSU Bunda Thamrin Aceh',
                'address' => 'Jl. Dr. Mr. T. H. Mohd Hasan, Batoh, Kecamatan Banda Raya, Kota Banda Aceh, 23122',
            ],
        ];

        foreach ($branches as $branch) {
            Branch::updateOrCreate(
                ['code' => $branch['code']],
                $branch
            );
        }
    }
}
