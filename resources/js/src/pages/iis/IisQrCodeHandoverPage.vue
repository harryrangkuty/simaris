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
        width: 170,
        ellipsis: true,
        sorter: (a, b) => a.code.localeCompare(b.code),
        sortDirections,
    },
    {
        title: "Status",
        key: "status",
        width: 135,
        align: "left",
        sorter: (a, b) => a.status.localeCompare(b.status),
        sortDirections,
    },
    {
        title: "Tipe",
        key: "type",
        width: 100,
        align: "center",
        sorter: (a, b) => a.asset_type.localeCompare(b.asset_type),
        sortDirections,
    },
    {
        title: "Penanggung Jawab (PJ) Barang",
        key: "pj",
        width: 230,
        sorter: (a, b) => a.pj_id - b.pj_id,
        sortDirections,
    },
    {
        title: "Tim Audit",
        key: "approvals",
        width: 150,
        align: "center",
    },
    {
        title: "Operator",
        key: "operator",
        width: 230,
        sorter: (a, b) => a.operator_id - b.operator_id,
        sortDirections,
    },
    {
        title: "Tanggal Submit",
        dataIndex: "submitted_at",
        width: 170,
        ellipsis: true,
    },
    {
        title: "Tanggal Diverifikasi PJ",
        dataIndex: "verified_at",
        width: 170,
        ellipsis: true,
    },
    {
        title: "Total Item",
        key: "items_count",
        align: "center",
        width: 70,
        sorter: (a, b) => a.items_count - b.items_count,
        sortDirections,
    },
    {
        title: "Catatan",
        dataIndex: "notes",
        align: "center",
        width: 150,
        sorter: (a, b) => a.notes.localeCompare(b.notes),
        sortDirections,
    },
    {
        title: "Aksi",
        key: "action",
        align: "center",
        width: 220,
        fixed: "right",
        className: "column-action",
    },
];

