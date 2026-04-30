<script>

export default {
  props: {
    title: String,
    constant: {
      type: Object,
      default: () => ({}),
    },
  },
  data() {
    return {
      options: null,
      form: {
        kode_lokasi: '',
        kategori: '',
        nup_start: '',
        nup_end: '',
        jenis_laporan: '1',
        date_end: null,
      },
      // date_format: "DD-MM-YYYY",
    };
  },

  mounted() {
    console.log(this.dayjs());
    this.form.date_end = this.dayjs();
    this.readKategori();
  },

  methods: {
    async readData(params) {
      const vm = this;
      params = {
        mode: 'report',
        ...vm.form,
        kategori: vm.form.kategori.split(' ').at(0),
        date_end: vm.filterDate(vm.form.date_end)
      };
      const queryString = new URLSearchParams(params).toString();
      console.log(params);

      window.open(
        window._HOST +
        `/laporan/history?${queryString}`,
        'name',
        'height=500, width=800, top=50, left=50'
      );
    },

    async readKategori() {
      const vm = this;
      const response = await vm.axios.get(window._HOST + '/laporan/kategori');
      if (response && response.data) {
        vm.options = response.data.models;
      }
    },
  },
};
</script>

<template>
  <div>
    <a-row type="flex" justify="center">
      <a-col :span="24">
        <a-card class="card" :title="title">
          <a-form :model="form" :label-col="{ span: 4 }" :wrapper-col="{ span: 20 }">
            <a-form-item label="Lokasi" data-column="kode_lokasi" :rules="[{ required: true }]">
              <a-select v-model:value="form.kode_lokasi" placeholder="--Pilih Lokasi--">
                <a-select-option :key="lokasi.id" v-for="lokasi in constant.KODE_LOKASI" :value="lokasi.kode">
                  {{ lokasi.kode + ' - ' + lokasi.nama }}
                </a-select-option>
              </a-select>
            </a-form-item>
            <a-form-item label="Kode Barang" data-column="jenis_laporan">
              <a-select v-model:value="form.kategori" show-search placeholder="Pilih Kategori"
                :options="options"></a-select>
              <select-kategori :kategori_id="form.kategori_id" :laporan="true" @kategori="setKategori($event)" />
            </a-form-item>
            <a-form-item label="NUP" data-column="jenis_laporan">
              <a-row type="flex" justify="space-between" align="middle">
                <a-col :span="10">
                  <a-input v-model:value="form.nup_start" placeholder="NUP Awal" />
                </a-col>
                <a-col :span="3">Sampai Dengan</a-col>
                <a-col :span="10">
                  <a-input v-model:value="form.nup_end" placeholder="NUP Akhir" />
                </a-col>
              </a-row>
            </a-form-item>
            <a-form-item label="Jenis Laporan" data-column="jenis_laporan" :rules="[{ required: true }]">
              <a-radio-group v-model:value="form.jenis_laporan" name="radioGroup">
                <a-radio value="1">Intrakomptabel</a-radio>
                <a-radio value="2">Ekstrakomptabel</a-radio>
                <a-radio value="3">Gabungan Intrakomptabel dan Ekstrakomptabel</a-radio>
              </a-radio-group>
            </a-form-item>
            <a-form-item label="Sampai Dengan Tanggal" data-column="tanggal" :rules="[{ required: true }]">
              <a-date-picker v-model:value="form.date_end" :format="date_format" />
            </a-form-item>
            <a-form-item :wrapper-col="{ offset: 4, span: 20 }">
              <a-button type="primary" @click="readData()">Proses</a-button>
            </a-form-item>
          </a-form>
        </a-card>
      </a-col>
    </a-row>
  </div>
</template>