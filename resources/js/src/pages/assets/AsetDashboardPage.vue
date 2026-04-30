<script>
import dayjs from 'dayjs';

const columns = [
  {
    title: "#",
    key: "number",
    align: "center",
    width: 100,
  },
  {
    title: "Satuan Kerja",
    key: "satker",
    dataIndex: ["satker", "nama"],
    align: "left",
    width: 300
  },
  {
    title: "Nilai Rupiah Pembelian",
    key: "nilai_perolehan",
    align: "right",
    width: 300
  },
  {
    title: "Persentase",
    key: "persentase",
    align: "right",
    width: 300
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
        satker_id: null,
      },
      selectedDate: dayjs(),
      nilai_perolehan: [],
      nilai_buku: [],
      total_nilai_global: null,

    };
  },

  created() {
    if (this.operator_aset && this.user.active_role_id) {
      this.filters.satker_id = this.user.satkers_id[0];
    } else if (this.reviewer_aset && this.user.active_role_id) {
      this.filters.satker_id = this.user.reviews_id[0];
    }
  },

  mounted() {
    this.updateYearFilter(this.selectedDate);
  },

  computed: {},

  methods: {
    async readData() {
      const vm = this;
      vm.loadingTrue();

      const params = {
        req: "table",
        ...vm.filter,
        ...vm.filters
      };

      const response = await vm.axios.get(vm.readRoute, { params })
      if (response && response.data) {
        vm.models = response.data.models;
        vm.total_nilai_global = response.data.total_nilai_global;
        vm.loadingFalse();
      }
    },
    async nilaiPerolehan() {
      const vm = this;
      vm.loadingTrue();

      const params = {
        req: "nilai_perolehan",
        ...vm.filters
      };

      const response = await vm.axios.get(vm.readRoute, { params })
      if (response && response.data) {
        vm.nilai_perolehan = response.data.models;
        vm.loadingFalse();
      }
    },
    async nilaiBuku() {
      const vm = this;
      vm.loadingTrue();

      const params = {
        req: "nilai_buku",
        ...vm.filters
      };

      const response = await vm.axios.get(vm.readRoute, { params })
      if (response && response.data) {
        vm.nilai_buku = response.data.models;
        vm.loadingFalse();
      }
    },
    updateYearFilter(selectedDate) {
      if (selectedDate) {
        const selectedYear = new Date(selectedDate).getFullYear();
        this.filters.year = selectedYear;
        this.nilaiPerolehan();
        this.nilaiBuku();
        this.readData();
      } else {
        this.filters.year = null;
      }
    },
    changeSatker() {
      const vm = this;
      vm.nilaiPerolehan();
      vm.nilaiBuku();
    },
  }
};
</script>

<template>
  <a-card class="card">
    <div class="lg:flex justify-between items-center mt-5">
      <div class="font-bold text-xl mb-4 lg:mb-0">
        RSU BUNDA THAMRIN - {{ filters.year }}
      </div>
      <div class="lg:flex justify-end">
        <div class="lg:flex gap-x-1.5">
          <div class="mb-1.5 lg:mb-0">
            <a-date-picker v-model:value="selectedDate" picker="year" @change="updateYearFilter" :allow-clear="false"
              class="lg:w-40 w-full font-bold" />
          </div>
          <div>
            <a-select v-model:value="filters.satker_id" show-search option-filter-prop="title"
              class="lg:w-[350px] w-full" placeholder="Satuan Kerja" @change="changeSatker">
              <a-select-option :key="null" :value="null" title="Semua Satker">
                Semua Satker
              </a-select-option>
              <a-select-option v-for="satker in constant.SATKER" :key="satker.id" :value="satker.id"
                :title="satker.nama">
                {{ satker.nama }}
              </a-select-option>
            </a-select>
          </div>
        </div>
      </div>
    </div>
    <a-row type="flex" class="mt-5">
      <a-col :span="24" :lg="12" class="lg:pr-2 mb-4 lg:mb-0">
        <pie-chart :chartData="nilai_perolehan" :title="'Nilai Rupiah Pembelian'" class="bg-lavender rounded p-2" />
      </a-col>
      <a-col :span="24" :lg="12" class="lg:pl-2">
        <bar-chart :chartData="nilai_buku" :title="'Nilai Buku'" :barName="'Kategori'" class="bg-lavender rounded p-2" />
      </a-col>
    </a-row>
    <div class="mt-7">
      <p class="text-base">Total Nilai Rupiah Pembelian Rumah Sakit Bunda Thamrin : <b>Rp.{{ idCurrency(total_nilai_global)
      }}</b></p>
      <!-- <a-table :scroll="{ x: 800 }" :columns="columns" :row-key="(obj) => obj.id" :pagination="_pagination"
        :loading="loadingStatus" :data-source="models" @change="handleTableChange">
        <template #bodyCell="{ index, column, record }">
          <template v-if="column.key === 'number'">
            {{ index + 1 }}
          </template>
          <template v-if="column.key === 'nilai_perolehan'">
            {{ idCurrency(record.total_nilai_perolehan) }}
          </template>
          <template v-if="column.key === 'persentase'">
            {{ ((record.total_nilai_perolehan / total_nilai_global) * 100).toFixed(2) }} %
          </template>
        </template>
      </a-table> -->
    </div>
  </a-card>
</template>