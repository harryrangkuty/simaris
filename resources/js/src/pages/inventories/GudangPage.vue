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
        title: "Nama",
        key: "nama",
        dataIndex: "nama",
        align: "left",
    },
    {
        title: "Kode",
        key: "kode",
        dataIndex: "kode",
        align: "center",
        width: 150,
    },
    {
        title: "Status Masuk",
        key: "status_masuk",
        align: "center",
        width: 120,
    },
    {
        title: "Status Keluar",
        key: "status_keluar",
        align: "center",
        width: 120,
    },
    {
        title: "Status",
        key: "deleted_at",
        align: "center",
        width: 180,
    },
];

import { debounce } from 'lodash';

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
                kode: null,
                status_keluar: false,
                status_masuk: false,
                users_id: [],
            },
            userOptions: []
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
                req: "table", results: 10, ...params, ...this.filter,
            };

            const response = await vm.axios.get(vm.readRoute, { params });
            if (response && response.data && response.data.models) {
                const pagination = { ...vm._pagination };
                pagination.total = response.data.models.total;
                vm.models = response.data.models.data;
                vm._pagination = pagination;
            }

            vm.loadingFalse();
        },

        newData() {
            const vm = this
            Object.assign(vm.$data.form, vm.$options.data().form);
            vm.$nextTick(function () {
                vm.showModal = true
            })
        },

        async readDataSingle(id) {
            const vm = this;
            const params = {
                req: "single", id: id,
            };

            const response = await vm.axios.get(vm.readRoute, { params });
            if (response && response.data && response.data.models) {
                vm.form = response.data.models
                vm.userOptions = response.data.users
            }

            vm.loadingFalse();
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
                    vm.openNotification('Berhasil menyimpan data', 'success');
                }else{
                    vm.openNotification('Berhasil mengubah data', 'success');
                }
                vm.readData();
                vm.showModal = false;
            }
        },

        getUserOptions: debounce(function (input) {
            this.searchUser(input);
        }, 200),

        searchUser(key = null, selected = null) {
            const vm = this;
            vm.searching = true;
            const params = {
                req: 'list_user',
                name: key,
                selected: selected,
            };
            vm.axios
                .get(vm.readRoute, { params: params })
                .then((response) => {
                    vm.userOptions = response.data;
                })
                .catch((error) => (vm.validation = vm.$onAjaxError(error)));
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
    }
};
</script>

<template>
    <a-row type="flex" justify="center">
        <a-col :span="24">
            <a-card class="card" title="Persediaan - Manajemen Gudang">
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
                <a-table :scroll="{ x: 800 }" :columns="columns" :row-key="(obj) => obj.id" :pagination="_pagination" :loading="loadingStatus"
                    :data-source="models" @change="handleTableChange">
                    <template #bodyCell="{ index, column, record }">
                        <template v-if="column.key === 'action'">
                            <a-button-group>
                                <a-button size="small" type="primary" @click="readDataSingle(record.id)">
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
            <a-form-item label="Nama Gudang" data-column="nama" :rules="[{ required: true }]">
                <a-input v-model:value="form.nama" placeholder="nama gudang" />
            </a-form-item>
            <a-form-item label="Kode" data-column="kode" :rules="[{ required: true }]">
                <a-input v-model:value="form.kode" placeholder="kode gudang" />
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
            <a-form-item label="List User" data-column="status_keluar">
                <a-select v-model:value="form.users_id" mode="multiple" show-search placeholder="list petugas gudang"
                    style="width: 100%" :filter-option="false" @search="getUserOptions">
                    <a-select-option v-for="user in userOptions" :key="user.id" :label="user.name" :value="user.id">
                        {{ user.name }}
                    </a-select-option>
                </a-select>
            </a-form-item>
        </a-form>
    </a-modal>
</template>