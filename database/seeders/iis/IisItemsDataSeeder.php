<?php

namespace Database\Seeders\iis;

use Illuminate\Database\Seeder;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Iis\IisItem;
use Illuminate\Support\Facades\DB;

class IisItemsDataSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('data/iis_master_items_perbekalan.xlsx');
        $rows = Excel::toArray([], $path)[0];

        DB::transaction(function () use ($rows) {
            foreach ($rows as $index => $row) {
                if ($index === 0) continue;

                IisItem::updateOrCreate(
                    [
                        'item_no' => $row[0],
                    ],
                    [
                        'name'   => $row[1],
                        // 'generic_name'          => $row[2],
                        'buying_price'          => (float) ($row[3] ?? 0),
                        'is_sell'               => $this->excelYNToBool($row[4] ?? null),
                        'is_buy'                => $this->excelYNToBool($row[5] ?? null),
                        'stock'                 => $this->excelYNToBool($row[6] ?? null),
                        'onhand'                => $row[7] ?? null,
                        'hpp'                   => (float) ($row[8] ?? 0),
                        'asset'                 => (float) ($row[9] ?? 0),
                    ]
                );
            }
        });
    }

    private function excelYNToBool($value): bool
    {
        return strtoupper(trim((string) $value)) === 'Y';
    }
}
