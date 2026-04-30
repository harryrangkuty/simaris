<script>
import { debounce } from "lodash-es";

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
        title: "Kode Gudang",
        key: "code",
        align: "left",
        width: 150,
        sorter: (a, b) => (a.code || '').localeCompare(b.code || ''),
        sortDirections
    },
    {
        title: "Nama gudang",
        dataIndex: "name",
        align: "left",
        width: 175,
        ellipsis: true,
        sorter: (a, b) => (a.name || '').localeCompare(b.name || ''),
        sortDirections
    },
    {
        title: "Kode Stock",
        key: "stock_codes",
        align: "left",
        width: 200,
        ellipsis: true,
    },
    {
        title: "Alamat Gudang",
        dataIndex: "address",
        align: "left",
        width: 250,
        ellipsis: true,
        sorter: (a, b) => (a.address || '').localeCompare(b.address || ''),
        sortDirections
    },
    {
        title: "Keterangan",
        dataIndex: "description",
        align: "left",
        width: 200,
        ellipsis: true,
        sorter: (a, b) => (a.description || '').localeCompare(b.description || ''),
        sortDirections
    },
    {
        title: "Bisa Terima",
        key: "can_receive",
        align: "center",
        width: 75,
    },
    {
        title: "Bisa Kirim",
        key: "can_dispatch",
        align: "center",
        width: 75,
    },
    {
        title: "PJ Gudang",
        key: "person_in_charge",
        align: "left",
        width: 200,
        ellipsis: true,
        sorter: (a, b) => {
            const nameA = a.person_in_charge?.name || '';
            const nameB = b.person_in_charge?.name || '';
            return nameA.localeCompare(nameB);
        },
        sortDirections,
    },
    {
        title: "Status",
        key: "deleted_at",
        align: "center",
        width: 75,
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
                code: null,
                name: null,
                address: null,
                description: null,
                can_receive: true,
                can_dispatch: true,
                person_in_charge_id: null,
                stock_code_ids: []
            },
            filter: {
                branch_id: null,
                status: "aktif",
            },
            userOptions: [],
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
                vm.fetchUsers()
            })
        },

        editData(m) {
            const vm = this
            vm.form = vm.lodash.cloneDeep(m)
            vm.form.stock_code_ids = m.stock_codes?.map(sc => sc.code) || []
            vm.$nextTick(() => {
                vm.showModal = true
                vm.fetchUsers(m.person_in_charge_id)
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

        async fetchUsers(param = "") {

            let url = "/lookups/users?";

            if (typeof param === "number") {
                url += `id=${param}`;
            } else if (/^[0-9]{6,}$/.test(param)) {
                url += `identifier=${encodeURIComponent(param)}`;
            } else {
                url += `search=${encodeURIComponent(param)}&limit=10`;
            }

            const res = await fetch(url);
            const data = await res.json();

            this.userOptions = Array.isArray(data) ? data : [];
        },

        onSearchUser: debounce(function (val) {
            const vm = this;
            vm.fetchUsers(val);
        }, 500),

        onSearchUser: debounce(function (val) {
            const vm = this;
            vm.fetchUsers(val);
        }, 500),
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
                            allow-clear option-label-prop="label" option-filter-prop="label"
                            class="min-w-56 lg:w-56 w-full" @change="readData">
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
                            placeholder="Ketikkan nama Gudang ...">
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
                            Tambah Data Gudang
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
                <template v-if="column.key === 'code'">
                    <a-tag color="#2db7f5">
                        <span class="text-sm">
                            {{ record.code }}
                        </span>
                    </a-tag>
                </template>
                <template v-if="column.key === 'can_receive'">
                    <div class="flex items-center justify-center">
                        <Icon :icon="record.can_receive ? 'line-md:check-all' : 'line-md:close-circle'"
                            class="text-[20px]" :class="record.can_receive ? 'text-green-600' : 'text-red-600'" />
                    </div>
                </template>
                <template v-else-if="column.key === 'can_dispatch'">
                    <div class="flex items-center justify-center">
                        <Icon :icon="record.can_dispatch ? 'line-md:check-all' : 'line-md:close-circle'"
                            class="text-[20px]" :class="record.can_dispatch ? 'text-blue-600' : 'text-amber-500'" />
                    </div>
                </template>
                <template v-if="column.key === 'person_in_charge'">
                    <div class="flex">
                        <a-tag color="blue">{{ record.person_in_charge?.identifier }}</a-tag>
                        <span>{{ record.person_in_charge?.name }}</span>
                    </div>
                </template>
                <template v-if="column.key === 'stock_codes'">
                    <ul class="list-disc list-inside space-y-1">
                        <li v-for="sc in record.stock_codes" :key="sc.code" class="flex items-center gap-2">
                            <a-tag color="blue">{{ sc.code }}</a-tag>
                            <span>{{ sc.name }}</span>
                        </li>
                    </ul>
                </template>
                <template v-if="column.key === 'deleted_at'">
                    <a-tag v-if="fb(record.deleted_at, false)" color="#f50">Non Aktif</a-tag>
                    <a-tag v-else color="#87d068">Aktif</a-tag>
                </template>
            </template>
        </a-table>
    </a-card>
    <a-modal v-model:open="showModal" :title="form.id ? 'Ubah Data Gudang' : 'Tambah Data Gudang'" width="700px"
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
            <a-form-item label="Nama Gudang" data-column="name" :rules="[{ required: true }]">
                <a-input v-model:value="form.name" placeholder="Isi Nama Gudang" />
            </a-form-item>
            <a-form-item label="Kode Gudang" data-column="code" :rules="[{ required: true }]">
                <a-input v-model:value="form.code" placeholder="Isi Kode Gudang" />
            </a-form-item>
            <a-form-item label="Jenis Stock" data-column="stock_code_ids"
                :rules="[{ required: true, message: 'Pilih minimal 1 jenis stock' }]">
                <a-select v-model:value="form.stock_code_ids" mode="multiple" placeholder="--Pilih Jenis Stock--"
                    show-search option-label-prop="display" option-filter-prop="label">
                    <a-select-option v-for="stk in constant.STOCK_CODE_OPTIONS" :key="stk.code" :value="stk.code"
                        :label="`${stk.code} ${stk.name}`" :display="`${stk.code} - ${stk.name}`">
                        <div class="flex items-center gap-2">
                            <a-tag color="blue">{{ stk.code }}</a-tag>
                            <span class="text-gray-700">{{ stk.name }}</span>
                        </div>
                    </a-select-option>
                </a-select>
            </a-form-item>
            <a-form-item label="Alamat Gudang" data-column="address">
                <a-input v-model:value="form.address" placeholder="Isi Alamat Gudang" />
            </a-form-item>
            <a-form-item label="Bisa Terima" data-column="can_receive" :rules="[{ required: true }]">
                <a-radio-group v-model:value="form.can_receive">
                    <a-radio :value="true">Ya</a-radio>
                    <a-radio :value="false">Tidak</a-radio>
                </a-radio-group>
            </a-form-item>
            <a-form-item label="Bisa Kirim" data-column="can_dispatch" :rules="[{ required: true }]">
                <a-radio-group v-model:value="form.can_dispatch">
                    <a-radio :value="true">Ya</a-radio>
                    <a-radio :value="false">Tidak</a-radio>
                </a-radio-group>
            </a-form-item>
            <a-form-item label="PJ Gudang" data-column="person_in_charge_id" :rules="[{ required: true }]">
                <a-select v-model:value="form.person_in_charge_id" placeholder="--Pilih PJ--" show-search
                    :filter-option="false" allow-clear @search="onSearchUser">
                    <a-select-option v-for="s in userOptions" :key="s.id" :value="s.id" :label="s.name">
                        <div class="flex items-center gap-2">
                            <a-tag color="blue">{{ s.identifier }}</a-tag>
                            <span class="text-gray-700">{{ s.name }}</span>
                        </div>
                    </a-select-option>
                </a-select>
            </a-form-item>
            <a-form-item label="Keterangan" data-column="description">
                <a-textarea v-model:value="form.description" :rows="3" placeholder="Isi Keterangan, cth: Gudang Obat" />
            </a-form-item>
        </a-form>
    </a-modal>
</template>