<script>

const menuItems = [
    { key: "pj_info", label: "Info PJ", icon: "streamline-cyber-color:person" },
    { key: "detail", label: "Detail ALKES", icon: "fluent-color:apps-list-detail-20" },
    { key: "location", label: "Lokasi ALKES", icon: "streamline-flex-color:map-location-flat" },
    { key: "condition", label: "Kondisi", icon: "material-symbols:conditions-rounded" },
    { key: "maintenance_history", label: "Riwayat Pemeliharaan", icon: "grommet-icons:host-maintenance" },
    { key: "usage", label: "Pemakaian ALKES", icon: "fluent:data-usage-edit-20-regular" },
    { key: "po_data", label: "Data PO IIS", icon: "streamline-emojis:money-bag" },
    { key: "movement_history", label: "Riwayat Perpindahan", icon: "fluent-color:history-32" },
    { key: "qr_code_no", label: "Preview QR Code", icon: "streamline-sharp-color:qr-code-flat" },
    { key: "documentation", label: "Dokumentasi", icon: "flat-color-icons:photo-reel" },
    { key: "data_change_history", label: "Riwayat Perubahan Data", icon: "material-icon-theme:dependencies-update" },
];

export default {
    name: "AssetDetail",

    props: {
        title: String,
        parent: {
            type: Object,
            required: true,
        },
        constant: {
            type: Object,
            default: () => ({}),
        },
    },

    data() {
        return {
            editingField: null,
            menuItems,
            activeMenu: "pj_info",
            drawerVisible: false,
        };
    },

    mounted() {
        this.readData();
    },

    computed: {
        breadcrumbItems() {
            return [
                { label: 'Dashboard', link: '/', icon: 'bi:grid' },
                { label: 'Daftar ALKES IIS', link: '/iis/alkes-list', icon: 'line-md:folder-multiple-filled' },
                { label: `${this.title}`, icon: 'line-md:folder-multiple-filled' }
            ]
        },
    },


    methods: {

        async readData() {
            const vm = this;
            vm.loadingTrue();

            const params = {
                req: "info_alkes",
                id: vm.parent.id
            };

            const response = await vm.axios.get(vm.readRoute, { params })
            if (response && response.data) {
                vm.models = response.data.models;
                vm.loadingFalse();
            }
        },

        async handleSave({ id, field, value, done }) {
            this.loadingTrue()

            try {
                const payload = {
                    req: 'write_detail',
                    id,
                    field,
                    value,
                }

                const response = await this.axios.post(this.writeRoute, payload)

                if (response?.data?.success) {
                    this.openNotification('Data berhasil diperbarui', 'success')
                    await this.readData()
                    done && done()
                }
            } catch (e) {
                this.$onAjaxError(e)
            } finally {
                this.loadingFalse()
            }
        },

        async handleSaveLocation({ id, field, value, done }) {
            this.loadingTrue()

            try {
                const payload = {
                    req: 'write_detail_location',
                    id,
                    field,
                    value,
                }

                const response = await this.axios.post(this.writeRoute, payload)

                if (response?.data?.success) {
                    this.openNotification('Data lokasi berhasil diperbarui', 'success')
                    await this.readData()
                    done && done()
                }
            } catch (e) {
                this.$onAjaxError(e)
            } finally {
                this.loadingFalse()
            }
        },

        setActive(key) {
            this.activeMenu = key;
        },
    },
};
</script>

