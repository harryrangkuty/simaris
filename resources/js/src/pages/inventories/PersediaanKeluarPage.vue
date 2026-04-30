<script>
const columns = [
    {
        title: "Action",
        key: "action",
        align: "left",
        width: 120,
    },
    {
        title: "#",
        key: "number",
        align: "center",
        width: 60,
    },
    {
        title: "Kode",
        key: "kode",
        dataIndex: 'kode',
        align: "center",
        width: 150,
    },
    {
        title: "Jenis",
        key: "jenis_transaksi",
        align: "center",
        width: 150,
    },
    {
        title: "Tgl Buku",
        key: "tgl_buku",
        align: "center",
        width: 180,
    },
    {
        title: "Gudang",
        key: "nama_gudang",
        dataIndex: 'nama_gudang',
        align: "left",
        width: 150,
    },
    {
        title: "Konsumen/Pengguna",
        key: "konsumen",
        dataIndex: 'konsumen',
        align: "left",
    },
    {
        title: "Jenis Barang",
        key: "details_count",
        dataIndex: "details_count",
        align: "center",
        width: 100,
    },
    {
        title: "Jumlah Barang",
        key: "details_sum_kuantitas",
        align: "center",
        width: 80,
    },
    {
        title: "Status",
        key: "status",
        align: "left",
        width: 80,
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
                gudang_id: null,
                jenis_transaksi_id: null,
                konsumen: null,
                notes: null,
            },
            filters:{
                status: null,
                pendingCount: null,
                finishCount: null,
            }
        };
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
                ...vm.filters,
            };

            const response = await vm.axios.get(vm.readRoute, { params });
            if (response && response.data) {
                const pagination = { ...vm._pagination };
                pagination.total = response.data.models.total;
                vm.loadingFalse();
                vm.models = response.data.models.data;
                vm.pendingCount = response.data.pending_count;
                vm.finishCount = response.data.finish_count;
                vm._pagination = pagination;
            }
        },

        newData() {
            const vm = this
            Object.assign(vm.$data.form, vm.$options.data().form);
            vm.$nextTick(function () {
                vm.showModal = true
            })
        },

        editData(m) {
            const vm = this
            vm.form = vm.lodash.cloneDeep(m)
            vm.$nextTick(function () {
                vm.showModal = true
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
                else{
                    vm.openNotification('Berhasil mengubah data', 'success');
                    vm.readData()
                }
                vm.showModal = false;
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
            const vm = this;
            const _window = window.open(vm.route + `?req=open&kode=${kode}`, '_self');
            if (window.focus) {
                _window.focus();
            }
            return false;
        },
    }
};
</script>

