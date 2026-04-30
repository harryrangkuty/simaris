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
        title: "Kode GL",
        key: "gl_code",
        align: "left",
        width: 120,
        ellipsis: true,
        sorter: (a, b) => (a.gl_code || '').localeCompare(b.gl_code || ''),
        sortDirections
    },
    {
        title: "Nama Supplier",
        dataIndex: "name",
        align: "left",
        width: 350,
        ellipsis: true,
        sorter: (a, b) => (a.name || '').localeCompare(b.name || ''),
        sortDirections
    },
    {
        title: "Alamat Supplier",
        dataIndex: "address",
        align: "left",
        width: 300,
        ellipsis: true,
        sorter: (a, b) => (a.address || '').localeCompare(b.address || ''),
        sortDirections
    },
    {
        title: "No Telpon Supplier",
        key: "phone",
        dataIndex: "phone",
        align: "left",
        width: 180,
        ellipsis: true,
        sorter: (a, b) => (a.phone || '').localeCompare(b.phone || ''),
        sortDirections
    },
    {
        title: "PPN",
        key: "is_ppn",
        align: "left",
        width: 100,
        sorter: (a, b) => a.is_ppn - b.is_ppn,
        sortDirections
    },
    {
        title: "Keterangan",
        dataIndex: "notes",
        align: "left",
        width: 120,
        ellipsis: true,
        sorter: (a, b) => (a.notes || '').localeCompare(b.notes || ''),
        sortDirections
    },
    {
        title: "PIC (Person in Charge)",
        dataIndex: "pic_name",
        align: "left",
        width: 150,
        ellipsis: true,
        sorter: (a, b) => (a.pic_name || '').localeCompare(b.pic_name || ''),
        sortDirections,
    },
    {
        title: "Status",
        key: "deleted_at",
        align: "center",
        width: 90,
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
                gl_code: null,
                name: null,
                address: null,
                phone: null,
                pic_name: null,
                is_ppn: false,
                notes: null,
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
                    <a-col class="w-full md:w-auto">
                        <a-select v-model:value="filter.status" class="min-w-32 lg:w-32 w-full" @change="readData">
                            <a-select-option value="aktif">Aktif</a-select-option>
                            <a-select-option value="non_aktif">Non Aktif</a-select-option>
                        </a-select>
                    </a-col>
                    <a-col class="w-full md:w-auto">
                        <a-input v-model:value="filter.search" @keyup.enter="readData"
                            placeholder="Ketikkan nama Supplier ...">
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
                            Tambah Data Supplier
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
                <template v-if="column.key === 'gl_code'">
                    <a-tag color="#2db7f5">
                        <span class="text-sm">
                            {{ record.gl_code }}
                        </span>
                    </a-tag>
                </template>
                <template v-if="column.key === 'is_ppn'">
                    <a-tag :color="record.is_ppn == 1 ? 'blue' : 'red'">
                        <span class="text-sm">
                            {{ record.is_ppn == 1 ? 'PPN' : 'Non-PPN' }}
                        </span>
                    </a-tag>
                </template>
                <template v-if="column.key === 'deleted_at'">
                    <a-tag v-if="fb(record.deleted_at, false)" color="#f50">Non Aktif</a-tag>
                    <a-tag v-else color="#87d068">Aktif</a-tag>
                </template>
            </template>
        </a-table>
    </a-card>
    <a-modal v-model:open="showModal" title="Tambah Data" width="700px" @ok="writeData" :mask-closable="false">
        <a-form ref="form" name="basic" :label-col="{ span: 8 }" :wrapper-col="{ span: 16 }">
            <a-form-item label="Nama Supplier" data-column="name" :rules="[{ required: true }]">
                <a-input v-model:value="form.name" placeholder="Isi Nama Supplier Barang" />
            </a-form-item>
            <a-form-item label="Kode GL" data-column="gl_code" :rules="[{ required: true }]">
                <a-input v-model:value="form.gl_code" placeholder="Isi Kode GL Supplier" />
            </a-form-item>
            <a-form-item label="No Telpon Supplier" data-column="phone" :rules="[{ required: true }]">
                <vue-tel-input v-model="form.phone" mode="international" :input-options="{ placeholder: '62877889900' }"
                    class="!border !border-[#d9d9d9] !rounded-[6px]" />
            </a-form-item>
            <a-form-item label="Alamat Supplier" data-column="address" :rules="[{ required: true }]">
                <a-input v-model:value="form.address" placeholder="Isi Alamat Supplier" />
            </a-form-item>
            <a-form-item label="PPN" data-column="is_ppn" :rules="[{ required: true }]">
                <a-radio-group v-model:value="form.is_ppn">
                    <a-radio :value="true">PPN</a-radio>
                    <a-radio :value="false">Non PPN</a-radio>
                </a-radio-group>
            </a-form-item>
            <a-form-item label="Nama PIC Supplier" data-column="pic_name">
                <a-input v-model:value="form.pic_name" placeholder="Isi Nama PIC Supplier" />
            </a-form-item>
            <a-form-item label="Keterangan" data-column="notes">
                <a-textarea v-model:value="form.notes" :rows="3" placeholder="Isi Keterangan, cth : OBAT" />
            </a-form-item>
        </a-form>
    </a-modal>
</template>