<template>
    <Breadcrumb :items="breadcrumbItems" :showBackButton="true" />
    <a-space direction="vertical" size="small" class="w-full">
        <!-- HEADER -->
        <a-card class="relative
            md:px-4 md:py-1
            overflow-hidden
            rounded-t-2xl rounded-b-none
            border border-violet-400/30
            bg-gradient-to-br
            from-[#2b1665] via-[#4b2ea7] to-[#0f102a]
            shadow-[0_20px_50px_-12px_rgba(124,58,237,0.45)] lg:mb-0 -mb-1">

            <!-- glow layer -->
            <div class="absolute -top-32 -right-32
                w-60 h-60 lg:w-80 lg:h-80
                bg-fuchsia-500/30 rounded-full blur-3xl"></div>

            <div class="absolute -bottom-32 -left-32
                w-60 h-60 lg:w-80 lg:h-80
                bg-cyan-400/20 rounded-full blur-3xl"></div>

            <a-row align="middle" justify="space-between" gutter="[12, 8]">

                <!-- LEFT INFO -->
                <a-col :xs="24" :lg="18">
                    <div class="flex flex-col gap-1">

                        <a-tag v-if="parent.branch_id" class="
                            backdrop-blur-md
                            bg-gradient-to-r from-violet-500/30 via-fuchsia-500/25 to-cyan-400/25
                            border border-white/20
                            text-white
                            shadow-[0_0_18px_rgba(168,85,247,0.35)]
                            flex items-center gap-2
                            px-3 py-1
                            rounded-full
                            w-full lg:w-fit
                            mb-2.5 lg:mb-2
                        ">
                            <Icon icon="streamline-sharp-color:warehouse-1-flat" class="text-lg" />
                            <span class="font-semibold text-sm truncate max-w-[220px]">
                                {{ parent.branch?.name }}
                            </span>
                        </a-tag>

                        <div class="flex items-center gap-2 flex-wrap">
                            <h1 class="font-semibold tracking-wide
                                text-base lg:text-xl
                                text-transparent bg-clip-text
                                bg-gradient-to-r from-emerald-300 via-cyan-300 to-teal-200">
                                {{ title || parent.description }}
                            </h1>

                            <!-- ITEM TAG -->
                            <a-tag v-if="parent.item" class="bg-gradient-to-r from-emerald-300 via-cyan-300 to-teal-200
                                text-slate-900
                                flex items-center gap-1
                                px-3 py-1
                                rounded-lg
                                text-xs lg:text-sm
                                w-full lg:w-fit
                                mb-2 lg:mb-0">
                                <Icon icon="mdi:cube-outline" class="text-sm" />
                                {{ parent.item?.code }} - {{ parent.item?.name }}
                            </a-tag>
                        </div>

                        <div class="text-xs lg:text-sm
                            text-cyan-200/80
                            flex items-center gap-2 lg:gap-3 flex-wrap">

                            <span class="flex items-center gap-1">
                                <Icon icon="mdi:shape-outline" />
                                {{ parent.item_no ? parent.item_no : parent.item_no_legacy }}
                            </span>

                            <span class="w-1 h-1 rounded-full bg-cyan-300/50"></span>

                            <span class="flex items-center gap-1">
                                <Icon icon="mdi:barcode-scan" />
                                {{ parent.qr_code_no }}
                            </span>
                        </div>
                    </div>
                </a-col>

                <!-- RIGHT META -->
                <a-col :xs="24" :lg="6" class="mt-4">
                    <div class="flex flex-col items-start lg:items-end gap-2">

                        <!-- PJ -->
                        <a-tag class="backdrop-blur-md bg-white/10
                            border border-white/20
                            text-cyan-200
                            flex items-center gap-1
                            text-xs lg:text-sm
                            px-2 lg:px-3 py-0.5 lg:py-1
                            rounded-full w-full lg:w-fit">
                            <Icon icon="mdi:account-tie-outline" />
                            <span class="opacity-70">PJ</span>:
                            <b class="text-white">
                                {{ parent.b_user ? `${parent.b_user.identifier} - ${parent.b_user.name}` : '-' }}
                            </b>
                        </a-tag>

                        <!-- STATUS SERAH TERIMA -->
                        <a-tag class="flex items-center gap-1.5 px-3 py-1.5 text-xs lg:text-sm
                            rounded-full font-medium border-0 shadow-sm
                            transition-all duration-300 hover:scale-105" :class="parent.is_handed_over
                                ? 'bg-gradient-to-r from-emerald-500 to-green-400 text-white shadow-emerald-500/30'
                                : parent.is_handover_active
                                    ? 'bg-gradient-to-r from-amber-500 to-yellow-400 text-white shadow-amber-500/30'
                                    : 'bg-gradient-to-r from-rose-500 to-red-400 text-white shadow-red-500/30'">
                            <Icon :icon="parent.is_handed_over
                                ? 'line-md:circle-to-confirm-circle-transition'
                                : parent.is_handover_active
                                    ? 'line-md:loading-loop'
                                    : 'line-md:close-circle'" class="text-base" />

                            <span>
                                {{
                                    parent.is_handed_over
                                        ? 'Sudah Serah Terima'
                                        : parent.is_handover_active
                                            ? 'Sedang Proses Serah Terima'
                                            : 'Belum Serah Terima'
                                }}
                            </span>

                            <span v-if="parent.handover_code"
                                class="ml-1 text-[10px] lg:text-xs bg-black/30 px-2 py-0.5 rounded">
                                Kode : {{ parent.handover_code }}
                            </span>
                        </a-tag>

                        <!-- PRINT STATUS -->
                        <div class="relative flex items-center
                            gap-3
                            px-3 lg:px-4
                            py-2 lg:py-3
                            rounded-xl
                            text-xs lg:text-sm
                            backdrop-blur-md border
                            w-full lg:w-fit" :class="parent.print_count > 0
                                ? 'bg-emerald-500/15 text-emerald-300 border-emerald-400/30'
                                : 'bg-rose-500/15 text-rose-300 border-rose-400/30'">

                            <!-- ICON -->
                            <div class="flex items-center justify-center
                                w-8 h-8
                                rounded-full
                                bg-white/10">

                                <Icon :icon="parent.print_count > 0
                                    ? 'line-md:check-all'
                                    : 'line-md:close-circle'" class="text-lg" />
                            </div>

                            <!-- TEXT -->
                            <div class="flex flex-col leading-tight space-y-0.5">

                                <template v-if="parent.print_count > 0">
                                    <div class="font-medium">
                                        QR Code sudah dicetak {{ parent.print_count }}x
                                    </div>

                                    <div class="text-[11px] lg:text-xs opacity-80">
                                        oleh <b>{{ parent.last_print_by?.name }}</b>
                                    </div>

                                    <div class="text-[10px] lg:text-xs opacity-60">
                                        {{ parent.last_print_at }}
                                    </div>
                                </template>

                                <template v-else>
                                    <div class="font-medium">
                                        QR Code belum pernah dicetak
                                    </div>
                                </template>

                            </div>
                        </div>

                    </div>
                </a-col>

            </a-row>
        </a-card>

        <!-- MAIN CONTENT AREA -->
        <a-row :gutter="[8, 8]">
            <!-- LEFT CONTENT -->
            <a-col :xs="24" :sm="24" :lg="19">
                <a-card class="shadow-md rounded-b-2xl rounded-t-none h-full">
                    <transition name="fade" mode="out-in">
                        <div :key="activeMenu">
                            <!-- MENU PJ -->
                            <AlkesPJ v-if="activeMenu === 'pj_info'" :model-data="models" @save="handleSave" />

                            <!-- MENU Detail -->
                            <AlkesDetail v-if="activeMenu === 'detail'" :model-data="models" @save="handleSave" />

                            <!-- MENU LOKASI -->
                            <AlkesLocation v-if="activeMenu === 'location'" :model-data="models" :constant="constant"
                                @save="handleSaveLocation" />

                            <!-- MENU KONDISI -->
                            <AlkesCondition v-if="activeMenu === 'condition'" :model-data="models" @save="handleSave" />

                            <!-- MENU MAINTENANCE -->
                            <AlkesMaintenance v-if="activeMenu === 'maintenance'" :model-data="models" />

                            <!-- MENU PEMAKAIAN -->
                            <AlkesUsage v-if="activeMenu === 'usage'" :model-data="models" />

                            <!-- MENU QR CODE -->
                            <AlkesQr v-if="activeMenu === 'qr_code_no'" :model-data="models" />

                            <!-- MENU PERUBAHAN DATA -->
                            <InventoryDataChange v-if="activeMenu === 'data_change_history'" :model-data="models" />

                        </div>
                    </transition>
                </a-card>
            </a-col>

            <!-- RIGHT MENU -->
            <a-col :xs="0" :sm="0" :lg="5">
                <a-card class="shadow-md rounded-b-2xl  rounded-t-none bg-violet-50 h-full"
                    :body-style="{ padding: '0.5rem' }">
                    <a-menu mode="inline" :selectedKeys="[activeMenu]" @select="({ key }) => setActive(key)">
                        <a-menu-item v-for="item in menuItems" :key="item.key" class="!flex items-center">
                            <template #icon>
                                <Icon :icon="item.icon" class="mr-2 !text-xl !text-purple-950" />
                            </template>
                            <span>{{ item.label }}</span>
                        </a-menu-item>
                    </a-menu>
                </a-card>
            </a-col>
        </a-row>

        <!-- FLOATING MENU MOBILE -->
        <a-float-button class="lg:hidden !bottom-24" type="primary" description="Menu"
            :style="{ backgroundColor: '#ef4444' }" @click="drawerVisible = true" />
        <a-drawer v-model:open="drawerVisible" title="Menu" placement="right" class="md:hidden">
            <a-menu mode="inline" :selectedKeys="[activeMenu]"
                @select="({ key }) => { setActive(key); drawerVisible = false }">
                <a-menu-item v-for="item in menuItems" :key="item.key">
                    <div class="flex items-center w-full gap-3 px-4 py-3"
                        :class="{ 'bg-gray-100 font-medium': activeMenu === item.key }">
                        <Icon :icon="item.icon" class="flex-shrink-0 !text-xl text-gray-700" />
                        <span class="text-gray-800">{{ item.label }}</span>
                    </div>
                </a-menu-item>
            </a-menu>
        </a-drawer>
    </a-space>
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
