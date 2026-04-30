<script>

const sortDirections = ['ascend', 'descend'];

const columns = [
    {
        title: "#",
        key: "number",
        align: "center",
        width: 50,
    },
    {
        title: "Kode",
        key: "code",
        width: 180,
        ellipsis: true,
        sorter: (a, b) => a.code.localeCompare(b.code),
        sortDirections,
    },
    {
        title: "Status",
        key: "status",
        width: 120,
        align: "left",
        sorter: (a, b) => a.status.localeCompare(b.status),
        sortDirections,
    },
    {
        title: "Jenis Aset",
        key: "asset_type",
        width: 120,
        align: "center",
        sorter: (a, b) => a.asset_type.localeCompare(b.asset_type),
        sortDirections,
    },
    {
        title: "Detail Perpindahan Lokasi",
        key: "location_detail",
        align: "center",
        width: 180,
        align: "center",
    },
    {
        title: "Pengaju",
        key: "operator",
        width: 240,
        sorter: (a, b) => a.operator_id - b.operator_id,
        sortDirections,
    },
    {
        title: "PJ Tujuan",
        key: "to_pj_id",
        width: 240,
        sorter: (a, b) => a.to_pj_id - b.to_pj_id,
        sortDirections,
    },
    {
        title: "Tgl Submit",
        dataIndex: "submitted_at",
        width: 160,
        ellipsis: true,
    },
    {
        title: "Tgl Verifikasi",
        dataIndex: "verified_at",
        width: 160,
        ellipsis: true,
    },
    {
        title: "Total Item",
        key: "items_count",
        align: "center",
        width: 90,
        sorter: (a, b) => a.items_count - b.items_count,
        sortDirections,
    },
    {
        title: "Catatan",
        dataIndex: "notes",
        width: 180,
        ellipsis: true,
    },
    {
        title: "Aksi",
        key: "action",
        align: "center",
        width: 200,
        fixed: "right",
        className: "column-action",
    },
];

