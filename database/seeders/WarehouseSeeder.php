<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MasterData\Warehouse;
use Illuminate\Support\Facades\DB;

class WarehouseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // daftar gudang
        $warehouses = [
            // Medan
            [
                'code' => 'GD-PBK',
                'name' => 'Gudang Perbekalan',
                'branch_id' => 1,
                'address' => 'Jl. Sei Batang Hari No.28-30-42, Babura Sunggal, Kec. Medan Sunggal, Kota Medan, Sumatera Utara 20112',
                'description' => 'Gudang utama untuk penyimpanan dan distribusi aset serta perbekalan',
                'person_in_charge_id' => 1,
                'editor_id' => 1,
                'stock_codes' => ['160.03', '160.04'], // relasi ke pivot
            ],
            [
                'code' => 'GD-GZI',
                'name' => 'Gudang Gizi',
                'branch_id' => 1,
                'address' => 'Jl. Sei Batang Hari No.28-30-42, Babura Sunggal, Kec. Medan Sunggal, Kota Medan, Sumatera Utara 20112',
                'description' => 'Gudang untuk penyimpanan bahan gizi',
                'person_in_charge_id' => 1,
                'editor_id' => 1,
                'stock_codes' => ['160.05'],
            ],
            [
                'code' => 'GD-TBG',
                'name' => 'Gudang Tabung',
                'branch_id' => 1,
                'address' => 'Jl. Sei Batang Hari No.28-30-42, Babura Sunggal, Kec. Medan Sunggal, Kota Medan, Sumatera Utara 20112',
                'description' => 'Gudang tabung untuk penyimpanan dan pendistribusian tabung gas',
                'person_in_charge_id' => 1,
                'editor_id' => 1,
                'stock_codes' => ['160.06'],
            ],
            // Aceh
            [
                'code' => 'GD-PBK-ACH',
                'name' => 'Gudang Perbekalan',
                'branch_id' => 2,
                'address' => 'JJl. Dr. Mr. T. H. Mohd Hasan, Batoh, Kecamatan Banda Raya, Kota Banda Aceh, 23122',
                'description' => 'Gudang utama untuk penyimpanan dan distribusi aset serta perbekalan',
                'person_in_charge_id' => 1,
                'editor_id' => 1,
                'stock_codes' => ['160.03', '160.04'], // relasi ke pivot
            ],
            [
                'code' => 'GD-GZI-ACH',
                'name' => 'Gudang Gizi',
                'branch_id' => 2,
                'address' => 'JJl. Dr. Mr. T. H. Mohd Hasan, Batoh, Kecamatan Banda Raya, Kota Banda Aceh, 23122',
                'description' => 'Gudang untuk penyimpanan bahan gizi',
                'person_in_charge_id' => 1,
                'editor_id' => 1,
                'stock_codes' => ['160.05'],
            ],
            [
                'code' => 'GD-TBG-ACH',
                'name' => 'Gudang Tabung',
                'branch_id' => 2,
                'address' => 'JJl. Dr. Mr. T. H. Mohd Hasan, Batoh, Kecamatan Banda Raya, Kota Banda Aceh, 23122',
                'description' => 'Gudang tabung untuk penyimpanan dan pendistribusian tabung gas',
                'person_in_charge_id' => 1,
                'editor_id' => 1,
                'stock_codes' => ['160.06'],
            ],
        ];

        foreach ($warehouses as $data) {
            // Buat atau update warehouse
            $warehouse = Warehouse::updateOrCreate(
                ['code' => $data['code']],
                collect($data)->except('stock_codes')->toArray()
            );

            // Hubungkan ke stock codes lewat pivot
            if (!empty($data['stock_codes'])) {
                foreach ($data['stock_codes'] as $stockCode) {
                    DB::table('warehouse_stock_codes')->updateOrInsert(
                        [
                            'warehouse_id' => $warehouse->id,
                            'stock_code'   => $stockCode,
                        ],
                        [
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }
            }
        }
    }
}
