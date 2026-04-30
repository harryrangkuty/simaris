<script>
const columns = [
  {
    title: "#",
    key: "number",
    align: "center",
    width: 10,
  },
  {
    title: "Deskripsi",
    dataIndex: "deskripsi",
    align: "left",
  },
  {
    title: "Kategori",
    key: "kategori",
    align: "left",
    width: 200,
  },
  {
    title: "Kondisi",
    key: "kondisi",
    align: "center",
    width: 170,
  },
  {
    title: "Kuantitas",
    dataIndex: "kuantitas",
    align: "right",
    width: 80,
  },
  {
    title: "Harga Satuan",
    key: "harga_satuan",
    align: "right",
    width: 150,
  },
  {
    title: "Nilai Perolehan",
    key: "nilai_perolehan",
    align: "right",
    width: 150,
  },
  {
    title: "Subtotal",
    key: "sub_total",
    align: "right",
    width: 150,
  },
  {
    title: "Komptabel",
    dataIndex: "komptabel",
    align: "left",
    width: 100,
  },
  {
    title: "Masa Manfaat",
    key: "masa_manfaat",
    align: "center",
    width: 30,
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
        satker_id: null,
        year: null,
        jenis_transaksi_id: null,
        status_perolehan: null,
      },
      is_fetched: false,
      activeFile: null,
      selectedFileName: null
    };
  },
  created() { },

  mounted() { },

  methods: {
    async readData() {
      const vm = this;
      vm.loadingTrue();

      const params = {
        req: "rekap_per_satker",
        ...vm.filters
      };

      const response = await vm.axios.get(vm.readRoute, { params })
      if (response && response.data) {
        vm.models = response.data.models;
        vm.is_fetched = true;
        vm.loadingFalse();
      }
    },

    async previewFile(file) {
      const vm = this
      vm.selectedFileName = file.name; 
      vm.showModal = true;
      vm.activeFile = file.name;
      vm.loadingTrue();

      const params = {
        req: "preview_file",
        no: file.no,
        code: file.code,
        filename: file.name
      };

      const config = {
        params,
        responseType: 'blob',
      };

      const response = await vm.axios.get(vm.readRoute, config);

      if (response && response.data) {
        const url = window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }));
        document.getElementById('pdf').data = url;
        document.getElementById('pdfx').src = url;

        vm.loadingFalse();
      }
    },
  }
};
</script>

