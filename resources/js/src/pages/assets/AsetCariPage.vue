<script>

const columns = [
    {
        title: "Action",
        key: "action",
        align: "center",
        width: 10
    },
    {
        title: "Deskripsi",
        key: "deskripsi",
        width: 300
    },
    {
        title: "Jenis Perolehan",
        key: "jenis_perolehan",
        width: 200
    },
    {
        title: "Tgl Perolehan",
        key: "tgl_perolehan",
        align: "center"
    },
    {
        title: "Tgl Buku",
        key: "tgl_buku",
        align: "center"
    },
    {
        title: "Kategori",
        key: "kategori",
        align: "center"
    },
    {
        title: "NUP",
        key: "nup",
        dataIndex: "nup",
        align: 'center'
    },
    {
        title: "Kondisi",
        key: "kondisi",
        align: "center",
        width: 70
    },
    {
        title: "Nilai Buku",
        key: "nilai_buku",
        align: "right"
    },
    {
        title: "Komptabel",
        key: "komptabel",
        dataIndex: "komptabel"
    },
    {
        title: "Nama Ruang",
        key: "ruang_nama",
        dataIndex: "ruang_nama",
        width: 200
    },
    {
        title: "Jenis KIB",
        key: "jenis_kib"
    },
    {
        title: "Status",
        key: "status",
        align: 'center'
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
            showTable: false,
            showPrintLabelModal: false,
            filters: {
                satker_id: null,
                kib: null,
                tipe_ruang: null,
                ruang_id: null,
                kondisi: null,
                henti_guna: null,
                komptabel: null,
                kategori_id: null,
                nilai_buku_null: false,
                aset_dihapus: false,
                tahun_perolehan: null,
                kode_perolehan: null,
                keterangan_simak: null,
            },
            form_cetak_label: {
                id: null,
                mode: 'standard',
                tipe_kertas: 'kertas-biasa',
            },
            showSelectKategori: false,
            selectedDate: null,
            totalData: 0,
        };
    },
    
    created() {
        if (this.operator_aset && this.user.active_role_id) {
            this.filters.satker_id = this.user.satkers_id[0];
        } else if (this.reviewer_aset && this.user.active_role_id) {
            this.filters.satker_id = this.user.reviews_id[0];
        }
    },

    computed: {
        filteredRuangOptions() {
            if (this.filters.satker_id) {
                return this.constant.RUANG.filter(ruang => ruang.satker_id === this.filters.satker_id);
            } else {
                return this.constant.RUANG
            }
        },
        displaySatkerName() {
            return this.filters.satker_id && this.constant.SATKER[this.filters.satker_id] ? this.constant.SATKER[this.filters.satker_id].nama : '';
        }
    },

    methods: {

        async readData(v) {
            let params = v ?? {
                total: this._pagination.total,
                page: this._pagination.current,
            };
            const vm = this;
            vm.loadingTrue();
            vm.showTable = true;

            params = {
                req: "table",
                results: 10,
                ...params,
                ...vm.filter,
                ...vm.filters
            };

            const response = await vm.axios.get(vm.readRoute, { params })
            if (response && response.data) {

                const pagination = { ...vm._pagination };
                pagination.total = response.data.models.total;
                vm.models = response.data.models.data;
                vm._pagination = pagination;
                vm.loadingFalse();
                vm.totalData = response.data.models.total; 
            }
        },

        printData() {
            const vm = this;

            let params = {
                req: 'print_data',
                ...vm.filter,
                ...vm.filters
            };

            params = Object.fromEntries(
                Object.entries(params).filter(([_, v]) => v !== null && v !== false && v !== undefined)
            );

            const _window = window.open(`${window._HOST}/aset/cari/read?${new URLSearchParams(params).toString()}`, 'name', 'height=500, width=850, top=50, left=50');
            if (window.focus) {
                _window.focus()
            }
            return false;
        },

        openDetail(kategori_id, nup) {
            const paddedNup = String(nup).padStart(6, '0');

            const idToOpen = kategori_id + paddedNup;

            const _window = window.open(`/aset/profil?req=open&id=${idToOpen}`);
            if (window.focus) {
                _window.focus();
            }
            return false;
        },

        openPrintModalLabel(id) {
            const vm = this;           
            vm.$nextTick(function () {
                vm.showPrintLabelModal = true;
                vm.form_cetak_label.id = id;
            })
        },

        printAsetLabel() {
            const vm = this;
            const _window = window.open(`/aset/profil?req=single_label_print&id=${vm.form_cetak_label.id}&mode=${vm.form_cetak_label.mode}&tipe_kertas=${vm.form_cetak_label.tipe_kertas}`, 'name', 'height=500, width=800, top=50, left=50');
            if (window.focus) {
                _window.focus();
            }
            return false;
        },

        clearFilter() {
            this.showTable = false;
            this.showSelectKategori = false; 
            this.filters.kategori_id = null;
            this.models = [];
        },

        updateYearFilter(selectedDate) {
            if (selectedDate) {
                const selectedYear = new Date(selectedDate).getFullYear();
                this.filters.tahun_perolehan = selectedYear;
            } else {
                this.filters.tahun_perolehan = null;
            }
        },
        
        async exportToExcel() {
            const vm = this;
            let params = {
                req: "data_in_excel",
                ...vm.filter,
                ...vm.filters
            };

            params = Object.fromEntries(
                Object.entries(params).filter(([_, v]) => v !== null && v !== false && v !== undefined)
            );

            const response = await vm.axios.get(vm.readRoute, { 
                params, 
                responseType: 'blob', 
            }).catch((e) => vm.$onAjaxError(e));

            if (response) {
                const fileURL = window.URL.createObjectURL(new Blob([response.data]));
                const link = document.createElement('a');
                link.href = fileURL;
                link.setAttribute('download', 'data_aset_tetap.xlsx');
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                vm.openNotification('Berhasil ekspor data ke Excel', 'success');
            }
        }
    }
};
</script>

