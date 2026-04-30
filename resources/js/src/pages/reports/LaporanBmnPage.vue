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
      filters: {
        satker_id: null,
        tahun: null,
        jenjang_kategori: 1,
      },
      filters_neraca: {
        jenis_laporan: '3',
        tahun: null
      },
      tanggal: null,
    }
  },

  computed: {
    displaySatkerName() {
      return this.filters.satker_id && this.constant.SATKER[this.filters.satker_id] ? this.constant.SATKER[this.filters.satker_id].nama : '';
    }
  },

  created() {
    if (this.operator_aset) {
      this.filters.satker_id = this.user.satkers_id[0];
    } else if (this.reviewer_aset && !this.sudo) {
      this.filters.satker_id = this.user.reviews_id[0];
    }
    this.filters.tahun = this.dayjs().year().toString()
    this.filters_neraca.tahun = this.dayjs().year().toString()
  },

  methods: {

    report(penyusutan = false) {
      const vm = this;
      let params = new URLSearchParams();

      Object.keys(vm.filters).forEach(key => {
        if (vm.filters[key] !== null && vm.filters[key] !== undefined) {
          params.append(key, vm.filters[key]);
        }
      });

      if (penyusutan)
        params.append('mode', 'penyusutan');

      const url = `${vm.route}/report?${params.toString()}`;
      const _window = window.open(url, 'name', 'height=500, width=900, top=50, left=50');
      if (window.focus) {
        _window.focus();
      }
      return false;
    },

    neraca(params) {
      const vm = this;
      const tahun = vm.dayjs(vm.filters_neraca.tahun).year()

      params = {
        tahun: tahun
      };

      if (vm.constant) {
        params.tipe = 'pembantu'
      }
      const queryString = new URLSearchParams(params).toString();

      window.open(
        window._HOST +
        `/laporan/neraca/generate?${queryString}`,
        'name',
        'height=500, width=800, top=50, left=50'
      );
    },

  },
};
</script>

<template>
  <div class="!w-full shadow-md rounded-[8px] overflow-hidden flex-grow lg:mb-0 mb-6">
    <a-card class="card card-white !px-5 h-full" title="Laporan BMN">
      <a-row :gutter="{ xs: 8, sm: 16, md: 24, lg: 32 }">
        <a-col class="gutter-row" :span="24" :md="12">
          <a-form :label-col="{ span: 6 }" :wrapper-col="{ span: 18 }">
            <a-form-item label="Satuan Kerja" :rules="[{ required: true }]">
              <a-select v-model:value="filters.satker_id" show-search option-filter-prop="title" style="width: 100%"
                placeholder="Pilih Satuan Kerja" :disabled="Object.keys(constant.SATKER).length < 2"
                :title="displaySatkerName">
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
            <a-form-item label="Tahun" :rules="[{ required: true }]">
              <a-date-picker v-model:value="filters.tahun" picker="year" value-format="YYYY" :allow-clear="false" />
            </a-form-item>
          </a-form>
        </a-col>
        <a-col class="gutter-row" :span="24" :md="12">
          <a-form :label-col="{ span: 6 }" :wrapper-col="{ span: 18 }">
            <!-- Kosong dulu -->
          </a-form>
        </a-col>
      </a-row>
      <a-button type="primary" class="flex items-center justify-center w-full sm:w-auto min-w-[6rem] mb-2 mr-2"
        @click="report()">Nilai Mutasi</a-button>
      <!-- <a-button type="primary" class="flex items-center justify-center w-full sm:w-auto min-w-[6rem] mb-2"
        @click="report(true)">Nilai Penyusutan</a-button> -->
    </a-card>
    <br>
    <a-card class="card card-white !px-5 h-full" title="Laporan Neraca">
      <a-row :gutter="{ xs: 8, sm: 16, md: 24, lg: 32 }">
        <a-col class="gutter-row" :span="24" :md="12">
          <a-form :model="form" :label-col="{ span: 6 }" :wrapper-col="{ span: 28 }">
            <a-form-item label="Tahun" :rules="[{ required: true }]">
              <a-date-picker v-model:value="filters_neraca.tahun" picker="year" value-format="YYYY"
                :allow-clear="false" />
            </a-form-item>
          </a-form>
        </a-col>
        <a-col class="gutter-row" :span="24" :md="12">
          <a-form :label-col="{ span: 6 }" :wrapper-col="{ span: 18 }">
            <!-- Kosong dulu -->
          </a-form>
        </a-col>
      </a-row>
      <a-button type="primary" class="flex items-center justify-center w-full sm:w-auto md:w-40"
        @click="neraca()">Proses</a-button>
    </a-card>
  </div>
</template>