const selectItemColumns = [
    {
        title: "Nomor QR Code",
        key: "qr_code_no",
        width: 300,
        ellipsis: true,
        sorter: (a, b) =>
            (a.qr_code_no || "").localeCompare(b.qr_code_no || ""),
        sortDirections,
    },
    {
        title: "Deskripsi",
        dataIndex: "description",
        width: 250,
        ellipsis: true,
        sorter: (a, b) =>
            (a.description || "").localeCompare(b.description || ""),
        sortDirections,
    },
    {
        title: "Kategori",
        key: "category_name",
        width: 200,
        sorter: (a, b) =>
            (a.category_name || "").localeCompare(
                b.category_name || ""
            ),
        sortDirections,
    },
    {
        title: "Nomor Urut Aset",
        dataIndex: "asset_number",
        width: 120,
        align: "center",
        sorter: (a, b) => (a.asset_number || 0) - (b.asset_number || 0),
        sortDirections,
    },
    {
        title: "Posisi",
        key: "position",
        width: 250,
        ellipsis: true,
        sorter: (a, b) =>
            (a.position || "").localeCompare(b.position || ""),
        sortDirections,
    },
    {
        title: "Unit",
        key: "unit",
        width: 200,
        ellipsis: true,
        sorter: (a, b) =>
            (a.unit || "").localeCompare(b.unit || ""),
        sortDirections,
    },
    {
        title: "PJ Gudang",
        key: "pj",
        width: 250,
        ellipsis: true,
        sorter: (a, b) =>
            (a.pj_nik || "").localeCompare(b.pj_nik || ""),
        sortDirections,
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
            selectItemColumns,
            assetLoaded: false,
            assetModels: [],
            selectedRowKeys: [],
            form: {
                id: null,
                to_type: null,
                to_pj_id: null,
                asset_type: null,
                notes: null,
                to_location: {
                    branch_id: null,
                    warehouse_id: null
                },
                items: [],

            },
            filter: {
                status: null,
                unit: null,
                to_pj_id: null,
            },
            showPDFModal: false, // modal PDF viewer
            pdfContent: null, // HTML dari backend
            rejectModalVisible: false,
            rejectForm: {
                id: null,
                notes: '',
            },
            userOptions: [],
            roomOptions: [],
            rejectLoading: false,
            showRejectionModal: false,
            rejection_note: null,
            rejection_date: null,
            rejector: null,
            locationModalVisible: false,
            selectedMovement: null,
            isEditing: false,
            confirmModalVisible: false,
            confirmAction: null,
            confirmRecord: null,
            movementItems: null,
        };
    },

    computed: {
        breadcrumbItems() {
            return [
                { label: 'Dashboard', link: '/', icon: 'bi:grid' },
                { label: `${this.title}`, icon: 'line-md:folder-multiple-filled' }
            ]
        },
        selectedCount() {
            return this.selectedRowKeys.length;
        },

        filteredWarehouses() {
            if (!this.form.to_location.branch_id) {
                return []
            }

            return this.constant.WAREHOUSES.filter(w =>
                w.branch_id === this.form.to_location.branch_id
            )
        },

        selectedWarehouse() {
            return this.constant.WAREHOUSES.find(
                w => w.id === this.form.to_location.warehouse_id
            )
        },
    },

    watch: {
        selectedRowKeys(keys) {
            this.form.items = keys;
        },
        'form.to_location.branch_id'(val, oldVal) {
            if (this.isEditing) return;
            if (val !== oldVal) {
                this.form.to_location.warehouse_id = null
            }
        },
    },

    mounted() {
        this.readData();
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
                vm.totalData = response.data.models.total;
            }
        },

        newData() {
            const vm = this;
            Object.assign(vm.$data.form, vm.$options.data().form);
            vm.selectedRowKeys = [];
            vm.assetModels = [];
            vm._pagination.current = 1;
            vm.assetLoaded = false;

            vm.$nextTick(function () {
                vm.showModal = true;
                vm.fetchUsers();
            })
        },

        editData(m) {
            const vm = this;

            vm.isEditing = true;
            vm.form = vm.lodash.cloneDeep(m);

            vm.$nextTick(() => {
                vm.showModal = true;

                const userPromise = m.to_pj_id
                    ? vm.fetchUsers(m.to_pj_id)
                    : vm.fetchUsers();

                userPromise
                    .then(() => {
                        return vm.fetchRooms();
                    })
                    .then(() => {
                        return vm.fetchAssets();
                    })
                    .then(() => {
                        vm.selectedRowKeys = m.items ?? [];
                        vm.assetLoaded = true;
                    })
                    .finally(() => {
                        vm.isEditing = false;
                    });
            });
        },

        async fetchAssets() {
            const vm = this;
            if (!vm.form.to_location.warehouse_id || !vm.form.asset_type) return;

            vm.loadingTrue();
            vm.assetLoaded = false;
            vm.assetModels = [];
            vm.selectedRowKeys = [];

            const params = {
                asset_type: vm.form.asset_type,
                movement_id: vm.form.id
            };

            const response = await vm.axios
                .get("/lookups/iis-available-mutation-and-return-assets", { params })
                .catch((e) => vm.$onAjaxError(e));

            if (response?.data?.models) {
                vm.assetModels = response.data.models;
                vm.assetLoaded = true;
            }

            vm.loadingFalse();
        },

        async writeData() {
            const vm = this;

            vm.form.items = vm.selectedRowKeys;

            if (!vm.form.items.length) {
                vm.openNotification('Pilih minimal 1 barang', 'warning');
                return;
            }

            vm.loadingTrue();

            const payload = {
                req: "write",
                ...vm.form,
            };

            const response = await vm.axios
                .post(vm.writeRoute, payload)
                .catch((e) => vm.$onAjaxError(e));

            if (response?.data?.success) {

                if (!vm.form.id) {
                    vm.openNotification('Serah terima retur berhasil dibuat ...', 'success');
                }
                else {
                    vm.openNotification('Serah terima retur berhasil diupdate', 'success');
                }

                vm.showModal = false;
                vm.readData();
            }

            vm.loadingFalse();
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

        async openConfirm(record) {
            this.confirmModalVisible = true
            this.confirmAction = record.status === 'draft' ? 'submit' : 'verify'
            this.confirmRecord = record
            await this.fetchMovementItems(record.id)
        },

        async fetchMovementItems(id) {
            this.loadingTrue()

            const res = await this.axios.get(this.readRoute, {
                params: {
                    req: 'movement_items',
                    id
                }
            })

            if (res?.data?.models) {
                this.movementItems = res.data.models
            }

            this.loadingFalse()
        },

        async doAuthorize() {
            const vm = this

            vm.loadingTrue()

            const payload = {
                req: 'authorize',
                id: vm.confirmRecord.id,
                action: vm.confirmAction,
            }

            const response = await vm.axios
                .post(vm.writeRoute, payload)
                .catch(e => vm.$onAjaxError(e))

            if (response?.data?.success) {
                vm.openNotification(
                    vm.confirmAction === 'submit'
                        ? 'Data berhasil disubmit'
                        : 'Data berhasil diverifikasi',
                    'success'
                )

                vm.confirmModalVisible = false
                vm.readData()
            }

            vm.loadingFalse()
        },

        async openLocationModal(id) {
            this.locationModalVisible = true
            await this.fetchMovementItems(id)
            this.selectedMovement = this.movementItems?.[0] ?? null
        },

        openRejectModal(record) {
            this.rejectForm.id = record.id;
            this.rejectForm.notes = '';
            this.rejectModalVisible = true;
        },

        async submitReject() {
            const vm = this;

            if (!vm.rejectForm.notes.trim()) {
                vm.openNotification('Alasan penolakan wajib diisi', 'warning');
                return;
            }

            vm.rejectLoading = true;

            const payload = {
                req: 'authorize',
                id: vm.rejectForm.id,
                action: 'reject',
                notes: vm.rejectForm.notes,
            };

            const response = await vm.axios
                .post(vm.writeRoute, payload)
                .catch(e => vm.$onAjaxError(e));

            if (response?.data?.success) {
                vm.openNotification('Serah terima berhasil ditolak', 'success');
                vm.rejectModalVisible = false;
                vm.readData();
            }

            vm.rejectLoading = false;
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

        openPDF(id) {
            const vm = this;
            const pdfUrl = `${vm.readRoute}?req=pdf&id=${id}`;
            vm.pdfContent = pdfUrl;
            vm.showPDFModal = true;
        },

        openRejectionModal(note, date, user) {
            this.rejection_note = note;
            this.rejection_date = date;
            this.rejector = user;
            this.showRejectionModal = true;
        },
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
                        <a-select v-model:value="filter.status" placeholder="--Pilih Status--"
                            class="min-w-40 lg:w-40 w-full" allow-clear @change="readData">
                            <a-select-option value="draft">Draft</a-select-option>
                            <a-select-option value="submitted">Disubmit</a-select-option>
                            <a-select-option value="verified">Diverifikasi</a-select-option>
                            <a-select-option value="rejected">Ditolak</a-select-option>
                        </a-select>
                    </a-col>

                    <!-- Search -->
                    <a-col class="w-full md:w-auto">
                        <a-input v-model:value="filter.search" @keyup.enter="readData" placeholder="Ketikkan kode ...">
                            <template #addonAfter>
                                <span @click="readData" class="text-white text-base">
                                    <Icon icon="ant-design:search-outlined" />
                                </span>
                            </template>
                        </a-input>
                    </a-col>

                    <a-col v-if="can('iis.borrow-return.create')" class="w-full md:w-auto">
                        <a-button type="primary"
                            class="bg-gradient-to-r from-purple-400 via-blue-500 to-blue-700 hover:from-yellow-400 hover:via-yellow-600 hover:to-purple-500 flex items-center justify-center text-white font-medium border-0 shadow-md transition-all duration-300 w-full"
                            @click="newData()">
                            <Icon icon="line-md:plus" class="mr-1" />
                            Tambah Retur Peminjaman
                        </a-button>
                    </a-col>
                </a-row>
            </a-col>
        </a-row>

        <!-- Toolbar -->
        <div class="mb-2 font-medium">
            <span>Total: {{ _pagination.total }} Data</span>
        </div>

        <a-table :scroll="{ x: 800 }" :columns="columns" :data-source="models" :row-key="obj => obj.id"
            :pagination="_pagination" :loading="loadingStatus" @change="handleTableChange">
            <template #bodyCell="{ index, column, record }">
                <!-- Aksi -->
                <template v-if="column.key === 'action'">
                    <a-button-group class="flex justify-center">
                        <!-- Authorize -->
                        <a-tooltip :title="record.status === 'draft' ? 'Submit' : 'Verifikasi'" v-if="
                            // SUBMIT
                            (can('iis.borrow-return.submit')
                                && record.status === 'draft'
                                && record.operator_id == user.id)

                            ||

                            // VERIFY (hanya boleh verify milik sendiri)
                            (can('iis.borrow-return.verify')
                                && record.status === 'submitted'
                                && record.to_pj_id == user.id)
                        ">
                            <div class="px-2">
                                <a-button type="primary" size="small" class="bg-gradient-to-r from-purple-400 via-blue-500 to-blue-700
                                        hover:from-yellow-400 hover:via-yellow-600 hover:to-purple-500
                                        flex items-center justify-center text-white font-medium
                                        border-0 shadow-md transition-all duration-300" @click="openConfirm(record)">
                                    <Icon icon="streamline-color:send-email" class="mr-1" />
                                    {{ record.status === 'draft' ? 'Submit' : 'Verif' }}
                                </a-button>
                            </div>
                        </a-tooltip>

                        <!-- REJECT -->
                        <a-tooltip title="Tolak Serah Terima" v-if="
                            can('iis.borrow-return.reject')
                            && record.status === 'submitted'
                            && record.to_pj_id == user.id
                        ">
                            <div class="px-2">
                                <a-button type="primary" size="small" class="bg-gradient-to-r from-purple-400 via-red-500 to-red-700
                                    hover:from-yellow-400 hover:via-yellow-600 hover:to-purple-500
                                    flex items-center justify-center text-white font-medium
                                    border-0 shadow-md transition-all duration-300" @click="openRejectModal(record)">
                                    <Icon icon="line-md:close-circle" class="mr-1" />
                                    Tolak
                                </a-button>
                            </div>
                        </a-tooltip>

                        <!-- Edit -->
                        <a-tooltip title="Edit Data"
                            v-if="can('iis.borrow-return.update') && record.status === 'draft'">
                            <a-button size="small" type="text" @click="editData(record)" :style="{ padding: '0 5px' }">
                                <Icon icon="line-md:pencil-twotone"
                                    class="flex justify-center text-green-500 text-[24px]" />
                            </a-button>
                        </a-tooltip>

                        <!-- Detail PDF -->
                        <a-tooltip title="Lihat PDF">
                            <a-button size="small" type="text" @click="openPDF(record.id)"
                                :style="{ padding: '0 5px' }">
                                <Icon icon="material-icon-theme:pdf"
                                    class="flex justify-center text-blue-500 text-[24px]" />
                            </a-button>
                        </a-tooltip>

                        <!-- Hapus -->
                        <a-tooltip title="Hapus Data"
                            v-if="can('iis.borrow-return.delete') && record.status === 'draft'">
                            <a-popconfirm title="Yakin menghapus data?" @confirm="deleteData(record.id, 'delete')">
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
                <template v-if="column.key === 'code'">
                    <a-tag color="#2db7f5">
                        <span class="text-sm">{{ record.code }}</span>
                    </a-tag>
                </template>
                <template v-if="column.key === 'pj'">
                    <span>
                        {{ record.pj?.identifier }} - {{ record.pj?.name }}
                    </span>
                </template>
                <template v-if="column.key === 'operator'">
                    <a-tag color="purple">
                        {{ record.operator?.identifier }} - {{ record.operator?.name }}
                    </a-tag>
                </template>
                <template v-if="column.key === 'to_pj_id'">
                    <a-tag color="red">
                        {{ record.to_pj?.identifier }} - {{ record.to_pj?.name }}
                    </a-tag>
                </template>
                <template v-if="column.key === 'asset_type'">
                    <a-tag :color="record.asset_type === 'inventory' ? 'blue'
                        : record.asset_type === 'alkes' ? 'red'
                            : 'default'">
                        <span class="text-sm">
                            {{ record.asset_type === 'inventory'
                                ? 'Inventaris'
                                : record.asset_type === 'alkes'
                                    ? 'Alkes'
                                    : '-' }}
                        </span>
                    </a-tag>
                </template>

                <template v-if="column.key === 'location_detail'">
                    <div class="flex justify-center">
                        <a-tooltip title="Detail perpindahan lokasi">
                            <a-button size="small" ghost
                                class="flex items-center gap-x-1 !border-blue-500 !text-blue-600 hover:!bg-blue-50"
                                @click="openLocationModal(record.id)">
                                <Icon icon="fluent-color:apps-list-detail-32" class="text-lg" />
                                Lihat
                            </a-button>
                        </a-tooltip>
                    </div>
                </template>

                <template v-if="column.key === 'items_count'">
                    <a-tag color="cyan" class="font-semibold">
                        {{ record.items_count }} item
                    </a-tag>
                </template>
                <template v-if="column.key === 'status'">
                    <a-tag v-if="record.status === 'draft'" color="orange">
                        DRAFT
                    </a-tag>
                    <a-tag v-else-if="record.status === 'submitted'" color="blue">
                        DISUBMIT
                    </a-tag>
                    <a-tag v-else-if="record.status === 'verified'" color="green">
                        DIVERIFIKASI
                    </a-tag>
                    <span v-else-if="record.status === 'rejected'" class="flex items-center">
                        <a-tag color="red">
                            DITOLAK
                        </a-tag>
                        <a-tooltip title="Lihat Detail">
                            <a-button size="small" type="text"
                                @click="openRejectionModal(record.rejection_note, record.rejected_at, record.rejector)"
                                :style="{ padding: '0 5px' }">
                                <Icon icon="line-md:file-search-twotone"
                                    class="flex justify-center text-blue-500 text-[24px]" />
                            </a-button>
                        </a-tooltip>
                    </span>
                    <a-tag v-else color="red">
                        {{ record.status }}
                    </a-tag>
                </template>
                <template v-if="column.key === 'verified_by'">
                    <a-tag color="red">
                        {{ record.verificator?.name }}
                    </a-tag>
                </template>
            </template>
        </a-table>
    </a-card>

    <!-- ================= MODAL NEW / UPDATE ================= -->
    <a-modal v-model:open="showModal" :title="form.id ? 'Ubah Data Retur' : 'Tambah Data Retur'" width="1200px"
        @ok="writeData" :mask-closable="false" :destroy-on-close="true" :style="{ top: '30px' }">
        <a-form layout="vertical">
            <a-row :gutter="16">

                <a-col :xs="24" :md="12">
                    <a-form-item label="Jenis Barang" required>
                        <a-select v-model:value="form.asset_type" @change="fetchAssets" allow-clear
                            placeholder="--Pilih Tipe--">
                            <a-select-option value="inventory">
                                Inventaris
                            </a-select-option>
                            <a-select-option value="alkes" required>
                                Alat kesehatan (ALKES)
                            </a-select-option>
                        </a-select>
                    </a-form-item>
                </a-col>

                <a-col :xs="24" :md="12">
                    <a-form-item label="Catatan Retur">
                        <a-textarea v-model:value="form.notes" :rows="1" />
                    </a-form-item>
                </a-col>

                <!-- ================= LOKASI TUJUAN GUDANG ================= -->
                <div class="px-2 w-full">
                    <div class="mb-4 py-2 px-4 rounded-xl border bg-blue-50">
                        <h3 class="font-semibold text-slate-700 mb-3">Lokasi Gudang Tujuan</h3>
                        <div class="grid lg:grid-cols-2 lg:gap-4">

                            <a-form-item label="Cabang Tujuan" data-column="to_location.branch_id"
                                :rules="[{ required: true }]">
                                <a-select v-model:value="form.to_location.branch_id" placeholder="--Pilih Cabang--"
                                    option-label-prop="label" option-filter-prop="label" show-search allow-clear>
                                    <a-select-option v-for="u in constant.BRANCHES" :key="u.id" :value="u.id"
                                        :label="`${u.name}`">
                                        <div class="flex items-center gap-2">
                                            <a-tag color="blue">{{ u.name }}</a-tag>
                                        </div>
                                    </a-select-option>
                                </a-select>
                            </a-form-item>

                            <a-form-item label="Pilih Gudang" data-column="to_location.warehouse_id"
                                :rules="[{ required: true }]">
                                <a-select v-model:value="form.to_location.warehouse_id" placeholder="--Pilih Gudang--"
                                    allow-clear show-search option-label-prop="display" option-filter-prop="label"
                                    class="w-full lg:w-96" @change="fetchAssets">
                                    <a-select-option v-for="w in filteredWarehouses" :key="w.id" :value="w.id"
                                        :label="`${w.code} - ${w.name} - ${w.branch?.name || ''}`"
                                        :display="`${w.code} - ${w.name} - ${w.branch?.name || '-'}`">
                                        <div class="flex items-start gap-3">
                                            <Icon icon="streamline-plump-color:warehouse-1"
                                                class="text-amber-500 text-xl mt-0.5" />
                                            <div class="flex flex-col leading-tight">
                                                <span class="font-medium text-slate-800">
                                                    {{ w.code }} - {{ w.name }} - {{ w.branch?.name || '-' }}
                                                </span>
                                                <span class="text-xs text-gray-500">
                                                    PJ :
                                                    {{ w.person_in_charge?.identifier || '-' }}
                                                    - {{ w.person_in_charge?.name || '-' }}
                                                    <span v-if="w.person_in_charge?.position">
                                                        • {{ w.person_in_charge.position }}
                                                    </span>
                                                </span>
                                            </div>
                                        </div>
                                    </a-select-option>
                                </a-select>
                            </a-form-item>
                        </div>
                    </div>
                </div>
            </a-row>

            <a-alert v-if="assetLoaded" type="info" show-icon class="mb-2">
                <template #message>
                    <span class="font-semibold">
                        {{ selectedCount }} barang dipilih (Keterangan: Barang yg muncul dibawah hanya barang yang sudah
                        dicetak QR Code nya)
                    </span>
                </template>
            </a-alert>

            <!-- TABEL PILIH BARANG -->
            <a-table v-if="assetLoaded" :columns="selectItemColumns" :data-source="assetModels" :row-key="r => r.id"
                :pagination="{ pageSize: 30, showSizeChanger: true }" :loading="loadingStatus"
                :scroll="{ x: 2000, y: 350 }" :row-selection="{
                    selectedRowKeys,
                    preserveSelectedRowKeys: true,
                    onChange: keys => selectedRowKeys = keys
                }">
                <template #bodyCell="{ column, record }">
                    <template v-if="column.key === 'qr_code_no'">
                        <a-tag color="#2db7f5">
                            <span class="text-sm">{{ record.qr_code_no }}</span>
                        </a-tag>
                    </template>

                    <template v-if="column.key === 'pj'">
                        <a-tag color="cyan">
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

                    <template v-if="column.key === 'position'">
                        <a-tag color="#8C5AE6">
                            <span class="text-sm">
                                <span>{{ positionText(record) }}</span>
                            </span>
                        </a-tag>
                    </template>

                    <template v-if="column.key === 'unit'">
                        <a-tag color="#108ee9">
                            <span class="text-sm">
                                <span>{{ unitText(record) }}</span>
                            </span>
                        </a-tag>
                    </template>

                </template>
            </a-table>
        </a-form>
    </a-modal>

    <!-- Modal Detail Perpindahan Lokasi -->
    <a-modal v-model:open="locationModalVisible" title="Detail Perpindahan Lokasi" :footer="null" width="720px">
        <div class="bg-slate-50 rounded-2xl p-5 space-y-5">

            <!-- HEADER -->
            <div
                class="flex items-center gap-4 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl p-4 border border-blue-100">
                <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-blue-500 text-white shadow">
                    <Icon icon="mdi:map-marker-path" class="text-2xl" />
                </div>

                <div class="flex-1">
                    <div class="text-xs text-blue-600 font-medium uppercase tracking-wide">
                        Retur Barang
                    </div>
                    <div class="text-lg font-semibold text-slate-800">
                        Detail Perpindahan Lokasi
                    </div>
                </div>

                <a-tag color="blue" class="font-medium">
                    Snapshot
                </a-tag>
            </div>

            <!-- FLOW -->
            <div class="flex items-center justify-center gap-3">
                <a-tag color="blue" class="flex items-center gap-1 px-3 py-1">
                    <Icon icon="mdi:warehouse" />
                    Lokasi Asal
                </a-tag>

                <Icon icon="mdi:arrow-right-thick" class="text-slate-400 text-lg" />

                <a-tag color="green" class="flex items-center gap-1 px-3 py-1">
                    <Icon icon="mdi:map-marker-check-outline" />
                    Lokasi Tujuan
                </a-tag>
            </div>

            <!-- CONTENT -->
            <a-row :gutter="[16, 16]">

                <!-- ASAL -->
                <a-col :span="12">
                    <div class="relative bg-white rounded-xl p-4 shadow-sm border border-blue-100 h-full">

                        <!-- Accent bar -->
                        <div class="absolute left-0 top-0 h-full w-1 bg-blue-500 rounded-l-xl"></div>

                        <div class="flex items-center gap-2 mb-4 pl-2">
                            <Icon icon="mdi:warehouse" class="text-blue-500 text-xl" />
                            <span class="font-semibold text-slate-800">
                                Lokasi Asal
                            </span>
                        </div>

                        <div class="space-y-3 text-sm pl-2">

                            <div class="flex items-start gap-3">
                                <Icon icon="mdi:source-branch" class="text-green-500 mt-1" />
                                <div>
                                    <div class="text-xs text-slate-500">Cabang</div>
                                    <div class="font-medium">
                                        {{ selectedMovement?.from_location?.branch_name ?? '-' }}
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <Icon icon="mdi:office-building-outline" class="text-green-500 mt-1" />
                                <div>
                                    <div class="text-xs text-slate-500">Gedung</div>
                                    <div class="font-medium">
                                        {{ selectedMovement?.from_location?.building_name ?? '-' }}
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div class="flex items-start gap-3">
                                    <Icon icon="mdi:stairs" class="text-green-500 mt-1" />
                                    <div>
                                        <div class="text-xs text-slate-500">Lantai</div>
                                        <div class="font-medium">
                                            {{ selectedMovement?.from_location?.floor ?? '-' }}
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-start gap-3">
                                    <Icon icon="mdi:domain" class="text-green-500 mt-1" />
                                    <div>
                                        <div class="text-xs text-slate-500">Unit</div>
                                        <div class="font-medium">
                                            {{ selectedMovement?.from_location?.unit_name ?? '-' }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <Icon icon="mdi:door" class="text-green-500 mt-1" />
                                <div>
                                    <div class="text-xs text-slate-500">Ruangan</div>
                                    <div class="font-medium">
                                        {{ selectedMovement?.from_location?.room_name ?? '-' }}
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </a-col>

                <!-- TUJUAN -->
                <a-col :span="12">
                    <div class="relative bg-white rounded-xl p-4 shadow-sm border border-green-100 h-full">

                        <!-- Accent bar -->
                        <div class="absolute left-0 top-0 h-full w-1 bg-green-500 rounded-l-xl"></div>

                        <div class="flex items-center gap-2 mb-4 pl-2">
                            <Icon icon="mdi:map-marker-check-outline" class="text-green-600 text-xl" />
                            <span class="font-semibold text-slate-800">
                                Lokasi Tujuan
                            </span>
                        </div>

                        <div class="space-y-3 text-sm pl-2">

                            <div class="flex items-start gap-3">
                                <Icon icon="mdi:source-branch" class="text-green-500 mt-1" />
                                <div>
                                    <div class="text-xs text-slate-500">Cabang</div>
                                    <div class="font-medium">
                                        {{ selectedMovement?.to_location?.branch_name ?? '-' }}
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <Icon icon="mdi:office-building-outline" class="text-green-500 mt-1" />
                                <div>
                                    <div class="text-xs text-slate-500">Gedung</div>
                                    <div class="font-medium">
                                        {{ selectedMovement?.to_location?.building_name ?? '-' }}
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div class="flex items-start gap-3">
                                    <Icon icon="mdi:stairs" class="text-green-500 mt-1" />
                                    <div>
                                        <div class="text-xs text-slate-500">Lantai</div>
                                        <div class="font-medium">
                                            {{ selectedMovement?.to_location?.floor ?? '-' }}
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-start gap-3">
                                    <Icon icon="mdi:domain" class="text-green-500 mt-1" />
                                    <div>
                                        <div class="text-xs text-slate-500">Unit</div>
                                        <div class="font-medium">
                                            {{ selectedMovement?.to_location?.unit_name ?? '-' }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-start gap-3">
                                <Icon icon="mdi:door" class="text-green-500 mt-1" />
                                <div>
                                    <div class="text-xs text-slate-500">Ruangan</div>
                                    <div class="font-medium">
                                        {{ selectedMovement?.to_location?.room_name ?? '-' }}
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </a-col>

            </a-row>

            <!-- FOOTER -->
            <div class="flex justify-end pt-2">
                <a-button type="primary" ghost class="!border-slate-300 !text-slate-600"
                    @click="locationModalVisible = false">
                    Tutup
                </a-button>
            </div>

        </div>
    </a-modal>

    <!-- Modal Preview Item Movement -->
    <a-modal v-model:open="confirmModalVisible" :title="confirmAction === 'submit'
        ? 'Konfirmasi Submit Retur'
        : 'Konfirmasi Verifikasi Retur'" width="820px" :mask-closable="false">
        <div class="bg-slate-50 rounded-2xl p-5 space-y-5">

            <!-- HEADER -->
            <div
                class="flex items-center gap-4 bg-gradient-to-r from-purple-50 to-indigo-50 rounded-xl p-4 border border-purple-100">
                <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-purple-500 text-white shadow">
                    <Icon icon="mdi:clipboard-text-outline" class="text-2xl" />
                </div>

                <div class="flex-1">
                    <div class="text-xs text-purple-600 font-medium uppercase tracking-wide">
                        Retur Barang
                    </div>
                    <div class="text-lg font-semibold text-slate-800">
                        {{ confirmAction === 'submit'
                            ? 'Konfirmasi Submit Retur'
                            : 'Konfirmasi Verifikasi Retur' }}
                    </div>
                    <div class="text-xs text-slate-500 mt-1">
                        Kode: <span class="font-medium">{{ confirmRecord?.code }}</span>
                    </div>
                </div>

                <a-tag :color="confirmAction === 'submit' ? 'orange' : 'green'" class="font-medium">
                    {{ confirmAction === 'submit' ? 'Submit' : 'Verifikasi' }}
                </a-tag>
            </div>

            <!-- INFO PJ -->
            <div class="flex items-center gap-3 bg-white rounded-xl p-4 border border-slate-200">
                <Icon icon="mdi:account-circle-outline" class="text-slate-400 text-xl" />
                <div>
                    <div class="text-xs text-slate-500">Penerima / PJ</div>
                    <div class="font-medium text-slate-800">
                        {{ confirmRecord?.to_pj?.name ?? '-' }}
                    </div>
                </div>
            </div>

            <!-- ITEMS LIST -->
            <div class="space-y-4">

                <div v-for="(item, index) in movementItems" :key="item.id"
                    class="relative bg-white rounded-xl p-4 shadow-sm border border-slate-200">
                    <!-- Accent bar -->
                    <div class="absolute left-0 top-0 h-full w-1 bg-indigo-500 rounded-l-xl"></div>

                    <div class="flex items-start gap-4 pl-2">

                        <!-- ICON -->
                        <div
                            class="flex items-center justify-center w-10 h-10 rounded-lg bg-indigo-100 text-indigo-600">
                            <Icon icon="mdi:barcode-scan" class="text-xl" />
                        </div>

                        <!-- CONTENT -->
                        <div class="flex-1 space-y-2">

                            <div class="flex items-center justify-between">
                                <div class="font-semibold text-slate-800">
                                    {{ item.qr_code_no }}
                                </div>

                                <a-tag color="blue">
                                    #{{ index + 1 }}
                                </a-tag>
                            </div>

                            <div class="text-sm text-slate-600">
                                {{ item.description || '-' }}
                            </div>

                            <div class="flex items-center gap-2 text-sm">
                                <Icon icon="mdi:tools" class="text-slate-400" />
                                <span class="text-slate-500">Kondisi:</span>
                                <a-tag :color="item.condition?.toLowerCase().includes('rusak')
                                    ? 'red'
                                    : 'green'">
                                    {{ item.condition }}
                                </a-tag>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

        </div>

        <template #footer>
            <div class="flex justify-end gap-x-2">
                <a-button type="primary"
                    class="bg-gradient-to-r from-purple-400 via-red-500 to-red-700 hover:from-yellow-400 hover:via-yellow-600 hover:to-purple-500 flex items-center justify-center text-white font-medium border-0 shadow-md transition-all duration-300"
                    @click="confirmModalVisible = false">
                    Tutup
                </a-button>
                <a-button type="primary" class="bg-gradient-to-r from-purple-400 via-blue-500 to-blue-700
                    hover:from-yellow-400 hover:via-yellow-600 hover:to-purple-500
                    flex items-center justify-center text-white font-medium
                    border-0 shadow-md transition-all duration-300" @click="doAuthorize">
                    {{ confirmAction === 'submit' ? 'Submit' : 'Verifikasi' }}
                </a-button>
            </div>
        </template>
    </a-modal>

    <!-- Modal Preview PDF -->
    <a-modal v-model:open="showPDFModal" title="Berkas Digital Serah Terima Retur Barang" width="900px"
        :mask-closable="false">
        <iframe v-if="pdfContent" :src="pdfContent" width="100%" height="600px"></iframe>
        <template #footer>
            <div class="flex justify-end gap-2">
                <a-button type="default" @click="showPDFModal = false">
                    Tutup
                </a-button>
            </div>
        </template>
    </a-modal>

    <!-- Modal Rejection -->
    <a-modal v-model:open="rejectModalVisible" title="Tolak Serah Terima" ok-text="Tolak" ok-type="danger"
        cancel-text="Batal" :confirm-loading="rejectLoading" @ok="submitReject">
        <a-form layout="vertical">
            <a-form-item label="Alasan Penolakan" required>
                <a-textarea v-model:value="rejectForm.notes" rows="4" placeholder="Masukkan alasan penolakan..." />
            </a-form-item>
        </a-form>
    </a-modal>

    <!-- Modal Rejection Data -->
    <a-modal v-model:open="showRejectionModal" title="Alasan Penolakan Retur" width="500px" :mask-closable="false">

        <!-- ALERT -->
        <a-alert type="error" show-icon class="mb-4">
            <template #message>
                Serah terima retur ditolak
            </template>

            <template #description>
                <div>
                    <strong>Catatan:</strong>
                    <span class="ml-1 text-red-600 font-medium">
                        {{ rejection_note || 'Tidak ada catatan penolakan' }}
                    </span>
                </div>
            </template>
        </a-alert>

        <!-- INFO CARD -->
        <div class="bg-gray-50 rounded-lg p-4 space-y-4">
            <div class="grid grid-cols-2 gap-3">

                <!-- Tanggal Ditolak -->
                <div class="bg-white rounded-md p-3 shadow-sm text-center">
                    <div class="text-xs text-gray-500">Tanggal Ditolak</div>
                    <div class="text-sm font-semibold text-red-600">{{ rejection_date || '-' }}</div>
                </div>

                <!-- Oleh / Rejector -->
                <div class="bg-white rounded-md p-3 shadow-sm text-center">
                    <div class="text-xs text-gray-500">Oleh</div>
                    <div class="text-sm font-semibold text-blue-600 flex flex-col gap-1 justify-center items-center">
                        <div class="flex items-center">
                            <a-tag color="blue" class="text-xs font-medium">{{ rejector.identifier }}</a-tag>
                            <span>{{ rejector.name }}</span>
                        </div>
                        <div class="text-gray-500 text-xs">{{ rejector.position || '-' }}</div>
                    </div>
                </div>

            </div>
        </div>

        <!-- FOOTER -->
        <template #footer>
            <a-button @click="showRejectionModal = false">Tutup</a-button>
        </template>
    </a-modal>
</template>
