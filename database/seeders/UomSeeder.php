<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MasterData\Uom;

class UomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $uoms = [
            ['code' => 'PCS', 'name' => 'Pcs'],
            ['code' => 'SET', 'name' => 'Set'],
            ['code' => 'UNIT', 'name' => 'Unit'],
            ['code' => 'BOX', 'name' => 'Box'],
            ['code' => 'BOTOL', 'name' => 'Botol'],
            ['code' => 'ROLL', 'name' => 'Roll'],
            ['code' => 'RIM', 'name' => 'Rim'],
            ['code' => 'PACK', 'name' => 'Pack'],
            ['code' => 'KOTAK', 'name' => 'Kotak'],
            ['code' => 'BUAH', 'name' => 'Buah'],
            ['code' => 'LEMBAR', 'name' => 'Lembar'],
            ['code' => 'BUNGKUS', 'name' => 'Bungkus'],
            ['code' => 'JERIGEN', 'name' => 'Jerigen'],
            ['code' => 'LITER', 'name' => 'Liter'],
            ['code' => 'KG', 'name' => 'Kilogram'],
            ['code' => 'G', 'name' => 'Gram'],
            ['code' => 'MG', 'name' => 'Miligram'],
            ['code' => 'M', 'name' => 'Meter'],
            ['code' => 'CM', 'name' => 'Centimeter'],
            ['code' => 'MM', 'name' => 'Milimeter'],
            ['code' => 'M2', 'name' => 'Meter Persegi'],
            ['code' => 'M3', 'name' => 'Meter Kubik'],
            ['code' => 'BATANG', 'name' => 'Batang'],
            ['code' => 'BAL', 'name' => 'Bal'],
            ['code' => 'PAIL', 'name' => 'Pail'],
            ['code' => 'SAK', 'name' => 'Sak'],
            ['code' => 'GULUNG', 'name' => 'Gulung'],
            ['code' => 'MATA', 'name' => 'Mata'],
            ['code' => 'PASANG', 'name' => 'Pasang'],
            ['code' => 'PAK', 'name' => 'Pak'],
            ['code' => 'KEPING', 'name' => 'Keping'],
            ['code' => 'BLOK', 'name' => 'Blok'],
            ['code' => 'ROL', 'name' => 'Rol'],
            ['code' => 'GALON', 'name' => 'Galon'],
            ['code' => 'KARTON', 'name' => 'Karton'],
            ['code' => 'TABUNG', 'name' => 'Tabung'],
            ['code' => 'BUKU', 'name' => 'Buku'],
            ['code' => 'HELAI', 'name' => 'Helai'],
            ['code' => 'LUSIN', 'name' => 'Lusin'],
            ['code' => 'DOS', 'name' => 'Dos'],
            ['code' => 'BKS', 'name' => 'Bks'],
            ['code' => 'BTL', 'name' => 'Btl'],
            ['code' => 'KTK', 'name' => 'Ktk'],
            ['code' => 'PLT', 'name' => 'Pallet'],
            ['code' => 'CM3', 'name' => 'Centimeter Kubik'],
            ['code' => 'ML', 'name' => 'Mililiter'],
            ['code' => 'ONS', 'name' => 'Ons'],
            ['code' => 'TON', 'name' => 'Ton'],
            ['code' => 'KWINTAL', 'name' => 'Kwintal'],
            ['code' => 'MG2', 'name' => 'Miligram Persegi'],
            ['code' => 'LBR', 'name' => 'Lbr'],
            ['code' => 'LTR', 'name' => 'Ltr'],
            ['code' => 'POTONG', 'name' => 'Potong'],
            ['code' => 'PAKET', 'name' => 'Paket'],
            ['code' => 'BIDANG', 'name' => 'Bidang'],
            ['code' => 'SETR', 'name' => 'Setir'],
            ['code' => 'LUS', 'name' => 'Lusin'],
            ['code' => 'GRS', 'name' => 'Gross'],
            ['code' => 'KLG', 'name' => 'Kaleng'],
            ['code' => 'PSG', 'name' => 'Pasang'],
            ['code' => 'RLS', 'name' => 'Rolls'],
            ['code' => 'BTG', 'name' => 'Batang'],
            ['code' => 'CM2', 'name' => 'Centimeter Persegi'],
        ];

        foreach ($uoms as $uom) {
            Uom::updateOrCreate(
                ['code' => $uom['code']],
                [
                    'name' => $uom['name'],
                    'is_active' => true,
                ]
            );
        }
    }
}
