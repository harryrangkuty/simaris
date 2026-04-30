<script>
import dayjs from 'dayjs';

const columns = [
{
        title: "#",
        key: "number",
        align: "center",
        width: 60,
    },
    {
        title: "Nama",
        key: "Nama",
        dataIndex: 'nama',
        align: "left",
        width: 300
    },
    {
        title: "Kategori",
        key: "kategori",
        align: "left",
        width: 350,
    },
    {
        title: "Stock Sekarang",
        key: "stock",
        align: "center",
        width: 200,
    },
    {
        title: "Stock Minimal",
        key: "stok_min",
        align: "center",
        width: 200,
    },

];
export default {
  props: {
    constant: {
        type: Object,
        default: () => ({}),
    },
  },
  data() {
    return {
      columns,
      filters: {
        year: null,
      },
      selectedDate: dayjs(),
      data_barang: [],
      nilai_persediaan: [],
      
    };
  },

  mounted() {
    this.updateYearFilter(this.selectedDate); 
  },
  methods: {
    async readData() {
        const vm = this;
        vm.loadingTrue();
        const params = {
            req: "list_barang_kekurangan_stock",
            ...vm.filter,
            ...vm.filters,
        };

        const response = await vm.axios.get(vm.readRoute, { params })
        if (response && response.data) {
            vm.models = response.data.models
            vm.loadingFalse();
        }
    },
    async countBarang() {
      const vm = this;
      vm.loadingTrue();

      const params = {
          req: "persediaan_barang",
          ...vm.filters
      };

      const response = await vm.axios.get(vm.readRoute, { params })
      if (response && response.data) {
        vm.data_barang = response.data.models;
        vm.loadingFalse();
      }
    },
    async nilaiPersediaan() {
      const vm = this;
      vm.loadingTrue();

      const params = {
          req: "nilai_persediaan",
          ...vm.filters
      };

      const response = await vm.axios.get(vm.readRoute, { params })
      if (response && response.data) {
        vm.nilai_persediaan = response.data.models;
        vm.loadingFalse();
      }
    },
    updateYearFilter(selectedDate) {
      if (selectedDate) {
          const selectedYear = new Date(selectedDate).getFullYear();
          this.filters.year = selectedYear;
          this.countBarang();
          this.nilaiPersediaan();
          this.readData();
      } else {
          this.filters.year = null;
      }
    },
  }
};
</script>

<template>
  <div class="lg:flex justify-between items-center mt-5">
    <div class="lg:flex justify-end">
      <div class="lg:flex gap-x-1.5">
        <div class="mb-1.5 lg:mb-0">
          <a-date-picker v-model:value="selectedDate" picker="year" @change="updateYearFilter" 
            class="lg:w-40 :w-full font-bold">
          </a-date-picker>
        </div>
      </div>
    </div>
  </div>
  <a-row type="flex" class="mt-5">
    <a-col :span="24" :lg="12" class="lg:pr-2 mb-4 lg:mb-0">
        <pie-chart v-if="data_barang.length > 0" :chartData="data_barang" :mode="user.mode"
          :title="'Stock dan Nilai Barang'" class="bg-white rounded p-2"/>
    </a-col>
    <a-col :span="24" :lg="12" class="lg:pl-2">
        <bar-chart v-if="nilai_persediaan.length > 0" :chartData="nilai_persediaan" :title="'Nilai Persediaan'" 
          :barName="'Transaksi Persediaan'" class="bg-white rounded p-2"/>
    </a-col>
  </a-row>
  <div v-if="models.length > 0" class="mt-8">
    <div class="text-lg font-bold mb-4">List Barang Kekurangan Stock</div>
    <a-table :scroll="{ x: 800 }" :columns="columns" :row-key="(obj) => obj.id" :pagination="false" :loading="loadingStatus"
      :data-source="models" @change="handleTableChange">
      <template #bodyCell="{ index, column, record }">
          <template v-if="column.key === 'number'">
              {{ index + 1 }}
          </template>
          <template v-if="column.key === 'kategori'">
              {{ record.kategori_id }} - {{ record.kategori_nama }}
          </template>
          <template v-if="column.key === 'stok_min'">
              {{ record.stok_minimum }}
          </template>
          <template v-if="column.key === 'stok_max'">
              {{ record.stok_maksimum }}
          </template>
          <template v-if="column.key === 'stock'">
              {{ record.stock }}
          </template>
      </template>
    </a-table>
  </div>
</template>