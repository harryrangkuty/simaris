<?php

namespace Database\Seeders\iis;

use Illuminate\Database\Seeder;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Iis\IisCategory;
use Illuminate\Support\Facades\DB;

class IisCategoriesDataSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('data/iis_daftar_kategori.xlsx');

        if (!file_exists($path)) {
            $this->command->error("File tidak ditemukan: {$path}");
            return;
        }

        $rows = Excel::toArray([], $path)[0];

        DB::transaction(function () use ($rows) {
            foreach ($rows as $index => $row) {
                // Skip header
                if ($index === 0) {
                    continue;
                }

                // Validasi minimal
                if (empty($row[0])) {
                    continue;
                }

                IisCategory::updateOrCreate(
                    [
                        'category_name' => trim($row[0]),
                    ],
                );
            }
        });
    }
}