<template>
  <div class="w-full flex justify-center">
    <div class="lg:w-4/5 w-full shadow-md rounded-[8px] overflow-hidden mb-6">
      <a-card class="card card-white !px-5 !mt-0" title="Monitor Per Satker">
        <a-row>
          <a-col class="gutter-row" :span="24" :md="12">
            <a-form ref="form" name="basic" :label-col="{ span: 6 }" :wrapper-col="{ span: 18 }">
              <a-form-item label="Satuan Kerja" data-column="satker_id" :rules="[{ required: true }]">
                <a-select v-model:value="filters.satker_id" allow-clear show-search option-filter-prop="title"
                  style="width: 100%" placeholder="Pilih Satker">
                  <a-select-option v-for="satker in constant.SATKER" :key="satker.id" :value="satker.id"
                    :title="satker.nama">
                    {{ satker.nama }}
                  </a-select-option>
                </a-select>
              </a-form-item>
              <a-form-item label="Jenis Transaksi" data-column="jenis_transaksi_id">
                <a-select v-model:value="filters.jenis_transaksi_id" allow-clear show-search option-filter-prop="title"
                  style="width: 100%" placeholder="Pilih Satker">
                  <a-select-option v-for="transaksi in constant.JENIS_TRANSAKSI_OPT" :key="transaksi.kode"
                    :value="transaksi.kode" :title="transaksi.uraian">
                    {{ transaksi.kode }} - {{ transaksi.uraian }}
                  </a-select-option>
                </a-select>
              </a-form-item>
            </a-form>
          </a-col>
          <a-col class="gutter-row" :span="24" :md="12">
            <a-form ref="form" name="basic" :label-col="{ span: 6 }" :wrapper-col="{ span: 18 }">
              <a-form-item label="Tahun" data-column="year">
                <a-date-picker v-model:value="filters.year" picker="year" value-format="YYYY" />
              </a-form-item>
              <a-form-item label="Status" data-column="status_perolehan">
                <a-select v-model:value="filters.status_perolehan" placeholder="--Pilih Status Perolehan--">
                  <a-select-option :value="null">Semua</a-select-option>
                  <a-select-option value="OPEN">Open</a-select-option>
                  <a-select-option value="CLOSE">Close</a-select-option>
                  <a-select-option value="FINISH">Finish</a-select-option>
                </a-select>
              </a-form-item>
            </a-form>
          </a-col>
        </a-row>
        <a-button type="primary" @click="readData()" :disabled="!filters.satker_id">Read</a-button>
      </a-card>
    </div>
  </div>
  <div v-if="loadingStatus && !showModal" class="flex items-center justify-center bg-lavender gap-x-2 pb-10">
    <div class="w-4 h-4 border-4 border-t-4 border-blue-500 border-solid rounded-full animate-ping"></div>
    Memuat....
  </div>
  <div v-else-if="models.length > 0">
    <h1 class="text-xl px-2 font-bold">Data Perolehan</h1>
    <a-collapse :bordered="false" class="rounded-full">
      <a-collapse-panel v-for="(x, y) in models" :key="y">
        <template #header>
          <div class="flex items-center gap-x-2">
            <span class="font-bold">{{ x.kode }}</span>
            <a-tag :color="x.status == 'FINISH' ? 'blue' : (x.status == 'OPEN' ? 'green' : 'red')">
              <span>{{ x.status }}</span>
            </a-tag>
          </div>
        </template>
        <a-row :gutter="16">
          <a-col class="gutter-row" :span="24" :md="12">
            <a-descriptions bordered size="middle" :column="1" class="mt-2">
              <a-descriptions-item>
                <template #label>
                  <div class="flex items-center gap-x-2">
                    <i class="i-[ant-design--file-unknown] text-lg" />
                    <span>Jenis Transaksi</span>
                  </div>
                </template>
                <span>{{ x.jenis_transaksi.kode }} - {{ x.jenis_transaksi.uraian }}</span>
              </a-descriptions-item>
              <a-descriptions-item>
                <template #label>
                  <div class="flex items-center gap-x-2">
                    <i class="i-[ant-design--file-unknown] text-lg" />
                    <span>Tanggal Perolehan</span>
                  </div>
                </template>
                <span>{{ filterDate(x.tgl_perolehan) }}</span>
              </a-descriptions-item>
              <a-descriptions-item>
                <template #label>
                  <div class="flex items-center gap-x-2">
                    <i class="i-[ant-design--file-unknown] text-lg" />
                    <span>Sumber Dana</span>
                  </div>
                </template>
                <span>{{ x.sumber_dana }}</span>
              </a-descriptions-item>
              <a-descriptions-item>
                <template #label>
                  <div class="flex items-center gap-x-2">
                    <i class="i-[ant-design--file-unknown] text-lg" />
                    <span>Tanggal Surat</span>
                  </div>
                </template>
                <span>{{ x.tgl_surat }}</span>
              </a-descriptions-item>
              <a-descriptions-item>
                <template #label>
                  <div class="flex items-center gap-x-2">
                    <i class="i-[ant-design--file-unknown] text-lg" />
                    <span>Nomor Surat</span>
                  </div>
                </template>
                <span>{{ x.no_surat }}</span>
              </a-descriptions-item>
              <a-descriptions-item>
                <template #label>
                  <div class="flex items-center gap-x-2">
                    <i class="i-[ant-design--file-unknown] text-lg" />
                    <span>Total Jenis Aset</span>
                  </div>
                </template>
                <span>{{ x.detail_count }} Jenis Aset</span>
              </a-descriptions-item>
              <a-descriptions-item>
                <template #label>
                  <div class="flex items-center gap-x-2">
                    <i class="i-[ant-design--file-unknown] text-lg" />
                    <span>Total Aset</span>
                  </div>
                </template>
                <span>{{ x.total_item }} Aset</span>
              </a-descriptions-item>
              <a-descriptions-item>
                <template #label>
                  <div class="flex items-center gap-x-2">
                    <i class="i-[ant-design--file-unknown] text-lg" />
                    <span>Total Nilai Perolehan</span>
                  </div>
                </template>
                <span>Rp{{ idCurrency(x.total) }}</span>
              </a-descriptions-item>
            </a-descriptions>
          </a-col>
          <a-col class="gutter-row" :span="24" :md="12">
            <a-descriptions bordered size="middle" :column="1" class="mt-2">
              <a-descriptions-item>
                <template #label>
                  <div class="flex items-center gap-x-2">
                    <i class="i-[ant-design--file-unknown] text-lg" />
                    <span>Catatan</span>
                  </div>
                </template>
                <span>{{ x.notes ? x.notes : '-' }}</span>
              </a-descriptions-item>
              <a-descriptions-item>
                <template #label>
                  <div class="flex items-center gap-x-2">
                    <i class="i-[ant-design--file-unknown] text-lg" />
                    <span>Editor</span>
                  </div>
                </template>
                <span>{{ x.editor.name }}</span>
              </a-descriptions-item>
              <a-descriptions-item>
                <template #label>
                  <div class="flex items-center gap-x-2">
                    <i class="i-[ant-design--file-unknown] text-lg" />
                    <span>Tanggal Buku</span>
                  </div>
                </template>
                <span>{{ x.created_at }}</span>
              </a-descriptions-item>
              <a-descriptions-item>
                <template #label>
                  <div class="flex items-center gap-x-2">
                    <i class="i-[ant-design--file-unknown] text-lg" />
                    <span>Terakhir Diubah Pada</span>
                  </div>
                </template>
                <span>{{ x.updated_at }}</span>
              </a-descriptions-item>
              <a-descriptions-item>
                <template #label>
                  <div class="flex items-center gap-x-2">
                    <i class="i-[ant-design--file-unknown] text-lg" />
                    <span>Dokumen</span>
                  </div>
                </template>
                <a-row v-for="file in x.files" type="flex" justify="space-between" align="middle"
                  :class="['border-2', 'rounded-md', { 'border-sky-400 bg-blue-100': file.name === activeFile }]"
                  class="p-1.5 mt-1.5">
                  <a-col class="lg:w-10/12 w-3/4">
                    <a-tooltip :title="file.name">
                      <div class="truncate">{{ file.name }}</div>
                    </a-tooltip>
                  </a-col>
                  <a-col>
                    <a-button @click="previewFile(file)" type="success" size="small">
                      <Icon icon='ant-design:eye-outlined' />
                    </a-button>
                  </a-col>
                </a-row>
              </a-descriptions-item>
            </a-descriptions>
          </a-col>
        </a-row>
        <p class="text-lg font-bold text-center my-4">Detail Perolehan</p>
        <a-table :scroll="{ x: 800 }" :columns="columns" :row-key="(obj) => obj.id" :pagination="false"
          :loading="loadingStatus" :data-source="x.detail">
          <template #bodyCell="{ record, index, column }">
            <template v-if="column.key === 'number'">
              {{ index + 1 }}
            </template>
            <template v-if="column.key === 'kategori'">
              {{ record.kategori.kd_brg }} - {{ record.kategori.uraian }}
            </template>
            <template v-if="column.key === 'kondisi'">
              <span :style="{
                backgroundColor:
                  record.kondisi === 1
                    ? 'green'
                    : record.kondisi === 2
                      ? 'orange'
                      : 'red',
              }" class="px-3 py-1 rounded text-white">
                {{ labelKondisi(record.kondisi) }}
              </span>
            </template>
            <template v-if="column.key === 'harga_satuan'">
              {{ idCurrency(record.nilai_perolehan) }}
            </template>
            <template v-if="column.key === 'nilai_perolehan'">
              {{
                idCurrency(
                  this.numRound(
                    (record.nilai_perolehan * (100 + record.ppn)) / 100
                  )
                )
              }}
            </template>
            <template v-if="column.key === 'sub_total'">
              {{ idCurrency(record.sub_total) }}
            </template>
            <template v-if="column.key === 'masa_manfaat'">
              {{
                record.masa_manfaat
                  ? record.masa_manfaat + "&nbsp;Bulan"
                  : "-"
              }}
            </template>
          </template>
        </a-table>
      </a-collapse-panel>
    </a-collapse>
  </div>
  <div v-else-if="is_fetched && models.length == 0" class="flex gap-x-1 true-center">
    <Icon icon="line-md:alert-circle" class="text-xl text-red-500" />
    <span class="text-base font-semibold">Data Tidak Ditemukan</span>
  </div>
  <a-modal v-model:open="showModal" :title="selectedFileName" width="900px" :footer="null">
    <div v-if="loadingStatus" class="absolute inset-0 flex items-center justify-center bg-white gap-x-2">
      <div class="w-4 h-4 border-4 border-t-4 border-blue-500 border-solid rounded-full animate-ping"></div>
      Memuat....
    </div>
    <object :id="'pdf'" type="application/pdf" class="rounded-[8px] !w-full !h-96">
      <embed :id="'pdfx'" type="application/pdf" />
      <param name="view" value="fit" />
    </object>
  </a-modal>
</template>