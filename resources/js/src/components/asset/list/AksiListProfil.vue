<script>
const columns = [
    {
        title: "Action",
        key: "action",
        align: "center",
        width: 170
    },
    {
        title: "Deskripsi",
        key: "deskripsi",
        dataIndex: 'deskripsi'
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
        title: "Ruang",
        key: "ruang",
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
    emits: ['thereIsSelectedItem'],
    props: {
        constant: {
            type: Object,
            default: () => ({}),
        },
    },
    data() {
        return {
            columns,
            showActionModal: false,
            showTable: false,
            checked_data: [],
            selected_categories_by_satker: [],
            nup_range: [],
            nupRange: [1, 100],
            filters: {
                satker_id: null,
                ruang_id: null,
                kategori_id: null,
                nilai_buku_null: false,
                henti_guna: false,
                status_distribusi: 0,
                tahun_perolehan: null,
                kode_perolehan: null,
            },
            form_distribusi: {
                tipe_ruang: null,
                ruang_id: null,
                keterangan_lokasi: null,
                catatan: null,
                satker_id: null,
            },
            form_ubah_kondisi: {
                kondisi: null,
            },
            form_penghentigunaan: {
                henti_guna: null,
            },
            form_cetak_label: {
                kategori_id: null,
                nup_start: null,
                nup_end: null,
                mode: 'standard',
                tipe_kertas: 'kertas-biasa',
            },
            showSelectKategori: false,
            selectedDate: null,
            selectAll: false,
            selectPaginated: false,
            selectedItems: [],
            actionType: null,
            distributionType: null,
            models_without_paginate: [],
            watcherActive: true,
            _pagination: {
                showSizeChanger: true,
                pageSize: 10,
            },
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
        window.addEventListener('beforeunload', this.handleBeforeUnload);
    },

    beforeDestroy() {
        window.removeEventListener('beforeunload', this.handleBeforeUnload);
    },

    computed: {
        filteredRuangOptions() {
            if (this.filters.satker_id) {
                return this.constant.RUANG.filter(ruang => ruang.satker_id === this.filters.satker_id);
            } else {
                return this.constant.RUANG;
            }
        },
        isAllSelected() {
            if (this.models_without_paginate.length > 0) {
                return this.selectedItems.length === this.models_without_paginate.length;
            } else if (this.models.length > 0) {
                return this.models.every(item => this.selectedItems.includes(item.id)) && this._pagination.total <= this._pagination.pageSize;;
            } else {
                return false;
            }
        },
        isPaginatedDataSelected() {
            return this.models.length > 0 && this.models.every(item => this.selectedItems.includes(item.id));
        },
        hasJenisKib() {
            return this.selectedItems.some(itemId =>
                this.models.some(item =>
                    item.id === itemId &&
                    item.jenis_kib &&
                    !["Alat Laboratorium", "Alat Angkutan", "Senjata"].includes(item.jenis_kib)));
        },
        displaySatkerName() {
            return this.filters.satker_id && this.constant.SATKER[this.filters.satker_id] ? this.constant.SATKER[this.filters.satker_id].nama : '';
        }
    },

    watch: {
        'filters.satker_id': function (newVal, oldVal) {
            //menangani form cetak label setelah ada perubahan satker_id
            this.selectedCategoryData();
            Object.assign(this.$data.form_cetak_label, this.$options.data().form_cetak_label);

            //menangani perubahan satker_id jika ada item yang telah di pilih
            if (this.selectedItems.length > 0 && newVal !== oldVal && this.watcherActive) {
                this.$confirm({
                    title: 'Konfirmasi',
                    content: 'Data akan direset karna anda mengubah filter satuan kerja. Apakah Anda yakin?',
                    onOk: () => {
                        this.models = [];
                        this.selectedItems = [];
                        this.selectAll = false;
                        this.showTable = false;
                        this.filters.ruang_id = null;
                        this.filters.kategori_id = null;
                        this.filters.nilai_buku_null = false;
                        this.filters.henti_guna = false;
                        this.filters.status_distribusi = 0;
                        this.filters.tahun_perolehan = null;
                        this.filters.kode_perolehan = null;
                        this.filter.search = null;
                        this.selectedDate = null;
                    },
                    onCancel: () => {
                        this.watcherActive = false;
                        this.filters.satker_id = oldVal;
                        this.$nextTick(() => {
                            this.watcherActive = true;
                        });
                    },
                });
            } else if (this.models.length > 0 && newVal !== oldVal && this.watcherActive) {
                this.models = [];
                this.showTable = false;
            }
        },
        actionType() {
            this.resetForms();
        },
        distributionType() {
            Object.assign(this.$data.form_distribusi, this.$options.data().form_distribusi);
        },
        'form_distribusi.tipe_ruang'() {
            this.form_distribusi.ruang_id = null;
            this.form_distribusi.keterangan_lokasi = null;
            this.form_distribusi.catatan = null;
        },
        models() {
            this.selectPaginated = this.isPaginatedDataSelected;
        },
        showActionModal() {
            this.actionType = null;
            this.distributionType = null;
        },
        isAllSelected(newVal) {
            this.selectAll = newVal;
        },
        isPaginatedDataSelected(newVal) {
            this.selectPaginated = newVal;
        },
        selectedItems: {
            handler(newVal) {
                if (newVal.length > 0) {
                    this.$emit('thereIsSelectedItem', true);
                } else {
                    this.$emit('thereIsSelectedItem', false);
                }
            },
            deep: true
        },
        "form_cetak_label.kategori_id": function (newVal) {
            if (newVal !== null) {
                this.nupRangeCheck(newVal);
            }
        },
        'form_cetak_label.nup_start'(newVal) {
            this.form_cetak_label.nup_end = newVal + 1;
        },
    },

    methods: {

        async readData(v) {
            const vm = this;
            vm.showTable = true;
            vm.loadingTrue();

            let params = v ?? {
                total: vm._pagination.total,
                page: vm._pagination.current,
                results: vm._pagination.pageSize,
            };

            params = {
                req: "data_profil",
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

        viewCheckedData() {
            const vm = this;
            vm.$nextTick(function () {
                vm.showModal = true;
            })
        },

        clearFilter() {
            this.showTable = false;
            this.showSelectKategori = false;
            this.filters.kategori_id = null;
            this.models = null;
        },

        updateYearFilter(selectedDate) {
            if (selectedDate) {
                const selectedYear = new Date(selectedDate).getFullYear();
                this.filters.tahun_perolehan = selectedYear;
            } else {
                this.filters.tahun_perolehan = null;
            }
        },

        toggleSelection(id) {
            if (!this.selectedItems.includes(id)) {
                this.selectedItems.push(id);
            } else {
                const index = this.selectedItems.indexOf(id);
                if (index > -1) {
                    this.selectedItems.splice(index, 1);
                }
            }

            this.selectAll = this.isAllSelected;
            this.selectPaginated = this.isPaginatedDataSelected;
        },

        async selectAllItems() {
            const vm = this;
            this.loadingTrue();

            const params = {
                req: "data_profil_all",
                ...vm.filter,
                ...vm.filters
            };

            const response = await vm.axios.get(vm.readRoute, { params })
            if (response && response.data) {
                vm.models_without_paginate = response.data.models;
                if (this.selectAll) {
                    this.selectedItems = this.models_without_paginate.map(i => i.id)
                } else {
                    this.selectedItems = [];
                }
                this.loadingFalse();
            }
        },

        async selectAllPaginatedData() {
            if (this.selectPaginated) {
                this.models.forEach(item => {
                    if (!this.selectedItems.includes(item.id)) {
                        this.selectedItems.push(item.id);
                    }
                });
            } else {
                this.selectedItems = this.selectedItems.filter(id => !this.models.some(item => item.id === id));
            }

            this.selectAll = this.isAllSelected;
        },

        clearFilter() {
            this.showTable = false;
            this.showSelectKategori = false;
            this.filters.kategori_id = null;
            this.models = [];
        },

        handleBeforeUnload(event) {
            if (this.selectedItems.length > 0) {
                const confirmationMessage = 'Anda memiliki data yang belum disimpan. Apakah Anda yakin ingin meninggalkan halaman ini?';
                event.returnValue = confirmationMessage;
                return confirmationMessage;

            }
        },

        action() {
            const vm = this;
            if (vm.selectedItems.length === 0) {
                this.$message.warning('Silakan pilih setidaknya satu item.');
                return;
            }
            vm.resetForms();
            vm.$nextTick(function () {
                vm.showActionModal = true
            })
        },

        async confirmAction() {
            const vm = this;

            let form = {};

            if (vm.actionType === 'distribusi') {
                form = {
                    req: 'distribute_bulk',
                    id: vm.selectedItems,
                    ...vm.form_distribusi
                };
            } else if (vm.actionType === 'ubah_kondisi') {
                form = {
                    req: 'update_condition_bulk',
                    id: vm.selectedItems,
                    ...vm.form_ubah_kondisi
                };
            } else if (vm.actionType === 'penghentigunaan') {
                form = {
                    req: 'asset_deactivate_bulk',
                    id: vm.selectedItems,
                    ...vm.form_penghentigunaan
                };
            }

            const response = await vm.axios.post(vm.writeRoute, form).catch((e) => vm.$onAjaxError(e));
            if (response && response.data) {
                vm.selectedItems = [];
                vm.resetForms();
                vm.showActionModal = false;
                vm.openNotification(response.data.message, 'success');
                vm.readData();
            }
        },

        async selectedCategoryData() {
            const vm = this;
            this.loadingTrue();
            const params = {
                req: "selected_categories_by_satker",
                satker_id: vm.filters.satker_id
            };

            const response = await vm.axios.get(vm.readRoute, { params })
            if (response && response.data) {
                vm.selected_categories_by_satker = response.data.models;
                vm.loadingFalse();
            }
        },

        async nupRangeCheck(newVal) {
            const vm = this;
            this.loadingTrue();
            const params = {
                req: "check_nup_range",
                kategori_id: newVal,
                satker_id: vm.filters.satker_id
            };

            const response = await vm.axios.get(vm.readRoute, { params })
            if (response && response.data) {
                vm.nup_range = response.data.models;
                vm.form_cetak_label.nup_start = vm.nup_range.min;
                vm.loadingFalse();
            }
        },

        printAsetLabel() {
            const vm = this;
            const _window = window.open(`${vm.route}?req=multi_label_print&kategori_id=${vm.form_cetak_label.kategori_id}&nup_start=${vm.form_cetak_label.nup_start}&nup_end=${vm.form_cetak_label.nup_end}&mode=${vm.form_cetak_label.mode}&tipe_kertas=${vm.form_cetak_label.tipe_kertas}`, 'name', 'height=500, width=800, top=50, left=50');
            if (window.focus) {
                _window.focus();
            }
            return false;
        },

        resetForms() {
            const vm = this;
            Object.assign(vm.$data.form_distribusi, vm.$options.data().form_distribusi);
            Object.assign(vm.$data.form_ubah_kondisi, vm.$options.data().form_ubah_kondisi);
            Object.assign(vm.$data.form_penghentigunaan, vm.$options.data().form_penghentigunaan);
        }

    },
};
</script>

<template>
    <div class="flex flex-wrap justify-end gap-2 items-center w-full md:w-auto mb-4">
        <span class="hidden lg:block">Satuan Kerja: </span>
        <a-select v-model:value="filters.satker_id" show-search option-filter-prop="title"
            placeholder="Pilih Satuan Kerja" class="lg:w-[350px] w-full"
            :getPopupContainer="(trigger) => trigger.parentNode" :title="displaySatkerName">
            <a-select-option v-for="satker in constant.SATKER" :key="satker.id" :value="satker.id" :title="satker.nama"
                :disabled="Object.keys(constant.SATKER).length < 2">
                {{ satker.nama }}
            </a-select-option>
        </a-select>
    </div>
    <div class="!w-full shadow-md rounded-[8px] overflow-hidden mb-6">
        <a-card class="card card-white !px-5 h-full" title="Cetak Label">
            <a-row :gutter="{ xs: 8, sm: 16, md: 24, lg: 32 }">
                <a-col class="gutter-row" :span="24" :md="12">
                    <a-form ref="form" name="basic" :label-col="{ span: 5 }" :wrapper-col="{ span: 19 }">
                        <a-form-item label="Kategori" data-column="kategori_id" :rules="[{ required: true }]">
                            <a-select v-model:value="form_cetak_label.kategori_id" allow-clear show-search
                                option-filter-prop="title" style="width: 100%" placeholder="Pilih Kategori">
                                <a-select-option v-for="i in selected_categories_by_satker" :key="i.kategori_id"
                                    :value="i.kategori_id" :title="i.kategori_nama">
                                    {{ i.kategori_id }} - {{ i.kategori_nama }}
                                </a-select-option>
                            </a-select>
                        </a-form-item>
                        <a-form-item label="Set NUP Range"
                            :rules="[{ required: true, message: 'NUP range harus diisi' }]">
                            <a-row :gutter="10">
                                <a-col :span="17">
                                    <a-slider v-model:value="form_cetak_label.nup_start" :min="nup_range.min"
                                        :max="nup_range.max - 1" :disabled="!form_cetak_label.kategori_id" />
                                </a-col>
                                <a-col :span="7">
                                    <a-input-number v-model:value="form_cetak_label.nup_start" :min="nup_range.min"
                                        :max="nup_range.max - 1" placeholder="Nup Awal" :controls="false"
                                        :disabled="!form_cetak_label.kategori_id" />
                                </a-col>
                            </a-row>
                            <a-row :gutter="10" class="mt-1.5">
                                <a-col :span="17">
                                    <a-slider v-model:value="form_cetak_label.nup_end" :min="nup_range.min"
                                        :max="nup_range.max" :disabled="!form_cetak_label.kategori_id" />
                                </a-col>
                                <a-col :span="7">
                                    <a-input-number v-model:value="form_cetak_label.nup_end"
                                        :min="form_cetak_label.nup_start + 1" :max="nup_range.max"
                                        placeholder="Nup Akhir" :controls="false"
                                        :disabled="!form_cetak_label.kategori_id" />
                                </a-col>
                            </a-row>
                        </a-form-item>
                        <a-form-item v-if="form_cetak_label.kategori_id && nup_range">
                            <template #label>
                                <Icon icon="line-md:alert-circle" />
                                <span class="ml-1">Info NUP</span>
                            </template>
                            <a-alert message="Nup yang Tersedia"
                                :description="'Minimal: ' + nup_range.min + ' | Maksimal: ' + nup_range.max" type="info"
                                show-icon />
                        </a-form-item>
                    </a-form>
                </a-col>
                <a-col class="gutter-row" :span="24" :md="12">
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
                </a-col>
            </a-row>
            <a-button type="primary" @click="printAsetLabel()" :disabled="!form_cetak_label.kategori_id || !form_cetak_label.nup_start || !form_cetak_label.nup_end">Cetak Label</a-button>
        </a-card>
    </div>
    <div class="!w-full shadow-md rounded-[8px] overflow-hidden mb-6">
        <a-card class="card card-white !px-5 h-full">
            <template #title>
                <a-tooltip placement="topLeft"
                    title="Anda dapat melakukan aksi lainnya seperti distribusi, mengubah kondisi dan juga mengubah status keaktifan aset secara gelondongan">
                    <span>Aksi Lainnya</span>
                </a-tooltip>
            </template>
            <a-row :gutter="{ xs: 8, sm: 16, md: 24, lg: 32 }">
                <a-col class="gutter-row" :span="24" :md="12" :xl="8">
                    <a-form :label-col="{ span: 7 }" :wrapper-col="{ span: 17 }">
                        <a-form-item label="Nama Ruang">
                            <a-select v-model:value="filters.ruang_id" show-search allow-clear
                                option-filter-prop="title" style="width: 100%" placeholder="Pilih Ruang">
                                <a-select-option :key="ruang.id" v-for="ruang in filteredRuangOptions" :value="ruang.id"
                                    :title="ruang.nama" :search="ruang.kode + ' ' + ruang.nama">
                                    {{ ruang.kode }} - {{ ruang.nama }}
                                </a-select-option>
                            </a-select>
                        </a-form-item>
                        <a-form-item label="Perolehan">
                            <a-tooltip placement="topLeft"
                                title="Masuk ke Menu Perolehan AT lalu salin Kode Perolehan, contoh AM240527-0042">
                                <a-input v-model:value="filters.kode_perolehan"
                                    placeholder="Input Kode Perolehan (AM..-...)" style="width: 100%">
                                    <template #addonAfter>
                                        <Icon icon='ant-design:search-outlined' />
                                    </template>
                                </a-input>
                            </a-tooltip>
                        </a-form-item>
                        <a-form-item label="Status Distribusi">
                            <a-select v-model:value="filters.status_distribusi" show-search
                                :getPopupContainer="(trigger) => trigger.parentNode">
                                <a-select-option :value=0>Semua</a-select-option>
                                <a-select-option :value=1>Sudah Distribusi</a-select-option>
                                <a-select-option :value=2>Belum Distribusi</a-select-option>
                            </a-select>
                        </a-form-item>
                    </a-form>
                </a-col>
                <a-col class="gutter-row" :span="24" :md="12" :xl="8">
                    <a-form :label-col="{ span: 7 }" :wrapper-col="{ span: 17 }">
                        <a-form-item label="Tahun Perolehan">
                            <a-date-picker v-model:value="selectedDate" picker="year"
                                @change="updateYearFilter"></a-date-picker>
                        </a-form-item>
                        <a-form-item label="Nilai Buku 0">
                            <a-checkbox :checked="filters.nilai_buku_null" @change="filters.nilai_buku_null = !filters.nilai_buku_null" />
                            <a-tooltip placement="topLeft"
                                title="Untuk mencari data aset yang sudah berstatus henti guna">
                                <span class="ml-4">
                                    Henti Guna :&nbsp;
                                    <a-checkbox :checked="filters.henti_guna" @change="filters.henti_guna = !filters.henti_guna" />
                                </span>
                            </a-tooltip>
                        </a-form-item>
                    </a-form>
                </a-col>
                <a-col class="gutter-row" :span="24" :md="12" :xl="8">
                    <a-form :label-col="{ span: 7 }" :wrapper-col="{ span: 17 }">
                        <a-form-item label="Deskripsi">
                            <a-input v-model:value="filter.search" placeholder="Ketikkan Deskripsi" style="width: 100%">
                                <template #addonAfter>
                                    <Icon icon='ant-design:search-outlined' />
                                </template>
                            </a-input>
                        </a-form-item>
                        <a-form-item label="Kategori">
                            <select-kategori v-if="showSelectKategori" v-model:kategori_id="filters.kategori_id"
                                :is_filter="true" mode="aset" />
                            <a-button-group class="!w-full flex flex-col sm:flex-row mb-4">
                                <a-button @click="clearFilter" :type="showSelectKategori ? '' : 'primary'">
                                    Semua Kategori
                                </a-button>
                                <a-button v-if="!showSelectKategori" @click="showSelectKategori = true" class="!w-full">
                                    Filter Kategori
                                </a-button>
                            </a-button-group>
                        </a-form-item>
                    </a-form>
                </a-col>
            </a-row>
            <a-button-group class="gap-x-3 my-3">
                <a-button type="success" @click="readData()">Read Data</a-button>
                <a-button v-if="models.length > 0" type="dashed">
                    <a-tooltip placement="topLeft"
                        :title="models.length == 0 ? 'Tidak dapat memilih karna data tidak ditemukan' : 'Pilih Semua Data'">
                        <a-checkbox v-model:checked="selectAll" @change="selectAllItems()" title="Pilih Semua">
                            <span>Pilih Semua</span>
                        </a-checkbox>
                    </a-tooltip>
                </a-button>
            </a-button-group>
            <hr v-if="models.length > 0"/>
            <a-row v-if="selectedItems.length > 0" type="flex" justify="space-between" align="middle" style="gap: 1em"
                class="mt-3">
                <a-col>
                    <span><strong>Jumlah item yang dipilih: {{ selectedItems.length }}</strong></span>
                </a-col>
                <a-col class="flex items-center gap-x-4">
                    <a-button type="warning" @click="viewCheckedData()">Lihat Data yang Dipilih</a-button>
                    <a-button type="primary" @click="action()" class="w-28">Aksi</a-button>
                </a-col>
            </a-row>
            <a-table v-if="showTable" :columns="columns" :row-key="(obj) => obj.id"
                :pagination="{ ..._pagination, position: ['topRight', 'bottomRight'] }" :loading="loadingStatus"
                :data-source="models" @change="handleTableChange">
                <template #headerCell="{ column }">
                    <template v-if="column.key === 'action'">
                        <a-tooltip placement="topLeft"
                            :title="models.length == 0 ? 'Tidak dapat memilih karna data tidak ditemukan' : 'Pilih Semua Data Pada Paginasi ini'">
                            <a-checkbox v-model:checked="selectPaginated" @change="selectAllPaginatedData()"
                                title="Pilih Semua" :disabled="models.length == 0">
                                <span class="text-white">Pilih Per Paginasi</span>
                            </a-checkbox>
                        </a-tooltip>
                    </template>
                </template>
                <template #bodyCell="{ index, column, record }">
                    <template v-if="column.key === 'action'">
                        <a-checkbox :checked="selectedItems.indexOf(record.id) > -1"
                            @change="toggleSelection(record.id)"></a-checkbox>
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
                    <template v-if="column.key === 'ruang'">
                        {{ record.tipe_ruang ? record.tipe_ruang + ' / ' + record.ruang_nama : '-' }}
                    </template>
                    <template v-if="column.key === 'jenis_kib'">
                        {{ record.jenis_kib ? record.jenis_kib : '-' }}
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
        </a-card>
    </div>

    <a-modal v-model:open="showModal" :title="'Data Terpilih'" width="1200px" :mask-closable="false" :footer="null">
        <aset-terpilih :selectedId="selectedItems" />
    </a-modal>
    <!-- Modal Aksi -->
    <a-modal v-model:open="showActionModal" title="Aksi" width="700px" @ok="confirmAction" :mask-closable="false"
        :destroy-on-close="true">
        <a-form ref="form" name="basic" :label-col="{ span: 8 }" :wrapper-col="{ span: 16 }">
            <a-form-item>
                <template #label>
                    <strong>Pilihan Aksi</strong>
                </template>
                <a-radio-group v-model:value="actionType" button-style="solid">
                    <a-radio-button value="distribusi">Distribusi</a-radio-button>
                    <a-radio-button value="ubah_kondisi">Ubah Kondisi</a-radio-button>
                    <a-radio-button value="penghentigunaan">Penghentigunaan</a-radio-button>
                </a-radio-group>
            </a-form-item>
            <hr class="mb-4" />
            <!-- FORM UNTUK AKSI DISTRIBUSI -->
            <template v-if="actionType === 'distribusi'">
                <a-form-item label="Satuan Kerja">
                    <span>{{ constant.SATKER[filters.satker_id].nama }}</span>
                </a-form-item>
                <a-form-item label="Jumlah Aset Dipilih">
                    <span> {{ selectedItems.length }} Aset</span>
                </a-form-item>
                <a-form-item label="Pilihan Distribusi">
                    <a-radio-group v-model:value="distributionType" button-style="solid">
                        <a-tooltip placement="topLeft"
                            :title="hasJenisKib ? 'Cek Data yang anda pilih, untuk beberapa jenis KIB (Gedung Bangunan, Bangunan Air, Alat Besar dan Tanah) tidak bisa dilakukan distribusi ruang' : null">
                            <a-radio-button value="ruang" :disabled="hasJenisKib">Distribusi Ruang</a-radio-button>
                        </a-tooltip>
                        <a-radio-button value="satker">Distribusi Satker</a-radio-button>
                    </a-radio-group>
                </a-form-item>
                <!-- Jika pilihan distribusi adalah Distribusi Ruang -->
                <template v-if="distributionType === 'ruang'">
                    <a-form-item label="Tipe Ruang" data-column="tipe_ruang" :rules="[{ required: true }]">
                        <a-select v-model:value="form_distribusi.tipe_ruang" placeholder="--Pilih Tipe Ruang--">
                            <a-select-option value="DBR">DBR</a-select-option>
                            <a-select-option value="DBL">DBL</a-select-option>
                        </a-select>
                    </a-form-item>
                    <!-- Input ruangan jika tipe ruang yang dipilih adalah DBR -->
                    <a-form-item v-if="form_distribusi.tipe_ruang == 'DBR'" label="Ruang" data-column="ruang_id"
                        :rules="[{ required: true }]">
                        <a-select v-model:value="form_distribusi.ruang_id" placeholder="--Pilih Ruang--" show-search
                            option-filter-prop="search">
                            <a-select-option :key="ruang.id" v-for="ruang in filteredRuangOptions" :value="ruang.id"
                                :title="ruang.nama" :search="ruang.kode + ' ' + ruang.nama">
                                {{ ruang.kode }} - {{ ruang.nama }}
                            </a-select-option>
                        </a-select>
                    </a-form-item>
                    <!-- Input teks area untuk keterangan lokasi jika tipe ruang yang dipilih adalah DBL -->
                    <a-form-item v-if="form_distribusi.tipe_ruang == 'DBL'" label="Keterangan Lokasi"
                        data-column="keterangan_lokasi" :rules="[{ required: true }]">
                        <a-textarea v-model:value="form_distribusi.keterangan_lokasi" :rows="2"
                            placeholder="Masukkan keterangan lokasi"></a-textarea>
                    </a-form-item>
                    <a-form-item label="Catatan" data-column="catatan">
                        <a-textarea v-model:value="form_distribusi.catatan" :rows="3"
                            placeholder="Catatan"></a-textarea>
                    </a-form-item>
                </template>
                <!-- Jika pilihan distribusi adalah Distribusi Satker -->
                <template v-else-if="distributionType === 'satker'">
                    <a-form-item label="Satuan Kerja Tujuan" data-column="satker_id" :rules="[{ required: true }]">
                        <template v-if="constant.SATKER[filters.satker_id].id === 4">
                            <a-select v-model:value="form_distribusi.satker_id" placeholder="--Pilih Satuan Kerja--"
                                show-search option-filter-prop="title"
                                :getPopupContainer="(trigger) => trigger.parentNode">
                                <a-select-option :key="satker.id" v-for="satker in constant.DISTRIBUTESATKER"
                                    :value="satker.id" :title="satker.nama">
                                    {{ satker.nama }}
                                </a-select-option>
                            </a-select>
                        </template>
                        <template v-else>
                            <span>Biro Pengelolaan Aset dan Usaha</span>
                            <span v-text="form_distribusi.satker_id = 4" style="display: none;"></span>
                        </template>
                    </a-form-item>
                </template>
            </template>
            <!-- FORM UNTUK AKSI UBAH KONDISI -->
            <template v-if="actionType === 'ubah_kondisi'">
                <a-form ref="form" name="basic" :label-col="{ span: 8 }" :wrapper-col="{ span: 16 }">
                    <a-form-item label="Jumlah Aset Dipilih">
                        <span> {{ selectedItems.length }} Aset</span>
                    </a-form-item>
                    <a-form-item label="Kondisi" data-column="kondisi">
                        <a-select v-model:value="form_ubah_kondisi.kondisi" placeholder="--Pilih Kondisi--">
                            <a-select-option :value="1">Baik</a-select-option>
                            <a-select-option :value="2">Rusak Ringan</a-select-option>
                            <a-select-option :value="3">Rusak Berat</a-select-option>
                        </a-select>
                    </a-form-item>
                </a-form>
            </template>
            <!-- FORM UNTUK AKSI PENGGHENTIGUNAAN ASET -->
            <template v-if="actionType === 'penghentigunaan'">
                <a-form ref="form" name="basic" :label-col="{ span: 8 }" :wrapper-col="{ span: 16 }">
                    <a-form-item label="Jumlah Aset Dipilih">
                        <span> {{ selectedItems.length }} Aset</span>
                    </a-form-item>
                    <a-form-item label="Status Henti Guna" data-column="henti_guna">
                        <a-select v-model:value="form_penghentigunaan.henti_guna" placeholder="--Pilih Status--">
                            <a-select-option :value="0">Aktif Dipakai</a-select-option>
                            <a-select-option :value="1">Dihentigunakan</a-select-option>
                        </a-select>
                    </a-form-item>
                </a-form>
            </template>
        </a-form>
    </a-modal>
</template>