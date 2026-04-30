<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MasterData\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'code' => '200',
                'name' => 'AKTIVA TETAP',
                'is_active' => true,
            ],
            [
                'code' => '200.01',
                'name' => 'TANAH',
                'is_active' => true,
            ],
            [
                'code' => '200.02',
                'name' => 'BANGUNAN',
                'is_active' => true,
            ],
            [
                'code' => '200.03',
                'name' => 'SARANA DAN PRASARANA',
                'is_active' => true,
            ],
            [
                'code' => '200.04',
                'name' => 'MESIN DAN PERALATAN',
                'is_active' => true,
            ],
            [
                'code' => '200.05',
                'name' => 'ALAT-ALAT MEDIS',
                'is_active' => true,
            ],
            [
                'code' => '200.06',
                'name' => 'KENDARAAN',
                'is_active' => true,
            ],
            [
                'code' => '200.07',
                'name' => 'INVENTARIS AC',
                'is_active' => true,
            ],
            [
                'code' => '200.08',
                'name' => 'INVENTARIS KOMPUTER',
                'is_active' => true,
            ],
            [
                'code' => '200.09',
                'name' => 'INVENTARIS PERABOTAN',
                'is_active' => true,
            ],
            [
                'code' => '200.10',
                'name' => 'INVENTARIS PERLENGKAPAN KANTOR',
                'is_active' => true,
            ],
            [
                'code' => '200.11',
                'name' => 'INVENTARIS PERLENGKAPAN DAPUR',
                'is_active' => true,
            ],
            [
                'code' => '200.12',
                'name' => 'INVENTARIS LAUNDRY',
                'is_active' => true,
            ],
            [
                'code' => '200.13',
                'name' => 'INVENTARIS GORDYN',
                'is_active' => true,
            ],
            [
                'code' => '200.14',
                'name' => 'PERALATAN LAINNYA',
                'is_active' => true,
            ],
            [
                'code' => '800.022',
                'name' => 'BY. OKSIGEN,UDARA TRKAN DAN N2O',
                'is_active' => true,
            ],
            [
                'code' => '800.031',
                'name' => 'BY. MAKAN PASIEN',
                'is_active' => true,
            ],
            [
                'code' => '800.055',
                'name' => 'BY. SUPPLIES ADMINISTRASI',
                'is_active' => true,
            ],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['code' => $category['code']],
                $category
            );
        }
    }
}
