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
        title: "Branch",
        key: "branch",
        align: "left",
        width: 225,
        sorter: (a, b) => a.branch_id - b.branch_id,
        sortDirections
    },
    {
        title: "Nama Gedung",
        dataIndex: "name",
        align: "left",
        width: 175,
        ellipsis: true,
        sorter: (a, b) => (a.name || '').localeCompare(b.name || ''),
        sortDirections
    },
    {
        title: "Jumlah Lantai",
        key: "floors_count",
        align: "left",
        width: 200,
        sorter: (a, b) => a.floors_count - b.floors_count,
    },
    {
        title: "Dibuat pada",
        dataIndex: "created_at",
        align: "left",
        width: 125,
        ellipsis: true,
        sorter: (a, b) => (a.status || '').localeCompare(b.status || ''),
        sortDirections
    },
    {
        title: "Diperbarui pada",
        dataIndex: "updated_at",
        align: "left",
        width: 125,
        ellipsis: true,
        sorter: (a, b) => (a.status || '').localeCompare(b.status || ''),
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
                branch_id: null,
                name: null,
                floors_count: null,
            },
            filter: {
                branch_id: null,
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
                vm.showModal = true;
            })
        },

        editData(m) {
            const vm = this
            vm.form = vm.lodash.cloneDeep(m)
            vm.$nextTick(() => {
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

        async deleteData(id, req = 'delete') {
            const vm = this;
            vm.loadingTrue()
            const form = {
                req: req,
                id: id
            };
            const response = await vm.axios.post(vm.writeRoute, form).catch((e) => vm.$onAjaxError(e));
            if (response && response.data) {
                if (req === 'delete') {
                    vm.openNotification('Berhasil menghapus data', 'success');
                } else {
                    vm.openNotification('Berhasil menghapus data secara permanen', 'success');
                }
                vm.readData();
                vm.showModal = false;
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
                    <!-- Branch -->
                    <a-col class="w-full md:w-auto">
                        <a-select v-model:value="filter.branch_id" placeholder="--Pilih Cabang--" show-search
                            allow-clear option-label-prop="label" option-filter-prop="label" class="min-w-56 lg:w-56 w-full"
                            @change="readData">
                            <a-select-option v-for="u in constant.BRANCHES" :key="u.id" :value="u.id"
                                :label="`${u.name}`">
                                <div class="flex items-center gap-2">
                                    <a-tag color="blue">{{ u.name }}</a-tag>
                                </div>
                            </a-select-option>
                        </a-select>
                    </a-col>

                    <a-col class="w-full md:w-auto">
                        <a-select v-model:value="filter.status" class="min-w-32 lg:w-32 w-full" @change="readData">
                            <a-select-option value="aktif">Aktif</a-select-option>
                            <a-select-option value="non_aktif">Non Aktif</a-select-option>
                        </a-select>
                    </a-col>
                    <a-col class="w-full md:w-auto">
                        <a-input v-model:value="filter.search" @keyup.enter="readData"
                            placeholder="Ketikkan nama Gedung ...">
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
                            Tambah Data Gedung
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
                <template v-if="column.key === 'action' && filter.status == 'aktif'">
                    <a-button-group class="flex justify-center">
                        <!-- Edit -->
                        <a-tooltip title="Edit Data">
                            <a-button size="small" type="text" @click="editData(record)" :style="{ padding: '0 5px' }">
                                <Icon icon="line-md:pencil-twotone"
                                    class="flex justify-center text-green-500 text-[24px]" />
                            </a-button>
                        </a-tooltip>
                        <!-- Hapus -->
                        <a-tooltip title="Hapus Data">
                            <a-popconfirm title="Yakin menghapus data?" @confirm="deleteData(record.id, 'delete')">
                                <a-button type="text" size="small" :style="{ padding: '0 5px' }">
                                    <Icon icon="line-md:trash" class="flex justify-center text-red-500 text-[24px]" />
                                </a-button>
                            </a-popconfirm>
                        </a-tooltip>
                    </a-button-group>
                </template>
                <template v-if="column.key == 'action' && filter.status == 'non_aktif'">
                    <a-popconfirm title="Yakin merestore data?" @confirm="deleteData(record.id, 'restore')">
                        <a-button size="small" type="primary">
                            <Icon icon="ant-design:rollback-outlined" />
                        </a-button>
                    </a-popconfirm>
                </template>
                <template v-if="column.key === 'number'">
                    {{ (_pagination.current - 1) * _pagination.pageSize + (index + 1) }}
                </template>
                <template v-if="column.key === 'branch'">
                    <a-tag color="blue">
                        <span class="text-sm">
                            {{ record.branch.name }}
                        </span>
                    </a-tag>
                </template>
                <template v-if="column.key === 'floors_count'">
                    <a-tag color="#2db7f5">
                        <span class="text-sm">
                            {{ record.floors_count }} Lantai
                        </span>
                    </a-tag>
                </template>
            </template>
        </a-table>
    </a-card>
    <a-modal v-model:open="showModal" :title="form.id ? 'Ubah Data Gedung' : 'Tambah Data Gedung'" width="700px"
        @ok="writeData" :mask-closable="false">
        <a-form ref="form" name="basic" :label-col="{ span: 8 }" :wrapper-col="{ span: 16 }">
            <a-form-item label="Cabang" data-column="branch_id" :rules="[{ required: true }]">
                <a-select v-model:value="form.branch_id" placeholder="--Pilih Cabang--" show-search allow-clear
                    option-label-prop="label" option-filter-prop="label" class="w-full lg:w-58">
                    <a-select-option v-for="u in constant.BRANCHES" :key="u.id" :value="u.id" :label="`${u.name}`">
                        <div class="flex items-center gap-2">
                            <a-tag color="blue">{{ u.name }}</a-tag>
                        </div>
                    </a-select-option>
                </a-select>
            </a-form-item>
            <a-form-item label="Nama Gedung" data-column="name" :rules="[{ required: true }]">
                <a-input v-model:value="form.name" placeholder="Isi Nama Gedung" />
            </a-form-item>
            <a-form-item label="Jumlah Lantai" name="floors_count" :rules="[{ required: true }]">
                <a-input-number v-model:value="form.floors_count" :min="1" :max="100" class="w-full"
                    placeholder="Contoh: 7" />
            </a-form-item>
        </a-form>
    </a-modal>
</template>