<template>
    <a-row type="flex" justify="center">
        <a-col :span="24">
            <a-card class="card" title="Persediaan - Transaksi Keluar">
                <template #extra>
                    <a-row type="flex" style="gap: 1em">
                        <a-col flex="auto">
                            <a-input v-model:value="filter.search" @keyup.enter="readData" placeholder="Ketikkan Kode atau Konsumen"
                                style="width: 300px">
                                <template #addonAfter>
                                    <Icon icon='ant-design:search-outlined' />
                                </template>
                            </a-input>
                        </a-col>
                        <a-col flex="auto">
                            <div class="relative flex items-center">
                                <Icon v-if="pendingCount > 0" 
                                    icon="mdi:asterisk" 
                                    class="text-red-500 h-5 w-5 absolute -top-2 -left-2 z-50"/>
                                <a-select v-model:value="filters.status" show-search allow-clear style="width: 150px"
                                    placeholder="Pilih Status" :getPopupContainer="(trigger) => trigger.parentNode"
                                    @change="readData()">
                                    <a-select-option value="O" :class="pendingCount > 0 ? '!text-red-500 !bg-red-50' : ''">Pending ({{ pendingCount }})</a-select-option>
                                    <a-select-option value="F">Finish ({{ finishCount }})</a-select-option>
                                </a-select>
                            </div>
                        </a-col>
                        <a-col flex="auto">
                            <a-range-picker v-model:value="date_range" :format="date_format" allow-clear style="width: 200px"
                                @change="readData()" @calendarChange="filterDateRange" />
                        </a-col>
                        <a-col>
                            <a-button type="primary" @click="newData()">Tambah Data</a-button>
                        </a-col>
                    </a-row>
                </template>
                <a-table :scroll="{ x: 800 }" :columns="columns" :row-key="(obj) => obj.id" :pagination="_pagination" :loading="loadingStatus"
                    :data-source="models" @change="handleTableChange">
                    <template #bodyCell="{ index, column, record }">
                        <template v-if="column.key === 'action'">
                            <a-button-group>
                                <a-tooltip placement="topLeft" :title="record.status == 'F' ? `Tidak Dapat Mengubah Persediaan berstatus FINISH ` : null">
                                    <a-button :disabled="record.status == 'F'" size="small" type="primary" @click="editData(record)">
                                        <Icon icon='ant-design:form-outlined' />
                                    </a-button>
                                </a-tooltip>
                                <a-button size="small" type="success" @click="openDetail(record.kode)">
                                    <Icon icon='ant-design:database-outlined' />
                                </a-button>
                                <a-tooltip placement="topLeft" :title="record.status == 'F' ? `Tidak Dapat Menghapus Persediaan berstatus FINISH` : null">
                                    <a-popconfirm title="Yakin menghapus data?" @confirm="deleteData(record.id)">
                                        <a-button :disabled="record.status == 'F'" size="small" type="danger">
                                            <Icon icon='ant-design:delete-outlined' />
                                        </a-button>
                                    </a-popconfirm>
                                </a-tooltip>
                            </a-button-group>
                        </template>
                        <template v-if="column.key === 'number'">
                            {{ index + 1 }}
                        </template>
                        <template v-if="column.key === 'jenis_transaksi'">
                            {{ record.jenis_transaksi.kode }} - {{ record.jenis_transaksi.uraian }}
                        </template>
                        <template v-if="column.key === 'tgl_buku'">
                            {{ filterDate(record.created_at) }}
                        </template>
                        <template v-if="column.key === 'details_sum_kuantitas'">
                            {{ fb(Math.abs(record.details_sum_kuantitas), 0) }}
                        </template>
                        <template v-if="column.key === 'status'">
                            <a-tag v-if="fb(record.status, null) == 'O'" color="#f50">PENDING</a-tag>
                            <a-tag v-else color="#87d068">FINISH</a-tag>
                        </template>
                    </template>
                </a-table>
            </a-card>
        </a-col>
    </a-row>
    <a-modal v-model:open="showModal" title="Tambah Data" width="700px" @ok="writeData" :mask-closable="false">
        <a-form ref="form" name="basic" :label-col="{ span: 8 }" :wrapper-col="{ span: 16 }">
            <a-form-item label="Gudang" data-column="gudang_id" :rules="[{ required: true }]">
                <a-select v-model:value="form.gudang_id" placeholder="--Pilih Gudang--">
                    <a-select-option :key="gudang.id" v-for="gudang in constant.GUDANG_OPT" :value="gudang.id">
                        {{ gudang.nama }}
                    </a-select-option>
                </a-select>
            </a-form-item>
            <a-form-item label="Jenis Transaksi" data-column="jenis_transaksi_id" :rules="[{ required: true }]">
                <a-select v-model:value="form.jenis_transaksi_id" placeholder="--Pilih Jenis Transaksi--">
                    <a-select-option :key="jenis.kode" v-for="jenis in constant.JENIS_TRANSAKSI_OPT" :value="jenis.kode">
                        {{ jenis.kode + ' - ' + jenis.uraian }}
                    </a-select-option>
                </a-select>
            </a-form-item>
            <a-form-item label="Konsumen/Pengguna/Penerima" data-column="konsumen" :rules="[{ required: true }]">
                <a-textarea v-model:value="form.konsumen" :rows="3" placeholder="konsumen/pengguna/penerima"
                    style="resize: none;" />
            </a-form-item>
            <a-form-item label="Catatan" data-column="notes">
                <a-textarea v-model:value="form.notes" :rows="3" placeholder="catatan" style="resize: none;" />
            </a-form-item>
        </a-form>
    </a-modal>
</template>