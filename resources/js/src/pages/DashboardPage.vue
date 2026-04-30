<script>
import {
  UserOutlined,
  FolderOpenOutlined,
  DatabaseOutlined,
  HomeOutlined,
  SwapOutlined,
  AppstoreOutlined,
  InboxOutlined,
  ImportOutlined,
  ExportOutlined,
  DeliveredProcedureOutlined,
  RollbackOutlined,
  ShoppingCartOutlined,
  DeleteOutlined,
  FileTextOutlined,
  SafetyOutlined,
  TeamOutlined,
  SettingOutlined,
  DeploymentUnitOutlined,
  MedicineBoxOutlined
} from '@ant-design/icons-vue'

export default {
  props: {
    constant: {
      type: Object,
      default: () => ({}),
    },
  },
  components: {
    UserOutlined,
    FolderOpenOutlined,
    DatabaseOutlined,
    HomeOutlined,
    SwapOutlined,
    AppstoreOutlined,
    InboxOutlined,
    ImportOutlined,
    ExportOutlined,
    DeliveredProcedureOutlined,
    RollbackOutlined,
    ShoppingCartOutlined,
    DeleteOutlined,
    FileTextOutlined,
    SafetyOutlined,
    TeamOutlined,
    SettingOutlined,
    DeploymentUnitOutlined,
    MedicineBoxOutlined
  },
  data() {
    return {
      menus: [
        { title: 'Inventarisku', desc: 'Data inventaris pribadi', icon: 'FolderOpenOutlined', color: 'text-blue-500', url: 'my-inventories' },
        { title: 'Inventarisku (IIS)', desc: 'Data dari sistem IIS', icon: 'DatabaseOutlined', color: 'text-indigo-500', url: 'iis/my-inventories' },
        { title: 'Scan Pemakaian ALKES', desc: 'Scan Pemakaian Alat Kesehatan', icon: 'MedicineBoxOutlined', color: 'text-blue-500', url: '#' },
        { title: 'Distribusi Barang IIS', desc: 'Perpindahan barang IIS dari Gudang ke User', icon: 'DeliveredProcedureOutlined', color: 'text-red-500', url: 'iis/distribution' },
        { title: 'Mutasi Barang IIS', desc: 'Perpindahan barang IIS dari User ke User', icon: 'SwapOutlined', color: 'text-purple-500', url: 'iis/mutation' },
        { title: 'Pengembalian/Retur Barang IIS', desc: 'Proses pengembalian barang IIS dari User ke Gudang', icon: 'RollbackOutlined', color: 'text-lime-600', url: 'iis/mutation' },
        { title: 'Pinjam Barang IIS', desc: 'Proses peminjaman barang IIS dari User ke Gudang', icon: 'DeploymentUnitOutlined', color: 'text-red-600', url: 'iis/borrow' },
        { title: 'Pengembalian/Retur Pinjaman Barang IIS', desc: 'Proses pengembalian peminjaman barang IIS dari User ke Gudang', icon: 'RollbackOutlined', color: 'text-lime-600', url: 'iis/borrow-return' },

        { title: 'Stok Gudang', desc: 'Ketersediaan barang', icon: 'HomeOutlined', color: 'text-green-600', url: '#' },
        { title: 'Barang Masuk', desc: 'Penerimaan barang', icon: 'ImportOutlined', color: 'text-sky-500', url: '#' },
        { title: 'Barang Keluar', desc: 'Distribusi barang', icon: 'ExportOutlined', color: 'text-orange-500', url: '#' },

        { title: 'Aset Tetap', desc: 'Data aset bernilai tetap', icon: 'AppstoreOutlined', color: 'text-emerald-500', url: '#' },
        { title: 'Aset Habis Pakai', desc: 'Barang konsumsi', icon: 'InboxOutlined', color: 'text-teal-500', url: '#' },
        { title: 'Pembelian', desc: 'Request pembelian', icon: 'ShoppingCartOutlined', color: 'text-pink-500', url: '#' },
        { title: 'Penghapusan', desc: 'Aset rusak / hilang', icon: 'DeleteOutlined', color: 'text-red-500', url: '#' },

        { title: 'Laporan Aset', desc: 'Rekap & export', icon: 'FileTextOutlined', color: 'text-slate-600', url: '#' },
        { title: 'Audit & Riwayat', desc: 'Log pergerakan', icon: 'SafetyOutlined', color: 'text-cyan-600', url: '#' },
        { title: 'Vendor', desc: 'Manajemen vendor', icon: 'TeamOutlined', color: 'text-fuchsia-500', url: '#' },
        { title: 'Master Data', desc: 'Data pendukung', icon: 'SettingOutlined', color: 'text-gray-600', url: '#' },
      ],
    }
  },
}
</script>
<template>
  <div class="flex justify-center">
    <a-card class="w-full rounded-2xl" :bordered="false">

      <!-- HEADER -->
      <div class="mb-4 text-center">
        <a-typography-title :level="3" class="!mb-1 font-bold tracking-tight text-gray-800">
          Selamat Datang
        </a-typography-title>

        <a-typography-text type="secondary" class="text-lg tracking-wide text-gray-600">
          {{ constant.user?.name || 'Pengguna' }}
        </a-typography-text>

        <div class="mt-4 flex items-center justify-center gap-4">
          <div class="h-px flex-1 bg-gray-300"></div>

          <a-tag color="purple"
            class="flex items-center gap-2 rounded-full border-purple-400 px-4 py-1 text-sm shadow-sm">
            <UserOutlined />
            {{ constant.user?.position }} - {{ constant.user?.division }}
          </a-tag>

          <div class="h-px flex-1 bg-gray-300"></div>
        </div>
      </div>

      <!-- GRID MENU -->
      <a-row :gutter="[20, 20]" justify="center" align="stretch">
        <a-col v-for="(menu, index) in menus" :key="index" :xs="24" :sm="12" :lg="8" :xl="6" class="flex">
          <a :href="menu.url" class="block no-underline w-full">
            <a-card hoverable class="
              group relative overflow-hidden text-center
              rounded-[18px]
              border border-[#c4b5fd]
              bg-gradient-to-b from-[#fafaff] to-white
              transition-all duration-300
              hover:-translate-y-1
              hover:border-indigo-300
              hover:shadow-[0_14px_30px_rgba(79,70,229,0.18)]
              h-full flex flex-col
            ">
              <!-- overlay -->
              <span class="
                pointer-events-none absolute inset-0
                bg-gradient-to-br
                from-indigo-500/10 to-purple-500/10
                opacity-0 transition-opacity duration-300
                group-hover:opacity-100
              " />

              <div class="relative z-10 mx-auto mb-1 flex h-12 w-12 items-center justify-center rounded-full
                bg-white shadow-sm ring-1 ring-indigo-200/60
                transition group-hover:ring-indigo-300">
                <component :is="menu.icon" class="text-[28px]" :class="menu.color" />
              </div>

              <div class="relative z-10 font-semibold text-gray-800">
                {{ menu.title }}
              </div>

              <div class="relative z-10 mt-1 text-[13px] text-gray-500">
                {{ menu.desc }}
              </div>
            </a-card>
          </a>
        </a-col>
      </a-row>
    </a-card>
  </div>
</template>