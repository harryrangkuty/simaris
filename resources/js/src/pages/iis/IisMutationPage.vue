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
        title: "Pengaju/ PJ Lama",
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
        title: "Atasan PJ Lama",
        key: "approver_id",
        width: 240,
    },
    {
        title: "Tgl Submit",
        dataIndex: "submitted_at",
        width: 160,
        ellipsis: true,
    },
    {
        title: "Tgl Verifikasi Atasan",
        dataIndex: "approved_at",
        width: 160,
        ellipsis: true,
    },
    {
        title: "Tgl Verifikasi PJ Baru",
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
                to_pj_id: null,
                approver_id: null,
                asset_type: null,
                notes: null,
                to_location: {
                    branch_id: null,
                    building_id: null,
                    floor: null,
                    unit_id: null,
                    room_id: null,
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
            leaderOptions: [],
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
            pdfLoading: false,
            pdfContent: null,
            pdfTimeout: null,
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
        modalTitle() {
            switch (this.confirmAction) {
                case 'submit':
                    return 'Konfirmasi Submit Mutasi'
                case 'approve':
                    return 'Konfirmasi Approval Mutasi'
                case 'verify':
                    return 'Konfirmasi Verifikasi Mutasi'
                default:
                    return 'Konfirmasi'
            }
        },
        headerText() {
            switch (this.confirmAction) {
                case 'submit':
                    return 'Konfirmasi Submit Mutasi'
                case 'approve':
                    return 'Konfirmasi Approval Mutasi'
                case 'verify':
                    return 'Konfirmasi Verifikasi Mutasi'
                default:
                    return 'Konfirmasi'
            }
        },
        actionLabel() {
            switch (this.confirmAction) {
                case 'submit': return 'Submit'
                case 'approve': return 'Approve'
                case 'verify': return 'Verifikasi'
                default: return ''
            }
        },
        actionColor() {
            switch (this.confirmAction) {
                case 'submit': return 'orange'
                case 'approve': return 'blue'
                case 'verify': return 'green'
                default: return 'default'
            }
        },
        headerIcon() {
            switch (this.confirmAction) {
                case 'submit': return 'mdi:send'
                case 'approve': return 'mdi:check-circle-outline'
                case 'verify': return 'mdi:shield-check-outline'
                default: return 'mdi:clipboard-text-outline'
            }
        },
        headerIconBg() {
            switch (this.confirmAction) {
                case 'submit': return 'bg-orange-500'
                case 'approve': return 'bg-blue-500'
                case 'verify': return 'bg-green-500'
                default: return 'bg-purple-500'
            }
        },
        filteredWarehouses() {
            if (!this.form.from_location.branch_id) {
                return []
            }

            return this.constant.WAREHOUSES.filter(w =>
                w.branch_id === this.form.from_location.branch_id
            )
        },
        filteredBuildings() {
            if (!this.form.to_location.branch_id) return [];

            return this.constant.BUILDINGS.filter(
                b => b.branch_id === this.form.to_location.branch_id
            );
        },
        floorList() {
            const building = this.constant.BUILDINGS.find(
                b => b.id === this.form.to_location.building_id
            );

            if (!building) return [];

            const floors = [];

            // BASEMENT FLOOR
            floors.push({
                value: 'BASEMENT',
                label: 'Lantai Basement',
            });

            // Lantai atas (1, 2, 3, ...)
            for (let i = 1; i <= building.floors_count; i++) {
                floors.push({
                    value: String(i),
                    label: `Lantai ${i}`,
                });
            }

            return floors;
        },

    },

    watch: {
        selectedRowKeys(keys) {
            this.form.items = keys;
        },
        'form.to_location.branch_id'(val, oldVal) {
            if (this.isEditing) return;
            if (val !== oldVal) {
                this.form.to_location.building_id = null
                this.form.to_location.floor = null
                this.form.to_location.unit_id = null
                this.form.to_location.room_id = null
                this.form.to_pj_id = null
            }
        },
        'form.to_location.building_id'(val, oldVal) {
            if (this.isEditing) return;
            if (val !== oldVal) {
                this.form.to_location.floor = null;
                this.form.to_location.room_id = null;
                this.roomOptions = [];
            }
        },
        'form.to_location.floor'(val, oldVal) {
            if (this.isEditing) return;
            if (val !== oldVal) {
                this.form.to_location.room_id = null;
                this.fetchRooms();
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

                // Fetch Users Leader
                vm.fetchUsers("", {
                    leaderOnly: true,
                    target: "leaderOptions",
                });
            })
        },

        async editData(m) {
            const vm = this;

            vm.isEditing = true;
            vm.assetLoaded = false;
            vm.assetModels = [];
            vm.selectedRowKeys = [];

            try {
                const { data } = await vm.axios.get(vm.readRoute, {
                    params: {
                        req: 'movement_detail',
                        id: m.id
                    }
                });

                const full = data.model;

                vm.form = vm.lodash.cloneDeep(full);
                const firstItem = full.items?.[0];

                vm.form.to_location = firstItem?.to_location ?? {
                    branch_id: null,
                    building_id: null,
                    floor: null,
                    unit_id: null,
                    room_id: null,
                };

                vm.selectedRowKeys = full.items.map(i => i.asset_id);
                const pendingApproval = (full.approvals || [])
                    .find(a => a.status === 'pending');

                vm.form.approver_id = pendingApproval?.user_id ?? null;

                vm.showModal = true;

                await vm.$nextTick();

                if (vm.form.approver_id) {
                    await vm.fetchUsers(
                        vm.form.approver_id,
                        { leaderOnly: true, target: "leaderOptions" }
                    );
                }

                if (vm.form.to_pj_id) {
                    await vm.fetchUsers(vm.form.to_pj_id);
                }

                if (vm.fetchRooms) {
                    await vm.fetchRooms();
                }

                await vm.fetchAssets();

                vm.selectedRowKeys = m.items ?? [];
                vm.assetLoaded = true;

            } finally {
                vm.isEditing = false;
            }
        },

        async fetchAssets() {
            const vm = this;
            if (!vm.form.to_pj_id || !vm.form.asset_type) return;

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
                    vm.openNotification('Serah terima mutasi berhasil dibuat ...', 'success');
                }
                else {
                    vm.openNotification('Serah terima mutasi berhasil diupdate', 'success');
                }

                vm.showModal = false;
                vm.readData();
            }

            vm.loadingFalse();
        },

        async fetchUsers(
            param = "",
            { leaderOnly = false, target = "userOptions" } = {}
        ) {
            let url = "/lookups/users?";

            if (typeof param === "number") {
                url += `id=${param}`;
            } else if (/^[0-9]{6,}$/.test(param)) {
                url += `identifier=${encodeURIComponent(param)}`;
            } else {
                url += `search=${encodeURIComponent(param)}&limit=10`;
            }

            if (leaderOnly) {
                url += "&type=leader";
            }

            const res = await fetch(url);
            const data = await res.json();

            this[target] = Array.isArray(data) ? data : [];
        },

        onSearchUser: debounce(function (val) {
            this.fetchUsers(val, {
                leaderOnly: false,
                target: "userOptions",
            });
        }, 500),

        onSearchLeader: debounce(function (val) {
            this.fetchUsers(val, {
                leaderOnly: true,
                target: "leaderOptions",
            });
        }, 500),

        async fetchRooms(param = "") {

            if (!this.form.to_location.building_id || !this.form.to_location.floor) {
                this.roomOptions = [];
                return;
            }

            this.loadingTrue();

            try {
                let url = "/lookups/rooms?";
                url += `building_id=${this.form.to_location.building_id}`;
                url += `&floor=${encodeURIComponent(this.form.to_location.floor)}`;

                if (param) {
                    url += `&search=${encodeURIComponent(param)}`;
                }

                url += `&limit=100`;

                const res = await fetch(url);
                const data = await res.json();

                this.roomOptions = Array.isArray(data) ? data : [data];
            } finally {
                this.loadingFalse();
            }
        },

        onSearchRoom: debounce(function (val) {
            this.fetchRooms(val);
        }, 500),

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
            if (record.status === 'draft') {
                this.confirmAction = 'submit'
            } else if (record.status === 'submitted') {
                this.confirmAction = 'approve'
            } else if (record.status === 'approved') {
                this.confirmAction = 'verify'
            }
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

                const messages = {
                    submit: 'Data berhasil disubmit',
                    approve: 'Data berhasil di-approve',
                    verify: 'Data berhasil diverifikasi',
                }

                vm.openNotification(
                    messages[vm.confirmAction] || 'Aksi berhasil',
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
            this.pdfLoading = true;

            this.$nextTick(() => {
                this.pdfContent = `${this.readRoute}?req=pdf&id=${id}&t=${Date.now()}`;
            });

            this.showPDFModal = true;
            this.startPdfLoadingTimeout();
        },

        startPdfLoadingTimeout() {
            if (this.pdfTimeout) clearTimeout(this.pdfTimeout);

            this.pdfTimeout = setTimeout(() => {
                this.pdfLoading = false;
            }, 2000); // 1.5–2 detik
        },

        onPdfLoad() {
            this.pdfLoading = false;

            if (this.pdfTimeout) {
                clearTimeout(this.pdfTimeout);
            }
        },

        openRejectionModal(note, date, user) {
            this.rejection_note = note;
            this.rejection_date = date;
            this.rejector = user;
            this.showRejectionModal = true;
        },

        isMyTurnToApprove(record) {
            if (!record.approvals?.length) return false;

            const next = record.approvals
                .filter(a => a.status === 'pending')
                .sort((a, b) => a.approval_order - b.approval_order)[0];

            if (!next) return false;

            return next.user_id === this.user.id;
        },

        actionButtonText(status) {
            if (status === 'draft') return 'Submit';
            if (status === 'submitted') return 'Sahkan';
            if (status === 'approved') return 'Verif';
            return '';
        },

        actionButtonIcon(status) {
            if (status === 'draft') return 'streamline-color:send-email';
            if (status === 'submitted') return 'duo-icons:approved';
            if (status === 'approved') return 'streamline-color:shield-check';
            return '';
        },

        actionButtonClass(status) {
            if (status === 'draft') {
                return 'bg-gradient-to-r from-purple-400 via-blue-500 to-blue-700 hover:from-yellow-400 hover:via-yellow-600 hover:to-purple-500';
            }

            if (status === 'submitted') {
                return 'bg-gradient-to-r from-emerald-400 via-green-500 to-emerald-700 hover:from-lime-400 hover:via-green-600 hover:to-emerald-500';
            }

            if (status === 'approved') {
                return 'bg-gradient-to-r from-blue-400 via-cyan-500 to-blue-700 hover:from-sky-400 hover:via-cyan-600 hover:to-blue-500';
            }

            return '';
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
                            <a-select-option value="approved">Diverifikasi Atasan</a-select-option>
                            <a-select-option value="verified">Diverifikasi PJ Baru</a-select-option>
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

                    <a-col v-if="can('iis.mutation.create')" class="w-full md:w-auto">
                        <a-button type="primary"
                            class="bg-gradient-to-r from-purple-400 via-blue-500 to-blue-700 hover:from-yellow-400 hover:via-yellow-600 hover:to-purple-500 flex items-center justify-center text-white font-medium border-0 shadow-md transition-all duration-300 w-full"
                            @click="newData()">
                            <Icon icon="line-md:plus" class="mr-1" />
                            Tambah Mutasi
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
                        <a-tooltip :title="record.status === 'draft'
                            ? 'Submit'
                            : record.status === 'submitted'
                                ? 'Approve'
                                : 'Verifikasi'
                            " v-if="
                                // SUBMIT
                                (can('iis.mutation.submit')
                                    && record.status === 'draft'
                                    && record.operator_id === user.id)

                                ||

                                // APPROVE (approval-based)
                                (can('iis.mutation.approve')
                                    && record.status === 'submitted'
                                    && isMyTurnToApprove(record))

                                ||

                                // VERIFY
                                (can('iis.mutation.verify')
                                    && record.status === 'approved'
                                    && record.to_pj_id === user.id)
                            ">
                            <div class="px-2">
                                <a-button type="primary" size="small" :class="[
                                    'flex items-center justify-center text-white font-medium',
                                    'border-0 shadow-md transition-all duration-300',
                                    actionButtonClass(record.status)
                                ]" @click="openConfirm(record)">
                                    <Icon :icon="actionButtonIcon(record.status)" class="mr-1" />
                                    {{ actionButtonText(record.status) }}
                                </a-button>
                            </div>
                        </a-tooltip>

                        <!-- REJECT -->
                        <a-tooltip title="Tolak Serah Terima" v-if="
                            can('iis.mutation.reject') && (

                                // Reject oleh Approver
                                (record.status === 'submitted'
                                    && isMyTurnToApprove(record))

                                ||

                                // Reject oleh PJ Tujuan
                                (record.status === 'approved'
                                    && record.to_pj_id === user.id)

                            )
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
                        <a-tooltip title="Edit Data" v-if="can('iis.mutation.update') && record.status === 'draft'">
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
                        <a-tooltip title="Hapus Data" v-if="can('iis.mutation.delete') && record.status === 'draft'">
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

                <template v-if="column.key === 'approver_id'">
                    <a-tag color="green">
                        {{ record.approvals[0]?.user?.identifier }} - {{ record.approvals[0]?.user?.name }} - {{
                            record.approvals[0]?.position }}
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
                    <a-tag v-else-if="record.status === 'approved'" color="orange">
                        DIVERIFIKASI ATASAN
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
    <a-modal v-model:open="showModal" :title="form.id ? 'Ubah Data Mutasi' : 'Tambah Data Mutasi'" width="1300px"
        @ok="writeData" :mask-closable="false" :destroy-on-close="true" :style="{ top: '30px' }">
        <a-form layout="vertical">
            <a-row :gutter="16">

                <a-col :xs="24" :md="12" :lg="8">
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

                <a-col :xs="24" :md="12" :lg="8">
                    <a-form-item label="Request Persetujuan Atasan" data-column="approver_id"
                        :rules="[{ required: true }]">
                        <a-select v-model:value="form.approver_id" placeholder="--Pilih Atasan--" show-search
                            allow-clear class="w-full lg:w-96" option-label-prop="label" option-filter-prop="label"
                            @search="onSearchLeader">
                            <a-select-option v-for="u in leaderOptions" :key="u.id" :value="u.id"
                                :label="`${u.identifier} - ${u.name} - ${u.position || ''}`">
                                <div class="flex items-start gap-3">
                                    <Icon icon="mdi:account-circle-outline" class="text-sky-500 text-xl mt-0.5" />
                                    <div class="flex flex-col leading-tight">
                                        <span class="font-medium text-slate-800">{{ u.name }}</span>
                                        <span class="text-xs text-slate-400">
                                            {{ u.identifier }}<span v-if="u.position"> • {{ u.position }}</span>
                                        </span>
                                    </div>
                                </div>
                            </a-select-option>
                        </a-select>
                    </a-form-item>
                </a-col>

                <a-col :xs="24" :md="12" :lg="8">
                    <a-form-item label="Catatan Mutasi">
                        <a-textarea v-model:value="form.notes" :rows="1" />
                    </a-form-item>
                </a-col>

                <!-- ================= LOKASI TUJUAN ================= -->
                <div class="px-2 w-full">
                    <div class="mb-4 py-2 px-4 rounded-xl border bg-blue-50">
                        <h3 class="font-semibold text-slate-700 mb-3">Lokasi Tujuan & Penanggung Jawab</h3>
                        <div class="grid lg:grid-cols-3 lg:gap-4">

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

                            <a-form-item label="Gedung Tujuan" data-column="to_location.building_id"
                                :rules="[{ required: true }]">
                                <a-select v-model:value="form.to_location.building_id" placeholder="--Pilih Gedung--"
                                    allow-clear show-search class="w-full" option-label-prop="label">
                                    <a-select-option v-for="b in filteredBuildings" :key="b.id" :value="b.id"
                                        :label="b.name">
                                        {{ b.name }}
                                    </a-select-option>
                                </a-select>
                            </a-form-item>

                            <a-form-item label="Lantai" data-column="to_location.floor" :rules="[{ required: true }]">
                                <a-select v-model:value="form.to_location.floor" placeholder="--Pilih Lantai--"
                                    allow-clear show-search class="w-full" :disabled="!form.to_location.building_id"
                                    option-label-prop="label">
                                    <a-select-option v-for="f in floorList" :key="f.value" :value="f.value"
                                        :label="f.label">
                                        {{ f.label }}
                                    </a-select-option>
                                </a-select>
                            </a-form-item>
                            <a-form-item label="Unit Tujuan" data-column="to_location.unit_id"
                                :rules="[{ required: true }]">
                                <a-select v-model:value="form.to_location.unit_id" show-search allow-clear
                                    class="w-full" placeholder="--Pilih Unit--" option-label-prop="label"
                                    option-filter-prop="label">
                                    <a-select-option v-for="u in constant.UNITS" :key="u.id" :value="u.id"
                                        :label="`${u.name}`">
                                        <div class="flex flex-col">
                                            <span class="font-medium">{{ u.name }}</span>
                                            <span class="text-xs text-gray-400">{{ u.department
                                                }}</span>
                                        </div>
                                    </a-select-option>
                                </a-select>
                            </a-form-item>

                            <a-form-item label="Ruangan Tujuan" data-column="to_location.room_id">
                                <a-select v-model:value="form.to_location.room_id" placeholder="--Pilih Ruangan--"
                                    show-search allow-clear
                                    :disabled="!form.to_location.building_id || !form.to_location.floor"
                                    :filter-option="false" @search="onSearchRoom">
                                    <a-select-option v-for="r in roomOptions" :key="r.id" :value="r.id" :label="r.name">
                                        <div class="flex flex-col">
                                            <span class="font-medium">{{ r.name }}</span>
                                            <span class="text-xs text-gray-400">
                                                Lantai {{ r.floor }}
                                            </span>
                                        </div>
                                    </a-select-option>
                                </a-select>
                            </a-form-item>
                            <a-form-item label="Penanggung Jawab (PJ) Barang" data-column="to_pj_id"
                                :rules="[{ required: true }]">
                                <a-select v-model:value="form.to_pj_id" placeholder="--Pilih PJ--" show-search
                                    allow-clear class="w-full lg:w-96" option-label-prop="label"
                                    option-filter-prop="label" @search="onSearchUser" @change="fetchAssets">
                                    <a-select-option v-for="u in userOptions" :key="u.id" :value="u.id"
                                        :label="`${u.identifier} - ${u.name} - ${u.position || ''}`">
                                        <div class="flex items-start gap-3">
                                            <Icon icon="mdi:account-circle-outline"
                                                class="text-sky-500 text-xl mt-0.5" />
                                            <div class="flex flex-col leading-tight">
                                                <span class="font-medium text-slate-800">{{ u.name }}</span>
                                                <span class="text-xs text-slate-400">
                                                    {{ u.identifier }}<span v-if="u.position"> • {{ u.position }}</span>
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
                        dicetak QR Code nya atau barang yg telah ditempel QR Code dari aplikasi IIS)
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
                        Mutasi Barang
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
    <a-modal v-model:open="confirmModalVisible" :title="modalTitle" width="820px" :mask-closable="false">
        <div class="bg-slate-50 rounded-2xl p-5 space-y-5">

            <!-- HEADER -->
            <div
                class="flex items-center gap-4 bg-gradient-to-r from-purple-50 to-indigo-50 rounded-xl p-4 border border-purple-100">
                <div class="flex items-center justify-center w-12 h-12 rounded-xl text-white shadow"
                    :class="headerIconBg">
                    <Icon :icon="headerIcon" class="text-2xl" />
                </div>

                <div class="flex-1">
                    <div class="text-xs text-purple-600 font-medium uppercase tracking-wide">
                        Mutasi Barang IIS
                    </div>
                    <div class="text-lg font-semibold text-slate-800">
                        {{ headerText }}
                    </div>
                    <div class="text-xs text-slate-500 mt-1">
                        Kode:
                        <span class="font-medium">{{ confirmRecord?.code }}</span>
                    </div>
                </div>

                <a-tag :color="actionColor" class="font-medium">
                    {{ actionLabel }}
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
    <a-modal v-model:open="showPDFModal" title="Berkas Digital Serah Terima Mutasi Barang" width="900px"
        :mask-closable="false">
        <div class="relative">

            <!-- LOADING -->
            <div v-if="pdfLoading" class="absolute inset-0 z-10 flex flex-col items-center justify-center
               bg-white/80 backdrop-blur-sm">

                <a-spin size="large" />
                <div class="mt-3 text-sm text-gray-500">
                    Memuat dokumen...
                </div>
            </div>

            <!-- IFRAME -->
            <iframe v-if="pdfContent" :src="pdfContent" :key="pdfContent" width="100%" height="600px"
                @load="onPdfLoad"></iframe>

        </div>
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
    <a-modal v-model:open="showRejectionModal" title="Alasan Penolakan Mutasi" width="500px" :mask-closable="false">

        <!-- ALERT -->
        <a-alert type="error" show-icon class="mb-4">
            <template #message>
                Serah terima Mutasi ditolak
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
