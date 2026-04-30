<script>
const columns = [
    {
        title: "Action",
        key: "action",
        align: "center",
        width: 10,
    },
    {
        title: "Deskripsi",
        key: "deskripsi",
        dataIndex: 'deskripsi',
    },
    {
        title: "Jenis Perolehan",
        key: "jenis_perolehan",
        width: 200
    },
    {
        title: "Tgl Perolehan",
        key: "tgl_perolehan",
        align: "center",
    },
    {
        title: "Tgl Buku",
        key: "tgl_buku",
        align: "center",
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
        align: 'center',
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
        dataIndex: "komptabel",
    },
    {
        title: "Tipe Ruang",
        key: "tipe_ruang",
        dataIndex: "tipe_ruang",
    },
    {
        title: "Nama Ruang",
        key: "ruang_nama",
        width: 200,
        dataIndex: "ruang_nama",
    },
    {
        title: "Jenis KIB",
        key: "jenis_kib",
        dataIndex: "jenis_kib",
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
            showPrintLabelModal: false,
            filters: {
                satker_id: null,
                kategori_id: null,
                kib: 0,
            },
            form_cetak_label: {
                id: null,
                mode: 'standard',
                tipe_kertas: 'kertas-biasa',
            },
            isModalVisible: false,
            isSelectedItemExist: false
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
        this.readData()
    },

    computed: {
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
            }

        },

        openDetail(kategori_id, nup) {
            const paddedNup = String(nup).padStart(6, '0');

            const idToOpen = kategori_id + paddedNup;

            const vm = this;
            const _window = window.open(`${vm.route}?req=open&id=${idToOpen}`);
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
            const _window = window.open(`${vm.route}?req=single_label_print&id=${vm.form_cetak_label.id}&mode=${vm.form_cetak_label.mode}&tipe_kertas=${vm.form_cetak_label.tipe_kertas}`, 'name', 'height=500, width=800, top=50, left=50');
            if (window.focus) {
                _window.focus();
            }
            return false;
        },

        // agar method dipanggil saat tab diklik
        handleTabClick(key) {
            this.$nextTick(() => {
                if (key === '1') {
                    this.readData();
                } else if (key === '3' && this.$refs.riwayatDistribusiRuang) {
                    this.$refs.riwayatDistribusiRuang.readData();
                }
            });
        },

        handleSelecteditemExist(isExist) {
            this.isSelectedItemExist = isExist;
        },

    }
};
</script>

<template>
    <a-row type="flex" justify="center">
        <a-col :span="24">
            <a-card class="card">
                <h1 class="text-xl font-semibold mt-5">List - Aset Tetap</h1>
                <a-tabs @tabClick="handleTabClick">
                    <a-tab-pane key="1" tab="Semua">
                        <a-row class="flex flex-wrap items-center justify-between mb-2 border-b-2">
                            <div class="flex justify-end items-end w-full">
                                <a-row class="flex flex-wrap justify-end gap-2 items-center w-full md:w-auto">
                                    <a-col class="lg:w-auto w-full mb-4 lg:mb-0">
                                        <a-select v-model:value="filters.kib" show-search class="lg:w-40 w-full"
                                            @change="readData" :getPopupContainer="(trigger) => trigger.parentNode">
                                            <a-select-option :value=0>Semua</a-select-option>
                                            <a-select-option :value=1>Memiliki KIB</a-select-option>
                                            <a-select-option :value=2>Tidak Memiliki KIB</a-select-option>
                                        </a-select>
                                    </a-col>
                                    <a-col class="lg:w-auto w-full mb-4 lg:mb-0">
                                        <a-select v-model:value="filters.satker_id" show-search
                                            option-filter-prop="title" class="lg:w-[350px] w-full"
                                            placeholder="Satuan Kerja" @change="readData"
                                            :getPopupContainer="(trigger) => trigger.parentNode"
                                            :title="displaySatkerName">
                                            <a-select-option v-for="satker in constant.SATKER" :key="satker.id"
                                                :value="satker.id" :title="satker.nama">
                                                {{ satker.nama }}
                                            </a-select-option>
                                        </a-select>
                                    </a-col>
                                    <a-col class="lg:w-auto w-full mb-4 lg:mb-0">
                                        <div class="lg:w-72 w-full">
                                            <select-kategori v-model:kategori_id="filters.kategori_id" :is_filter="true"
                                                mode="aset" @update:kategori_id="readData" />
                                        </div>
                                    </a-col>
                                    <a-col class="lg:w-auto w-full mb-4 lg:mb-0">
                                        <a-input v-model:value="filter.search" @keyup.enter="readData"
                                            placeholder="Ketikkan Deskripsi Aset" class="lg:w-40 w-full">
                                            <template #addonAfter>
                                                <Icon icon='ant-design:search-outlined' />
                                            </template>
                                        </a-input>
                                    </a-col>
                                </a-row>
                            </div>
                        </a-row>

                        <a-table :scroll="{ x: 800 }" :columns="columns" :row-key="(obj) => obj.id"
                            :pagination="_pagination" :loading="loadingStatus" :data-source="models"
                            @change="handleTableChange">
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
                    </a-tab-pane>
                    <a-tab-pane key="2">
                        <template #tab>
                            <a-tooltip placement="topLeft"
                                :title="isSelectedItemExist ? 'Ada data yang anda pilih pada tab ini. Harap di cek lebih lanjut' : null">
                                <div class="flex items-center gap-x-1">
                                    <span>Aksi</span>
                                    <Icon v-if="isSelectedItemExist" icon="line-md:alert-circle-twotone"
                                        class="text-yellow-500 h-5 w-5" />
                                </div>
                            </a-tooltip>
                        </template>
                        <aksi-list-profil :constant="constant" @thereIsSelectedItem="handleSelecteditemExist" />
                    </a-tab-pane>
                    <a-tab-pane key="3" tab="Riwayat Distribusi Ruang">
                        <riwayat-distribusi-ruang :constant="constant" ref="riwayatDistribusiRuang" />
                    </a-tab-pane>
                </a-tabs>
            </a-card>
        </a-col>
    </a-row>

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