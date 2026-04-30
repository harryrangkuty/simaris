<script>

export default {
  props: {
    constant: {
      type: Object,
      default: () => ({}),
    },
  },
  data() {
    return {
      satkers_transaction: [],
      filters: {
        satker_id: null,
        tahun: null,
        jenis_transaksi_id: null,
        jenjang_kategori: 1,
      },
    }
  },

  created() {
    if (this.operator_aset) {
      this.filters.satker_id = this.user.satkers_id[0];
    } else if (this.reviewer_aset && !this.sudo) {
      this.filters.satker_id = this.user.reviews_id[0];
    }
    this.filters.tahun = this.dayjs().year().toString()
  },

  watch: {
    'filters.satker_id': 'resetJenisTransaction',
    'filters.tahun': 'resetJenisTransaction'
  },

  methods: {

    report() {
      const vm = this;
      let params = new URLSearchParams();

      Object.keys(vm.filters).forEach(key => {
        if (vm.filters[key] !== null && vm.filters[key] !== undefined) {
          params.append(key, vm.filters[key]);
        }
      });

      const url = `${vm.route}/report?${params.toString()}`;
      const _window = window.open(url, 'name', 'height=500, width=900, top=50, left=50');
      if (window.focus) {
        _window.focus();
      }
      return false;
    },

    async getJenisTransaction() {
      const vm = this;
      this.loadingTrue();
      const params = {
        req: "get_jenis_transaksi",
        ...vm.filters
      };

      const response = await vm.axios.get(vm.readRoute, { params })
      if (response && response.data) {
        vm.satkers_transaction = response.data.models;
        vm.loadingFalse();
      }
    },

    resetJenisTransaction() {
      this.filters.jenis_transaksi_id = null;
      this.getJenisTransaction();
    }
  },
};
</script>

<template>
  <div class="!w-full shadow-md rounded-[8px] overflow-hidden flex-grow lg:mb-0 mb-6">
    <a-card class="card card-white !px-5 h-full" title="Laporan Transaksi">
      <a-row :gutter="{ xs: 8, sm: 16, md: 24, lg: 32 }">
        <a-col class="gutter-row" :span="24" :md="12">
          <a-form :label-col="{ span: 6 }" :wrapper-col="{ span: 18 }">
            <a-form-item label="Satuan Kerja" data-column="satker_id" :rules="[{ required: true }]">
              <a-select v-model:value="filters.satker_id" show-search option-filter-prop="title" style="width: 100%"
                placeholder="Pilih Satuan Kerja" :disabled="Object.keys(constant.SATKER).length < 2">
                <a-select-option :key="null" :value="null" title="Semua Satker">
                  Semua Satker
                </a-select-option>
                <a-select-option v-for="satker in constant.SATKER" :key="satker.id" :value="satker.id"
                  :title="satker.nama">
                  {{ satker.nama }}
                </a-select-option>
              </a-select>
            </a-form-item>
            <a-form-item label="Jenjang Kategori" :rules="[{ required: true }]">
              <a-select v-model:value="filters.jenjang_kategori" show-search style="width: 100%"
                placeholder="Pilih Jenjang Kategori">
                <a-select-option :value="1">Golongan</a-select-option>
                <a-select-option :value="3">Bidang</a-select-option>
                <a-select-option :value="5">Kelompok</a-select-option>
                <a-select-option :value="7">Sub Kelompok</a-select-option>
                <a-select-option :value="10">Sub-Sub Kelompok</a-select-option>
              </a-select>
            </a-form-item>
            <a-form-item label="Tahun">
              <a-date-picker v-model:value="filters.tahun" picker="year" value-format="YYYY" />
            </a-form-item>
            <a-form-item label="Jenis Transaksi" data-column="jenis_transaksi_id">
              <a-select v-model:value="filters.jenis_transaksi_id" show-search option-filter-prop="title" style="width: 100%"
                placeholder="Pilih Jenis Transaksi">
                <a-select-option v-for="i in satkers_transaction" :key="i.jenis_transaksi_id"
                  :value="i.jenis_transaksi_id" :title="i.jenis_transaksi_nama">
                  {{ i.jenis_transaksi_id }} - {{ i.jenis_transaksi_nama }}
                </a-select-option>
              </a-select>
            </a-form-item>
          </a-form>
        </a-col>
        <a-col class="gutter-row" :span="24" :md="12">
          <!-- Kosong dulu -->
        </a-col>
      </a-row>
      <a-button type="primary" class="flex items-center justify-center w-full sm:w-auto min-w-[6rem] mb-2"
        @click="report()">Report</a-button>
    </a-card>
  </div>
</template>