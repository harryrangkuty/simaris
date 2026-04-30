<script>
const columns = [
    {
        title: "Action",
        key: "action",
        align: "left",
        width: 150,
    },
    {
        title: "#",
        key: "number",
        align: "center",
        width: 60,
    },
    {
        title: "Kategori",
        key: "kategori",
        align: "left",
        width: 350,
    },
    {
        title: "Nama",
        key: "nama",
        dataIndex: "nama",
        align: "left",
    },
    {
        title: "Stok Sekarang",
        key: "stock",
        align: "center",
        width: 120,
    },
    {
        title: "Satuan",
        key: "satuan",
        align: "center",
        width: 120,
    },
    {
        title: "Masuk",
        key: "status_masuk",
        align: "center",
        width: 80,
    },
    {
        title: "Keluar",
        key: "status_keluar",
        align: "center",
        width: 80,
    },
    {
        title: "Min",
        key: "stok_min",
        align: "center",
        width: 80,
    },
    {
        title: "Max",
        key: "stok_max",
        align: "center",
        width: 80,
    },
    {
        title: "Catatan",
        key: "notes",
        dataIndex: 'notes',
        align: "left",
        width: 200,
    },
    {
        title: "Status",
        key: "deleted_at",
        align: "center",
        width: 100,
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
                kategori_id: null,
                kategori_nama: null,
                nama: null,
                satuan_id: null,
                satuan_nama: null,
                stok_minimum: 0,
                stok_maksimum: null,
                status_keluar: false,
                status_masuk: false,
                notes: null,
                etc: null,
            },
            filters: {
                gudang_id: null,
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
                ...vm.filters
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

        async deleteData(id, req = 'toggle') {
            const vm = this;
            vm.loadingTrue()
            const form = { req: req, id: id };
            const response = await vm.axios.post(vm.writeRoute, form).catch((e) => vm.$onAjaxError(e));
            if (response && response.data) {
                if(req === 'toggle'){
                    vm.openNotification('Berhasil mengubah status data', 'success');
                }else{
                    vm.openNotification('Berhasil menghapus data', 'success');
                }
                vm.readData();
                vm.showModal = false;
            }
        },

        openDetail(id) {
            const vm = this
            const _window = window.open(vm.route + `?req=open&id=${id}`, '_self');
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
            <a-card class="card"
                :title="'Persediaan - Manajemen Barang - ' + (filters.gudang_id ? constant.GUDANG_OPT[filters.gudang_id].nama : 'Rumah Sakit Bunda Thamrin')">
                <template #extra>
                    <a-row type="flex" style="gap: 1em">
                        <a-col>
                            <a-select v-model:value="filters.gudang_id" placeholder="--Pilih Gudang--" @change="readData"
                                style="width: 300px">
                                <a-select-option :key="0" :value="null">
                                    Semua Gudang
                                </a-select-option>
                                <a-select-option :key="gudang.id" v-for="gudang in constant.GUDANG_OPT" :value="gudang.id">
                                    {{ gudang.nama }}
                                </a-select-option>
                            </a-select>
                        </a-col>
                        <a-col>
                            <a-input v-model:value="filter.search" @keyup.enter="readData" placeholder="Cari ...">
                                <template #addonAfter>
                                    <Icon icon='ant-design:search-outlined' />
                                </template>
                            </a-input>
                        </a-col>
                        <a-col v-if="user.id <= 11">
                            <a-button type="primary" @click="newData()">Tambah Data</a-button>
                        </a-col>
                    </a-row>
                </template>
                <a-table :scroll="{ x: 800 }" :columns="columns" :row-key="(obj) => obj.id" :pagination="_pagination" :loading="loadingStatus"
                    :data-source="models" @change="handleTableChange">
                    <template #bodyCell="{ index, column, record }">
                        <a-button-group>
                            <template v-if="column.key === 'action'">
                                <a-button size="small" type="primary" @click="editData(record)">
                                    <Icon icon='ant-design:form-outlined' />
                                </a-button>
                                <a-button size="small" type="success" @click="openDetail(record.id)">
                                    <Icon icon='ant-design:database-outlined' />
                                </a-button>
                                <a-popconfirm title="Ubah status data?" @confirm="deleteData(record.id)">
                                    <a-button size="small" type="warning">
                                        <Icon icon='icon-park-solid:switch-button' />
                                    </a-button>
                                </a-popconfirm>
                                <a-popconfirm title="Yakin menghapus data?" @confirm="deleteData(record.id, 'delete')">
                                    <a-button size="small" type="danger">
                                        <Icon icon='ant-design:delete-outlined' />
                                    </a-button>
                                </a-popconfirm>
                            </template>
                        </a-button-group>
                        <template v-if="column.key === 'number'">
                            {{ index + 1 }}
                        </template>
                        <template v-if="column.key === 'kategori'">
                            {{ record.kategori_id }} - {{ record.kategori_nama }}
                        </template>
                        <template v-if="column.key === 'status_masuk'">
                            <Icon v-if="record.status_masuk" icon='ant-design:check-circle-filled' color="#87d068"
                                style="font-size: 1.5rem" />
                            <Icon v-else icon='ant-design:close-circle-filled' color="#f50" style="font-size: 1.5rem" />
                        </template>
                        <template v-if="column.key === 'status_keluar'">
                            <Icon v-if="record.status_keluar" icon='ant-design:check-circle-filled' color="#87d068"
                                style="font-size: 1.5rem" />
                            <Icon v-else icon='ant-design:close-circle-filled' color="#f50" style="font-size: 1.5rem" />
                        </template>
                        <template v-if="column.key === 'stok_min'">
                            {{ record.stok_minimum }}
                        </template>
                        <template v-if="column.key === 'stok_max'">
                            {{ record.stok_maksimum }}
                        </template>
                        <template v-if="column.key === 'stock'">
                            {{ record.stock }}
                        </template>
                        <template v-if="column.key === 'deleted_at'">
                            <a-tag v-if="fb(record.deleted_at, false)" color="#f50">Non Aktif</a-tag>
                            <a-tag v-else color="#87d068">Aktif</a-tag>
                        </template>
                    </template>
                </a-table>
            </a-card>
        </a-col>
    </a-row>
    <a-modal v-model:open="showModal" title="Tambah Data" width="700px" @ok="writeData" :destroy-on-close="true" :mask-closable="false">
        <a-form ref="form" name="basic" :label-col="{ span: 8 }" :wrapper-col="{ span: 16 }">
            <a-form-item label="Kategori" data-column="kategori_id" :rules="[{ required: true }]">
                <select-kategori v-model:kategori_id="form.kategori_id" v-model:kategori_nama="form.kategori_nama"
                    mode="persediaan" />
            </a-form-item>
            <a-form-item label="Nama Barang" data-column="nama" :rules="[{ required: true }]">
                <a-input v-model:value="form.nama" placeholder="nama barang" />
            </a-form-item>
            <a-form-item label="Satuan" data-column="satuan_id">
                <span>TBD</span>
            </a-form-item>
            <a-form-item label="Status Masuk" data-column="status_masuk" :rules="[{ required: true }]">
                <a-radio-group v-model:value="form.status_masuk">
                    <a-radio :value="true">Ya</a-radio>
                    <a-radio :value="false">Tidak</a-radio>
                </a-radio-group>
            </a-form-item>
            <a-form-item label="Status Keluar" data-column="status_keluar" :rules="[{ required: true }]">
                <a-radio-group v-model:value="form.status_keluar">
                    <a-radio :value="true">Ya</a-radio>
                    <a-radio :value="false">Tidak</a-radio>
                </a-radio-group>
            </a-form-item>
            <a-form-item label="Stok Minimum" data-column="stok_minimum" :rules="[{ required: true }]">
                <a-input v-model:value="form.stok_minimum" placeholder="stok minimum barang" />
            </a-form-item>
            <a-form-item label="Stok Maksimum" data-column="stok_maksimum">
                <a-input v-model:value="form.stok_maksimum" placeholder="stok maksimum barang" />
            </a-form-item>
            <a-form-item label="Catatan" data-column="notes">
                <a-textarea v-model:value="form.notes" :rows="3" placeholder="catatan barang" style="resize: none;" />
            </a-form-item>
        </a-form>
    </a-modal>
</template>