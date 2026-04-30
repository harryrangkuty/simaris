<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use Illuminate\Database\Seeder;
use Database\Seeders\iis\IisItemsDataSeeder;
use Database\Seeders\iis\IisCategoriesDataSeeder;
use Database\Seeders\iis\IisInventoriesDataSeeder;
use Database\Seeders\iis\IisAlkesDataSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            ApplicationMenuSeeder::class,
            UnitSeeder::class,
            MasterUserSeeder::class,
            TransactionTypeSeeder::class,
            ConfigurationSeeder::class,
            SupplierSeeder::class,
            StockCodeSeeder::class,
            BranchSeeder::class,
            WarehouseSeeder::class,
            BuildingSeeder::class,
            DepreciationGroupSeeder::class,
            CategorySeeder::class,
            UomSeeder::class,
            ItemSeeder::class,
            IisItemsDataSeeder::class,
            IisCategoriesDataSeeder::class,
            IisInventoriesDataSeeder::class,
            IisAlkesDataSeeder::class,
        ]);
    }
}
