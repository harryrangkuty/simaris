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
      form: {
        jenis_laporan: '1',
        rincian: '10',
        parameter: 'tanggal',
        tahun: null,
        semester: '1',
      },
      // date_format: "DD-MM-YYYY",
      tanggal: null,
    };
  },

  mounted() {
    this.form.tahun = this.dayjs();
    this.tanggal = this.dayjs();
    if (this.constant) {
      this.form.kode_lokasi = this.constant.KODE_LOKASI.at(0).kode
    }
  },

  methods: {
    async readData(params) {
      const vm = this;
      const tahun = vm.dayjs(vm.form.tahun).year()

      if (vm.form.parameter == 'tanggal') {
        vm.form.date_end = vm.filterDate(vm.tanggal);
      } else if (vm.form.parameter == 'semester') {
        vm.form.date_end = vm.form.semester == 1 ? tahun + '-06-30' : tahun + '-12-31'
      } else if (vm.form.parameter == 'tahun') {
        vm.form.date_end = tahun + '-12-31'
      }

      params = {
        ...vm.form,
        tahun: tahun
      };

      if (vm.constant) {
        params.tipe = 'pembantu'
      }
      const queryString = new URLSearchParams(params).toString();

      window.open(
        window._HOST +
        `/laporan/penyusutan/report?${queryString}`,
        'name',
        'height=500, width=800, top=50, left=50'
      );
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
            <a-form-item label="Jenis Laporan" data-column="jenis_laporan" :rules="[{ required: true }]">
              <a-radio-group v-model:value="form.jenis_laporan" name="radioGroup">
                <a-radio value="1">Intrakomptabel</a-radio>
                <a-radio value="2">Ekstrakomptabel</a-radio>
                <a-radio value="3">Gabungan Intrakomptabel dan Ekstrakomptabel</a-radio>
              </a-radio-group>
            </a-form-item>
            <a-form-item label="Lokasi" data-column="kode_lokasi" :rules="[{ required: true }]" v-if="constant">
              <a-select v-model:value="form.kode_lokasi" placeholder="--Pilih Lokasi--">
                <a-select-option :key="lokasi.id" v-for="lokasi in constant.KODE_LOKASI" :value="lokasi.kode">
                  {{ lokasi.kode + ' - ' + lokasi.nama }}
                </a-select-option>
              </a-select>
            </a-form-item>
            <a-form-item label="Rincian" data-column="rincian" :rules="[{ required: true }]">
              <a-radio-group v-model:value="form.rincian" name="radioGroup">
                <a-radio value="10">Sub-sub Kelompok</a-radio>
                <a-radio value="7">Sub Kelompok</a-radio>
                <a-radio value="5">Kelompok</a-radio>
              </a-radio-group>
            </a-form-item>
            <a-form-item label="Parameter" data-column="parameter" :rules="[{ required: true }]">
              <a-radio-group v-model:value="form.parameter" name="radioGroup">
                <a-radio value="tanggal">Per Tanggal</a-radio>
                <a-radio value="semester">Semesteran</a-radio>
                <a-radio value="tahun">Tahunan</a-radio>
              </a-radio-group>
            </a-form-item>
            <a-form-item label="Sampai Dengan Tanggal" data-column="tanggal" :rules="[{ required: true }]"
              v-if="form.parameter == 'tanggal'">
              <a-date-picker v-model:value="tanggal" :format="date_format" :allow-clear="false" />
            </a-form-item>
            <a-form-item label="Tahun" data-column="tahun" :rules="[{ required: true }]"
              v-if="form.parameter !== 'tanggal'">
              <a-date-picker v-model:value="form.tahun" picker="year" :allow-clear="false" />
            </a-form-item>
            <a-form-item label="Semester" data-column="semester" :rules="[{ required: true }]"
              v-if="form.parameter == 'semester'">
              <a-radio-group v-model:value="form.semester" name="radioGroup">
                <a-radio value="1">Semester I</a-radio>
                <a-radio value="2">Semester II</a-radio>
              </a-radio-group>
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