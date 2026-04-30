<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\MasterData\StockCode;

class StockCodeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $stockCodes = [
            [
                'code' => '160.03',
                'name' => 'Stock Supplies',
                'description' => 'Persediaan barang dari supplier untuk operasional atau produksi'
            ],
            [
                'code' => '160.04',
                'name' => 'Stock Inventaris',
                'description' => 'Barang inventaris atau aset tetap perusahaan'
            ],
            [
                'code' => '160.05',
                'name' => 'Stock Gizi',
                'description' => 'Persediaan bahan atau produk terkait gizi / makanan'
            ],
            [
                'code' => '160.06',
                'name' => 'Stock Tabung Gas',
                'description' => 'Persediaan tabung gas untuk keperluan operasional'
            ],
        ];

        foreach ($stockCodes as $sc) {
            StockCode::updateOrCreate(['code' => $sc['code']], $sc);
        }
    }
}
