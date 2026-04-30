<?php

namespace Database\Seeders\iis;

use App\Models\Iis\IisInventory;
use App\Models\MasterData\Building;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class IisInventoriesDataSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('data/iis_daftar_inventaris.xlsx');
        $rows = Excel::toArray([], $path)[0];

        DB::transaction(function () use ($rows) {
            foreach ($rows as $index => $row) {
                if ($index === 0) {
                    continue;
                }

                IisInventory::updateOrCreate(
                    ['qr_code_no' => $row[0]],
                    [
                        'branch_id'        => 1,
                        'category_name'    => $row[1],
                        'description'      => $row[2],
                        'building_id'      => $this->mapBuildingId($row[4], 1),
                        'floor'            => $this->normalizeFloor($row[5]),
                        'unit_legacy'      => $row[6],
                        'room_legacy'      => $row[7] ?? null,
                        'pj_nik'           => $row[8] ?? null,
                        'condition'        => $row[11],
                        'asset_number'     => $row[12],
                        'iis_operator'     => $row[13] ?? null,
                        'data_source'      => 'legacy_iis',
                        'created_by'       => 1,
                        'updated_by'       => 1,
                        'etc' => [
                            ['key' => 'Serial Number', 'value' => null],
                            ['key' => 'Merek', 'value' => null],
                        ],
                    ]
                );
            }
        });
    }

    /**
     * Mapping gedung
     */
    private function mapBuildingId($rawBuilding, $branchId = 1): ?int
    {
        if (! $rawBuilding) {
            return null;
        }

        $raw = trim((string) $rawBuilding);

        // angka → Gedung I, II, III
        if (is_numeric($raw)) {
            $name = 'Gedung '.$this->toRoman((int) $raw);
        } else {
            $name = ucfirst(strtolower($raw));
        }

        return Building::where('branch_id', $branchId)
            ->where('name', $name)
            ->value('id');
    }

    /**
     * NORMALISASI LANTAI (FINAL)
     * - BASEMENT = "BASEMENT"
     * - selain itu ambil ANGKA SAJA
     * - tidak ada G / GF / GROUND
     */
    private function normalizeFloor($raw): ?string
    {
        if (! $raw) {
            return null;
        }

        $raw = strtoupper(trim((string) $raw));

        if (str_contains($raw, 'BASEMENT') || str_starts_with($raw, 'B')) {
            return 'BASEMENT';
        }

        if (preg_match('/\b(\d{1,2})\b/', $raw, $m)) {
            return ltrim($m[1], '0');
        }

        return null;
    }

    private function toRoman(int $number): string
    {
        return [
            1 => 'I', 2 => 'II', 3 => 'III',
            4 => 'IV', 5 => 'V', 6 => 'VI',
            7 => 'VII', 8 => 'VIII',
            9 => 'IX', 10 => 'X',
        ][$number] ?? (string) $number;
    }
}
