<script>

const columns = [
    {
        title: "Action",
        key: "action",
        align: "center",
        width: 100,
    },
    {
        title: "ID",
        dataIndex: 'id',
        align: "center"
    },
    {
        title: "Name",
        dataIndex: 'name',
    },
];

const permissions_columns = [
    {
        title: "Action",
        key: "action",
        align: "center",
        width: 50,
    },
    {
        title: "ID",
        dataIndex: 'id',
        align: "center"
    },
    {
        title: "Name",
        dataIndex: 'name',
    },
];

export default {
    props: {
        title: String,
        constant: {
            type: Object,
            default: () => ({}),
        },
    },
    data() {
        return {
            columns,
            permissions_columns,
            permissions_data: [],
            available_permissions: [],
            form: {
                id: null,
                name: null,
                permissions: [],
            },
            selectedRole: {
                id: null,
                name: null,
            },
        };
    },

    computed: {
        breadcrumbItems() {
            return [
                { label: 'Dashboard', link: '/', icon: 'bi:grid' },
                { label: `${this.title}`, icon: 'line-md:folder-multiple-filled' }
            ]
        },
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
                ...vm.filter
            };

            const response = await vm.axios.get(vm.readRoute, { params })
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
                vm.showModal = true;
            })
        },

        editData(m) {
            this.form = {
                ...this.lodash.cloneDeep(m),
                permissions: m.permissions?.map(p => p.id) || []
            };
            this.showModal = true;
        },

        async writeData() {
            const vm = this;
            vm.loadingTrue()
            const form = {
                req: 'write',
                ...vm.form
            };
            const response = await vm.axios.post(vm.writeRoute, form).catch((e) => vm.$onAjaxError(e));
            if (response && response.data) {
                vm.openNotification('Berhasil menyimpan data', 'success');
                vm.readData();
                vm.showModal = false;
            }
        },

        async deleteData(id) {
            const vm = this;
            vm.loadingTrue()
            const form = {
                req: 'delete',
                id: id
            };
            const response = await vm.axios.post(vm.writeRoute, form).catch((e) => vm.$onAjaxError(e));
            if (response && response.data) {
                vm.openNotification('Berhasil menghapus data', 'success');
                vm.readData();
                vm.showModal = false;
            }
        },

        async selectRole(id, name) {
            const vm = this;
            vm.selectedRole.id = id
            vm.selectedRole.name = name
            vm.permissionsData(id);
        },

        async permissionsData(role_id = null) {
            const vm = this;
            vm.loadingTrue();

            const params = {
                req: "permission_data",
                results: 10,
                id: role_id,
            };

            const response = await vm.axios.get(vm.readRoute, { params })
            if (response && response.data) {
                vm.loadingFalse();
                vm.permissions_data = response.data.models;
                vm.available_permissions = response.data.available_permissions;
            }
        },

        async attachPermission() {
            const vm = this;
            vm.loadingTrue()
            const form = {
                req: 'attach_permission',
                permissions: vm.form.permissions,
                ...vm.selectedRole,
            };
            const response = await vm.axios.post(vm.writeRoute, form).catch((e) => vm.$onAjaxError(e));
            if (response && response.data) {
                vm.openNotification('Berhasil attach permission', 'success');
                vm.permissionsData(vm.selectedRole.id);
                vm.form.permissions = [];
            }
        },

        async detachPermission(id) {
            const vm = this;
            vm.loadingTrue()
            const form = {
                req: 'detach_permission',
                permissions: id,
                ...vm.selectedRole,
            };
            const response = await vm.axios.post(vm.writeRoute, form).catch((e) => vm.$onAjaxError(e));
            if (response && response.data) {
                vm.openNotification('Berhasil detach permission', 'success');
                vm.permissionsData(vm.selectedRole.id);
                vm.form.permissions = [];
            }
        },
    }
};
</script>

