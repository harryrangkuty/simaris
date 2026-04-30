<?php

namespace Database\Seeders\iis;

use App\Models\Iis\IisAlkes;
use App\Models\MasterData\Building;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class IisAlkesDataLentiSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('data/iis_data_alkes_from_lenti.xlsx');
        $rows = Excel::toArray([], $path)[0];

        // CEK DOUBLE
        // $barcodes = [];

        // foreach ($rows as $index => $row) {
        //     if ($index === 0) continue;

        //     $barcode = $row[0];

        //     if (in_array($barcode, $barcodes)) {
        //         dump("DUPLICATE: $barcode di row $index");
        //     } else {
        //         $barcodes[] = $barcode;
        //     }
        // }

        DB::transaction(function () use ($rows) {
            foreach ($rows as $index => $row) {
                if ($index === 0) {
                    continue;
                }

                $buildingId = $this->extractBuildingId($row[2], 1);
                $floor = $this->extractFloor($row[2]);

                IisAlkes::updateOrCreate(
                    ['qr_code_no' => $row[0]],
                    [
                        'branch_id' => 1,
                        'description' => $row[1],
                        'position_legacy' => $row[2],
                        'building_id' => $buildingId,
                        'floor' => $floor,
                        'room_id' => $this->extractRoomId($row[2], $buildingId, $floor),
                        'data_source' => 'legacy_iis',
                        'created_by' => 1,
                        'updated_by' => 1,
                        'etc' => [
                            ['key' => 'Serial Number', 'value' => $row[5]],
                            ['key' => 'Merek', 'value' => $row[3]],
                            ['key' => 'Type', 'value' => $row[4]],
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

    private function extractRoomId(?string $text, ?int $buildingId, ?string $floor): ?int
    {
        if (! $text) {
            return null;
        }

        $text = strtoupper($text);

        // 1. Ambil nomor dalam kurung (543A → 543)
        if (preg_match('/\((\d+)[A-Z]?\)/', $text, $m)) {
            $roomNumber = $m[1]; // 543

            return $this->findOrCreateRoom($roomNumber, $buildingId, $floor);
        }

        // 2. fallback: mapping keyword
        $map = [
            'R.I' => 'Rawat Inap',
            'RI' => 'Rawat Inap',
            'RAWAT INAP' => 'Rawat Inap',
            'IGD' => 'IGD',
            'OK' => 'OK',
            'LAB' => 'Lab',
        ];

        foreach ($map as $key => $roomName) {
            if (str_contains($text, $key)) {
                return $this->findOrCreateRoom($roomName, $buildingId, $floor);
            }
        }

        return null;
    }

    private function findOrCreateRoom(string $name, ?int $buildingId, ?string $floor): ?int
    {
        $room = \App\Models\MasterData\Room::where('name', $name)
            ->when($buildingId, fn ($q) => $q->where('building_id', $buildingId))
            ->when($floor, fn ($q) => $q->where('floor', $floor))
            ->first();

        if ($room) {
            return $room->id;
        }

        $newRoom = \App\Models\MasterData\Room::create([
            'name' => $name,
            'building_id' => $buildingId,
            'floor' => $floor,
            'registered_at' => now(),
            'is_lab' => false,
            'legacy_id' => null,
            'code' => null,
            'person_in_charge_id' => null,
        ]);

        dump("CREATE ROOM: {$name} | Gedung {$buildingId} | Lantai {$floor}");

        return $newRoom->id;
    }
}
