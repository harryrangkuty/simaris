<?php

use App\Http\Controllers\ApplicationMenuController;
use App\Http\Controllers\SetTahunKerjaController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\LookUpController;
use App\Http\Controllers\MyInventoriesController;

use App\Http\Controllers\Administrator\ConfigurationController;
use App\Http\Controllers\Administrator\PermissionController;
use App\Http\Controllers\Administrator\RoleController;
use App\Http\Controllers\Administrator\UserController;

use App\Http\Controllers\MasterData\BranchController;
use App\Http\Controllers\MasterData\CategoryController;
use App\Http\Controllers\MasterData\DepreciationGroupController;
use App\Http\Controllers\MasterData\ItemController;
use App\Http\Controllers\MasterData\RoomController;
use App\Http\Controllers\MasterData\StockCodeController;
use App\Http\Controllers\MasterData\SupplierController;
use App\Http\Controllers\MasterData\TransactionTypeController;
use App\Http\Controllers\MasterData\UnitController;
use App\Http\Controllers\MasterData\UomController;
use App\Http\Controllers\MasterData\WarehouseController;
use App\Http\Controllers\MasterData\BuildingController;

use App\Http\Controllers\Asset\AsetCariController;
use App\Http\Controllers\Asset\AsetPenghapusanController;
use App\Http\Controllers\Asset\AsetRuangController;
use App\Http\Controllers\Asset\AssetProfileController;

use App\Http\Controllers\Inventory\BarangController;
use App\Http\Controllers\Inventory\GudangController;
use App\Http\Controllers\Inventory\PersediaanKeluarController;
use App\Http\Controllers\Inventory\PersediaanMasukController;
use App\Http\Controllers\Inventory\PersediaanMutasiController;
use App\Http\Controllers\Inventory\PersediaanOrderMasukController;
use App\Http\Controllers\Inventory\PersediaanOrderMutasiController;

use App\Http\Controllers\Iis\IisInventoriesController;
use App\Http\Controllers\Iis\IisAlkesController;
use App\Http\Controllers\Iis\IisACController;
use App\Http\Controllers\Iis\IisQrCodeHandoverController;
use App\Http\Controllers\Iis\IisCategoriesController;
use App\Http\Controllers\Iis\IisItemsController;
use App\Http\Controllers\Iis\MyIisInventoriesController;
use App\Http\Controllers\Iis\IisDistributionController;
use App\Http\Controllers\Iis\IisMutationController;
use App\Http\Controllers\Iis\IisReturnController;
use App\Http\Controllers\Iis\IisBorrowController;
use App\Http\Controllers\Iis\IisBorrowReturnController;

use App\Http\Controllers\Procurement\ProcurementController;
use App\Http\Controllers\Procurement\ProcurementRequestController;

use App\Http\Controllers\Report\BmnController;
use App\Http\Controllers\Report\BmnOldController;
use App\Http\Controllers\Report\HistoryController;
use App\Http\Controllers\Report\KdpReportController;
use App\Http\Controllers\Report\KdpReportTransaksiController;
use App\Http\Controllers\Report\KinerjaController;
use App\Http\Controllers\Report\NeracaController;
use App\Http\Controllers\Report\PenyusutanController;
use App\Http\Controllers\Report\TransaksiController;

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/profil/access', [AssetProfileController::class, 'profil'])->name('access.profil');
Route::get('/list-dbr', [AsetRuangController::class, 'list_dbr'])->name('list-dbr');
Route::get('/test', [TestController::class, '__invoke'])->name('test');

// GUEST
Route::middleware(['guest'])->group(function () {
    Route::view('/', 'layouts.landing')->name('landing');
    // Login
    Route::post('/login', [AuthController::class, 'authenticate'])->name('auth.authenticate');
});

