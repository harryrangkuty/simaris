<script>
import { debounce } from "lodash-es";

const sortDirections = ['ascend', 'descend'];
const columns = [
    {
        title: "#",
        key: "number",
        align: "center",
        width: 50,
    },
    {
        title: "Branch",
        key: "branch",
        align: "center",
        width: 170,
    },
    {
        title: "Status",
        key: "status",
        align: "center",
        width: 75,
    },
    {
        title: "Kode Pengadaan",
        key: "code",
        align: "center",
        width: 140,
        ellipsis: true,
        sorter: (a, b) => (a.code || '').localeCompare(b.code || ''),
        sortDirections
    },
    {
        title: "Jenis Transaksi",
        key: "transaction_type",
        align: "center",
        width: 140,
        sorter: (a, b) => (a.transaction_type || '').localeCompare(b.transaction_type || ''),
        sortDirections
    },
    {
        title: "Sumber Dana",
        dataIndex: "funding_source",
        align: "center",
        width: 100,
        ellipsis: true,
        sorter: (a, b) => (a.funding_source || '').localeCompare(b.funding_source || ''),
        sortDirections
    },
    {
        title: "Tgl Pembukuan",
        key: "book_date",
        align: "center",
        width: 120,
        ellipsis: true,
        sorter: (a, b) => new Date(a.book_date) - new Date(b.book_date),
        sortDirections,
    },
    {
        title: "Jenis Barang",
        key: "detail_count",
        align: "center",
        width: 60,
        sorter: (a, b) => a.detail_count - b.detail_count,
        sortDirections
    },
    {
        title: "Jumlah Barang",
        key: "detail_sum_quantity",
        align: "center",
        width: 60,
        sorter: (a, b) => a.detail_sum_quantity - b.detail_sum_quantity,
        sortDirections
    },
    {
        title: "Total Nilai Perolehan",
        key: "total",
        align: "center",
        width: 120,
    },
    {
        title: "Supplier",
        key: "supplier",
        align: "center",
        width: 170,
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
                transaction_type_code: null,
                funding_source: null,
                supplier_id: null,
                notes: null,
            },
            filters: {
                status: null,
                branch_id: null,
            },
            statusCounts: {},
            supplierOptions: [],
        };
    },

    created() {
        // Handle inisiasi fiscal_year
        this.initDateRange();
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
        this.readData();
    },

    methods: {

        async readData(v) {
            const vm = this;
            vm.loadingTrue();

            let params = v ?? {
                total: vm._pagination.total,
                page: vm._pagination.current,
            };

            params = {
                req: "table",
                results: 10,
                ...params,
                ...vm.filter,
                ...vm.filters
            };

            const response = await vm.axios.get(vm.readRoute, { params })
            if (response && response.data) {
                const pagination = { ...vm._pagination };
                pagination.total = response.data.models.total;
                vm.models = response.data.models.data;
                vm.statusCounts = response.data.counts || {};
                vm._pagination = pagination;
                vm.loadingFalse();
            }
        },

        newData() {
            const vm = this
            Object.assign(vm.$data.form, vm.$options.data().form);
            vm.$nextTick(function () {
                vm.showModal = true;
                vm.fetchSuppliers();
            })
        },

        editData(m) {
            const vm = this;
            vm.form = vm.lodash.cloneDeep(m);
            vm.$nextTick(function () {
                vm.showModal = true;
                if (m.supplier_id) {
                    vm.fetchSuppliers(m.supplier_id);
                }
                vm.fetchSuppliers();
            })
        },

        async writeData() {
            const vm = this;
            vm.loadingTrue()
            const form = {
                req: 'add_procurement',
                ...vm.form
            };
            const response = await vm.axios.post(vm.writeRoute, form).catch((e) => vm.$onAjaxError(e));
            if (response && response.data) {
                if (!form.id) {
                    vm.openNotification('Berhasil menyimpan data, mengalihkan ke halaman detail ...', 'success');
                    setTimeout(function () {
                        vm.openDetail(response.data.code);
                    }, 1500)
                }
                else {
                    vm.openNotification('Berhasil mengubah data', 'success');
                }
                vm.readData();
                vm.showModal = false;
                vm.loadingFalse()
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

        openDetail(code) {
            const vm = this
            const _window = window.open(vm.route + `?req=open&code=${code}`, '_self');
            if (window.focus) {
                _window.focus();
            }
            return false;
        },

        disabledDate(date) {
            const today = new Date();
            const currentYear = today.getFullYear();
            const fiscalYear = this.user?.fiscal_year;
            const startOfYear = new Date(fiscalYear, 0, 1);
            const endOfRange = fiscalYear < currentYear ? new Date(fiscalYear, 11, 31, 23, 59, 59) : today;

            return date < startOfYear || date > endOfRange;
        },

        getDefaultPickerValue(type = 'single') {
            const year = this.user.fiscal_year || new Date().getFullYear();
            const date = this.dayjs(`${year}-01-01`);
            return type === 'range' ? [date, date] : date;
        },

        initDateRange() {
            const today = this.dayjs();
            const currentYear = today.year();
            const fiscalYear = this.user?.fiscal_year;
            this.date_range = [
                this.dayjs(`${fiscalYear}-01-01T00:00:00.000Z`),
                this.dayjs(fiscalYear < currentYear ? `${fiscalYear}-12-31T00:00:00.000Z` : today)
            ];
            this.filterDateRange(this.date_range);
        },

        getTransactionTagColor(code) {
            const colorMap = {
                S00: 'gray',
                M01: 'green',
                M02: 'blue',
                M03: 'cyan',
            }
            return colorMap[code] || 'default'
        },
        statusColor(status) {
            switch (status) {
                case 'draft':
                    return '#ff4d4f' // merah
                case 'submitted':
                    return '#52c41a' // hijau
                case 'approved':
                    return '#1890ff' // biru
                case 'received':
                    return '#722ed1' // ungu (contoh)
                case 'registered':
                    return '#faad14' // oranye
                case 'cancelled':
                case 'rejected':
                    return '#ff4d4f' // merah
                default:
                    return '#d9d9d9' // abu-abu (draft/unknown)
            }
        },

        async fetchSuppliers(param = "") {
            const vm = this;
            vm.loadingTrue();
            try {
                let url = "/lookups/suppliers?";
                if (typeof param === "number" || /^[0-9]+$/.test(param)) {
                    url += `id=${param}`;
                } else {
                    url += `search=${encodeURIComponent(param)}&limit=10`;
                }

                const res = await fetch(url);
                const data = await res.json();

                vm.supplierOptions = Array.isArray(data) ? data : [data];
            } finally {
                vm.loadingFalse();
            }
        },

        onSearchSupplier: debounce(function (val) {
            const vm = this;
            vm.fetchSuppliers(val);
        }, 500),
    },
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
                        <div class="relative flex items-center">
                            <Icon v-if="statusCounts.draft > 0" icon="mdi:asterisk"
                                class="text-red-500 h-5 w-5 absolute -top-2 -left-2 z-50" />
                            <a-select v-model:value="filters.status" class="lg:w-40 w-full" show-search allow-clear
                                placeholder="Pilih Status" :getPopupContainer="(trigger) => trigger.parentNode"
                                @change="readData()">
                                <a-select-option value="draft">
                                    Draft ({{ statusCounts.draft || 0 }})
                                </a-select-option>
                                <a-select-option value="submitted">
                                    Submitted ({{ statusCounts.submitted || 0 }})
                                </a-select-option>
                                <a-select-option value="approved">
                                    Approved ({{ statusCounts.approved || 0 }})
                                </a-select-option>
                                <a-select-option value="received">
                                    Received ({{ statusCounts.received || 0 }})
                                </a-select-option>
                                <a-select-option value="registered">
                                    Registered ({{ statusCounts.registered || 0 }})
                                </a-select-option>
                                <a-select-option value="cancelled">
                                    Cancelled ({{ statusCounts.cancelled || 0 }})
                                </a-select-option>
                                <a-select-option value="rejected">
                                    Rejected ({{ statusCounts.rejected || 0 }})
                                </a-select-option>
                            </a-select>
                        </div>
                    </a-col>
                    <a-col class="w-full md:w-auto">
                        <a-select v-model:value="filter.branch_id" placeholder="--Pilih Gudang--" show-search
                            allow-clear option-label-prop="label" option-filter-prop="label" class="w-full lg:w-72">
                            <a-select-option v-for="branch in constant.BRANCH_RECEIVE_OPTIONS" :key="branch.id"
                                :value="branch.id" :label="`${branch.code} - ${branch.name}`">
                                <div class="flex items-center gap-2">
                                    <a-tag color="blue">{{ branch.code }}</a-tag>
                                    <span class="text-gray-700">{{ branch.name }}</span>
                                </div>
                            </a-select-option>
                        </a-select>
                    </a-col>
                    <a-col class="w-full md:w-auto">
                        <a-range-picker v-model:value="date_range" :format="date_format" @change="readData()"
                            @calendarChange="filterDateRange" class="w-full lg:w-72"
                            :default-picker-value="getDefaultPickerValue('range')" />
                    </a-col>
                    <a-col class="w-full md:w-auto">
                        <a-input v-model:value="filter.search" @keyup.enter="readData" placeholder="Ketikkan Kode ...">
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
                            Tambah Pengadaan
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
                        <!-- Detail -->
                        <a-tooltip title="Lihat Detail">
                            <a-button size="small" type="text" @click="openDetail(record.code)"
                                :style="{ padding: '0 5px' }">
                                <Icon icon="line-md:file-search-twotone"
                                    class="flex justify-center text-blue-500 text-[24px]" />
                            </a-button>
                        </a-tooltip>
                        <!-- Hapus -->
                        <a-tooltip title="Hapus Data">
                            <a-popconfirm title="Yakin menghapus data?" @confirm="deleteData(record.id)">
                                <a-button type="text" size="small" :style="{ padding: '0 5px' }">
                                    <Icon icon="line-md:trash" class="flex justify-center text-red-500 text-[24px]" />
                                </a-button>
                            </a-popconfirm>
                        </a-tooltip>
                    </a-button-group>
                </template>
                <template v-if="column.key === 'number'">
                    {{ (_pagination.current - 1) * _pagination.pageSize + (index + 1) }}
                </template>
                <template v-if="column.key === 'branch'">
                    <a-tag color="red">
                        <span>
                            {{ record.branch?.name }}
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
                <template v-if="column.key === 'transaction_type'">
                    <a-tag :color="getTransactionTagColor(record.transaction_type?.code)">
                        <span class="text-sm">
                            {{ record.transaction_type?.code }} - {{ record.transaction_type?.name }}
                        </span>
                    </a-tag>
                </template>
                <template v-if="column.key === 'book_date'">
                    <template v-if="record.book_date">
                        {{ filterDate(record.book_date) }}
                    </template>
                    <template v-else>
                        <a-tag color="red">Belum Diregister</a-tag>
                    </template>
                </template>
                <template v-if="column.key === 'detail_sum_quantity'">
                    {{ fb(record.detail_sum_quantity, '-') }}
                </template>
                <template v-if="column.key === 'detail_count'">
                    {{ record.detail_count > 0 ? record.detail_count : '-' }}
                </template>
                <template v-if="column.key === 'total'">
                    {{ record.total > 0 ? idCurrency(record.total) : '-' }}
                </template>
                <template v-if="column.key === 'status'">
                    <a-tag :color="statusColor(record.status)">
                        {{ fb(record.status, '-')?.toUpperCase() }}
                    </a-tag>
                </template>
                <template v-if="column.key === 'supplier'">
                    <a-tag :color="record.supplier?.gl_code ? 'blue' : 'red'">
                        <span>
                            {{ record.supplier?.gl_code ? `${record.supplier.gl_code} - ${record.supplier.name}` :
                                'Tidak ada Supplier' }}
                        </span>
                    </a-tag>
                </template>
            </template>
        </a-table>
    </a-card>
    <a-modal v-model:open="showModal" :title="form.id ? 'Ubah Data Pengadaan' : 'Tambah Data Pengadaan'" width="700px"
        @ok="writeData" :mask-closable="false">
        <a-form ref="form" name="basic" :label-col="{ span: 8 }" :wrapper-col="{ span: 16 }">
            <a-form-item label="Branch" data-column="branch_id" :rules="[{ required: true }]">
                <a-select v-model:value="form.branch_id" placeholder="--Pilih Branch--" show-search
                    option-label-prop="label" option-filter-prop="label">
                    <a-select-option v-for="branch in constant.BRANCH_RECEIVE_OPTIONS" :key="branch.id"
                        :value="branch.id" :label="`${branch.code} - ${branch.name}`">
                        <div class="flex items-center gap-2">
                            <a-tag color="blue">{{ branch.code }}</a-tag>
                            <span class="text-gray-700">{{ branch.name }}</span>
                        </div>
                    </a-select-option>
                </a-select>
            </a-form-item>
            <a-form-item label="Jenis Transaksi" data-column="transaction_type_code" :rules="[{ required: true }]">
                <a-select v-model:value="form.transaction_type_code" placeholder="--Pilih Jenis Transaksi--" show-search
                    option-label-prop="label" option-filter-prop="label">
                    <a-select-option v-for="trans in constant.TRANSACTION_TYPE_OPTIONS" :key="trans.code"
                        :value="trans.code" :label="`${trans.code} - ${trans.name}`">
                        <div class="flex items-center gap-2">
                            <a-tag color="blue">{{ trans.code }}</a-tag>
                            <span class="text-gray-700">{{ trans.name }}</span>
                        </div>
                    </a-select-option>
                </a-select>
            </a-form-item>
            <a-form-item v-if="form.transaction_type_code == 'M01'" label="Sumber Dana" data-column="funding_source"
                :rules="[{ required: true }]">
                <a-select v-model:value="form.funding_source" placeholder="--Pilih Sumber Dana--">
                    <a-select-option value="KAS">KAS</a-select-option>
                    <a-select-option value="BANK">BANK</a-select-option>
                </a-select>
            </a-form-item>
            <a-form-item v-if="form.transaction_type_code == 'M01'" label="Supplier" data-column="supplier_id"
                :rules="[{ required: true }]">
                <a-select v-model:value="form.supplier_id" placeholder="--Pilih Supplier--" show-search
                    :filter-option="false" allow-clear @search="onSearchSupplier">
                    <a-select-option v-for="s in supplierOptions" :key="s.id" :value="s.id" :label="s.name">
                        <div class="flex items-center gap-2">
                            <a-tag color="blue">{{ s.gl_code }}</a-tag>
                            <span class="text-gray-700">{{ s.name }}</span>
                        </div>
                    </a-select-option>
                </a-select>
            </a-form-item>
            <a-form-item label="Catatan" data-column="notes">
                <a-textarea v-model:value="form.notes" :rows="3" placeholder="Catatan" style="resize: none;" />
            </a-form-item>
        </a-form>
    </a-modal>
</template>