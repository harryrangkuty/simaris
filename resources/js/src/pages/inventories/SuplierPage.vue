<script>
const columns = [
    {
        title: "Action",
        key: "action",
        align: "left",
        width: 100,
    },
    {
        title: "#",
        key: "number",
        align: "center",
        width: 60,
    },
    {
        title: "Nama Suplier",
        key: "nama",
        dataIndex: "nama",
        align: "left",
        width: 200,
    },
    {
        title: "Telp Suplier",
        key: "telp",
        dataIndex: "telp",
        align: "left",
        width: 180,
    },
    {
        title: "Email Suplier",
        key: "email",
        dataIndex: 'email',
        align: "left",
        width: 250,
    },
    {
        title: "Alamat Suplier",
        key: "alamat",
        dataIndex: "alamat",
        align: "left",
        width: 300,
    },
    {
        title: "Contact Person",
        key: "kontak",
        align: "left",
        width: 250,
    },
    {
        title: "Status",
        key: "deleted_at",
        align: "center",
        width: 180,
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
                nama: null,
                alamat: null,
                telp: null,
                email: null,
                kontak_nama: null,
                kontak_telp: null,
            },
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
            vm.loadingTrue();
            const form = { 
                req: 'write', 
                ...vm.form 
            };
            const response = await vm.axios.post(vm.writeRoute, form).catch((e) => vm.$onAjaxError(e));
            if (response && response.data) {
                if (!form.id) {
                    vm.openNotification('Berhasil menyimpan data, mengalihkan ke halaman detail ...', 'success');
                }
                else{
                    vm.openNotification('Berhasil mengubah data', 'success');
                }
                vm.readData();
                vm.showModal = false;
                vm.loadingFalse();
            }
        },

        async deleteData(id, req = 'toggle') {
            const vm = this;
            vm.loadingTrue()
            const form = { 
                req: req, 
                id: id 
            };
            const response = await vm.axios.post(vm.writeRoute, form).catch((e) => vm.$onAjaxError(e));
            if (response && response.data) {
                if (req === 'toggle') {
                    vm.openNotification('Berhasil mengubah status data', 'success');
                } else {
                    vm.openNotification('Berhasil menghapus data', 'success');
                }
                vm.readData();
                vm.showModal = false;
            }
        },
    }
};
</script>

<template>
    <a-row type="flex" justify="center">
        <a-col :span="24">
            <a-card class="card" title="Manajemen Suplier">
                <template #extra>
                    <a-row type="flex" style="gap: 1em">
                        <a-col flex="auto">
                            <a-input v-model:value="filter.search" @keyup.enter="readData" placeholder="Ketikkan nama Supplier ...">
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
                                <a-button size="small" type="primary" @click="readDataSingle(record)">
                                    <Icon icon='ant-design:form-outlined' />
                                </a-button>
                                <a-popconfirm title="Ubah status data?" @confirm="deleteData(record.id)">
                                    <a-button size="small" type="success">
                                        <Icon icon='icon-park-solid:switch-button' />
                                    </a-button>
                                </a-popconfirm>
                                <a-popconfirm title="Yakin menghapus data?" @confirm="deleteData(record.id, 'delete')">
                                    <a-button size="small" type="danger">
                                        <Icon icon='ant-design:delete-outlined' />
                                    </a-button>
                                </a-popconfirm>
                            </a-button-group>
                        </template>
                        <template v-if="column.key === 'number'">
                            {{ index + 1 }}
                        </template>
                        <template v-if="column.key === 'kontak'">
                            <span v-if="record.kontak_nama">{{ record.kontak_nama }} - {{ record.kontak_telp }}</span>
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
    <a-modal v-model:open="showModal" title="Tambah Data" width="700px" @ok="writeData" :mask-closable="false">
        <a-form ref="form" name="basic" :label-col="{ span: 8 }" :wrapper-col="{ span: 16 }">
            <a-form-item label="Nama Suplier" data-column="nama" :rules="[{ required: true }]">
                <a-input v-model:value="form.nama" placeholder="nama suplier" />
            </a-form-item>
            <a-form-item label="No Telp" data-column="telp" :rules="[{ required: true }]">
                <a-input v-model:value="form.telp" placeholder="nomor telp suplier" />
            </a-form-item>
            <a-form-item label="Alamat Suplier" data-column="alamat" :rules="[{ required: true }]">
                <a-input v-model:value="form.alamat" placeholder="alamat suplier" />
            </a-form-item>
            <a-form-item label="Nama Kontak" data-column="kontak_nama">
                <a-input v-model:value="form.kontak_nama" placeholder="nama kontak suplier" />
            </a-form-item>
            <a-form-item label="No Telp Kontak" data-column="kontak_telp">
                <a-input v-model:value="form.kontak_telp" placeholder="nomor kontak suplier" />
            </a-form-item>
            <a-form-item label="Email" data-column="email">
                <a-input v-model:value="form.email" placeholder="alamat email suplier" />
            </a-form-item>
        </a-form>
    </a-modal>
</template>