<script>

const columns = [
    {
        title: "#",
        key: "number",
        align: "center",
        width: 10,
    },
    {
        title: "Tanggal Distribusi",
        key: "created_at",
        dataIndex: 'created_at',
    },
    {
        title: "Kode Distribusi",
        key: "code",
        dataIndex: 'code',
    },
    {
        title: "Satuan Kerja",
        dataIndex: ["satker", "nama"],
    },
    {
        title: "Tipe Ruang",
        key: "tipe_ruang",
        dataIndex: 'tipe_ruang',
    },
    {
        title: "Nama Ruang",
        key: "ruang_nama",
    },
    {
        title: "Keterangan Lokasi",
        key: "keterangan_lokasi",
        dataIndex: 'keterangan_lokasi',
    },
    {
        title: "Catatan",
        key: "catatan",
        dataIndex: 'catatan',
    },
];

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
        return {
            columns,
            form: {
                tipe_ruang: null,
                ruang_id: null,
                catatan: null,
                keterangan_lokasi: null,
                satker_id: null,
            },
            distributionType: null,
        };
    },

    mounted() {
        this.readData();
    },

    watch: {
        distributionType() {
            this.resetForm();
        },
        'form.tipe_ruang'() {
            this.form.ruang_id = null;
            this.form.catatan = null;
            this.form.keterangan_lokasi = null;
        }
    },

    computed: {
        filteredRuangOptions() {
            return this.constant.RUANG.filter(ruang => ruang.satker_id === this.parent.satker_id);
        },
        hasJenisKib() {
            return this.parent.jenis_kib && !["Alat Laboratorium", "Alat Angkutan", "Senjata"].includes(this.parent.jenis_kib);
        }
    },

    methods: {

        async readData() {
            const vm = this;
            vm.loadingTrue();
            const params = {
                id: vm.parent.id,
                req: "distribution_history",
                ...vm.filter,
            };

            const response = await vm.axios.get(vm.readRoute, { params })
            if (response && response.data) {
                vm.models = response.data.models
                vm.total = response.data.total
                vm.loadingFalse();
            }
        },

        resetForm() {
            this.form = {
                tipe_ruang: null,
                ruang_id: null,
                catatan: null,
                keterangan_lokasi: null,
                satker_id: null,
            };
        },

        async distribute() {
            const vm = this;

            const form = {
                req: 'distribute_single',
                id: vm.parent.id,
                ...vm.form
            };

            const response = await vm.axios.post(vm.writeRoute, form).catch((e) => vm.$onAjaxError(e));
            if (response && response.data) {
                vm.showModal = false;
                if (vm.distributionType === 'satker') {
                    vm.openNotification('Berhasil mendistribusikan barang, refreshing page...', 'success');
                    setTimeout(() => {
                        const baseUrl = window.location.href.split('?')[0];
                        window.location.href = baseUrl;
                    }, 3000);
                } else {
                    vm.openNotification('Berhasil mendistribusikan barang ...', 'success');
                    vm.readData();
                }
            }
        },
    },
};
</script>

