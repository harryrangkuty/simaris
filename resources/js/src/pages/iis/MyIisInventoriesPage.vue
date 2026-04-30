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
        title: "Nomor QR Code",
        key: "qr_code_no",
        width: 200,
        ellipsis: true,
        sorter: (a, b) => (a.qr_code_no || '').localeCompare(b.qr_code_no || ''),
        sortDirections,
    },
    {
        title: "Status Serah Terima QR",
        key: "is_handed_over",
        align: "center",
        width: 220,
        sorter: (a, b) => (a.is_handed_over || 0) - (b.is_handed_over || 0),
        sortDirections,
    },
    {
        title: "Deskripsi",
        dataIndex: "description",
        width: 235,
        ellipsis: true,
        sorter: (a, b) => (a.description || '').localeCompare(b.description || ''),
        sortDirections,
    },
    {
        title: "Item",
        key: "item_code",
        width: 335,
        align: "left",
        sorter: (a, b) => (a.item_code || '').localeCompare(b.item_code || ''),
        sortDirections,
    },
    {
        title: "Posisi Barang",
        key: "position",
        width: 300,
        ellipsis: true,
        sorter: (a, b) => (a.position || '').localeCompare(b.position || ''),
        sortDirections,
    },
    {
        title: "Kategori IIS",
        key: "category_name",
        width: 180,
        sorter: (a, b) => (a.category_name || '').localeCompare(b.category_name || ''),
        sortDirections,
    },
    {
        title: "Gedung",
        key: "building_id",
        width: 140,
        sorter: (a, b) => (a.building_id ?? 0) - (b.building_id ?? 0),
        sortDirections,
    },
    {
        title: "Lantai",
        key: "floor",
        width: 120,
        align: "center",
        sorter: (a, b) => (a.floor || '').localeCompare(b.floor || ''),
        sortDirections,
    },
    {
        title: "Unit",
        key: "unit",
        width: 170,
        ellipsis: true,
        sorter: (a, b) => (a.unit || '').localeCompare(b.unit || ''),
        sortDirections,
    },
    {
        title: "Ruang",
        key: "room",
        width: 200,
        ellipsis: true,
        sorter: (a, b) => (a.room || '').localeCompare(b.room || ''),
        sortDirections,
    },
    {
        title: "Kondisi",
        dataIndex: "condition",
        width: 130,
        align: "center",
        sorter: (a, b) => (a.condition || '').localeCompare(b.condition || ''),
        sortDirections,
    },
    {
        title: "Status Print QRCode",
        key: "status_print",
        width: 200,
        sorter: (a, b) => (a.print_count || '').localeCompare(b.print_count || ''),
        sortDirections,
    },
    {
        title: "Status Perpindahan Terakhir",
        key: "latest_movement_status",
        width: 200,
        sorter: (a, b) => (a.latest_movement_status || '').localeCompare(b.latest_movement_status || ''),
        sortDirections,
    },
    {
        title: "Aksi",
        key: "action",
        align: "center",
        width: 150,
        fixed: "right",
        className: "column-action",
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
            filter: {
                status: "active",
                handed_over: null,
                printed: null,
                movement_status: null,
            },
        };
    },

    computed: {
        breadcrumbItems() {
            return [
                { label: 'Dashboard', link: '/', icon: 'bi:grid' },
                { label: `${this.title}`, icon: 'line-md:folder-multiple-filled' }
            ]
        }
    },

    mounted() {
        this.readData();
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
        positionText(record) {
            return [
                record.building?.name,
                record.floor ? `Lantai ${record.floor}` : null,
                record.unit?.name || record.unit_legacy,
                record.room?.name || record.room_legacy,
            ]
                .filter(v => v && v !== '')
                .join(' / ');
        },

        unitText(record) {
            return record.unit?.name || record.unit_legacy;
        },

        roomText(record) {
            return record.room?.name || record.room_legacy;
        },

        getStatusLabel(status) {
            switch (status) {
                case 'draft': return 'DRAFT'
                case 'submit': return 'Sedang Proses Submit'
                case 'approve': return 'Telah disetujui atasan'
                case 'verified': return 'Telah diverifikasi PJ Tujuan'
                case 'rejected': return 'Ditolak PJ Tujuan'
                default: return status || 'Belum ada Riwayat'
            }
        },

        getTypeLabel(type) {
            switch (type) {
                case 'mutation': return 'Mutasi'
                case 'distribution': return 'Distribusi'
                case 'borrow': return 'Peminjaman'
                case 'borrow_return': return 'Pengembalian Peminjaman'
                case 'return': return 'Retur Gudang'
                default: return type || '-'
            }
        },

        getStatusColor(status) {
            switch (status) {
                case 'verified': return 'green'
                case 'approve': return 'orange'
                default: return 'red'
            }
        }
    },
};
</script>

