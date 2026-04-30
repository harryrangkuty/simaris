<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MasterData\DepreciationGroup;

class DepreciationGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $depreciations = [
            [
                'code' => 'K1',
                'name' => 'Kelompok 1',
                'lifespan_months' => 48,
                'method' => 'straight_line',
            ],
            [
                'code' => 'K2',
                'name' => 'Kelompok 2',
                'lifespan_months' => 96,
                'method' => 'straight_line',
            ],
            [
                'code' => 'K3',
                'name' => 'Kelompok 3',
                'lifespan_months' => 192,
                'method' => 'straight_line',
            ],
            [
                'code' => 'K4',
                'name' => 'Kelompok 4',
                'lifespan_months' => 240,
                'method' => 'straight_line',
            ],
        ];

        foreach ($depreciations as $d) {
            DepreciationGroup::updateOrCreate(
                ['code' => $d['code']],
                $d
            );
        }
    }
}
