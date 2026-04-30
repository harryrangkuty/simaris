<?php

namespace Database\Seeders;

use App\Models\MasterData\TransactionType;
use Illuminate\Database\Seeder;

class TransactionTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            // SALDO AWAL
            ['code' => 'S00', 'name' => 'Saldo Awal', 'is_active' => true, 'description' => 'Transaksi saldo awal merupakan saldo aset awal tahun atau awal implementasi sistem, sebagai akumulasi transaksi sebelumnya.'],
            
            // MASUK
            ['code' => 'M01', 'name' => 'Pembelian', 'is_active' => true, 'description' => 'Transaksi pembelian untuk mencatat aset yang diperoleh dari dana Kas atau dana lainnya pada tahun berjalan.'],
            ['code' => 'M02', 'name' => 'Hibah Masuk', 'is_active' => true, 'description' => 'Transaksi hibah masuk untuk mencatat aset yang diterima dari pihak eksternal tanpa pembayaran.'],
            ['code' => 'M03', 'name' => 'Retur Masuk', 'is_active' => true, 'description' => 'Transaksi retur masuk untuk mencatat aset yang dikembalikan setelah sebelumnya keluar.'],

            // KELUAR
            ['code' => 'K01', 'name' => 'Penjualan', 'is_active' => true, 'description' => 'Transaksi penjualan untuk mencatat aset yang dilepas melalui proses penjualan resmi.'],
            ['code' => 'K02', 'name' => 'Hibah Keluar', 'is_active' => true, 'description' => 'Transaksi hibah keluar untuk mencatat aset yang diberikan kepada pihak lain secara cuma-cuma.'],
            ['code' => 'K03', 'name' => 'Penghapusan', 'is_active' => true, 'description' => 'Transaksi penghapusan untuk mencatat aset yang dihapus dari daftar karena rusak/usang/keputusan resmi.'],

            // DISTRIBUSI
            ['code' => 'D01', 'name' => 'Distribusi Barang', 'is_active' => true, 'description' => 'Transaksi distribusi mencatat perpindahan aset antar unit/satker tanpa mengubah nilai perolehan.'],
            ['code' => 'D02', 'name' => 'Mutasi Barang', 'is_active' => true, 'description' => 'Transaksi mutasi mencatat perpindahan aset antar lokasi/unit kerja.'],
            ['code' => 'D03', 'name' => 'Peminjaman Barang', 'is_active' => true, 'description' => 'Transaksi peminjaman untuk mencatat aset yang dipinjamkan ke unit/pihak lain.'],
            ['code' => 'D04', 'name' => 'Pengembalian Barang', 'is_active' => true, 'description' => 'Transaksi pengembalian untuk mencatat aset yang dikembalikan dari status pinjam.'],

            // HAPUS / KOREKSI KHUSUS
            ['code' => 'H01', 'name' => 'Koreksi Masuk', 'is_active' => true, 'description' => 'Koreksi penambahan data aset akibat kesalahan pencatatan sebelumnya.'],
            ['code' => 'H02', 'name' => 'Koreksi Keluar', 'is_active' => true, 'description' => 'Koreksi pengurangan data aset akibat kesalahan pencatatan sebelumnya.'],
        ];

        TransactionType::truncate();
        TransactionType::insert($data);
    }
}
