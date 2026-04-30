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
        dataIndex: 'kode',
        align: "center",
        width: 150,
    },
    {
        title: "Tgl Buku",
        key: "tgl_buku",
        align: "center",
    },
    {
        title: "Gudang Asal",
        dataIndex: ["gudang_asal", "nama"],
        align: "left",
        width: 150,
    },
    {
        title: "Gudang Tujuan",
        dataIndex: ["gudang_tujuan", "nama"],
        align: "left",
        width: 150,
    },
    {
        title: "Nomor Invoice/Surat",
        dataIndex: "nomor_invoice",
        align: "center",
        width: 150,
    },
    {
        title: "Jlh Item",
        dataIndex: "details_count",
        align: "center",
        width: 80,
    },
    {
        title: "Status",
        key: "status",
        align: "center",
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
                nomor_invoice: null,
                gudang_asal_id: null,
                gudang_tujuan_id: null,
                notes: null,
            }
        };
    },

    mounted() {
        this.readData();
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
                ...this.filter,
            };

            const response = await vm.axios.get(vm.readRoute, { params });
            if (response && response.data) {
                const pagination = { ...vm._pagination };
                pagination.total = response.data.models.total;
                vm.loadingFalse();
                vm.models = response.data.models.data;
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
                vm.openNotification('Berhasil mengubah data', 'success');
                vm.readData();
                vm.showModal = false;
            }
        },

        async deleteData(id, req = 'delete') {
            const vm = this;
            vm.loadingTrue()
            const form = { req, id};
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
            <a-card class="card" title="Persediaan - Mutasi Antar Gudang">
                <template #extra>
                    <a-row type="flex" style="gap: 1em">
                        <a-col flex="auto">
                            <a-input v-model:value="filter.search" @keyup.enter="readData" placeholder="Cari ...">
                                <template #addonAfter>
                                    <Icon icon='ant-design:search-outlined' />
                                </template>
                            </a-input>
                        </a-col>
                        <a-col>
                            <a-button type="primary" @click="newData()">Tambah Data</a-button>
                        </a-col>
                    </a-row>
                </template>
                <a-table :scroll="{ x: 800 }" :columns="columns" :row-key="(obj) => obj.id" :pagination="_pagination"
                    :loading="loadingStatus" :data-source="models" @change="handleTableChange">
                    <template #bodyCell="{ index, column, record }">
                        <template v-if="column.key === 'action'">
                            <a-button-group>
                                <a-tooltip placement="topLeft"
                                    :title="record.status == 'F' ? `Tidak Dapat Mengubah Persediaan berstatus FINISH ` : null">
                                    <a-button :disabled="record.status == 'F'" size="small" type="primary"
                                        @click="editData(record)">
                                        <Icon icon='ant-design:form-outlined' />
                                    </a-button>
                                </a-tooltip>
                                <a-button size="small" type="success" @click="openDetail(record.kode)">
                                    <Icon icon='ant-design:database-outlined' />
                                </a-button>
                                <a-tooltip placement="topLeft"
                                    :title="record.status == 'F' ? `Tidak Dapat Menghapus Persediaan berstatus FINISH` : null">
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
                        <template v-if="column.key === 'tgl_buku'">
                            {{ filterDate(record.created_at) }}
                        </template>
                        <template v-if="column.key === 'status'">
                            <a-tag v-if="fb(record.status, null) == 'O'" color="#f50">Pending</a-tag>
                            <a-tag v-else color="#87d068">Finish</a-tag>
                        </template>
                    </template>
                </a-table>
            </a-card>
        </a-col>
    </a-row>
    <a-modal v-model:open="showModal" title="Tambah Data" width="700px" @ok="writeData" :mask-closable="false">
        <a-form ref="form" name="basic" :label-col="{ span: 8 }" :wrapper-col="{ span: 16 }">
            <a-form-item label="Gudang Asal" data-column="gudang_id" :rules="[{ required: true }]">
                <a-select v-model:value="form.gudang_asal_id" placeholder="--Pilih Gudang--" allow-clear>
                    <a-select-option :key="gudang.id"
                        v-for="gudang in constant.GUDANG_OPT.filter(g => g.id !== form.gudang_tujuan_id)"
                        :value="gudang.id">
                        {{ gudang.nama }}
                    </a-select-option>
                </a-select>
            </a-form-item>
            <a-form-item label="Gudang Tujuan" data-column="tujuan_id" :rules="[{ required: true }]">
                <a-select v-model:value="form.gudang_tujuan_id" placeholder="--Pilih Gudang--" allow-clear>
                    <a-select-option :key="gudang.id"
                        v-for="gudang in constant.GUDANG_OPT.filter(g => g.id !== form.gudang_asal_id)"
                        :value="gudang.id">
                        {{ gudang.nama }}
                    </a-select-option>
                </a-select>
            </a-form-item>
            <a-form-item label="No Invoice/Surat" data-column="nomor_invoice" :rules="[{ required: true }]">
                <a-input v-model:value="form.nomor_invoice" placeholder="no invoice/surat" />
            </a-form-item>
            <a-form-item label="Catatan" data-column="notes">
                <a-textarea v-model:value="form.notes" :rows="3" placeholder="catatan" style="resize: none;" />
            </a-form-item>
        </a-form>
    </a-modal>
</template>