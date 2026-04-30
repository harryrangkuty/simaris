<?php

namespace Database\Seeders;

use App\Models\MasterData\Item;
use App\Models\MasterData\ItemSequence;
use App\Models\MasterData\Uom;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class ItemSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('data/master_items.xlsx');
        $data = Excel::toArray([], $path);

        // ================================
        // Ambil sheet pertama
        // ================================
        $rows = $data[0];
        array_shift($rows); // buang header

        /**
         * =====================================================
         * STEP 1 — BUAT ITEM SEQUENCE DULU (GROUP BY COL 3)
         * =====================================================
         * Kolom 3 = ALK, ATK, COM, dll
         */
        $sequencePrefixes = collect($rows)
            ->pluck(3)
            ->filter(fn ($v) => trim((string) $v) !== '')
            ->map(fn ($v) => strtoupper(trim((string) $v)))
            ->unique()
            ->values();

        foreach ($sequencePrefixes as $prefix) {
            ItemSequence::firstOrCreate(
                ['prefix' => $prefix],
                ['last_number' => 0]
            );
        }

        /**
         * =====================================================
         * STEP 2 — SEED ITEMS + GENERATE CODE
         * =====================================================
         */
        foreach ($rows as $index => $row) {

            Log::info('LEGACY CHECK', [
                'row_index' => $index + 1,
                'legacy' => trim((string) $row[0]),
                'prefix' => trim((string) ($row[3] ?? '')),
            ]);

            DB::beginTransaction();

            try {
                // ================================
                // UOM
                // ================================
                $uomCode = strtoupper(trim((string) ($row[2] ?? '')));
                $uomExists = $uomCode !== ''
                    ? Uom::where('code', $uomCode)->exists()
                    : false;

                // ================================
                // SEQUENCE PREFIX
                // ================================
                $sequenceCode = strtoupper(trim((string) ($row[3] ?? '')));

                if ($sequenceCode === '') {
                    throw new \Exception('Sequence code kosong');
                }

                // ================================
                // LOCK SEQUENCE
                // ================================
                $sequence = ItemSequence::lockForUpdate()
                    ->where('prefix', $sequenceCode)
                    ->first();

                if (! $sequence) {
                    throw new \Exception("Sequence {$sequenceCode} tidak ditemukan");
                }

                // ================================
                // GENERATE CODE ITEM
                // ================================
                $nextNumber = $sequence->last_number + 1;
                $newItemCode = $sequenceCode.str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

                // ================================
                // SIMPAN ITEM
                // ================================

                $rawDep = strtoupper(trim((string) ($row[6] ?? '')));

                $allowed = ['K1', 'K2', 'K3', 'K4'];

                Item::updateOrCreate(
                    ['code_legacy' => trim((string) $row[0])],
                    [
                        'code' => $newItemCode,
                        'name' => trim((string) $row[1]),
                        'uom_code' => $uomExists ? $uomCode : null,
                        'stock_code' => trim((string) ($row[4] ?? '')),
                        'category_code' => trim((string) ($row[5] ?? '')),

                        'depreciation_group_code' => in_array($rawDep, $allowed, true)
                            ? $rawDep
                            : null,
                        'type' => strtoupper(trim((string) ($row[7] ?? ''))) === 'PERSEDIAAN'
                            ? 'inventory'
                            : 'asset',
                        'editor_id' => 1,
                        'is_active' => true,
                    ]
                );

                // ================================
                // UPDATE SEQUENCE
                // ================================
                $sequence->update([
                    'last_number' => $nextNumber,
                ]);

                DB::commit();

            } catch (\Throwable $th) {
                DB::rollBack();
                Log::error(
                    '❌ Gagal import baris ke-'.($index + 1).' : '.$th->getMessage(),
                    ['row' => $row]
                );
            }
        }
    }
}