const selectItemColumns = [
    {
        title: "#",
        key: "number",
        align: "center",
        width: 70,
    },
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
        title: "Posisi Barang",
        key: "position",
        width: 250,
        ellipsis: true,
        sorter: (a, b) =>
            (a.position || "").localeCompare(b.position || ""),
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
        width: 200,
        ellipsis: true,
        sorter: (a, b) =>
            (a.unit || "").localeCompare(b.unit || ""),
        sortDirections,
    },
    {
        title: "Ruang",
        key: "room",
        width: 200,
        ellipsis: true,
        sorter: (a, b) =>
            (a.room || "").localeCompare(b.room || ""),
        sortDirections,
    },
    {
        title: "Penanggung Jawab (PJ)",
        key: "pj",
        width: 350,
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
            assetModels: [],
            selectedRowKeys: [],
            form: {
                id: null,
                pj_id: null,
                asset_type: null,
                notes: null,
                items: [],
                approvers: []
            },
            filter: {
                status: null,
                unit: null,
                pj_id: null,
            },
            showPDFModal: false, // modal PDF viewer
            pdfContent: null, // HTML dari backend
            rejectModalVisible: false,
            rejectForm: {
                id: null,
                notes: '',
            },
            userOptions: [],
            rejectLoading: false,
            showRejectionModal: false,
            rejection_note: null,
            rejection_date: null,
            rejector: null,
            confirmModalVisible: false,
            confirmAction: null,
            confirmRecord: null,
            handoverItems: null,
            approvalModalVisible: false,
            selectedHandover: null,
            pdfLoading: false,
            pdfContent: null,
            pdfTimeout: null,
            assetPagination: {
                current: 1,
                pageSize: 30,
            }
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
                    return 'Konfirmasi Submit Serah Terima'
                case 'approve':
                    return 'Konfirmasi Approval Serah Terima'
                case 'verify':
                    return 'Konfirmasi Verifikasi Serah Terima'
                default:
                    return 'Konfirmasi'
            }
        },
        headerText() {
            switch (this.confirmAction) {
                case 'submit':
                    return 'Konfirmasi Submit Serah Terima'
                case 'approve':
                    return 'Konfirmasi Approval Serah Terima'
                case 'verify':
                    return 'Konfirmasi Verifikasi Serah Terima'
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
        }
    },

    watch: {
        selectedRowKeys(keys) {
            this.form.items = keys;
        },
    },

    mounted() {
        this.readData();
        this.fetchUsers();
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
            vm.assetPagination.current = 1;

            vm.$nextTick(function () {
                vm.showModal = true;
                vm.fetchUsers();
            })
        },

        async editData(m) {
            const vm = this;

            vm.assetPagination.current = 1;

            // RESET DULU
            vm.assetModels = [];
            vm.selectedRowKeys = [];

            vm.form = vm.lodash.cloneDeep(m);

            const approverIds = (m.approvals || [])
                .sort((a, b) => a.approval_order - b.approval_order)
                .map(a => a.user_id);

            vm.form.approvers = approverIds;

            vm.showModal = true;

            await vm.$nextTick();

            await Promise.all(approverIds.map(id => vm.fetchUsers(id)));

            if (m.pj_id) {
                await vm.fetchUsers(m.pj_id);
            }

            await vm.fetchAssets();

            vm.selectedRowKeys = m.items ?? [];
        },

        async fetchAssets(v) {
            const vm = this;
            if (!vm.form.pj_id || !vm.form.asset_type) return;

            const user = vm.userOptions.find(
                (u) => u.id === vm.form.pj_id
            );
            if (!user) return;

            let params = {
                page: vm.assetPagination.current,
                results: vm.assetPagination.pageSize,
                ...(v || {})
            };

            vm.loadingTrue();

            params = {
                asset_type: vm.form.asset_type,
                pj_nik: user.identifier,
                handover_id: vm.form.id,
                ...params
            };

            const response = await vm.axios
                .get("/lookups/iis-available-handover-assets", { params })
                .catch((e) => vm.$onAjaxError(e));

            if (response?.data?.models) {
                const pagination = { ...vm.assetPagination };

                pagination.total = response.data.models.total;
                pagination.current = response.data.models.current_page;
                pagination.pageSize = response.data.models.per_page;

                vm.assetModels = response.data.models.data;
                vm.assetPagination = pagination;

            }

            vm.loadingFalse();
        },

        handleAssetTableChange(pagination) {
            this.assetPagination.current = pagination.current;
            this.assetPagination.pageSize = pagination.pageSize;

            this.fetchAssets({
                page: pagination.current,
                results: pagination.pageSize
            });
        },

        async writeData() {
            const vm = this;

            vm.form.items = vm.selectedRowKeys;

            if (
                !vm.form.items.length &&
                (vm.form.approvers.length && vm.form.pj_id && vm.form.asset_type)
            ) {
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
                    vm.openNotification('Serah terima berhasil dibuat ...', 'success');
                }
                else {
                    vm.openNotification('Serah terima berhasil diupdate', 'success');
                }

                vm.showModal = false;
                vm.readData();
            }

            vm.loadingFalse();
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
            await this.fetchHandoverItems(record.id)
        },

        async fetchHandoverItems(id) {
            this.loadingTrue()

            const res = await this.axios.get(this.readRoute, {
                params: {
                    req: 'handover_items',
                    id
                }
            })

            if (res?.data?.models) {
                this.handoverItems = res.data.models
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

        openApprovalModal(record) {
            this.selectedHandover = record;
            this.approvalModalVisible = true;
        },

        getPhoto(user) {
            if (user?.photo_object?.url) {
                return user.photo_object.url;
            }
            return "/images/profile.png";
        },

        statusColor(status) {
            if (status === "approved") return "green";
            if (status === "rejected") return "red";
            return "orange";
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
            if (status === 'submitted') return 'Approve';
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
                            class="min-w-56 lg:w-56 w-full" allow-clear @change="readData">
                            <a-select-option value="draft">Draft</a-select-option>
                            <a-select-option value="submitted">Disubmit</a-select-option>
                            <a-select-option value="approved">Diverifikasi Tim Audit</a-select-option>
                            <a-select-option value="verified">Diverifikasi</a-select-option>
                            <a-select-option value="rejected">Ditolak</a-select-option>
                        </a-select>
                    </a-col>

                    <!-- PJ -->
                    <a-col v-if="can('iis.qrcode-handover.create')" class="w-full md:w-auto">
                        <a-select v-model:value="filter.pj_id" placeholder="--Pilih PJ--" show-search allow-clear
                            option-label-prop="label" option-filter-prop="label" class="w-full lg:w-96"
                            @search="onSearchUser" @change="readData">
                            <a-select-option v-for="pj in userOptions" :key="pj.id" :value="pj.id"
                                :label="`${pj.identifier} - ${pj.name} - ${pj.position}`">
                                <div class="flex items-center gap-2">
                                    <a-tag color="blue">{{ pj.identifier }} - {{ pj.name }}</a-tag>
                                    <span class="text-gray-700">{{ pj.position }}</span>
                                </div>
                            </a-select-option>
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

                    <a-col v-if="can('iis.qrcode-handover.create')" class="w-full md:w-auto">
                        <a-button type="primary"
                            class="bg-gradient-to-r from-purple-400 via-blue-500 to-blue-700 hover:from-yellow-400 hover:via-yellow-600 hover:to-purple-500 flex items-center justify-center text-white font-medium border-0 shadow-md transition-all duration-300 w-full"
                            @click="newData()">
                            <Icon icon="line-md:plus" class="mr-1" />
                            Tambah Data Serah Terima
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
                                (can('iis.qrcode-handover.submit')
                                    && record.status === 'draft'
                                    && record.operator_id == user.id)

                                ||

                                // APPROVE (approval-based)
                                (can('iis.qrcode-handover.approve')
                                    && record.status === 'submitted'
                                    && isMyTurnToApprove(record))

                                ||
                                // VERIFY (hanya boleh verify milik sendiri)
                                (can('iis.qrcode-handover.verify')
                                    && record.status === 'approved'
                                    && record.pj_id == user.id)
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
                            can('iis.qrcode-handover.reject') && (

                                // Reject oleh Approver
                                (record.status === 'submitted'
                                    && isMyTurnToApprove(record))

                                ||

                                // Reject oleh PJ
                                (record.status === 'approved'
                                    && record.pj_id === user.id)

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
                        <a-tooltip title="Edit Data"
                            v-if="can('iis.qrcode-handover.update') && record.status === 'draft'">
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
                            v-if="can('iis.qrcode-handover.delete') && record.status === 'draft'">
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
                    <a-tag color="#f50">
                        {{ record.pj?.identifier }} - {{ record.pj?.name }}
                    </a-tag>
                </template>
                <template v-if="column.key === 'approvals'">
                    <div class="flex justify-center">
                        <a-tooltip title="Detail Tim Audit">
                            <a-button size="small" ghost
                                class="flex items-center gap-x-1 !border-blue-500 !text-blue-600 hover:!bg-blue-50"
                                @click="openApprovalModal(record)">
                                <Icon icon="fluent-color:apps-list-detail-32" class="text-lg" />
                                Lihat
                            </a-button>
                        </a-tooltip>
                    </div>
                </template>
                <template v-if="column.key === 'operator'">
                    <a-tag color="purple">
                        {{ record.operator?.identifier }} - {{ record.operator?.name }}
                    </a-tag>
                </template>
                <template v-if="column.key === 'type'">
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
                <template v-if="column.key === 'items_count'">
                    <a-tag color="cyan" class="font-semibold">
                        {{ record.items_count }}
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
                        DIVERIFIKASI TIM AUDIT
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
                        {{ record.verifier?.name }}
                    </a-tag>
                </template>
            </template>
        </a-table>
    </a-card>

    <!-- ================= MODAL ================= -->
    <a-modal v-model:open="showModal"
        :title="form.id ? 'Ubah Serah Terima QR Code Barang dari IIS' : 'Tambah Serah Terima QR Code Barang dari IIS'"
        width="1200px" @ok="writeData" :mask-closable="false" :destroy-on-close="true" :style="{ top: '30px' }">
        <a-form layout="vertical">
            <a-row :gutter="16">

                <a-col :span="12">
                    <a-form-item label="Jenis Barang" data-column="asset_type" required>
                        <a-select v-model:value="form.asset_type" @change="fetchAssets" allow-clear
                            placeholder="--Pilih Tipe--">
                            <a-select-option value="inventory">
                                Inventaris
                            </a-select-option>
                            <a-select-option value="alkes">
                                Alat kesehatan (ALKES)
                            </a-select-option>
                        </a-select>
                    </a-form-item>
                </a-col>

                <a-col :span="12">
                    <a-form-item label="Penanggung Jawab (PJ) Barang" data-column="pj_id" required>
                        <a-select v-model:value="form.pj_id" placeholder="--Pilih PJ--" show-search allow-clear
                            class="w-full lg:w-96" option-label-prop="label" option-filter-prop="label"
                            @search="onSearchUser" @change="fetchAssets">
                            <a-select-option v-for="u in userOptions" :key="u.id" :value="u.id"
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

                <a-col :span="24">
                    <a-form-item label="Tim Audit/Verifikator/Approver/Saksi" data-column="approvers" required>
                        <a-select v-model:value="form.approvers" mode="multiple" placeholder="-- Pilih Tim Audit --"
                            show-search allow-clear option-label-prop="label" option-filter-prop="label"
                            @search="onSearchUser" :getPopupContainer="trigger => trigger.parentNode">
                            <a-select-option v-for="u in userOptions" :key="u.id" :value="u.id"
                                :label="`${u.identifier} - ${u.name}`">
                                <div class="flex flex-col">
                                    <span class="font-medium">{{ u.name }}</span>
                                    <span class="text-xs text-gray-400">
                                        {{ u.identifier }} • {{ u.position }}
                                    </span>
                                </div>
                            </a-select-option>
                        </a-select>
                    </a-form-item>
                </a-col>

                <a-col :span="24">
                    <a-form-item label="Catatan Serah Terima">
                        <a-textarea v-model:value="form.notes" />
                    </a-form-item>
                </a-col>
            </a-row>

            <a-alert v-show="form.pj_id && form.asset_type" type="info" show-icon class="mb-3">
                <template #message>
                    <span class="font-semibold">
                        {{ selectedCount }} barang dipilih (Keterangan: Barang yg muncul dibawah hanya barang yang sudah
                        dicetak QR Code nya atau barang yg telah ditempel QR Code dari aplikasi IIS)
                    </span>
                </template>
            </a-alert>

            <!-- TABEL PILIH BARANG -->
            <div class="relative">
                <!-- LOADING OVERLAY -->
                <transition name="fade">
                    <div v-if="loadingStatus" class="absolute inset-0 z-10 flex flex-col items-center justify-center
                   bg-white/70 backdrop-blur-sm">
                        <a-spin size="large" />
                        <div class="mt-2 text-gray-500 text-sm">
                            Memuat data aset...
                        </div>
                    </div>
                </transition>

                <a-table v-show="form.pj_id && form.asset_type" :columns="selectItemColumns" :data-source="assetModels"
                    :row-key="r => r.id" :pagination="assetPagination" :scroll="{ x: 2000, y: 350 }" :row-selection="{
                        selectedRowKeys,
                        preserveSelectedRowKeys: true,
                        onChange: keys => selectedRowKeys = keys
                    }" @change="handleAssetTableChange">
                    <template #bodyCell="{ index, column, record }">

                        <template v-if="column.key === 'number'">
                            {{ (assetPagination.current - 1) * assetPagination.pageSize + (index + 1) }}
                        </template>

                        <template v-if="column.key === 'qr_code_no'">
                            <a-tag color="#2db7f5">
                                <span class="text-sm">{{ record.qr_code_no }}</span>
                            </a-tag>
                        </template>

                        <template v-if="column.key === 'pj'">
                            <a-tag color="#108ee9">
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

                    </template>
                </a-table>
            </div>
        </a-form>
    </a-modal>

    <!-- Modal Preview PDF -->
    <a-modal v-model:open="showPDFModal" title="Berkas Digital Serah Terima Pendataan Ulang QR Code Inventaris"
        width="900px" :mask-closable="false">
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

    <!-- Modal Preview Item Handover -->
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
                        Serah Terima QR Code Barang IIS
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
                        {{ confirmRecord?.pj?.name ?? '-' }}
                    </div>
                </div>
            </div>

            <!-- ITEMS LIST -->
            <div class="space-y-4">
                <div v-for="(item, index) in handoverItems" :key="item.id"
                    class="relative bg-white rounded-xl p-4 shadow-sm border border-slate-200">
                    <div class="absolute left-0 top-0 h-full w-1 bg-indigo-500 rounded-l-xl"></div>

                    <div class="flex items-start gap-4 pl-2">

                        <div
                            class="flex items-center justify-center w-10 h-10 rounded-lg bg-indigo-100 text-indigo-600">
                            <Icon icon="mdi:barcode-scan" class="text-xl" />
                        </div>

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

                <a-button type="primary"
                    class="bg-gradient-to-r from-purple-400 via-blue-500 to-blue-700 hover:from-yellow-400 hover:via-yellow-600 hover:to-purple-500 flex items-center justify-center text-white font-medium border-0 shadow-md transition-all duration-300"
                    @click="doAuthorize">
                    {{ actionLabel }}
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
    <a-modal v-model:open="showRejectionModal" title="Alasan Penolakan" width="500px" :mask-closable="false">

        <!-- ALERT -->
        <a-alert type="error" show-icon class="mb-4">
            <template #message>
                Serah terima ditolak
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

    <!-- MODAL PREVIEW TIM AUDIT -->
    <a-modal v-model:open="approvalModalVisible" title="Tim Audit" :footer="null" width="720px">
        <div class="bg-slate-50 rounded-2xl p-5 space-y-5">

            <!-- HEADER -->
            <div
                class="flex items-center gap-4 bg-gradient-to-r from-indigo-50 to-blue-50 rounded-xl p-4 border border-indigo-100">
                <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-indigo-500 text-white shadow">
                    <Icon icon="mdi:account-group-outline" class="text-2xl" />
                </div>

                <div class="flex-1">
                    <div class="text-xs text-indigo-600 font-medium uppercase tracking-wide">
                        Approval Pendataan Ulang QR Code Barang IIS
                    </div>
                    <div class="text-lg font-semibold text-slate-800">
                        {{ selectedHandover?.code }}
                    </div>
                </div>

                <a-tag color="blue">
                    {{ selectedHandover?.status?.toUpperCase() }}
                </a-tag>
            </div>

            <!-- APPROVAL FLOW -->
            <div class="space-y-4">

                <div v-for="approval in selectedHandover?.approvals" :key="approval.id"
                    class="flex items-center gap-4 bg-white rounded-xl p-4 shadow-sm border">

                    <!-- PHOTO -->
                    <img :src="getPhoto(approval.user)" class="w-14 h-14 rounded-full object-cover border" />

                    <!-- INFO -->
                    <div class="flex-1">
                        <div class="font-semibold text-slate-800">
                            {{ approval.user?.name }}
                        </div>

                        <div class="text-sm text-slate-500">
                            {{ approval.user?.identifier }}
                        </div>

                        <div class="text-sm text-indigo-600 font-medium">
                            {{ approval.position }}
                        </div>
                    </div>

                    <!-- STATUS -->
                    <div class="text-right">
                        <a-tag :color="statusColor(approval.status)">
                            {{ approval.status?.toUpperCase() }}
                        </a-tag>

                        <div v-if="approval.approved_at" class="text-xs text-slate-400 mt-1">
                            {{ approval.approved_at }}
                        </div>

                        <div v-if="approval.rejected_at" class="text-xs text-red-400 mt-1">
                            {{ approval.rejected_at }}
                        </div>
                    </div>

                </div>

            </div>

            <!-- FOOTER -->
            <div class="flex justify-end pt-2">
                <a-button type="primary" ghost class="!border-slate-300 !text-slate-600"
                    @click="approvalModalVisible = false">
                    Tutup
                </a-button>
            </div>

        </div>
    </a-modal>
</template>