// AUTHENTICATED USER
Route::middleware(['auth', 'check.menu'])->group(function () {
    // DEFAULT MENU
    Route::prefix('dashboard')->group(function () {
        Route::get('/', [HomeController::class, '__invoke'])->name('dashboard');
        Route::get('/read', [HomeController::class, 'read'])->name('dashboard.read');
    });

    Route::prefix('my-inventories')->group(function () {
        Route::get('/', [MyInventoriesController::class, '__invoke'])->name('my_inventories');
        Route::get('/read', [MyInventoriesController::class, 'read'])->name('my_inventories.read');
    });

    Route::prefix('iis/my-inventories')->group(function () {
        Route::get('/', [MyIisInventoriesController::class, '__invoke'])->name('iis.my_iis.inventories');
        Route::get('/read', [MyIisInventoriesController::class, 'read'])->name('iis.my_iis.inventories.read');
    });

    Route::prefix('user-profile')->group(function () {
        Route::get('/', [UserProfileController::class, '__invoke'])->name('user-profile');
        Route::get('/read', [UserProfileController::class, 'read'])->name('user-profile.read');
        Route::post('/write', [UserProfileController::class, 'write'])->name('user-profile.write');
    });

    // LOGOUT
    Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

    // LOOKUP/SEARCH_OPTION/ETC
    Route::prefix('lookups')->group(function () {
        Route::get('/users', [LookupController::class, 'users'])->name('lookups.users');
        Route::get('/suppliers', [LookupController::class, 'suppliers'])->name('lookups.suppliers');
        Route::get('/items', [LookupController::class, 'items'])->name('lookups.items');
        Route::get('/items', [LookupController::class, 'items'])->name('lookups.items');
        Route::get('/iis-available-handover-assets', [LookupController::class, 'iisAvailableHandoverAssets'])->name('lookups.iis_available_handover_assets');
        Route::get('/iis-available-distribution-assets', [LookupController::class, 'iisAvailableDistributionAssets'])->name('lookups.iis_available_distribution_assets');
        Route::get('/iis-available-mutation-and-return-assets', [LookupController::class, 'iisAvailableMutationAndReturnAssets'])->name('lookups.iis_available_mutation_and_return_assets');
        Route::get('/rooms', [LookupController::class, 'rooms'])->name('lookups.rooms');
    });

    // MASTER DATA
    Route::get('/branches', [BranchController::class, '__invoke'])->name('branches');
    Route::get('/branches/read', [BranchController::class, 'read'])->name('branches.read');
    Route::post('/branches/write', [BranchController::class, 'write'])->name('branches.write');

    Route::get('/buildings', [BuildingController::class, '__invoke'])->name('buildings');
    Route::get('/buildings/read', [BuildingController::class, 'read'])->name('buildings.read');
    Route::post('/buildings/write', [BuildingController::class, 'write'])->name('buildings.write');
    
    Route::get('/warehouses', [WarehouseController::class, '__invoke'])->name('warehouses');
    Route::get('/warehouses/read', [WarehouseController::class, 'read'])->name('warehouses.read');
    Route::post('/warehouses/write', [WarehouseController::class, 'write'])->name('warehouses.write');

    Route::get('/suppliers', [SupplierController::class, '__invoke'])->name('suppliers');
    Route::get('/suppliers/read', [SupplierController::class, 'read'])->name('suppliers.read');
    Route::post('/suppliers/write', [SupplierController::class, 'write'])->name('suppliers.write');

    Route::get('/transaction-types', [TransactionTypeController::class, '__invoke'])->name('transaction_types');
    Route::get('/transaction-types/read', [TransactionTypeController::class, 'read'])->name('transaction_types.read');
    Route::post('/transaction-types/write', [TransactionTypeController::class, 'write'])->name('transaction_types.write');

    Route::get('/stock-codes', [StockCodeController::class, '__invoke'])->name('stock_codes');
    Route::get('/stock-codes/read', [StockCodeController::class, 'read'])->name('stock_codes.read');
    Route::post('/stock-codes/write', [StockCodeController::class, 'write'])->name('stock_codes.write');

    Route::get('/categories', [CategoryController::class, '__invoke'])->name('categories');
    Route::get('/categories/read', [CategoryController::class, 'read'])->name('categories.read');
    Route::post('/categories/write', [CategoryController::class, 'write'])->name('categories.write');

    Route::get('/depreciation-groups', [DepreciationGroupController::class, '__invoke'])->name('depreciation_groups');
    Route::get('/depreciation-groups/read', [DepreciationGroupController::class, 'read'])->name('depreciation_groups.read');
    Route::post('/depreciation-groups/write', [DepreciationGroupController::class, 'write'])->name('depreciation_groups.write');

    Route::get('/uoms', [UomController::class, '__invoke'])->name('uoms');
    Route::get('/uoms/read', [UomController::class, 'read'])->name('uoms.read');
    Route::post('/uoms/write', [UomController::class, 'write'])->name('uoms.write');

    Route::get('/items', [ItemController::class, '__invoke'])->name('items');
    Route::get('/items/read', [ItemController::class, 'read'])->name('items.read');
    Route::post('/items/write', [ItemController::class, 'write'])->name('items.write');

    Route::get('/units', [UnitController::class, '__invoke'])->name('units');
    Route::get('/units/read', [UnitController::class, 'read'])->name('units.read');
    Route::post('/units/write', [UnitController::class, 'write'])->name('units.write');

    Route::get('/rooms', [RoomController::class, '__invoke'])->name('rooms');
    Route::get('/rooms/read', [RoomController::class, 'read'])->name('rooms.read');
    Route::post('/rooms/write', [RoomController::class, 'write'])->name('rooms.write');

    // MENU ADMINISTRATOR
    Route::get('/application-menus', [ApplicationMenuController::class, '__invoke'])->name('application-menus');
    Route::get('/application-menus/read', [ApplicationMenuController::class, 'read'])->name('application-menus.read');
    Route::post('/application-menus/write', [ApplicationMenuController::class, 'write'])->name('application-menus.write');

    Route::get('/users', [UserController::class, '__invoke'])->name('users');
    Route::get('/users/read', [UserController::class, 'read'])->name('users.read');
    Route::post('/users/write', [UserController::class, 'write'])->name('users.write');

    Route::get('/permissions', [PermissionController::class, '__invoke'])->name('permissions');
    Route::get('/permissions/read', [PermissionController::class, 'read'])->name('permissions.read');
    Route::post('/permissions/write', [PermissionController::class, 'write'])->name('permissions.write');

    Route::get('/roles', [RoleController::class, '__invoke'])->name('roles');
    Route::get('/roles/read', [RoleController::class, 'read'])->name('roles.read');
    Route::post('/roles/write', [RoleController::class, 'write'])->name('roles.write');

    Route::get('/configurations', [ConfigurationController::class, '__invoke'])->name('configurations');
    Route::get('/configurations/read', [ConfigurationController::class, 'read'])->name('configurations.read');
    Route::post('/configurations/write', [ConfigurationController::class, 'write'])->name('configurations.write');

    // MODUL PENGADAAN
    Route::prefix('procurements')->group(function () {
        Route::get('/', [ProcurementController::class, '__invoke'])->name('procurements');
        Route::get('/read', [ProcurementController::class, 'read'])->name('procurements.read');
        Route::post('/write', [ProcurementController::class, 'write'])->name('procurements.write');

        Route::get('/request', [ProcurementRequestController::class, '__invoke'])->name('procurements.request');
        Route::get('/request/read', [ProcurementRequestController::class, 'read'])->name('procurements.request.read');
        Route::post('/request/write', [ProcurementRequestController::class, 'write'])->name('procurements.request.write');
    });

    // MODUL ASET
    Route::prefix('asset')->middleware('check-mode')->group(function () {
        Route::get('/profil', [AssetProfileController::class, '__invoke'])->name('aset.profil');
        Route::get('/profil/read', [AssetProfileController::class, 'read'])->name('aset.profil.read');
        Route::post('/profil/write', [AssetProfileController::class, 'write'])->name('aset.profil.write');

        Route::get('/penghapusan', [AsetPenghapusanController::class, '__invoke'])->name('aset.penghapusan');
        Route::get('/penghapusan/read', [AsetPenghapusanController::class, 'read'])->name('aset.penghapusan.read');
        Route::post('/penghapusan/write', [AsetPenghapusanController::class, 'write'])->name('aset.penghapusan.write');

        Route::get('/ruang', [AsetRuangController::class, '__invoke'])->name('aset.ruang');
        Route::get('/ruang/read', [AsetRuangController::class, 'read'])->name('aset.ruang.read');
        Route::post('/ruang/write', [AsetRuangController::class, 'write'])->name('aset.ruang.write');

        Route::get('/cari', [AsetCariController::class, '__invoke'])->name('aset.cari');
        Route::get('/cari/read', [AsetCariController::class, 'read'])->name('aset.cari.read');
    });

    // MENU PERSEDIAAN
    Route::prefix('persediaan')->middleware('check-mode')->group(function () {
        Route::get('/gudang', [GudangController::class, '__invoke'])->name('persediaan.gudang');
        Route::get('/gudang/read', [GudangController::class, 'read'])->name('persediaan.gudang.read');
        Route::post('/gudang/write', [GudangController::class, 'write'])->name('persediaan.gudang.write');

        Route::get('/barang', [BarangController::class, '__invoke'])->name('persediaan.barang');
        Route::get('/barang/read', [BarangController::class, 'read'])->name('persediaan.barang.read');
        Route::post('/barang/write', [BarangController::class, 'write'])->name('persediaan.barang.write');

        Route::get('/order-masuk', [PersediaanOrderMasukController::class, '__invoke'])->name('persediaan.order-masuk');
        Route::get('/order-masuk/read', [PersediaanOrderMasukController::class, 'read'])->name('persediaan.order-masuk.read');
        Route::post('/order-masuk/write', [PersediaanOrderMasukController::class, 'write'])->name('persediaan.order-masuk.write');

        Route::get('/masuk', [PersediaanMasukController::class, '__invoke'])->name('persediaan.masuk');
        Route::get('/masuk/read', [PersediaanMasukController::class, 'read'])->name('persediaan.masuk.read');
        Route::post('/masuk/write', [PersediaanMasukController::class, 'write'])->name('persediaan.masuk.write');

        Route::get('/order-mutasi', [PersediaanOrderMutasiController::class, '__invoke'])->name('persediaan.order-mutasi');
        Route::get('/order-mutasi/read', [PersediaanOrderMutasiController::class, 'read'])->name('persediaan.order-mutasi.read');
        Route::post('/order-mutasi/write', [PersediaanOrderMutasiController::class, 'write'])->name('persediaan.order-mutasi.write');

        Route::get('/mutasi', [PersediaanMutasiController::class, '__invoke'])->name('persediaan.mutasi');
        Route::get('/mutasi/read', [PersediaanMutasiController::class, 'read'])->name('persediaan.mutasi.read');
        Route::post('/mutasi/write', [PersediaanMutasiController::class, 'write'])->name('persediaan.mutasi.write');

        Route::get('/keluar', [PersediaanKeluarController::class, '__invoke'])->name('persediaan.keluar');
        Route::get('/keluar/read', [PersediaanKeluarController::class, 'read'])->name('persediaan.keluar.read');
        Route::post('/keluar/write', [PersediaanKeluarController::class, 'write'])->name('persediaan.keluar.write');

        Route::get('/koreksi', [PersediaanKoreksiController::class, '__invoke'])->name('persediaan.koreksi');
        Route::get('/koreksi/read', [PersediaanKoreksiController::class, 'read'])->name('persediaan.koreksi.read');
        Route::post('/koreksi/write', [PersediaanKoreksiController::class, 'write'])->name('persediaan.koreksi.write');
    });

    // MENU LAPORAN
    Route::prefix('laporan')->middleware('check-mode')->group(function () {

        // LAPORAN ASET
        Route::get('/bmn', [BmnController::class, '__invoke'])->name('laporan.bmn');
        Route::get('/bmn/report', [BmnController::class, 'report'])->name('laporan.bmn.report');

        Route::get('/transaksi', [TransaksiController::class, '__invoke'])->name('laporan.transaksi');
        Route::get('/transaksi/read', [TransaksiController::class, 'read'])->name('laporan.transaksi.read');
        Route::get('/transaksi/report', [TransaksiController::class, 'report'])->name('laporan.transaksi.report');

        // LAPORAN  KDP
        Route::get('/kdp', [KdpReportController::class, '__invoke'])->name('laporan.kdp');
        Route::get('/kdp/report', [KdpReportController::class, 'report'])->name('laporan.kdp.report');

        Route::get('/kdp/transaksi', [KdpReportTransaksiController::class, '__invoke'])->name('laporan.kdp.transaksi');
        Route::get('/kdp/transaksi/read', [KdpReportTransaksiController::class, 'read'])->name('laporan.kdp.transaksi.read');
        Route::get('/kdp/transaksi/report', [KdpReportTransaksiController::class, 'report'])->name('laporan.kdp.transaksi.report');

        // OLD
        Route::get('/history', [HistoryController::class, '__invoke'])->name('laporan.history');
        Route::get('/history/report', [HistoryController::class, 'report'])->name('laporan.history.report');

        Route::get('/bmn-old', [BmnOldController::class, '__invoke'])->name('laporan.bmn.old');
        Route::get('/bmn-old/report', [BmnOldController::class, 'report'])->name('laporan.bmn.old.report');

        Route::get('/penyusutan', [PenyusutanController::class, '__invoke'])->name('laporan.penyusutan');
        Route::get('/penyusutan/report', [PenyusutanController::class, 'report'])->name('laporan.penyusutan.report');

        Route::get('/neraca', [NeracaController::class, '__invoke'])->name('laporan.neraca');
        Route::get('/neraca/report', [NeracaController::class, 'report'])->name('laporan.neraca.report');
        Route::get('/neraca/generate', [NeracaController::class, 'generate'])->name('laporan.neraca.generate');

        // LAPORAN KINERJA
        Route::get('/kinerja', [KinerjaController::class, '__invoke'])->name('laporan.kinerja');
        Route::get('/kinerja/report', [KinerjaController::class, 'report'])->name('laporan.kinerja.report');
    });

    // MODUL IIS
    Route::prefix('iis')->group(function () {

        Route::prefix('inventories-list')->group(function () {
            Route::get('/', [IisInventoriesController::class, '__invoke'])->name('iis.inventories_list');
            Route::get('/read', [IisInventoriesController::class, 'read'])->name('iis.inventories_list.read');
            Route::post('/write', [IisInventoriesController::class, 'write'])->name('iis.inventories_list.write');
        });

        Route::prefix('alkes-list')->group(function () {
            Route::get('/', [IisAlkesController::class, '__invoke'])->name('iis.alkes_list');
            Route::get('/read', [IisAlkesController::class, 'read'])->name('iis.alkes_list.read');
            Route::post('/write', [IisAlkesController::class, 'write'])->name('iis.alkes_list.write');
        });

        Route::prefix('ac-list')->group(function () {
            Route::get('/', [IisACController::class, '__invoke'])->name('iis.ac_list');
            Route::get('/read', [IisACController::class, 'read'])->name('iis.ac_list.read');
            Route::post('/write', [IisACController::class, 'write'])->name('iis.ac_list.write');
        });

        Route::prefix('items')->group(function () {
            Route::get('/', [IisItemsController::class, '__invoke'])->name('iis.items');
            Route::get('/read', [IisItemsController::class, 'read'])->name('iis.items.read');
        });

        Route::prefix('categories-list')->group(function () {
            Route::get('/', [IisCategoriesController::class, '__invoke'])->name('iis.categories_list');
            Route::get('/read', [IisCategoriesController::class, 'read'])->name('iis.categories_list.read');
            Route::post('/write', [IisCategoriesController::class, 'write'])->name('iis.categories_list.write');
        });

        Route::prefix('qrcode-handover')->group(function () {
            Route::get('/', [IisQrCodeHandoverController::class, '__invoke'])->name('iis.barcode_handover');
            Route::get('/read', [IisQrCodeHandoverController::class, 'read'])->name('iis.barcode_handover.read');
            Route::post('/write', [IisQrCodeHandoverController::class, 'write'])->name('iis.barcode_handover.write');
        });

        Route::prefix('distribution')->group(function () {
            Route::get('/', [IisDistributionController::class, '__invoke'])->name('iis.distribution');
            Route::get('/read', [IisDistributionController::class, 'read'])->name('iis.distribution.read');
            Route::post('/write', [IisDistributionController::class, 'write'])->name('iis.distribution.write');
        });
        
        Route::prefix('mutation')->group(function () {
            Route::get('/', [IisMutationController::class, '__invoke'])->name('iis.mutation');
            Route::get('/read', [IisMutationController::class, 'read'])->name('iis.mutation.read');
            Route::post('/write', [IisMutationController::class, 'write'])->name('iis.mutation.write');
        });

        Route::prefix('return')->group(function () {
            Route::get('/', [IisReturnController::class, '__invoke'])->name('iis.return');
            Route::get('/read', [IisReturnController::class, 'read'])->name('iis.return.read');
            Route::post('/write', [IisReturnController::class, 'write'])->name('iis.return.write');
        });

        Route::prefix('borrow')->group(function () {
            Route::get('/', [IisBorrowController::class, '__invoke'])->name('iis.borrow');
            Route::get('/read', [IisBorrowController::class, 'read'])->name('iis.borrow.read');
            Route::post('/write', [IisBorrowController::class, 'write'])->name('iis.borrow.write');
        });

        Route::prefix('borrow-return')->group(function () {
            Route::get('/', [IisBorrowReturnController::class, '__invoke'])->name('iis.borrow_return');
            Route::get('/read', [IisBorrowReturnController::class, 'read'])->name('iis.borrow_return.read');
        Route::post('/write', [IisBorrowReturnController::class, 'write'])->name('iis.borrow_return.write');
        });

    });

    // CHANGELOG
    Route::prefix('panduan')->group(function () {
        Route::get('/', [ChangeLogController::class, '__invoke'])->name('panduan');
        Route::get('/read', [ChangeLogController::class, 'read'])->name('panduan.read');
        Route::post('/write', [ChangeLogController::class, 'write'])->name('panduan.write');
    });

    // SCAN QRCODE
    Route::get('/scan/resolve', [ScanController::class, 'resolve']);

    Route::fallback(function () {
        $vue = '<not-found/>';

        return response()->view('layouts.antd', compact('vue'), 404);
    });
});
