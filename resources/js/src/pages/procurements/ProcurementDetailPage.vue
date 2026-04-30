<script>

const menuItems = [
    { key: 'list', label: 'List Item Pengadaan', icon: 'streamline-ultimate-color:task-list-pin' },
    { key: 'notes', label: 'Catatan & Estimasi Sampai', icon: 'streamline-ultimate-color:notes-paper-text' },
    { key: 'docs', label: 'Dokumen', icon: 'streamline-sharp-color:zoom-document-flat' },
    { key: 'officer', label: 'Petugas', icon: 'streamline-emojis:man-police-officer-2' },
    { key: 'request_data', label: 'Data Permintaan', icon: 'streamline-plump-color:task-list-edit' },
]

export default {
    props: {
        title: String,
        parent: {
            type: Object,
            default: () => ({}),
        },
    },
    data() {
        return {
            menuItems,
            activeMenu: "list",
            isProcurementListEmpty: false,
            isProcurementDocumentEmpty: false,
            isProcurementDetailDocumentEmpty: false,
            drawerVisible: false,
        };
    },
    mounted() {
        this.readData();
    },

    methods: {
        async readData() {
            const vm = this;
            vm.loadingTrue();
            const params = {
                id: vm.parent.id,
                req: "procurement_info",
                ...vm.filter,
            };

            const response = await vm.axios.get(vm.readRoute, { params })
            if (response && response.data) {
                vm.models = response.data.models
                vm.total = response.data.total
                vm.loadingFalse();
            }
        },

        setActive(key) {
            this.activeMenu = key;
            this.$nextTick(() => {
                if (key === "list") this.$refs.assetAcquisitionList?.readData();
                if (key === "docs") {
                    this.$refs.assetDocument?.readData();
                    this.$refs.assetDocument?.readListFile();
                }
            });
        },

        showConfirm() {
            const vm = this;

            const delay = (ms) => new Promise(resolve => setTimeout(resolve, ms));

            vm.$confirm({
                title: 'Yakin untuk meregister data ini?',
                content: 'Pastikan semua data sudah benar karena data yang sudah diregister tidak bisa diubah lagi!',
                okText: 'Yakin',
                okType: 'primary',
                cancelText: 'Batal',
                onOk: () => {
                    return delay(2000).then(() => {
                        vm.register();
                    });
                },
                onCancel: () => { },
            });
        },

        handleEmptyAcquisitionList(isEmpty) {
            this.isProcurementListEmpty = isEmpty;
        },

        handleEmptyDocumentList(isEmpty) {
            this.isProcurementDocumentEmpty = isEmpty;
        },

        handleEmptyDocumentDetail(isEmpty) {
            this.isProcurementDetailDocumentEmpty = isEmpty;
        },
    },
};
</script>
<template>

    <!-- HEADER -->
    <a-card
        class="p-5 shadow-xl rounded-t-2xl rounded-none mb-2 bg-gradient-to-br from-[#2a1459] via-[#3a1e75] to-[#1c1b3a] text-[#a0ffe0] border border-[#3f2e75]/40">
        <a-row class="flex flex-wrap items-start justify-between mb-4 pb-4 gap-y-4">
            <a-col :xs="24" :sm="24" :lg="22">
                <a-row class="flex flex-wrap gap-2 justify-start w-full lg:w-auto">
                    <a-col class="w-full lg:w-auto">
                        <h1 class="text-lg font-semibold flex items-center gap-2 text-[#00ffb3]">
                            {{ title }}
                        </h1>
                    </a-col>
                    <a-col class="w-full lg:w-auto">
                        <a-tag color="blue" class="flex justify-center !text-base !font-semibold !px-2 !py-0.5 w-full">
                            {{ parent.code }}
                        </a-tag>
                    </a-col>
                    <a-col class="w-full lg:w-auto">
                        <a-tag v-if="parent.branch_id" color="orange"
                            class="flex items-center justify-center !text-base !font-semibold !px-2 !py-0.5 gap-x-2 w-full">
                            <Icon icon="streamline-sharp-color:warehouse-1-flat" />
                            <span class="font-semibold truncate">
                                {{ parent.branch?.name }}
                            </span>
                        </a-tag>
                    </a-col>
                    <a-col class="w-full lg:w-auto">
                        <a-tag v-if="parent.supplier" color="green"
                            class="flex items-center justify-center !text-base !font-semibold !px-2 !py-0.5 gap-x-2 w-full">
                            <Icon icon="streamline-plump-color:factory-plant" />
                            <span class="font-semibold truncate">
                                {{ parent.supplier?.gl_code }} - {{ parent.supplier?.name }} ( {{ parent.supplier.is_ppn
                                    ? 'PPN 11%' : 'NON-PPN' }})
                            </span>
                        </a-tag>
                    </a-col>
                    <a-col class="w-full lg:w-auto">
                        <!-- STATUS DRAFT -->
                        <a-button v-if="parent.status === 'draft'" type="primary"
                            class="flex items-center justify-center w-full bg-red-500" style="pointer-events: none;">
                            <Icon icon="line-md:check-list-3" class="mr-1" />
                            <span class="font-semibold">
                                DRAFT
                            </span>
                        </a-button>
                        <!-- STATUS SUBMIITED -->
                        <a-button v-if="parent.status === 'submitted'" type="primary"
                            class="flex items-center justify-center w-full bg-red-500" style="pointer-events: none;">
                            <Icon icon="line-md:check-list-3" class="mr-1" />
                            <span class="font-semibold">
                                SUBMITTED - PEMBELIAN
                            </span>
                        </a-button>
                        <!-- STATUS APPROVED -->
                        <a-button v-if="parent.status === 'approved'" type="primary"
                            class="flex items-center justify-center w-full bg-red-500" style="pointer-events: none;">
                            <Icon icon="line-md:check-list-3" class="mr-1" />
                            <span class="font-semibold">
                                APPROVED - ADMIN KEUANGAN
                            </span>
                        </a-button>
                        <!-- STATUS PO_CREATED -->
                        <a-button v-if="parent.status === 'po_created'" type="primary"
                            class="flex items-center justify-center w-full bg-red-500" style="pointer-events: none;">
                            <Icon icon="line-md:check-list-3" class="mr-1" />
                            <span class="font-semibold">
                                PO DIBUAT - PEMBELIAN
                            </span>
                        </a-button>
                        <!-- STATUS RECEIVED -->
                        <a-button v-if="parent.status === 'received'" type="primary"
                            class="flex items-center justify-center w-full bg-red-500" style="pointer-events: none;">
                            <Icon icon="line-md:check-list-3" class="mr-1" />
                            <span class="font-semibold">
                                RECEIVED - ADMIN GUDANG
                            </span>
                        </a-button>
                        <!-- STATUS REGISTERED -->
                        <a-button v-else-if="parent.status === 'registered'" type="primary"
                            class="flex items-center justify-center w-full bg-green-500" style="pointer-events: none;">
                            <Icon icon="line-md:check-list-3" class="mr-1" />
                            <span class="font-semibold">
                                TEREGISTRASI
                            </span>
                        </a-button>
                    </a-col>
                </a-row>
            </a-col>
            <a-col :xs="24" :sm="24" :lg="2" class="flex justify-end">
                <a-row class="flex flex-wrap gap-2 justify-start md:justify-end w-full lg:w-auto">
                    <a-col v-if="parent.status === 'draft'" class="w-full lg:w-auto">
                        <a-button type="primary"
                            class="bg-[#00ffc6] hover:bg-[#00e6b0] text-[#0b0f1a] font-semibold border-0 shadow-[0_0_12px_rgba(0,255,198,0.4)] hover:shadow-[0_0_20px_rgba(0,255,198,0.6)] flex items-center justify-center transition-all duration-300 ease-in-out rounded-lg hover:scale-[1.03] active:scale-[0.97] w-full"
                            @click="submit()">
                            <Icon icon="streamline-ultimate-color:shopping-bag-check" class="mr-1" />
                            SUBMIT
                        </a-button>
                    </a-col>
                    <a-col v-else-if="parent.status === 'submitted'" class="w-full lg:w-auto">
                        <a-popconfirm title="Yakin ingin melakukan validasi / approve transaksi ini?"
                            @confirm="approve()">
                            <a-button type="success"
                                class="bg-[#00ffc6] hover:bg-[#00e6b0] text-[#0b0f1a] font-semibold border-0 shadow-[0_0_12px_rgba(0,255,198,0.4)] hover:shadow-[0_0_20px_rgba(0,255,198,0.6)] flex items-center justify-center transition-all duration-300 ease-in-out rounded-lg hover:scale-[1.03] active:scale-[0.97] w-full">
                                <Icon icon="streamline-ultimate-color:zip-file-check" class="mr-1" />
                                VALIDASI
                            </a-button>
                        </a-popconfirm>
                    </a-col>
                    <a-col v-else-if="parent.status === 'approved'" class="w-full lg:w-auto">
                        <a-popconfirm title="Yakin ingin register transaksi ini?" @confirm="register()">
                            <a-button type="primary"
                                class="bg-[#00ffc6] hover:bg-[#00e6b0] text-[#0b0f1a] font-semibold border-0 shadow-[0_0_12px_rgba(0,255,198,0.4)] hover:shadow-[0_0_20px_rgba(0,255,198,0.6)] flex items-center justify-center transition-all duration-300 ease-in-out rounded-lg hover:scale-[1.03] active:scale-[0.97] w-full">
                                <Icon icon="streamline-ultimate-color:zip-file-check" class="mr-1" />
                                REGISTER
                            </a-button>
                        </a-popconfirm>
                    </a-col>
                </a-row>
            </a-col>
        </a-row>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <h3
                    class="font-semibold text-sm uppercase tracking-wide text-[#00ffb3] border-b border-[#00ffb3]/20 pb-1 mb-2">
                    INFO
                </h3>
                <div class="space-y-1.5 text-sm">
                    <div class="flex justify-between">
                        <span class="text-white">Tanggal Perolehan (Barang Diterima)</span>
                        <a-tag class="font-medium text-[#00ffb3]">{{ models.receive_date ?
                            filterDate(models.receive_date) : 'Barang belum diterima'
                            }}</a-tag>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-white">Tanggal Pembukuan</span>
                        <a-tag class="font-medium text-[#00ffb3]">{{ models.book_date ? filterDate(models.book_date) :
                            'Barang belum diregister' }}</a-tag>
                    </div>
                </div>
            </div>
            <div>
                <h3
                    class="font-semibold text-sm uppercase tracking-wide text-[#00ffb3] border-b border-[#00ffb3]/20 pb-1 mb-2">
                    Transaksi
                </h3>
                <div class="space-y-1.5 text-sm">
                    <div class="flex justify-between">
                        <span class="text-white">Jenis Transaksi</span>
                        <a-tag class="font-medium text-[#00ffb3]">
                            {{ models.transaction_type ? `${models.transaction_type.code} -
                            ${models.transaction_type.name}` : '-' }}
                        </a-tag>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-white">Sumber Dana</span>
                        <a-tag class="font-medium text-[#00ffb3]">{{ models.funding_source || '-' }}</a-tag>
                    </div>
                </div>
            </div>
            <div>
                <h3
                    class="font-semibold text-sm uppercase tracking-wide text-[#00ffb3] border-b border-[#00ffb3]/20 pb-1 mb-2">
                    Dokumen
                </h3>
                <div class="space-y-1.5 text-sm">
                    <div class="flex justify-between">
                        <span class="text-white">Tanggal Dokumen</span>
                        <a-tag class="font-medium text-[#00ffb3]">{{ models.letter_date ? filterDate(models.letter_date)
                            : 'Belum diinput' }}</a-tag>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-white">Nomor Dokumen</span>
                        <a-tag class="font-medium text-[#00ffb3]">{{ models.letter_number || 'Belum diinput' }}</a-tag>
                    </div>
                </div>
            </div>
        </div>
    </a-card>

    <!-- MAIN CONTENT AREA -->
    <a-row :gutter="[8, 8]" class="mt-2">
        <!-- LEFT CONTENT -->
        <a-col :xs="24" :sm="24" :md="19">
            <a-card class="shadow-md rounded-b-2xl rounded-none h-full">
                <transition name="fade" mode="out-in">
                    <div :key="activeMenu">
                        <div v-if="activeMenu === 'list'">
                            <procurement-item-list :parent="parent" @emptyAcquisitionList="handleEmptyAcquisitionList"
                                ref="assetAcquisitionList" />
                        </div>
                        <div v-else-if="activeMenu === 'docs'">
                            <procurement-document :parent="parent" @dokumenEmpty="handleEmptyDocumentList"
                                @detailDokumenEmpty="handleEmptyDocumentDetail" ref="assetDocument" />
                        </div>
                        <div v-else>
                            <p class="text-gray-500 italic">
                                Pilih menu di kanan untuk melihat data.
                            </p>
                        </div>
                    </div>
                </transition>
            </a-card>
        </a-col>

        <!-- RIGHT MENU -->
        <a-col :xs="0" :sm="0" :md="5">
            <a-card class="shadow-md rounded-b-2xl rounded-none bg-gray-50 h-full" :body-style="{ padding: '0.75rem' }">
                <a-menu mode="inline" :selectedKeys="[activeMenu]" @select="({ key }) => setActive(key)"
                    class="rounded-xl overflow-hidden shadow-sm bg-white border-0">
                    <a-menu-item v-for="item in menuItems" :key="item.key"
                        class="!flex items-center hover:!bg-gradient-to-r hover:!from-indigo-50 hover:!to-purple-50 !rounded-lg transition-all duration-300">
                        <template #icon>
                            <Icon :icon="item.icon" class="mr-2 !text-2xl !text-purple-950" />
                        </template>
                        <span>{{ item.label }}</span>
                    </a-menu-item>
                </a-menu>
            </a-card>
        </a-col>

    </a-row>

    <!-- FLOATING MENU (Mobile only) -->
    <a-float-button class="md:hidden bg-yellow-500 !text-white shadow-lg h-[50px] w-[50px]"
        @click="drawerVisible = true">
    </a-float-button>

    <!-- DRAWER MENU (Mobile only) -->
    <a-drawer v-model:open="drawerVisible" title="Menu" placement="right" class="md:hidden"
        :body-style="{ padding: '0' }">
        <a-menu mode="inline" :selectedKeys="[activeMenu]"
            @select="({ key }) => { setActive(key); drawerVisible = false }" class="!border-0">
            <a-menu-item v-for="item in menuItems" :key="item.key" class="!p-0">
                <!-- Bungkus isi dengan div flex agar Tailwind pasti berlaku -->
                <div class="flex items-center w-full px-4 py-3 gap-3 hover:bg-gray-50 transition-colors rounded-md"
                    :class="{ 'bg-gray-100 font-medium': activeMenu === item.key }">
                    <Icon :icon="item.icon" class="flex-shrink-0 !text-xl text-gray-700" />
                    <span class="text-gray-800">{{ item.label }}</span>
                </div>
            </a-menu-item>
        </a-menu>
    </a-drawer>

</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.25s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