<template>
    <Breadcrumb :items="breadcrumbItems" :showBackButton="true" />
    <a-row :gutter="12">
        <a-col :lg="14" :span="24">
            <a-card class="card h-full">
                <a-row class="flex flex-wrap items-start justify-between mb-4 pb-4 border-b-2 gap-y-4">
                    <a-col :xs="24" :sm="24" :md="6">
                        <h1 class="text-base font-semibold">
                            {{ title }}
                        </h1>
                    </a-col>
                    <a-col :xs="24" :sm="24" :md="18" class="flex justify-end">
                        <a-row class="flex flex-wrap gap-2 justify-start md:justify-end w-full md:w-auto">
                            <a-col class="w-full md:w-auto">
                                <a-input v-model:value="filter.search" @keyup.enter="readData"
                                    class="min-w-32 lg:w-64 w-full" placeholder="Cari Role ...">
                                    <template #addonAfter>
                                        <span @click="readData" class="text-white text-base">
                                            <Icon icon="ant-design:search-outlined" />
                                        </span>
                                    </template>
                                </a-input>
                            </a-col>
                            <a-col class="w-full md:w-auto">
                                <a-button class="flex items-center justify-center w-full" type="primary"
                                    @click="newData()">
                                    Tambah Role
                                </a-button>
                            </a-col>
                        </a-row>
                    </a-col>
                </a-row>
                <a-table :scroll="{ x: 800 }" :columns="columns" :row-key="(obj) => obj.id" :pagination="_pagination"
                    :loading="loadingStatus" :data-source="models" @change="handleTableChange">
                    <template #bodyCell="{ column, record }">
                        <template v-if="column.key === 'action'">
                            <a-button-group class="flex justify-center">
                                <a-button size="small" type="text" @click="editData(record)"
                                    :style="{ padding: '0 5px' }">
                                    <Icon icon="line-md:pencil-twotone"
                                        class="flex justify-center text-green-500 text-[24px]" />
                                </a-button>
                                <a-tooltip title="Kelola akses role di panel kanan">
                                    <a-button size="small" type="text" @click="selectRole(record.id, record.name)" :style="{ padding: '0 5px' }">
                                        <Icon icon="line-md:arrow-right-circle" class="flex justify-center text-blue-500 text-[24px]"/>
                                    </a-button>
                                </a-tooltip>
                            </a-button-group>
                        </template>
                    </template>
                </a-table>
            </a-card>
        </a-col>
        <a-col :lg="10" :span="24">
            <a-card class="card card-white !px-5 h-full">
                <div class="pb-4 border-b-2">
                    <h1 class="text-base font-semibold transition-all" :class="selectedRole.id ? 'text-blue-600' : 'text-gray-400'">
                        <template v-if="selectedRole.id">Permission untuk Role: {{ selectedRole.name }}</template>
                        <template v-else>Pilih role terlebih dahulu</template>
                    </h1>
                </div>
                <a-row class="py-4 flex flex-wrap gap-2 justify-start md:justify-end w-full md:w-auto">
                    <a-col class="w-full md:w-auto">
                        <a-select v-model:value="form.permissions" class="min-w-32 lg:w-64 w-full" mode="multiple"
                            show-search option-filter-prop="title" allow-clear placeholder="--Pilih Permission--">
                            <a-select-option v-for="permission in available_permissions" :key="permission.id"
                                :value="permission.id" :title="permission.name">
                                {{ permission.name }}
                            </a-select-option>
                        </a-select>
                    </a-col>
                    <a-col class="w-full md:w-auto">
                        <a-button type="primary" class="w-full items-center justify-center" @click="attachPermission()">
                            Attach Permission</a-button>
                    </a-col>
                </a-row>
                <a-table :scroll="{ x: 800 }" :columns="permissions_columns" :row-key="(obj) => obj.id"
                    :pagination="false" :loading="loadingStatus" :data-source="permissions_data"
                    @change="handleTableChange">
                    <template #bodyCell="{ column, record }">
                        <template v-if="column.key === 'action'">
                            <a-popconfirm title="Yakin detach permission ini?" @confirm="detachPermission(record.id)"
                                :disabled="record.name == 'sudo'">
                                <a-button size="small" danger type="text" :style="{ padding: '0 5px' }"
                                    :disabled="record.name == 'sudo'">
                                    <Icon icon="line-md:trash" class="flex justify-center text-red-500 text-[24px]" />
                                </a-button>
                            </a-popconfirm>
                        </template>
                    </template>
                </a-table>
            </a-card>
        </a-col>
    </a-row>
    <a-modal v-model:open="showModal" :title="form.id ? 'Ubah Data' : 'Tambah Data'" width="700px" @ok="writeData"
        :mask-closable="false" :destroy-on-close="true">
        <a-form ref="form" name="basic" :label-col="{ span: 8 }" :wrapper-col="{ span: 16 }">
            <a-form-item label="Name" data-column="name">
                <a-input v-model:value="form.name" placeholder="Nama Role" />
            </a-form-item>
        </a-form>
    </a-modal>
</template>