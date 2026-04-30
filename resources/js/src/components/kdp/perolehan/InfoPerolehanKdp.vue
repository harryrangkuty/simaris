<script>

export default {
    props: {
        parent: {
            type: Object,
            default: () => ({}),
        },
    },
    data() {
        return {};
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
                req: "info_perolehan",
                ...vm.filter,
            };

            const response = await vm.axios.get(vm.readRoute, { params })
            if (response && response.data) {
                vm.models = response.data.models
                vm.total = response.data.total
                vm.loadingFalse();
            }
        },
    },
};
</script>

<template>
    <a-row type="flex" justify="left" class="mb-4" :gutter="25">
        <!-- Kolom 1 -->
        <a-col :span="24" :lg="12" class="!flex flex-col">
            <div class="!w-full shadow-md rounded-[8px] overflow-hidden flex-grow mb-6">
                <div class="bg-[#77828c] text-white text-lg px-6 py-2 font-medium">
                    Info KDP
                </div>
                <a-card class="card card-white !px-5 h-full">
                    <a-form ref="form" name="info" :label-col="{ span: 9 }" :wrapper-col="{ span: 15 }">
                        <a-form-item label="Satuan Kerja" label-align="left" :colon="false" 
                            class="border-b-[1.5px] !mb-2 !px-1.5">
                            {{ models.satker?.nama }}
                        </a-form-item>
                        <a-form-item label="Kategori" label-align="left" :colon="false" 
                            class="border-b-[1.5px] !mb-2 !px-1.5">
                            {{ models.kategori_id ? `${models.kategori_id} - ${models.kategori_nama}` : ''}}
                        </a-form-item>
                        <a-form-item label="Deskripsi" label-align="left" :colon="false" 
                            class="border-b-[1.5px] !mb-2 !px-1.5">
                            {{ models.deskripsi }}
                        </a-form-item>
                        <a-form-item label="Alamat" label-align="left" :colon="false" 
                            class="border-b-[1.5px] !mb-2 !px-1.5">
                            {{ models.alamat }}
                        </a-form-item>
                        <a-form-item label="Kondisi" label-align="left" :colon="false" 
                            class="border-b-[1.5px] !mb-2 !px-1.5">
                            <span v-if="!loadingStatus" 
                                :style="{ backgroundColor: (models.kondisi == 1 ? 'green' : (models.kondisi == 2 ? 'orange' : 'red')) }"
                                class="px-3 py-1 rounded text-white">
                                {{ labelKondisi(models.kondisi, false) }}
                            </span>
                        </a-form-item>
                    </a-form>
                </a-card>
            </div>
            
            <div class="!w-full shadow-md rounded-[8px] overflow-hidden flex-grow mb-6">
                <div class="bg-[#77828c] text-white text-lg px-6 py-2 font-medium">
                    Info Perolehan
                </div>
                <a-card class="card card-white !px-5 h-full">
                    <a-form ref="form" name="info" :label-col="{ span: 9 }" :wrapper-col="{ span: 15 }">
                        <a-form-item label="Tanggal Perolehan" label-align="left" :colon="false" 
                            class="border-b-[1.5px] !mb-2 !px-1.5">
                            {{ models.tgl_perolehan ? filterDate(models.tgl_perolehan) : '' }}
                        </a-form-item>
                        <a-form-item label="Jenis Perolehan" label-align="left" :colon="false" 
                            class="border-b-[1.5px] !mb-2 !px-1.5">
                            {{ models.jenis_perolehan ? `${models.jenis_perolehan.kode} - ${models.jenis_perolehan.uraian}` : ''}}
                        </a-form-item>
                    </a-form>
                </a-card>
            </div>
        </a-col>

        <!-- Kolom 2 -->
        <a-col :span="24" :lg="12" class="!flex flex-col">
            <div class="!w-full shadow-md rounded-[8px] overflow-hidden flex-grow mb-6">
                <div class="bg-[#77828c] text-white text-lg px-6 py-2 font-medium">
                    Info Kontrak
                </div>
                <a-card class="card card-white !px-5 h-full">
                    <a-form ref="form" name="info" :label-col="{ span: 9 }" :wrapper-col="{ span: 15 }">
                        <a-form-item label="No Kontrak" label-align="left" :colon="false" 
                            class="border-b-[1.5px] !mb-2 !px-1.5">
                            {{ models.no_kontrak }}
                        </a-form-item>
                        <a-form-item label="Nilai Kontrak (Rp)" label-align="left" :colon="false" 
                            class="border-b-[1.5px] !mb-2 !px-1.5">
                            <div class="text-right">{{ idCurrency(models.nilai_kontrak) }}</div>
                        </a-form-item>
                        <a-form-item label="Nama Kontrak" label-align="left" :colon="false" 
                            class="border-b-[1.5px] !mb-2 !px-1.5">
                            {{ models.nama_kontrak }}
                        </a-form-item>
                        <a-form-item label="Alamat Kontrak" label-align="left" :colon="false" 
                            class="border-b-[1.5px] !mb-2 !px-1.5">
                            {{ models.alamat_kontrak }}
                        </a-form-item>
                        <a-form-item label="Catatan Kontraktor" label-align="left" :colon="false" 
                            class="border-b-[1.5px] !mb-2 !px-1.5">
                            {{ models.notes }}
                        </a-form-item>
                    </a-form>
                </a-card>
            </div>

            <div class="!w-full shadow-md rounded-[8px] overflow-hidden flex-grow mb-6">
                <div class="bg-[#77828c] text-white text-lg px-6 py-2 font-medium">
                    Dokumen
                </div>
                <a-card class="card card-white !px-5 h-full">
                    <a-form ref="form" name="info" :label-col="{ span: 9 }" :wrapper-col="{ span: 15 }">
                        <a-form-item label="No Dokumen Utama" label-align="left" :colon="false" 
                            class="border-b-[1.5px] !mb-2 !px-1.5">
                            {{ models.no_surat }}
                        </a-form-item>
                        <a-form-item label="Tanggal Dokumen" label-align="left" :colon="false" 
                            class="border-b-[1.5px] !mb-2 !px-1.5">
                            {{ models.tgl_surat ? filterDate(models.tgl_surat) : '' }}
                        </a-form-item>
                    </a-form>
                </a-card>
            </div>
        </a-col>
    </a-row>
</template>
