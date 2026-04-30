<script>

export default {
    props: {
        parent: {
            type: Object,
            default: () => ({}),
        },
        constant: {
            type: Object,
            default: () => ({}),
        },
    },
    data() {
        return {};
    },

    mounted() {},

    methods: {
        // agar method dipanggil saat tab diklik
        handleTabClick(key) {

            this.$nextTick(() => {
                if (key === '1' && this.$refs.infoProfilAsetTetap) {
                    this.$refs.infoProfilAsetTetap?.readData();
                } else if (key === '2' && this.$refs.asetKondisi && this.$refs.asetPemanfaatan && this.$refs.asetPemeliharaan && this.$refs.asetHentiGuna) {
                    this.$refs.asetKondisi?.readData();
                    this.$refs.asetPemanfaatan?.readData();
                    this.$refs.asetPemeliharaan?.readData();
                    this.$refs.asetHentiGuna?.readData();
                } else if (key === '3' && this.$refs.riwayatDistribusi) {
                    this.$refs.riwayatDistribusi?.readData();
                } else if (key === '4' && this.$refs.kib) {
                    this.$refs.kib?.readData();
                } else if (key === '5' && this.$refs.dokumenAssetProfile) {
                    this.$refs.dokumenAssetProfile?.readListImage();
                    this.$refs.dokumenAssetProfile?.readListDocument();
                    this.$refs.dokumenAssetProfile?.readListFilePerolehan();
                    this.$refs.dokumenAssetProfile?.readListFilePerubahan();
                } else if (key === '6' && this.$refs.AssetTransaction) {
                    this.$refs.AssetTransaction?.readData();
                }
            });
        },
    },
};
</script>

<template>
    <a-card class="card">
        <template #title>
            <div class="flex items-center gap-x-2">
                <span>Profil Aset Tetap</span>
                <a-tooltip placement="bottomLeft" title="Profil aset ini telah dihapus di menu Penghapusan AT">
                    <a-button class="!flex items-center gap-x-0.5" type="danger" v-if="parent.tgl_penghapusan">
                        <Icon icon="line-md:document-delete-twotone" class="text-lg"/>
                        <span>Dihapus</span>
                    </a-button>
                </a-tooltip>
            </div>
        </template>
        
        <a-tabs @tabClick="handleTabClick">
            <a-tab-pane key="1" tab="Info Profil Aset Tetap">
                <info-profil-aset-tetap :parent="parent" ref="infoProfilAsetTetap"/>
            </a-tab-pane>
            <a-tab-pane key="2" tab="Perubahan">
                <a-row type="flex" justify="space-between">
                    <a-col :span="24" :lg="12" class="lg:pr-3 pb-6">
                        <aset-kondisi mode="card" :parent="parent" ref="asetKondisi"/>
                    </a-col>
                    <a-col :span="24" :lg="12" class="lg:pl-3 pb-6">
                        <aset-pemanfaatan mode="card" :parent="parent" :constant="constant" ref="asetPemanfaatan"/>
                    </a-col>
                    <a-col :span="24" :lg="12" class="lg:pr-3 pb-6">
                        <aset-pemeliharaan mode="card" :parent="parent" ref="asetPemeliharaan"/>
                    </a-col>
                    <a-col :span="24" :lg="12" class="lg:pl-3 pb-6">
                        <aset-henti-guna mode="card" :parent="parent" ref="asetHentiGuna"/>
                    </a-col>
                </a-row>
            </a-tab-pane>
            <a-tab-pane key="3" tab="Distribusi">
                <distribusi-aset :parent="parent" :constant="constant" ref="riwayatDistribusi" />
            </a-tab-pane>
            <a-tab-pane v-if="parent.jenis_kib === 'Gedung Bangunan'" key="4" tab="KIB (Kartu Identitas Barang)">
                <kib-gedung-bangunan :parent="parent" ref="kib"/>
            </a-tab-pane>
            <a-tab-pane v-if="parent.jenis_kib === 'Bangunan Air'" key="4" tab="KIB (Kartu Identitas Barang)">
                <kib-bangunan-air :parent="parent" ref="kib"/>
            </a-tab-pane>
            <a-tab-pane v-if="parent.jenis_kib === 'Alat Besar'" key="4" tab="KIB (Kartu Identitas Barang)">
                <kib-alat-besar :parent="parent" ref="kib"/>
            </a-tab-pane>
            <a-tab-pane v-if="parent.jenis_kib === 'Alat Angkutan'" key="4" tab="KIB (Kartu Identitas Barang)">
                <kib-alat-angkutan :parent="parent" ref="kib"/>
            </a-tab-pane>
            <a-tab-pane v-if="parent.jenis_kib === 'Alat Laboratorium'" key="4" tab="KIB (Kartu Identitas Barang)">
                <kib-alat-laboratorium :parent="parent" ref="kib"/>
            </a-tab-pane>
            <a-tab-pane v-if="parent.jenis_kib === 'Tanah'" key="4" tab="KIB (Kartu Identitas Barang)">
                <!-- kib tanah -->
            </a-tab-pane>
            <a-tab-pane v-if="parent.jenis_kib === 'Senjata'" key="4" tab="KIB (Kartu Identitas Barang)">
                <!-- kib senjata -->
            </a-tab-pane>
            <a-tab-pane key="5" tab="Dokumen">
                <dokumen-aset-profil :parent="parent" ref="dokumenAssetProfile"/>
            </a-tab-pane>
            <a-tab-pane key="6" tab="Transaksi">
                <aset-transaksi :parent="parent" ref="AssetTransaction"/>
            </a-tab-pane>
        </a-tabs>
    </a-card>
</template>