<template>
    <div class="!w-full !h-full shadow-md rounded-[8px] overflow-hidden">
        <div class="flex justify-between bg-white px-6 py-2">
            <span class="font-bold text-lg">Distribusi</span>
            <a-popconfirm v-if="operator_aset && distributionType" title="Yakin distribusi?" @confirm="distribute()">
                <a-button type="primary" :disabled="loadingStatus"> Distribusi</a-button>
            </a-popconfirm>
        </div>
        <a-card class="card card-white !px-5" style="height: 100%;">
            <div v-if="operator_aset" class="mb-7">
                <a-radio-group v-model:value="distributionType" button-style="solid" class="w-full">
                    <a-tooltip placement="topLeft" :title="!operator_aset
                            ? 'Tidak Dapat Mendistribusikan Aset Karena Anda bukan Operator Aset'
                            : (parent.tgl_penghapusan ? 'Aset ini telah dihapus' : null)
                        "> 
                        <a-radio-button value="ruang" :disabled="hasJenisKib || !operator_aset || parent.tgl_penghapusan"
                            class="w-full sm:w-auto mb-2 sm:mb-0">Distribusi Ruang</a-radio-button>
                    </a-tooltip>
                    <a-tooltip placement="topLeft" :title="!operator_aset
                            ? 'Tidak Dapat Mendistribusikan Aset Karena Anda bukan Operator Aset'
                            : (parent.tgl_penghapusan ? 'Aset ini telah dihapus' : null)
                        "> 
                        <a-radio-button value="satker" :disabled="!operator_aset || parent.tgl_penghapusan"
                            class="w-full sm:w-auto">Distribusi Satker</a-radio-button>
                    </a-tooltip>
                </a-radio-group>
            </div>
            <a-form ref="form" name="basic">
                <!-- Jika pilihan distribusi adalah Distribusi Ruang -->
                <template v-if="distributionType === 'ruang'">
                    <a-row type="flex">
                        <a-col :span="24" :md="8" class="px-3">
                            <a-form-item label="Tipe Ruang" data-column="tipe_ruang" :rules="[{ required: true }]">
                                <a-select v-model:value="form.tipe_ruang" placeholder="--Pilih Tipe Ruang--">
                                    <a-select-option value="DBR">DBR</a-select-option>
                                    <a-select-option value="DBL">DBL</a-select-option>
                                </a-select>
                            </a-form-item>
                        </a-col>
                        <a-col v-if="form.tipe_ruang == 'DBR'" :span="24" :md="8" class="px-3">
                            <a-form-item label="Ruang" data-column="ruang_id" :rules="[{ required: true }]">
                                <a-select v-model:value="form.ruang_id" placeholder="--Pilih Ruang--" show-search
                                    option-filter-prop="search" :getPopupContainer="(trigger) => trigger.parentNode">
                                    <a-select-option :key="ruang.id" v-for="ruang in filteredRuangOptions"
                                        :value="ruang.id" :title="ruang.nama" :search="ruang.kode + ' ' + ruang.nama">
                                        {{ ruang.kode }} - {{ ruang.nama }}
                                    </a-select-option>
                                </a-select>
                            </a-form-item>
                        </a-col>
                        <a-col v-if="form.tipe_ruang == 'DBL'" :span="24" :md="8" class="px-3">
                            <a-form-item label="Keterangan Lokasi" data-column="keterangan_lokasi"
                                :rules="[{ required: true }]">
                                <a-textarea v-model:value="form.keterangan_lokasi" :rows="2"
                                    placeholder="Masukkan keterangan lokasi"></a-textarea>
                            </a-form-item>
                        </a-col>
                        <a-col :span="24" :md="8" class="px-3">
                            <a-form-item label="Catatan" data-column="catatan">
                                <a-textarea v-model:value="form.catatan" :rows="2" placeholder="Catatan"></a-textarea>
                            </a-form-item>
                        </a-col>
                    </a-row>
                </template>
                <!-- Jika pilihan distribusi adalah Distribusi Satker -->
                <template v-else-if="distributionType === 'satker'">
                    <a-form-item label="Satker Tujuan" data-column="satker_id" :rules="[{ required: true }]">
                        <template v-if="parent.satker_id === 4">
                            <a-select v-model:value="form.satker_id" placeholder="--Pilih Satuan Kerja--" show-search
                                option-filter-prop="title">
                                <a-select-option :key="satker.id" v-for="satker in constant.DISTRIBUTESATKER"
                                    :value="satker.id" :title="satker.nama">
                                    {{ satker.nama }}
                                </a-select-option>
                            </a-select>
                        </template>
                        <template v-else>
                            <span>Biro Pengelolaan Aset dan Usaha</span>
                            <span v-text="form.satker_id = 4" style="display: none;"></span>
                        </template>
                    </a-form-item>
                </template>
            </a-form>
            <a-table :scroll="{ x: 800 }" :columns="columns" :row-key="(obj) => obj.id" :pagination="false"
                :loading="loadingStatus" :data-source="models" @change="handleTableChange">
                <template #bodyCell="{ index, column, record }">
                    <template v-if="column.key === 'number'">
                        {{ index + 1 }}
                    </template>
                    <template v-if="column.key === 'created_at'">
                        {{ filterDate(record.created_at) }}
                    </template>
                    <template v-if="column.key === 'ruang_nama'">
                        {{ record.ruang_nama }}
                    </template>
                </template>
            </a-table>
        </a-card>
    </div>
</template>