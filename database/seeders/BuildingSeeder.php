<?php

namespace Database\Seeders;

use App\Models\MasterData\Building;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BuildingSeeder extends Seeder
{
    public function run(): void
    {
        $branchId = 1;

        $buildings = [
            'Gedung I'   => 7,
            'Gedung II'  => 8,
            'Gedung III' => 9,
            'Gedung IV'  => 11,
            'Gedung V'   => 10,
        ];

        DB::transaction(function () use ($buildings, $branchId) {
            foreach ($buildings as $name => $floorsCount) {
                Building::updateOrCreate(
                    [
                        'branch_id' => $branchId,
                        'name' => $name,
                    ],
                    [
                        'floors_count' => $floorsCount,
                    ]
                );
            }
        });
    }
}
