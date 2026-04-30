<?php

namespace Database\Seeders;

use App\Models\ApplicationMenu;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;

class ApplicationMenuSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        ApplicationMenu::truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $order = 1;

        /* =====================
         | DASHBOARD
         ===================== */
        $this->menu([
            'key' => 'dashboard',
            'icon' => 'bi:grid',
            'title' => 'Dashboard',
            'url' => 'dashboard',
        ], $order++);

        /* =====================
         | INVENTARIS PRIBADI
         ===================== */
        $this->menu([
            'key' => 'my-inventories',
            'icon' => 'noto-v1:laptop',
            'title' => 'Inventarisku',
            'url' => 'my-inventories',
            'permissions' => ['my-inventories.view'],
        ], $order++);

        $this->menu([
            'key' => 'iis/my-inventories',
            'icon' => 'icomoon-free:book',
            'title' => 'Inventarisku (IIS)',
            'url' => 'iis/my-inventories',
            'permissions' => ['my-iis-inventories.view'],
        ], $order++);

        /* =====================
         | PENGADAAN
         ===================== */
        $pengadaan = $this->header('Pengadaan Barang', $order++);

        $this->menu([
            'key' => 'procurements/request',
            'icon' => 'line-md:downloading-loop',
            'title' => 'Request Pengadaan',
            'url' => 'procurements/request',
            'permissions' => ['procurement_request.view'],
        ], 1, $pengadaan->id);

        $this->menu([
            'key' => 'procurements',
            'icon' => 'line-md:clipboard-plus',
            'title' => 'Pengadaan Barang',
            'url' => 'procurements',
            'permissions' => ['procurement.view'],
        ], 2, $pengadaan->id);

        /* =====================
         | ASET TETAP
         ===================== */
        $aset = $this->header('Aset Tetap', $order++);

        $this->menu([
            'key' => 'asset/profile',
            'icon' => 'line-md:text-box',
            'title' => 'Daftar Aset',
            'url' => 'asset/profile',
            'permissions' => ['asset.view'],
        ], 1, $aset->id);

        $this->menu([
            'key' => 'asset/search',
            'icon' => 'line-md:search',
            'title' => 'Cari Aset',
            'url' => 'asset/search',
            'permissions' => ['asset.view'],
        ], 2, $aset->id);

        $this->menu([
            'key' => 'asset/distribution',
            'icon' => 'line-md:turn-sharp-left',
            'title' => 'Distribusi Aset',
            'url' => 'asset/distribution',
            'permissions' => ['asset.distribute'],
        ], 3, $aset->id);

        $this->menu([
            'key' => 'asset/disposal',
            'icon' => 'line-md:clipboard-minus',
            'title' => 'Penghapusan Aset',
            'url' => 'asset/disposal',
            'permissions' => ['asset.dispose'],
        ], 4, $aset->id);

        $laporanAset = $this->menu([
            'key' => 'report/asset',
            'icon' => 'line-md:text-box-multiple',
            'title' => 'Laporan Aset',
            'url' => 'asset/report',
            'permissions' => ['asset.report'],
        ], 5, $aset->id);

        $this->menu([
            'key' => 'report/asset/transaction',
            'icon' => 'line-md:text-box',
            'title' => 'Laporan Transaksi Aset',
            'url' => 'report/asset/transaction',
            'permissions' => ['asset.report'],
        ], 1, $laporanAset->id);

        /* =====================
         | INVENTORY
         ===================== */
        $inventory = $this->header('Persediaan Habis Pakai', $order++);

        $this->menu([
            'key' => 'inventory/data',
            'icon' => 'streamline-ultimate:warehouse-cart-package-ribbon-bold',
            'title' => 'Daftar Stock',
            'url' => 'inventory/data',
            'permissions' => ['inventory.view'],
        ], 1, $inventory->id);

        $this->menu([
            'key' => 'inventory/out',
            'icon' => 'line-md:log-out',
            'title' => 'Persediaan Keluar',
            'url' => 'inventory/keluar',
            'permissions' => ['inventory.out'],
        ], 2, $inventory->id);

        $this->menu([
            'key' => 'inventory/mutation',
            'icon' => 'line-md:arrows-horizontal',
            'title' => 'Persediaan Mutasi',
            'url' => 'inventory/mutation',
            'permissions' => ['inventory.mutate'],
        ], 3, $inventory->id);

        /* =====================
         | IIS MODUL
         ===================== */
        $iis = $this->header('IIS Modul', $order++);

        $iisMenus = [
            ['iis/inventories-list', 'icon-park-outline:adjacent-item', 'Daftar Inventaris IIS', 'iis/inventories-list', 'iis.inventories-list.view'],
            ['iis/alkes-list', 'streamline-plump:medical-bag-solid', 'Daftar Alkes IIS', 'iis/alkes-list', 'iis.alkes-list.view'],
            ['iis/ac-list', 'mdi:air-conditioner', 'Daftar AC IIS', 'iis/ac-list', 'iis.ac-list.view'],
            ['iis/items', 'streamline-freehand:design-tool-pen-brush-cup', 'Daftar Item IIS', 'iis/items', 'iis.items.view'],
            ['iis/categories-list', 'line-md:iconify2-static', 'Daftar Kategori IIS', 'iis/categories-list', 'iis.categories-list.view'],
            ['iis/qrcode-handover', 'la:qrcode', 'Serah Terima QR Code', 'iis/qrcode-handover', 'iis.qrcode-handover.view'],
            ['iis/distribution', 'streamline-freehand:crm-lead-distribution', 'Distribusi Barang IIS', 'iis/distribution', 'iis.distribution.view'],
            ['iis/mutation', 'covid:mutation-2', 'Mutasi Barang IIS', 'iis/mutation', 'iis.mutation.view'],
            ['iis/return', 'line-md:backup-restore', 'Retur Barang IIS', 'iis/return', 'iis.return.view'],
            ['iis/borrow', 'cryptocurrency:lend', 'Pinjam Barang IIS', 'iis/borrow', 'iis.borrow.view'],
            ['iis/borrow-return', 'icon-park-twotone:back', 'Retur Pinjaman', 'iis/borrow-return', 'iis.borrow-return.view'],
            ['iis/disposal', 'streamline-plump:delete-row', 'Pemusnahan Barang IIS', 'iis/disposal', 'iis.disposal.view'],
        ];

        $i = 1;
        foreach ($iisMenus as $m) {
            $this->menu([
                'key' => $m[0],
                'icon' => $m[1],
                'title' => $m[2],
                'url' => $m[3],
                'permissions' => [$m[4]],
            ], $i++, $iis->id);
        }

        /* =====================
         | MASTER DATA
         ===================== */
        $master = $this->header('Master Data', $order++);

        $masters = [
            ['branches', 'icon-park-outline:branch-one', 'Manajemen Branch', 'branches', 'branch.view'],
            ['buildings', 'streamline-pixel:health-hospital-building-1', 'Manajemen Gedung', 'buildings', 'building.view'],
            ['warehouses', 'mdi:warehouse', 'Manajemen Gudang', 'warehouses', 'warehouse.view'],
            ['transaction-types', 'tabler:transaction-dollar', 'Manajemen Jenis Transaksi', 'transaction-types', 'transaction_type.view'],
            ['stock-codes', 'line-md:clipboard-list', 'Manajemen Kode Stok', 'stock-codes', 'stock_code.view'],
            ['categories', 'line-md:grid-3-twotone', 'Manajemen Kategori', 'categories', 'category.view'],
            ['depreciation-groups', 'line-md:speed-loop', 'Kelompok Penyusutan', 'depreciation-groups', 'depreciation_group.view'],
            ['uoms', 'line-md:brake-abs-twotone', 'Manajemen Satuan', 'uoms', 'uom.view'],
            ['items', 'line-md:list-3-filled', 'Manajemen Master Item', 'items', 'item.view'],
            ['suppliers', 'line-md:phone-call-loop', 'Manajemen Supplier', 'suppliers', 'supplier.view'],
            ['units', 'mdi:account-group', 'Manajemen Unit', 'units', 'unit.view'],
            ['rooms', 'fluent:conference-room-20-regular', 'Manajemen Ruangan', 'rooms', 'room.view'],
        ];

        $i = 1;
        foreach ($masters as $m) {
            $this->menu([
                'key' => $m[0],
                'icon' => $m[1],
                'title' => $m[2],
                'url' => $m[3],
                'permissions' => [$m[4]],
            ], $i++, $master->id);
        }

        /* =====================
         | ADMINISTRATOR
         ===================== */
        $admin = $this->header('Administrator', $order++);

        $this->menuAdmin($admin->id);
    }

    private function header(string $title, int $order)
    {
        return ApplicationMenu::create([
            'type' => 'header',
            'title' => $title,
            'order' => $order,
            'is_active' => true,
        ]);
    }

    private function menu(array $data, int $order, ?int $parentId = null)
    {
        $menu = ApplicationMenu::create([
            'type' => 'menu',
            'parent_id' => $parentId,
            'key' => $data['key'] ?? null,
            'icon' => $data['icon'] ?? null,
            'title' => $data['title'],
            'url' => $data['url'] ?? null,
            'order' => $order,
            'is_active' => true,
        ]);

        if (! empty($data['permissions'])) {
            $permissionIds = Permission::whereIn('name', $data['permissions'])->pluck('id');
            $menu->permissions()->syncWithoutDetaching($permissionIds);
        }

        return $menu;
    }

    private function menuAdmin(int $parentId)
    {
        $adminMenus = [
            ['application-menus', 'streamline-ultimate:coding-apps-website-web-form-drop-down-menu-form-3', 'Manajemen Menu Aplikasi', 'application-menus'],
            ['users', 'line-md:account', 'Manajemen User', 'users'],
            ['roles', 'line-md:file-document-cancel', 'Manajemen Role', 'roles'],
            ['permissions', 'line-md:watch', 'Manajemen Permission', 'permissions'],
            ['configurations', 'line-md:cog', 'Konfigurasi Sistem', 'configurations'],
        ];

        $i = 1;
        foreach ($adminMenus as $m) {
            $this->menu([
                'key' => $m[0],
                'icon' => $m[1],
                'title' => $m[2],
                'url' => $m[3],
                'permissions' => ['system.admin'],
            ], $i++, $parentId);
        }
    }
}
