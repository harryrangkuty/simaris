<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MasterData\Unit;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('data/master_units.xlsx');
        $data = Excel::toArray([], $path);

        $rows = $data[0];
        array_shift($rows); // buang header

        foreach ($rows as $index => $row) {
            try {
                $unitName = trim((string) ($row[0] ?? ''));
                $department = trim((string) ($row[1] ?? ''));

                if ($unitName === '') {
                    throw new \Exception('Nama unit kosong');
                }

                Unit::updateOrCreate(
                    ['name' => $unitName],
                    [
                        'department' => $department !== '' ? $department : null,
                    ]
                );

            } catch (\Throwable $th) {
                Log::error(
                    '❌ Gagal import unit baris ke-'.($index + 1).' : '.$th->getMessage(),
                    ['row' => $row]
                );
            }
        }
    }
}