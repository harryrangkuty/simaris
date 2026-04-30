<?php

namespace Database\Seeders\iis;

use App\Models\Iis\IisAlkes;
use App\Models\MasterData\Building;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class IisAlkesDataSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('data/iis_data_alkes_from_db_android.xlsx');
        $rows = Excel::toArray([], $path)[0];

        DB::transaction(function () use ($rows) {
            foreach ($rows as $index => $row) {
                if ($index === 0) {
                    continue;
                }

                IisAlkes::updateOrCreate(
                    ['qr_code_no' => $row[0]],
                    [
                        'branch_id'        => 1,
                        'item_no_legacy'   => $row[1],
                        'description'      => $row[2],
                        'position_legacy'  => $row[3],
                        'building_id'      => $this->extractBuildingId($row[3], 1),
                        'floor'            => $this->extractFloor($row[3]),

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
     * Ambil GEDUNG dari text legacy
     * Contoh:
     * - G3/06/R.I
     * - GEDUNG II LT.1
     */
    private function extractBuildingId(?string $text, int $branchId = 1): ?int
    {
        if (! $text) {
            return null;
        }

        $text = strtoupper($text);

        // G3 / GEDUNG III
        if (
            preg_match('/\bG\s*([IVX]+|\d+)\b/', $text, $m) ||
            preg_match('/GEDUNG\s*([IVX]+|\d+)/', $text, $m)
        ) {
            $raw = $m[1];

            if (is_numeric($raw)) {
                $raw = $this->toRoman((int) $raw);
            }

            $name = 'Gedung '.$raw;

            return Building::where('branch_id', $branchId)
                ->where('name', $name)
                ->value('id');
        }

        return null;
    }

    /**
     * NORMALISASI LANTAI (FINAL)
     * - BASEMENT → "BASEMENT"
     * - selain itu ambil ANGKA SAJA
     * - tidak ada G / GF / GROUND
     */
    private function extractFloor(?string $text): ?string
    {
        if (! $text) {
            return null;
        }

        $text = strtoupper($text);

        if (str_contains($text, 'BASEMENT') || preg_match('/\bB\d*\b/', $text)) {
            return 'BASEMENT';
        }

        // LT.7 / LANTAI 7 / /07/
        if (
            preg_match('/\bLT\.?\s*(\d+)\b/', $text, $m) ||
            preg_match('/\/(\d{1,2})\//', $text, $m)
        ) {
            return ltrim($m[1], '0'); // 07 → 7
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