<template>
    <Breadcrumb :items="breadcrumbItems" :showBackButton="true" />
    <a-card>
        <!-- Header -->
        <a-row class="flex flex-wrap items-start justify-between mb-4 pb-4 border-b-2 gap-y-4">
            <a-col :xs="24" :sm="24" :md="6">
                <h1 class="text-base font-semibold">{{ title }}</h1>
            </a-col>
            <a-col :xs="24" :sm="24" :md="18" class="flex justify-end">
                <a-row class="flex flex-wrap gap-2 justify-start md:justify-end w-full md:w-auto">

                    <!-- Status -->
                    <a-col class="w-full md:w-auto">
                        <a-select v-model:value="filter.status" class="min-w-32 lg:w-32 w-full" @change="readData">
                            <a-select-option value="active">Aktif Guna</a-select-option>
                            <a-select-option value="inactive">Henti Guna</a-select-option>
                        </a-select>
                    </a-col>

                    <!-- Status Movement -->
                    <a-col class="w-full md:w-auto">
                        <a-select v-model:value="filter.movement_status" placeholder="--Pilih Status Movement--"
                            class="min-w-52 lg:w-52 w-full" @change="readData">
                            <a-select-option value="draft">DRAFT</a-select-option>
                            <a-select-option value="submitted">DISUBMIT</a-select-option>
                            <a-select-option value="approved">DISETUJUI ATASAN</a-select-option>
                            <a-select-option value="verified">DIVERIFIKASI</a-select-option>
                        </a-select>
                    </a-col>

                    <!-- Serah Terima -->
                    <a-col class="w-full md:w-auto">
                        <a-select v-model:value="filter.handed_over" placeholder="Serah Terima"
                            class="min-w-48 lg:w-48 w-full" @change="readData" allow-clear>
                            <a-select-option value="n">Belum Serah Terima</a-select-option>
                            <a-select-option value="y">Sudah Serah Terima</a-select-option>
                        </a-select>
                    </a-col>

                    <!-- Status Print -->
                    <a-col class="w-full md:w-auto">
                        <a-select v-model:value="filter.printed" placeholder="Status Barcode"
                            class="min-w-48 lg:w-48 w-full" @change="readData" allow-clear>
                            <a-select-option value="n">Belum Dibarcode</a-select-option>
                            <a-select-option value="y">Sudah Dibarcode</a-select-option>
                        </a-select>
                    </a-col>

                    <!-- Search -->
                    <a-col class="w-full md:w-72">
                        <a-input v-model:value="filter.search" @keyup.enter="readData"
                            placeholder="Ketikkan deskripsi inventaris ...">
                            <template #addonAfter>
                                <span @click="readData" class="text-white text-base">
                                    <Icon icon="ant-design:search-outlined" />
                                </span>
                            </template>
                        </a-input>
                    </a-col>
                </a-row>
            </a-col>
        </a-row>

        <div class="mb-2 font-medium">
            <span>Total Inventarisku (IIS): {{ _pagination.total }} Data</span>
        </div>

        <!-- Table -->
        <a-table :scroll="{ x: 800 }" :columns="columns" :row-key="(obj) => obj.qr_code_no" :pagination="_pagination"
            :loading="loadingStatus" :data-source="models" @change="handleTableChange">
            <template #bodyCell="{ index, column, record }">

                <!-- Aksi -->
                <template v-if="column.key === 'action'">
                    <a-button-group class="flex justify-center">

                        <!-- Detail -->
                        <a-tooltip title="Lihat Detail">
                            <a :href="`${route}?req=open&code=${record.encrypt_code}`">
                                <a-button size="small" type="text" :style="{ padding: '0 5px' }">
                                    <Icon icon="line-md:file-search-twotone"
                                        class="flex justify-center text-blue-500 text-[24px]" />
                                </a-button>
                            </a>
                        </a-tooltip>
                    </a-button-group>
                </template>

                <template v-if="column.key === 'number'">
                    {{ (_pagination.current - 1) * _pagination.pageSize + (index + 1) }}
                </template>

                <template v-if="column.key === 'qr_code_no'">
                    <a-tag color="#2db7f5">
                        <span class="text-sm">{{ record.qr_code_no }}</span>
                    </a-tag>
                </template>

                <template v-if="column.key === 'item_code'">
                    <a-tag color="blue">
                        <span class="text-sm">{{ record.item?.code }} - {{ record.item?.name }}</span>
                    </a-tag>
                </template>

                <template v-if="column.key === 'pj'">
                    <a-tag color="blue">
                        <span class="text-sm">
                            {{ record.b_user?.identifier }} - {{ record.b_user?.name }}
                        </span>
                    </a-tag>
                </template>

                <template v-if="column.key === 'category_name'">
                    <a-tag v-if="record.category_name" color="pink">
                        <span class="text-sm">{{ record.category_name }}</span>
                    </a-tag>
                    <span v-else>-</span>
                </template>

                <template v-if="column.key === 'is_deactivated'">
                    <a-tag v-if="record.is_deactivated" color="red">
                        <span class="text-sm">Tidak Aktif</span>
                    </a-tag>
                    <a-tag v-else color="green">
                        <span class="text-sm">Aktif</span>
                    </a-tag>
                </template>

                <template v-if="column.key === 'is_handed_over'">
                    <a-tag v-if="record.is_handed_over" color="green">
                        <span class="text-sm">Sudah Serah Terima</span>
                    </a-tag>
                    <a-tag v-else color="red">
                        <span class="text-sm">Belum Serah Terima</span>
                    </a-tag>
                </template>

                <template v-if="column.key === 'position'">
                    <a-tag color="#8C5AE6">
                        <span class="text-sm">
                            <span>{{ positionText(record) }}</span>
                        </span>
                    </a-tag>
                </template>

                <template v-if="column.key === 'building_id'">
                    <a-tag color="#108ee9">
                        <span class="text-sm">
                            <span>{{ record.building?.name }}</span>
                        </span>
                    </a-tag>
                </template>

                <template v-if="column.key === 'floor'">
                    <a-tag color="cyan">
                        <span>
                            {{ record.floor ? `Lantai ${record.floor}` : '-' }}
                        </span>
                    </a-tag>
                </template>

                <template v-if="column.key === 'unit'">
                    <a-tag color="blue">
                        <span class="text-sm">
                            <span>{{ unitText(record) }}</span>
                        </span>
                    </a-tag>
                </template>

                <template v-if="column.key === 'room'">
                    <a-tag color="blue">
                        <span class="text-sm">
                            <span>{{ roomText(record) }}</span>
                        </span>
                    </a-tag>
                </template>

                <template v-if="column.key === 'status_print'">
                    <div v-if="record.print_count > 0" class="flex items-center justify-center gap-2">
                        <Icon icon="line-md:circle-to-confirm-circle-transition" class="text-green-500 text-lg" />
                        <a-tag color="green" class="font-semibold">
                            {{ record.print_count }}x
                        </a-tag>
                        <a-tooltip
                            :title="`Terakhir dicetak ${record.last_print_at} oleh ${record.last_print_by?.name ?? '-'}`">
                            <span class="text-sm text-gray-700 truncate max-w-[120px]">
                                {{ record.last_print_by?.name ?? 'System' }}
                            </span>
                        </a-tooltip>
                    </div>

                    <div v-else class="flex items-center justify-center gap-2 text-gray-400">
                        <Icon icon="line-md:close-circle" class="text-red-400 text-lg" />
                        <span class="italic text-sm">
                            Belum pernah dicetak
                        </span>
                    </div>
                </template>

                <template v-if="column.key === 'latest_movement_status'">

                    <a-tag v-if="record.latest_movement_status" :color="getStatusColor(record.latest_movement_status)">
                        <span class="text-sm">
                            {{ getStatusLabel(record.latest_movement_status) }} - {{
                                getTypeLabel(record.latest_movement_type) }}
                        </span>
                    </a-tag>
                    <a-tag v-else color="red">
                        <span class="text-sm">Belum ada Riwayat</span>
                    </a-tag>
                </template>

            </template>
        </a-table>
    </a-card>
</template>