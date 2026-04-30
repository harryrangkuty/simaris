<script>

export default {
    props: {
        parent: {
            type: Object,
            default: () => ({}),
        },
    },

    data() {
        return {
            isListPenghapusanEmpty: false,
            isProcurementDocumentEmpty: false,
            isProcurementDetailDocumentEmpty: false,
        };
    },

    computed: {
        'locked': function () {
            return this.parent.status == 'CLOSE' || this.parent.status == 'FINISH';
        }
    },

    methods: {

        handleTabClick(key) {
            this.$nextTick(() => {
                if (key === '1' && this.$refs.infoPenghapusanAsetTetap) {
                    this.$refs.infoPenghapusanAsetTetap.readData();
                } else if (key === '2' && this.$refs.listPenghapusanAsetTetap) {
                    this.$refs.listPenghapusanAsetTetap?.readData();
                } else if (key === '3' && this.$refs.dokumenPenghapusanAsetTetap) {
                    this.$refs.dokumenPenghapusanAsetTetap?.readData();
                    this.$refs.dokumenPenghapusanAsetTetap?.readListFile();
                }
            });
        },

        async authorize() {
            const vm = this;
            vm.loadingTrue()
            const form = { req: 'authorize', id: vm.parent.id };
            const response = await vm.axios.post(vm.writeRoute, form).catch((e) => vm.$onAjaxError(e));
            if (response && response.data) {
                vm.openNotification('Berhasil mengubah status data, refreshing page ...', 'success');
                setTimeout(function () {
                    window.location.reload();
                }, 1500)
            }
        },

        async submit() {
            const vm = this;
            vm.loadingTrue()
            const form = { req: 'submit', id: vm.parent.id };
            const response = await vm.axios.post(vm.writeRoute, form).catch((e) => vm.$onAjaxError(e));
            if (response && response.data) {
                vm.openNotification('Berhasil submit penghapusan aset, refreshing page ...', 'success');
                setTimeout(function () {
                    window.location.reload();
                }, 1500)
            }
        },

        async deleteData() {
            const vm = this;
            vm.loadingTrue()
            const form = { req: 'delete', id: vm.parent.id };
            const response = await vm.axios.post(vm.writeRoute, form).catch((e) => vm.$onAjaxError(e));
            if (response && response.data) {
                vm.openNotification('Berhasil menghapus data', 'success');
                vm.showModal = false;
                // Redirect
                setTimeout(() => {
                    const baseUrl = window.location.href.split('?')[0];
                    window.location.href = baseUrl;
                }, 1500);
            }
        },

        showConfirm() {
            const vm = this;

            const delay = (ms) => new Promise(resolve => setTimeout(resolve, ms));

            vm.$confirm({
                title: 'Yakin untuk menghapus data ini?',
                content: 'Pastikan semua data sudah benar karena data yang sudah dihapus tidak bisa diubah lagi!',
                okText: 'Yakin',
                okType: 'primary',
                cancelText: 'Batal',
                onOk: () => {
                    return delay(2000).then(() => {
                        vm.submit();
                    });
                },
                onCancel: () => { },
            });
        },

        handleListPenghapusanEmpty(isEmpty) {
            this.isListPenghapusanEmpty = isEmpty;
        },

        handleEmptyDocumentList(isEmpty) {
            this.isProcurementDocumentEmpty = isEmpty;
        },

        handleEmptyDocumentDetail(isEmpty) {
            console.log('tes');
            this.isProcurementDetailDocumentEmpty = isEmpty;
        },

    }
};
</script>

<template>
    <a-card class="card">
        <a-row class="flex gap-x-2 items-center justify-between mb-2 border-b-2">
            <div :class="[{ 'w-full': parent.status === 'FINISH' }, 'flex sm:flex-row flex-col gap-x-2']">
                <h1 class="font-bold text-xl !m-0">{{ 'Penghapusan Aset Tetap - ' + parent.kode }}</h1>
                <a-button v-if="parent.status === 'FINISH'" type="success"
                    class="flex items-center justify-center w-full sm:w-auto min-w-[6rem]"
                    style="pointer-events: none;">
                    <Icon icon="octicon:checklist-16" class="mr-1" />
                    Terhapus
                </a-button>
            </div>

            <div class="flex w-full md:w-auto">
                <a-row class="flex flex-wrap justify-start sm:justify-end gap-2 items-center w-full">
                    <a-col class="w-full sm:w-auto" v-if="parent.status !== 'FINISH' && operator_aset">
                        <a-popconfirm v-if="parent.status == 'OPEN'" title="Yakin mengunci transaksi?"
                            class="mr-1  w-full sm:w-auto" @confirm="authorize()">
                            <a-button class="flex items-center justify-center w-full mb-2 sm:w-auto min-w-[6rem]"
                                type="success" :disabled="loadingStatus">Selesai</a-button>
                        </a-popconfirm>
                        <a-popconfirm v-else title="Yakin membatalkan transaksi?" @confirm="authorize()"
                            class="mr-1 w-full sm:w-auto">
                            <a-button class="flex items-center justify-center w-full  mb-2 sm:w-auto min-w-[6rem]"
                                type="danger" :disabled="loadingStatus">Buka</a-button>
                        </a-popconfirm>
                        <a-popconfirm title="Yakin menghapus data?" @confirm="deleteData()"
                            class="mr-1 w-full sm:w-auto" :disabled="locked || loadingStatus">
                            <a-button class="flex items-center justify-center w-full  mb-2 sm:w-auto min-w-[6rem]"
                                type="danger" :disabled="locked || loadingStatus">Hapus</a-button>
                        </a-popconfirm>
                        <a-button v-if="parent.status == 'CLOSE'"
                            class="flex items-center justify-center w-full sm:w-auto min-w-[6rem]" type="primary"
                            @click="showConfirm">Submit</a-button>
                    </a-col>
                </a-row>
            </div>
        </a-row>

        <a-tabs @tabClick="handleTabClick">
            <a-tab-pane key="1" tab="Info Penghapusan">
                <info-penghapusan-aset-tetap :parent="parent" ref="infoPenghapusanAsetTetap" />
            </a-tab-pane>
            <a-tab-pane key="2" :force-render="true">
                <template #tab>
                    <a-tooltip placement="topLeft"
                        :title="isListPenghapusanEmpty ? 'List Penghapusan Masih Kosong' : null">
                        <span>List Penghapusan</span>
                        <Icon v-if="isListPenghapusanEmpty" icon="line-md:alert-circle-twotone"
                            class="text-red-500 ml-1 h-5 w-5" />
                    </a-tooltip>
                </template>
                <list-penghapusan-aset-tetap :parent="parent" @listPenghapusanEmpty="handleListPenghapusanEmpty"
                    ref="listPenghapusanAsetTetap" />
            </a-tab-pane>
            <a-tab-pane key="3" :force-render="true">
                <template #tab>
                    <a-tooltip placement="topLeft"
                        :title="(isProcurementDocumentEmpty || isProcurementDetailDocumentEmpty) ? 'Cek Data Dokumen' : null">
                        <span>Dokumen Penghapusan</span>
                        <Icon v-if="isProcurementDocumentEmpty || isProcurementDetailDocumentEmpty" icon="line-md:alert-circle-twotone"
                            class="text-red-500 ml-1 h-5 w-5" />
                    </a-tooltip>
                </template>
                <dokumen-aset :parent="parent" @dokumenEmpty="handleEmptyDocumentList"
                    @detailDokumenEmpty="handleEmptyDocumentDetail" ref="dokumenPenghapusanAsetTetap" />
            </a-tab-pane>
        </a-tabs>
    </a-card>

</template>