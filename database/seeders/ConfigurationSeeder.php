<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Configuration;

class ConfigurationSeeder extends Seeder
{
    public function run()
    {
        $configs = [
            [
                'key' => 'input_distribution',
                'label' => 'Distribution Input',
                'value' => '1',
                'message' => '-',
            ],
            [
                'key' => 'input_maintenance',
                'label' => 'Maintenance Input',
                'value' => '0',
                'message' => 'Maintenance input period has ended. Please contact the Asset Bureau.',
            ],
            [
                'key' => 'input_deletion',
                'label' => 'Deletion Input',
                'value' => '0',
                'message' => 'Deletion input period has not started yet.',
            ],
            [
                'key' => 'input_procurement',
                'label' => 'Procurement Input',
                'value' => '1',
                'message' => '-',
            ],
            [
                'key' => 'available_fiscal_year',
                'label' => 'Available Fiscal Year',
                'value' => '2024,2025',
                'message' => '-',
            ],
        ];

        foreach ($configs as $config) {
            Configuration::updateOrCreate(
                ['key' => $config['key']],
                $config
            );
        }
    }
}
