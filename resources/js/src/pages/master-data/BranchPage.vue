<script>

const sortDirections = ['ascend', 'descend'];
const columns = [
    {
        title: "#",
        key: "number",
        align: "center",
        width: 60,
    },
    {
        title: "Kode Cabang",
        key: "code",
        align: "left",
        width: 120,
        ellipsis: true,
        sorter: (a, b) => (a.code || '').localeCompare(b.code || ''),
        sortDirections
    },
    {
        title: "Nama Cabang",
        dataIndex: "name",
        align: "left",
        width: 170,
        ellipsis: true,
        sorter: (a, b) => (a.name || '').localeCompare(b.name || ''),
        sortDirections
    },
    {
        title: "Alamat",
        dataIndex: "address",
        align: "left",
        width: 300,
        ellipsis: true,
        sorter: (a, b) => (a.address || '').localeCompare(b.address || ''),
        sortDirections
    },
    {
        title: "Status",
        key: "is_active",
        align: "center",
        width: 90,
        ellipsis: true,
        sorter: (a, b) => (a.is_active || '').localeCompare(b.is_active || ''),
        sortDirections
    },
    {
        title: "Action",
        key: "action",
        align: "center",
        width: 110,
        fixed: 'right',
        className: 'column-action'
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
            form: {
                id: null,
                code: null,
                name: null,
                description: null,
                is_active: true,
            },
            filter: {
                status: "aktif",
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
            const vm = this;
            vm.loadingTrue();
            let params = v ?? {
                total: this._pagination.total,
                page: this._pagination.current,
            };
            params = {
                req: "table",
                results: 10,
                ...params,
                ...vm.filter,
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
                    vm.openNotification('Berhasil menyimpan data ...', 'success');
                }
                else {
                    vm.openNotification('Berhasil mengubah data', 'success');
                }
                vm.readData();
                vm.showModal = false;
                vm.loadingFalse();
            }
        },
    }
};
</script>

<template>
    <Breadcrumb :items="breadcrumbItems" :showBackButton="true" />
    <a-card>
        <a-row class="flex flex-wrap items-start justify-between mb-4 pb-4 border-b-2 gap-y-4">
            <a-col :xs="24" :sm="24" :md="6">
                <h1 class="text-base font-semibold">
                    {{ title }}
                </h1>
            </a-col>
            <a-col :xs="24" :sm="24" :md="18" class="flex justify-end">
                <a-row class="flex flex-wrap gap-2 justify-start md:justify-end w-full md:w-auto">
                    <a-col class="w-full md:w-auto">
                        <a-select v-model:value="filter.status" class="min-w-32 lg:w-32 w-full" @change="readData">
                            <a-select-option value="aktif">Aktif</a-select-option>
                            <a-select-option value="non_aktif">Non Aktif</a-select-option>
                        </a-select>
                    </a-col>
                    <a-col class="w-full md:w-auto">
                        <a-input v-model:value="filter.search" @keyup.enter="readData"
                            placeholder="Ketikkan nama Transaksi ...">
                            <template #addonAfter>
                                <span @click="readData" class="text-white text-base">
                                    <Icon icon="ant-design:search-outlined" />
                                </span>
                            </template>
                        </a-input>
                    </a-col>
                    <a-col class="w-full md:w-auto">
                        <a-button type="primary"
                            class="bg-gradient-to-r from-purple-400 via-blue-500 to-blue-700 hover:from-yellow-400 hover:via-yellow-600 hover:to-purple-500 flex items-center justify-center text-white font-medium border-0 shadow-md transition-all duration-300 w-full"
                            @click="newData()">
                            <Icon icon="line-md:plus" class="mr-1" />
                            Tambah Data Cabang
                        </a-button>
                    </a-col>
                </a-row>
            </a-col>
        </a-row>
        <div class="mb-2 font-medium">
            Total: {{ _pagination.total }} Data
        </div>
        <a-table :scroll="{ x: 800 }" :columns="columns" :row-key="(obj) => obj.id" :pagination="_pagination"
            :loading="loadingStatus" :data-source="models" @change="handleTableChange">
            <template #bodyCell="{ index, column, record }">
                <template v-if="column.key === 'action'">
                    <a-button-group class="flex justify-center">
                        <!-- Edit -->
                        <a-tooltip title="Edit Data">
                            <a-button size="small" type="text" @click="editData(record)" :style="{ padding: '0 5px' }">
                                <Icon icon="line-md:pencil-twotone"
                                    class="flex justify-center text-green-500 text-[24px]" />
                            </a-button>
                        </a-tooltip>
                    </a-button-group>
                </template>
                <template v-if="column.key === 'number'">
                    {{ (_pagination.current - 1) * _pagination.pageSize + (index + 1) }}
                </template>
                <template v-if="column.key === 'code'">
                    <a-tag color="#2db7f5">
                        <span class="text-sm">
                            {{ record.code }}
                        </span>
                    </a-tag>
                </template>
                <template v-if="column.key === 'is_active'">
                    <a-tag v-if="fb(record.is_active, true)" color="#87d068">Aktif</a-tag>
                    <a-tag v-else color="#f50">Non Aktif</a-tag>
                </template>
            </template>
        </a-table>
    </a-card>
    <a-modal v-model:open="showModal" :title="form.id ? 'Ubah Data Branch' : 'Tambah Data Branch'" width="600px"
        @ok="writeData" :mask-closable="false">
        <a-form ref="form" name="warehouseForm" :label-col="{ span: 7 }" :wrapper-col="{ span: 17 }"
            layout="horizontal">
            <a-form-item label="Nama Transaksi" name="name" :rules="[{ required: true }]">
                <a-input v-model:value="form.name" placeholder="Isi Nama Transaksi" allow-clear />
            </a-form-item>
            <a-form-item label="Kode Transaksi" name="code" :rules="[{ required: true }]">
                <a-input v-model:value="form.code" placeholder="Isi Kode Transaksi" allow-clear />
            </a-form-item>
            <a-form-item label="Keterangan" name="description">
                <a-textarea v-model:value="form.description" :rows="3" placeholder="Isi keterangan tambahan (opsional)"
                    allow-clear />
            </a-form-item>
            <a-form-item label="Status Aktif" name="is_active">
                <a-switch v-model:checked="form.is_active" checked-children="Aktif" un-checked-children="Nonaktif" />
            </a-form-item>
        </a-form>
    </a-modal>
</template>