<template>
    <div class="!w-full shadow-md rounded-[8px] overflow-hidden flex-grow lg:mb-0 mb-6">
        <a-card class="card card-white !px-5 h-full" title="Cari AT">
            <a-row :gutter="{ xs: 8, sm: 16, md: 24, lg: 32 }">
                <a-col class="gutter-row" :span="24" :md="12" :xl="8">
                    <a-form :label-col="{ span: 7 }" :wrapper-col="{ span: 17 }">
                        <a-form-item label="Satuan Kerja">
                            <a-select v-model:value="filters.satker_id" show-search
                                option-filter-prop="title" style="width: 100%" placeholder="Pilih Satuan Kerja"
                                :disabled="Object.keys(constant.SATKER).length < 2"
                                :title="displaySatkerName">
                                <a-select-option v-for="satker in constant.SATKER" :key="satker.id"
                                    :value="satker.id" :title="satker.nama">
                                    {{ satker.nama }}
                                </a-select-option>
                            </a-select>
                        </a-form-item>
                        <a-form-item label="Nama Ruang">
                            <a-select v-model:value="filters.ruang_id" show-search allow-clear
                                option-filter-prop="title" style="width: 100%" placeholder="Pilih Ruang"
                                :getPopupContainer="(trigger) => trigger.parentNode">
                                <a-select-option :key="ruang.id" v-for="ruang in filteredRuangOptions" :value="ruang.id"
                                    :title="ruang.nama" :search="ruang.kode + ' ' + ruang.nama">
                                    {{ ruang.kode }} - {{ ruang.nama }}
                                </a-select-option>
                            </a-select>
                        </a-form-item>
                        <a-form-item label="Tahun Perolehan">
                            <a-date-picker v-model:value="selectedDate" picker="year" @change="updateYearFilter"></a-date-picker>
                        </a-form-item>
                        <a-form-item label="Komptabel">
                            <a-select v-model:value="filters.komptabel" show-search allow-clear style="width: 100%"
                                placeholder="Komptabel" :getPopupContainer="(trigger) => trigger.parentNode">
                                <a-select-option value="Intra">Intrakomptabel</a-select-option>
                                <a-select-option value="Ekstra">Ekstrakomptabel</a-select-option>
                            </a-select>
                        </a-form-item>
                    </a-form>
                </a-col>
                <a-col class="gutter-row" :span="24" :md="12" :xl="8">
                    <a-form :label-col="{ span: 7 }" :wrapper-col="{ span: 17 }">
                        <a-form-item label="Deskripsi">
                            <a-input v-model:value="filter.search" placeholder="Ketikkan Deskripsi" style="width: 100%"/>
                        </a-form-item>
                        <a-form-item label="Keterangan">
                            <a-input v-model:value="filters.keterangan_simak" placeholder="Ketikkan Keterangan dari SIMAK-BMN" style="width: 100%"/>
                        </a-form-item>
                        <a-form-item label="Kepemilikan KIB">
                            <a-select v-model:value="filters.kib" show-search allow-clear placeholder="KIB" style="width: 100%" 
                                :getPopupContainer="(trigger) => trigger.parentNode">
                                <a-select-option :value=1>Memiliki KIB</a-select-option>
                                <a-select-option :value=2>Tidak Memiliki KIB</a-select-option>
                            </a-select>
                        </a-form-item>
                        <a-form-item label="Kondisi">
                            <a-select v-model:value="filters.kondisi" show-search allow-clear style="width: 100%"
                                placeholder="Kondisi" :getPopupContainer="(trigger) => trigger.parentNode">
                                <a-select-option :value=1>Baik</a-select-option>
                                <a-select-option :value=2>Rusak Ringan</a-select-option>
                                <a-select-option :value=3>Rusak Berat</a-select-option>
                            </a-select>
                        </a-form-item>
                        <a-form-item label="Kategori">
                            <select-kategori v-if="showSelectKategori" v-model:kategori_id="filters.kategori_id" :is_filter="true" mode="aset"/>
                            <a-button-group class="!w-full flex flex-col sm:flex-row mb-4">
                                <a-button @click="clearFilter" :type="showSelectKategori ? '' : 'primary'" class="!w-full">
                                    Semua Kategori
                                </a-button>
                                <a-button v-if="!showSelectKategori" @click="showSelectKategori = true" class="!w-full">
                                    Filter Kategori
                                </a-button>
                            </a-button-group>
                        </a-form-item>
                    </a-form>
                </a-col>
                <a-col class="gutter-row" :span="24" :md="12" :xl="8">
                    <a-form :label-col="{ span: 7 }" :wrapper-col="{ span: 17 }">
                        <a-form-item label="Tipe Ruang">
                            <a-select v-model:value="filters.tipe_ruang" show-search allow-clear style="width: 100%"
                                placeholder="Pilih Tipe Ruang" :getPopupContainer="(trigger) => trigger.parentNode">
                                <a-select-option value="DBR">DBR</a-select-option>
                                <a-select-option value="DBL">DBL</a-select-option>
                            </a-select>
                        </a-form-item>
                        <a-form-item label="Status Keaktifan">
                            <a-select v-model:value="filters.henti_guna" show-search allow-clear style="width: 100%"
                                placeholder="Pilih Status Keaktifan" :getPopupContainer="(trigger) => trigger.parentNode">
                                <a-select-option :value=0>Aktif dipakai</a-select-option>
                                <a-select-option :value=1>Henti Guna</a-select-option>
                            </a-select>
                        </a-form-item>
                        <a-form-item label="Perolehan">
                            <a-tooltip placement="topLeft" title="Masuk ke Menu Perolehan AT lalu salin Kode Perolehan, contoh AM240527-0042">
                                <a-input v-model:value="filters.kode_perolehan" placeholder="Input Kode Perolehan (AM..-...)" style="width: 100%"/>
                            </a-tooltip>
                        </a-form-item>
                        <a-form-item label="Nilai Buku 0">
                            <a-checkbox :checked="filters.nilai_buku_null" @change="filters.nilai_buku_null = !filters.nilai_buku_null"  />
                            <a-tooltip placement="topLeft"
                                title="Untuk mencari data aset yang sudah berstatus henti guna">
                                <span class="ml-4">
                                    Aset yang Dihapus :&nbsp;
                                    <a-checkbox :checked="filters.aset_dihapus" @change="filters.aset_dihapus = !filters.aset_dihapus" />
                                </span>
                            </a-tooltip>
                        </a-form-item>
                    </a-form>
                </a-col>
            </a-row>
            <a-button-group class="!w-full flex flex-col sm:flex-row gap-x-2">
                <a-button type="primary"  class="flex items-center justify-center w-full sm:w-auto min-w-[6rem] mb-2" @click="readData()">Read Data</a-button>
                <a-button type="success"  class="flex items-center justify-center w-full sm:w-auto min-w-[6rem] mb-2" @click="printData()">Print Data</a-button>
                <a-button type="warning"  class="flex items-center justify-center w-full sm:w-auto min-w-[6rem] mb-2" @click="exportToExcel()">Export to Excel</a-button>
            </a-button-group>
        </a-card>
    </div>
    <div class="!w-full shadow-md rounded-[8px] overflow-hidden flex-grow lg:mb-0 mt-8 mb-6">
        <a-card class="card card-white !px-5 h-full">
            <div v-if="totalData > 0" class="my-5"><strong>Total Data: {{ totalData }}</strong></div>
            <a-table v-if="showTable" :columns="columns" :row-key="(obj) => obj.id" :pagination="_pagination"
                :loading="loadingStatus" :data-source="models" @change="handleTableChange">
                <template #bodyCell="{ index, column, record }">
                    <template v-if="column.key === 'action'">
                        <a-button-group>
                            <a-button size="small" type="primary" @click="openPrintModalLabel(record.id)">
                                Label
                            </a-button>
                            <a-button size="small" type="success"
                                @click="openDetail(record.kategori_id, record.nup)">
                                <Icon icon='ant-design:database-outlined' />
                            </a-button>
                        </a-button-group>
                    </template>
                    <template v-if="column.key === 'number'">
                        {{ index + 1 }}
                    </template>
                    <template v-if="column.key === 'deskripsi'">
                        <span class="break-all">
                            {{ record.deskripsi }}
                        </span>
                    </template>
                    <template v-if="column.key === 'jenis_perolehan'">
                        {{ record.jenis_perolehan.kode }} {{ record.jenis_perolehan.uraian }}
                    </template>
                    <template v-if="column.key === 'kategori'">
                        {{ record.kategori_id }} <br> {{ record.kategori_nama }}
                    </template>
                    <template v-if="column.key === 'tgl_perolehan'">
                        {{ filterDate(record.tgl_perolehan) }}
                    </template>
                    <template v-if="column.key === 'tgl_buku'">
                        {{ filterDate(record.tgl_buku) }}
                    </template>
                    <template v-if="column.key === 'nilai_buku'">
                        {{ idCurrency(record.nilai_buku) }}
                    </template>
                    <template v-if="column.key === 'tgl_penghapusan'">
                        {{ record.tgl_penghapusan ? filterDate(record.tgl_penghapusan) : '-' }}
                    </template>
                    <template v-if="column.key === 'jenis_kib'">
                        {{ record.jenis_kib ? record.jenis_kib : '-' }}
                    </template>
                    <template v-if="column.key === 'kondisi'">
                        <span
                            :style="{ backgroundColor: (record.kondisi === 1 ? 'green' : (record.kondisi === 2 ? 'orange' : 'red')) }"
                            class="px-3 py-1 rounded text-white">
                            {{ labelKondisi(record.kondisi) }}
                        </span>
                    </template>
                    <template v-if="column.key === 'status'">
                        <a-tag :color="record.henti_guna == 0 ? 'green' : 'red'">
                            <span>{{ record.henti_guna == 0 ? 'Aktif Guna': 'Henti Guna'}}</span>
                        </a-tag>
                    </template>
                </template>
            </a-table>
        </a-card>
    </div>
    <!-- Modal Print Label -->
    <a-modal v-model:open="showPrintLabelModal" :title="'Cetak Label'" width="700px" :mask-closable="false"
        @ok="printAsetLabel" ok-text="Cetak Label">
        <a-form ref="form" name="basic" :label-col="{ span: 5 }" :wrapper-col="{ span: 19 }">
            <a-form-item label="Mode" data-column="mode" :rules="[{ required: true }]">
                <a-select v-model:value="form_cetak_label.mode" placeholder="--Pilih Mode--"
                    title="Mode akan menentukan ukuran sticker">
                    <a-select-option value="standard"
                        title="Ini adalah mode standard yang akan mencetak label dengan ukuran standard berbentuk persegi panjang">Standard</a-select-option>
                    <a-select-option value="mini"
                        title="Ini adalah mode yang akan mencetak label dengan ukuran mini berbentuk persegi">Mini</a-select-option>
                </a-select>
            </a-form-item>
            <a-form-item label="Tipe Kertas" data-column="tipe_kertas" :rules="[{ required: true }]">
                <a-select v-model:value="form_cetak_label.tipe_kertas" placeholder="--Pilih Tipe Kertas--">
                    <a-select-option value="kertas-biasa"
                        title="Kertas biasa adalah kertas yang berukuran a4, f4 dan ukuran lainnya">Kertas
                        Biasa</a-select-option>
                    <a-select-option value="stiker"
                        title="Kertas stiker adalah kertas stiker label yang telah terpotong">Kertas
                        Stiker</a-select-option>
                </a-select>
            </a-form-item>
        </a-form>
    </a-modal>
</template>