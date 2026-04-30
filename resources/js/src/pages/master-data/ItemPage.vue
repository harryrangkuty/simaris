<script>

const sortDirections = ['ascend', 'descend'];
const columns = [
    {
        title: "#",
        key: "number",
        align: "center",
        width: 90,
    },
    {
        title: "Kode Item",
        key: "code",
        align: "left",
        width: 120,
        ellipsis: true,
        sorter: (a, b) => (a.code || '').localeCompare(b.code || ''),
        sortDirections
    },
    {
        title: "Kode Item di IIS",
        key: "code_legacy",
        align: "left",
        width: 150,
        sorter: (a, b) => (a.code_legacy || '').localeCompare(b.code_legacy || ''),
        sortDirections
    },
    {
        title: "Nama Item",
        dataIndex: "name",
        align: "left",
        width: 300,
        ellipsis: true,
        sorter: (a, b) => (a.name || '').localeCompare(b.name || ''),
        sortDirections
    },
    {
        title: "Satuan",
        key: "uom_code",
        align: "left",
        width: 120,
        sorter: (a, b) => (a.uom_code || '').localeCompare(b.uom_code || ''),
        sortDirections
    },
    {
        title: "Jenis Stock Masuk",
        key: "stock_code",
        align: "left",
        width: 250,
        ellipsis: true,
        sorter: (a, b) => (a.stock_code || '').localeCompare(b.stock_code || ''),
        sortDirections
    },
    {
        title: "Kategori",
        key: "category_code",
        align: "left",
        width: 295,
        sorter: (a, b) => (a.category_code || '').localeCompare(b.category_code || ''),
        sortDirections
    },
    {
        title: "Jenis Item",
        key: "type",
        align: "left",
        width: 120,
        sorter: (a, b) => (a.type || '').localeCompare(b.type || ''),
        sortDirections
    },
    {
        title: "Kelompok Penyusutan",
        key: 'depreciation_group_code',
        width: 220,
        ellipsis: true,
        sorter: (a, b) => (a.depreciation_group_code || '').localeCompare(b.depreciation_group_code || ''),
        sortDirections
    },
    {
        title: "Minimum Stock",
        dataIndex: "min_stock",
        align: "left",
        width: 110,
        sorter: (a, b) => (a.min_stock || '').localeCompare(b.min_stock || ''),
        sortDirections
    },
    {
        title: "Maximum Stock",
        dataIndex: "max_stock",
        align: "left",
        width: 110,
        sorter: (a, b) => (a.max_stock || '').localeCompare(b.max_stock || ''),
        sortDirections
    },
    {
        title: "Catatan",
        dataIndex: "notes",
        align: "left",
        width: 120,
        ellipsis: true,
        sorter: (a, b) => (a.notes || '').localeCompare(b.notes || ''),
        sortDirections
    },
    {
        title: "Status",
        key: "is_active",
        align: "center",
        width: 90,
        ellipsis: true,
        sorter: (a, b) => a.is_active - b.is_active,
        sortDirections
    },
    {
        title: "Dibuat pada",
        dataIndex: "created_at",
        align: "left",
        width: 200,
        ellipsis: true,
        sorter: (a, b) => a.created_at - b.created_at,
        sortDirections
    },
    {
        title: "Diubah pada",
        dataIndex: "updated_at",
        align: "left",
        width: 200,
        ellipsis: true,
        sorter: (a, b) => a.updated_at - b.updated_at,
        sortDirections
    },
    {
        title: "Terakhir diubah oleh",
        dataIndex: ["editor", "name"],
        align: "left",
        width: 200,
        ellipsis: true,
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
                code: null,
                prefix: null,
                code_legacy: null,
                name: null,
                uom_code: null,
                stock_code: null,
                category_code: null,
                depreciation_group_code: null,
                type: null,
                min_stock: 0,
                max_stock: 0,
                notes: null,
                is_active: true,
            },
            filter: {
                status: "aktif",
                type: null,
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

            console.log(m);
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
                        <a-select v-model:value="filter.type" placeholder="Filter Tipe ..."
                            class="min-w-56 lg:w-56 w-full" @change="readData">
                            <a-select-option value="asset">Aset</a-select-option>
                            <a-select-option value="inventory">Persediaan</a-select-option>
                        </a-select>
                    </a-col>
                    <a-col class="w-full md:w-auto">
                        <a-input v-model:value="filter.search" @keyup.enter="readData"
                            placeholder="Ketikkan Kode/Nama ...">
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
                            Tambah Item
                        </a-button>
                    </a-col>
                </a-row>
            </a-col>
        </a-row>
        <!-- Total data -->
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
                <template v-if="column.key === 'code'">
                    <a-tag color="#2db7f5">
                        <span class="text-sm">
                            {{ record.code }}
                        </span>
                    </a-tag>
                </template>
                <template v-if="column.key === 'code_legacy'">
                    <a-tag color="red">
                        <span class="text-sm">
                            {{ record.code_legacy }}
                        </span>
                    </a-tag>
                </template>
                <template v-if="column.key === 'stock_code'">
                    <a-tag color="blue">
                        <span class="text-sm">
                            {{ record.stock?.code }} - {{ record.stock?.name }}
                        </span>
                    </a-tag>
                </template>
                <template v-if="column.key === 'category_code'">
                    <a-tag color="orange">
                        <span class="text-sm">
                            {{ record.category.code }} - {{ record.category.name }}
                        </span>
                    </a-tag>
                </template>
                <template v-if="column.key === 'type'">
                    <a-tag :color="record.type === 'asset' ? '#108ee9' : '#f50'">
                        <span class="text-sm">
                            {{ record.type === 'asset'
                                ? 'Aset'
                                : 'Persediaan'
                            }}
                        </span>
                    </a-tag>
                </template>
                <template v-if="column.key === 'uom_code'">
                    <a-tag color="blue">
                        <span class="text-sm">
                            {{ record.uom?.code ?? 'Belum dipasangkan' }}
                        </span>
                    </a-tag>
                </template>
                <template v-if="column.key === 'depreciation_group_code'">
                    <template v-if="record.depreciation_group_code">
                        <a-tag color="#f50">
                            <span class="uppercase">
                                {{ record.depreciation_group.code }} - {{ record.depreciation_group.name }} ({{
                                    record.depreciation_group.lifespan_months }} Bulan)
                            </span>
                        </a-tag>
                    </template>
                    <template v-else>
                        <a-tag color="default">
                            <span class="text-sm text-gray-500">
                                Tidak Ada Penyusutan
                            </span>
                        </a-tag>
                    </template>
                </template>
                <template v-if="column.key === 'is_active'">
                    <a-tag v-if="fb(record.is_active, true)" color="#87d068">Aktif</a-tag>
                    <a-tag v-else color="#f50">Non Aktif</a-tag>
                </template>
            </template>
        </a-table>
    </a-card>
    <a-modal v-model:open="showModal" :title="form.code ? 'Ubah Item' : 'Tambah Item'" width="900px" @ok="writeData"
        :mask-closable="false">
        <a-form ref="form" name="itemForm" :label-col="{ span: 8 }" :wrapper-col="{ span: 16 }">
            <!-- Prefix Code -->
            <a-form-item v-if="!form.code" label="Prefix Kode Item Untuk Generate" data-column="prefix"
                :rules="[{ required: true }]">
                <a-select v-model:value="form.prefix" placeholder="--Pilih Prefix--" allow-clear>
                    <a-select-option v-for="p in constant.PREFIXES" :key="p.prefix" :value="p.prefix" :label="p.prefix">
                        {{ p.prefix }}
                    </a-select-option>
                </a-select>
            </a-form-item>

            <!-- Kode Item Legacy -->
            <a-form-item label="Kode Item di IIS" data-column="code_legacy">
                <a-input v-model:value="form.code_legacy" placeholder="Opsional" allow-clear />
            </a-form-item>

            <!-- Nama Item -->
            <a-form-item label="Nama Item" data-column="name" :rules="[{ required: true }]">
                <a-input v-model:value="form.name" placeholder="Isi Nama Item" />
            </a-form-item>

            <!-- Satuan -->
            <a-form-item label="Satuan" data-column="uom_code" :rules="[{ required: true }]">
                <a-select v-model:value="form.uom_code" placeholder="--Pilih Satuan--" allow-clear show-search
                    option-label-prop="label" option-filter-prop="label">
                    <a-select-option v-for="u in constant.UOMS" :key="u.code" :value="u.code"
                        :label="`${u.code} - ${u.name}`">
                        <div class="flex items-center gap-3">
                            <!-- Tag Kode -->
                            <a-tag color="blue">{{ u.code }}</a-tag>

                            <span class="text-xs text-gray-600">
                                <b>Label:</b> {{ u.name }}
                            </span>
                        </div>
                    </a-select-option>
                </a-select>
            </a-form-item>
            
            <!-- Jenis Stock -->
            <a-form-item label="Jenis Stock Masuk" data-column="stock_code" :rules="[{ required: true }]">
                <a-select v-model:value="form.stock_code" placeholder="--Pilih Stock--" allow-clear show-search
                    option-label-prop="label" option-filter-prop="label">
                    <a-select-option v-for="s in constant.STOCK_CODES" :key="s.code" :value="s.code"
                        :label="`${s.code} - ${s.name}`">
                        {{ s.code }} - {{ s.name }}
                    </a-select-option>
                </a-select>
            </a-form-item>

            <!-- Kategori -->
            <a-form-item label="Kategori" data-column="category_code" :rules="[{ required: true }]">
                <a-select v-model:value="form.category_code" placeholder="--Pilih Kategori--" allow-clear show-search
                    option-label-prop="label" option-filter-prop="label">
                    <a-select-option v-for="c in constant.CATEGORIES" :key="c.code" :value="c.code"
                        :label="`${c.code} - ${c.name}`">
                        {{ c.code }} - {{ c.name }}
                    </a-select-option>
                </a-select>
            </a-form-item>

            <!-- Jenis Item -->
            <a-form-item label="Jenis Item" data-column="type" :rules="[{ required: true }]">
                <a-select v-model:value="form.type" placeholder="--Pilih Jenis--" allow-clear show-search
                    option-label-prop="label" option-filter-prop="label">
                    <a-select-option value="asset">Aset</a-select-option>
                    <a-select-option value="inventory">Persediaan</a-select-option>
                </a-select>
            </a-form-item>

            <!-- Kelompok Penyusutan -->
            <a-form-item label="Kelompok Penyusutan" data-column="depreciation_group_code">
                <a-select v-model:value="form.depreciation_group_code" placeholder="Opsional" allow-clear show-search
                    option-label-prop="label" option-filter-prop="label">
                    <a-select-option v-for="d in constant.DEPRECIATION_GROUPS" :key="d.code" :value="d.code"
                        :label="`${d.code} - ${d.name}`">
                        {{ d.code }} - {{ d.name }} ({{ d.lifespan_months }} Bulan)
                    </a-select-option>
                </a-select>
            </a-form-item>

            <!-- Min Stock -->
            <a-form-item label="Minimum Stock" data-column="min_stock">
                <a-input-number v-model:value="form.min_stock" :min="0" class="w-full" />
            </a-form-item>

            <!-- Max Stock -->
            <a-form-item label="Maximum Stock" data-column="max_stock">
                <a-input-number v-model:value="form.max_stock" :min="0" class="w-full" />
            </a-form-item>

            <!-- Catatan -->
            <a-form-item label="Catatan" data-column="notes">
                <a-textarea v-model:value="form.notes" :rows="3" placeholder="Isi catatan tambahan (opsional)" />
            </a-form-item>

            <!-- Status Aktif -->
            <a-form-item label="Status" data-column="is_active" :rules="[{ required: true }]">
                <a-radio-group v-model:value="form.is_active">
                    <a-radio :value="true">Aktif</a-radio>
                    <a-radio :value="false">Non Aktif</a-radio>
                </a-radio-group>
            </a-form-item>
        </a-form>
    </a-modal>
</template>