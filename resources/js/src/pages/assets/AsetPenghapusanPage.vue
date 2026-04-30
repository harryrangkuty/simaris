<script>
const columns = [
    {
        title: "Action",
        key: "action",
        align: "center",
        width: 100,
    },
    {
        title: "#",
        key: "number",
        align: "center",
        width: 10,
    },
    {
        title: "Kode",
        key: "kode",
        dataIndex: 'kode',
        align: "center",
        width: 150,
    },
    {
        title: "Jenis Transaksi",
        key: "jenis_transaksi",
        align: "center",
        width: 250,
    },
    {
        title: "Tanggal Buku",
        key: "tanggal_buku",
        align: "center",
    },
    {
        title: "Tgl Penghapusan",
        key: "tgl_penghapusan",
        align: "center",
    },
    {
        title: "Satuan Kerja",
        dataIndex: ["satker", "nama"],
    },
    {
        title: "Catatan Penghapusan",
        dataIndex: "notes",
    },
    {
        title: "Status",
        key: "status",
        align: "center",
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
            form: {
                id: null,
                jenis_transaksi_id: null,
                no_surat: null,
                tgl_surat: null,
                satker_id: null,
                notes: null,
            },
            filters: {
                satker_id: null,
                status: null,
            },
            closeCount: null,
            openCount: null,
            finishCount: null,
        };
    },

    created() {
        if (this.user.satkers_id && this.user.satkers_id.length > 0) {
            this.filters.satker_id = this.user.satkers_id[0];
        } else if (this.user.reviews_id && this.user.reviews_id.length > 0) {
            this.filters.satker_id = this.user.reviews_id[0];
        }
    },

    mounted() {
        this.readData()
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
                vm.closeCount = response.data.close_count;
                vm.openCount = response.data.open_count;
                vm.finishCount = response.data.finish_count;
                vm._pagination = pagination;
                vm.loadingFalse();
            }

        },

        newData() {
            const vm = this
            Object.assign(vm.$data.form, vm.$options.data().form);
            vm.$nextTick(function () {
                vm.showModal = true,
                    this.form.satker_id = this.filters.satker_id
            })
        },

        editData(m) {
            const vm = this
            vm.form = vm.lodash.cloneDeep(m)
            vm.$nextTick(function () {
                vm.showModal = true
                vm.form.jenis_transaksi_id = m.jenis_transaksi.kode;
            })
        },

        async writeData() {
            const vm = this;
            vm.loadingTrue()
            const form = { req: 'write', ...vm.form };
            const response = await vm.axios.post(vm.writeRoute, form).catch((e) => vm.$onAjaxError(e));
            if (response && response.data) {
                if (!form.id) {
                    vm.openNotification('Berhasil menyimpan data, mengalihkan ke halaman detail ...', 'success');
                    setTimeout(function () {
                        vm.openDetail(response.data.kode);
                    }, 3000)
                }
                else {
                    vm.openNotification('Berhasil mengubah data', 'success');
                    vm.readData()
                }
                vm.showModal = false;
                vm.loadingFalse()
            }
        },

        async deleteData(id, req = 'delete') {
            const vm = this;
            vm.loadingTrue()
            const form = { req: req, id: id };
            const response = await vm.axios.post(vm.writeRoute, form).catch((e) => vm.$onAjaxError(e));
            if (response && response.data) {
                vm.openNotification('Berhasil menghapus data', 'success');
                vm.readData();
                vm.showModal = false;
            }
        },

        openDetail(kode) {
            const vm = this
            const _window = window.open(vm.route + `?req=open&kode=${kode}`, '_self');
            if (window.focus) {
                _window.focus();
            }
            return false;
        },

        disabledDate(date) {
            const today = new Date();
            return date && date > today;
        },
    }
};
</script>
<template>
    <a-row type="flex" justify="center">
        <a-col :span="24">
            <a-card class="card">
                <a-row class="flex flex-wrap items-center justify-between border-b-2 mt-5 mb-4">
                    <h1 class="text-xl font-semibold mb-4 lg:mb-0">Aset Tetap - Penghapusan</h1>
                    <div class="flex justify-end items-end w-full md:w-auto">
                        <a-row class="flex flex-wrap justify-start sm:justify-end gap-2 items-center w-full">
                            <a-col class="lg:w-auto w-full mb-4 lg:mb-0">
                                <a-select v-model:value="filters.satker_id" show-search option-filter-prop="title"
                                    class="lg:w-[350px] w-full" placeholder="Satuan Kerja" @change="readData"
                                    :title="displaySatkerName">
                                    <a-select-option v-for="satker in constant.SATKER" :key="satker.id"
                                        :value="satker.id" :title="satker.nama">
                                        {{ satker.nama }}
                                    </a-select-option>
                                </a-select>
                            </a-col>
                            <a-col class="lg:w-auto w-full mb-4 lg:mb-0">
                                <div class="relative flex items-center">
                                    <Icon v-if="openCount > 0 || closeCount > 0" icon="mdi:asterisk"
                                        class="text-red-500 h-5 w-5 absolute -top-2 -left-2 z-50" />
                                    <a-select v-model:value="filters.status" class="lg:w-40 w-full" show-search
                                        allow-clear placeholder="Pilih Status"
                                        :getPopupContainer="(trigger) => trigger.parentNode" @change="readData()">
                                        <a-select-option value="OPEN"
                                            :class="openCount > 0 ? '!text-red-500 !bg-red-50' : ''">Open ({{ openCount
                                            }})</a-select-option>
                                        <a-select-option value="CLOSE"
                                            :class="closeCount > 0 ? '!text-red-500 !bg-red-50' : ''">Close ({{
                                                closeCount }})</a-select-option>
                                        <a-select-option value="FINISH">Finish ({{ finishCount }})</a-select-option>
                                    </a-select>
                                </div>
                            </a-col>
                            <a-col class="lg:w-auto w-full mb-4 lg:mb-0">
                                <a-range-picker v-model:value="date_range" :format="date_format" allow-clear
                                    @change="readData()" @calendarChange="filterDateRange" class="w-full lg:w-72" />
                            </a-col>
                            <a-col v-if="operator_aset" class="lg:w-auto w-full mb-4 lg:mb-0">
                                <a-button class="flex items-center justify-center w-full" type="primary"
                                    @click="newData()">Tambah Data</a-button>
                            </a-col>
                        </a-row>
                    </div>
                </a-row>

                <a-table :scroll="{ x: 800 }" :columns="columns" :row-key="(obj) => obj.id" :pagination="_pagination"
                    :loading="loadingStatus" :data-source="models" @change="handleTableChange">
                    <template #bodyCell="{ index, column, record }">
                        <template v-if="column.key === 'action'">
                            <a-button-group>
                                <a-tooltip v-if="!operator_aset" placement="topLeft"
                                    title="Tidak Dapat Mengubah Penghapusan Karena Anda bukan Operator Aset">
                                    <a-button size="small" disabled>
                                        <Icon icon='ant-design:form-outlined' />
                                    </a-button>
                                </a-tooltip>
                                <a-tooltip v-else placement="topLeft"
                                    :title="record.status == 'CLOSE' || record.status == 'FINISH' ? `Tidak Dapat Mengubah Penghapusan berstatus ${record.status}` : null">
                                    <a-button :disabled="record.status == 'CLOSE' || record.status == 'FINISH'"
                                        size="small" type="primary" @click="editData(record)">
                                        <Icon icon='ant-design:form-outlined' />
                                    </a-button>
                                </a-tooltip>
                                <a-button size="small" type="success" @click="openDetail(record.kode)">
                                    <Icon icon='ant-design:database-outlined' />
                                </a-button>
                                <a-tooltip v-if="!operator_aset" placement="topLeft"
                                    title="Tidak Dapat Menghapus Data Penghapusan AT Karena Anda bukan Operator Aset">
                                    <a-button size="small" disabled>
                                        <Icon icon='ant-design:delete-outlined' />
                                    </a-button>
                                </a-tooltip>
                                <a-tooltip v-else placement="topLeft"
                                    :title="record.status == 'CLOSE' || record.status == 'FINISH' ? `Tidak Dapat Menghapus Data Penghapusan AT berstatus ${record.status}` : null">
                                    <a-popconfirm title="Yakin menghapus data?" @confirm="deleteData(record.id)">
                                        <a-button :disabled="record.status == 'CLOSE' || record.status == 'FINISH'"
                                            size="small" type="danger">
                                            <Icon icon='ant-design:delete-outlined' />
                                        </a-button>
                                    </a-popconfirm>
                                </a-tooltip>
                            </a-button-group>
                        </template>
                        <template v-if="column.key === 'number'">
                            {{ index + 1 }}
                        </template>
                        <template v-if="column.key === 'tanggal_buku'">
                            {{ filterDate(record.created_at) }}
                        </template>
                        <template v-if="column.key === 'jenis_transaksi'">
                            {{ record.jenis_transaksi.kode }} - {{ record.jenis_transaksi.uraian }}
                        </template>
                        <template v-if="column.key === 'tgl_penghapusan'">
                            {{ record.tgl_penghapusan ? filterDate(record.tgl_penghapusan) : '-' }}
                        </template>
                        <template v-if="column.key === 'status'">
                            <a-tag
                                :color="fb(record.status) === 'OPEN' ? '#52c41a' : (fb(record.status) === 'FINISH' ? '#1890ff' : '#ff4d4f')">
                                {{ fb(record.status, 'CLOSE') }}
                            </a-tag>
                        </template>
                    </template>
                </a-table>
            </a-card>
        </a-col>
    </a-row>
    <a-modal v-model:open="showModal" :title="form.id ? 'Ubah Data' : 'Tambah Data'" width="700px" @ok="writeData"
        :mask-closable="false">
        <a-form ref="form" name="basic" :label-col="{ span: 8 }" :wrapper-col="{ span: 16 }">
            <a-form-item label="Satuan Kerja" data-column="satker_id" :rules="[{ required: true }]">
                <a-select v-model:value="form.satker_id" placeholder="--Pilih Satuan Kerja--" show-search
                    option-filter-prop="title" :getPopupContainer="(trigger) => trigger.parentNode">
                    <a-select-option :key="satker.id" v-for="satker in constant.SATKER" :value="satker.id"
                        :title="satker.nama">
                        {{ satker.nama }}
                    </a-select-option>
                </a-select>
            </a-form-item>
            <a-form-item label="Jenis Transaksi" data-column="jenis_transaksi_id" :rules="[{ required: true }]">
                <a-radio-group v-model:value="form.jenis_transaksi_id">
                    <a-radio v-for="jenis in constant.JENIS_TRANSAKSI_OPT" :key="jenis.kode" :value="jenis.kode"
                        style="display: block">
                        {{ jenis.kode + ' - ' + jenis.uraian }}
                        <a-tooltip placement="topRight" v-if="jenis.kode === '301' || jenis.kode === '303'">
                            <template #title>
                                <span>Jenis transaksi ini digunakan untuk menghapus aset yang masih aktif</span>
                            </template>
                            <Icon icon="ant-design:info-circle-outlined" />
                        </a-tooltip>
                        <a-tooltip placement="topRight" v-else>
                            <template #title>
                                <span>Jenis transaksi ini digunakan untuk menghapus aset yang telah
                                    dihentigunakan</span>
                            </template>
                            <Icon icon="ant-design:info-circle-outlined" />
                        </a-tooltip>
                    </a-radio>
                </a-radio-group>
            </a-form-item>
            <a-form-item label="Tanggal Dokumen" data-column="tgl_surat">
                <a-date-picker v-model:value="form.tgl_surat" :value-format="date_format" :allow-clear="false"
                    placeholder="Tanggal Dokumen" :disabled-date="disabledDate" />
                <template #extra>
                    <small>
                        <span class="text-red-400 font-bold">Tidak Wajib diisi sekarang </span>. Tanggal dokumen dapat
                        diisi ketika upload dokumen
                    </small>
                </template>
            </a-form-item>
            <a-form-item label="Nomor Dokumen Utama" data-column="no_surat">
                <a-input v-model:value="form.no_surat" placeholder="Isi Nomor Dokumen Utama" />
                <template #extra>
                    <small>
                        <span class="text-red-400 font-bold">Tidak Wajib diisi sekarang </span>. Nomor dokumen dapat
                        diisi nanti ketika upload dokumen
                    </small>
                </template>
            </a-form-item>
            <a-form-item label="Catatan" data-column="notes">
                <a-textarea v-model:value="form.notes" :rows="3" placeholder="Catatan" style="resize: none;" />
            </a-form-item>
        </a-form>
    </a-modal>
</template>