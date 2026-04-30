<script>

export default {
    props: {
        parent: {
            type: Object,
            default: () => ({}),
        },
    },

    mounted() {
        this.readData()
    },

    methods: {

        async readData() {
            const vm = this;
            vm.loadingTrue();
            const params = {
                id: vm.parent.id,
                req: "info_penghapusan",
            };

            const response = await vm.axios.get(vm.readRoute, { params });
            if (response && response.data) {
                vm.models = response.data.models
                vm.total = response.data.total
                vm.loadingFalse();
            }
        },
    }
};
</script>

<template>
      <a-row type="flex" justify="left" class="mb-4" :gutter="25">
         <!-- Kolom 1 -->
        <a-col :span="24" :lg="12" class="!flex flex-col">
            <div class="!w-full shadow-md rounded-[8px] overflow-hidden flex-grow lg:mb-0 mb-6">
                <div class="bg-[#77828c] text-white text-lg px-6 py-2 font-medium">
                    Info Penghapusan
                </div>
                <a-card class="card card-white !px-5 h-full">
                    <a-form ref="form" name="info" :label-col="{ span: 9 }" :wrapper-col="{ span: 15 }">
                        <a-form-item label="Satuan Kerja" label-align="left" :colon="false" 
                            class="border-b-[1.5px] !mb-2 !px-1.5">
                            {{ models.satker?.nama }}
                        </a-form-item>
                        <a-form-item label="Tanggal Buku" label-align="left" :colon="false" 
                            class="border-b-[1.5px] !mb-2 !px-1.5">
                            {{ models.created_at ? filterDate(models.created_at) : '' }}
                        </a-form-item>
                        <a-form-item label="Tanggal Penghapusan" label-align="left" :colon="false" 
                            class="border-b-[1.5px] !mb-2 !px-1.5">
                            {{ models.tgl_penghapusan ? filterDate(models.tgl_penghapusan) : '' }}
                        </a-form-item>
                        <a-form-item label="Catatan" label-align="left" :colon="false" 
                            class="border-b-[1.5px] !mb-2 !px-1.5">
                            {{ models.notes }}
                        </a-form-item>
                    </a-form>
                </a-card>
            </div>
        </a-col>

         <!-- Kolom 2 -->
         <a-col :span="24" :lg="12" class="!flex flex-col">
            <div class="mb-6">
                <div class="!w-full shadow-md rounded-[8px] overflow-hidden flex-grow lg:mb-0">
                    <div class="bg-[#77828c] text-white text-lg px-6 py-2 font-medium">
                        Transaksi
                    </div>
                    <a-card class="card card-white !px-5">
                        <a-form ref="form" name="info" :label-col="{ span: 9 }" :wrapper-col="{ span: 15 }">
                            <a-form-item label="Jenis Transaksi" label-align="left" :colon="false" 
                                class="border-b-[1.5px] !mb-2 !px-1.5">
                                {{ models.jenis_transaksi ? `${models.jenis_transaksi.kode} - ${models.jenis_transaksi.uraian}` : ''}}
                            </a-form-item>
                        </a-form>
                    </a-card>
                </div>
            </div>
            <div class="flex-grow">
                <div class="shadow-md rounded-[8px] overflow-hidden h-full">
                    <div class="bg-[#77828c] text-white text-lg px-6 py-2 font-medium">
                        Dokumen
                    </div>
                    <a-card class="card card-white !px-5">
                        <a-form ref="form" name="info" :label-col="{ span: 9 }" :wrapper-col="{ span: 15 }">
                            <a-form-item label="Tanggal Dokumen" label-align="left" :colon="false" 
                                class="border-b-[1.5px] !mb-2 !px-1.5">
                                {{ models.tgl_surat ? filterDate(models.tgl_surat) : '' }}
                            </a-form-item>
                            <a-form-item label="Nomor Dokumen" label-align="left" :colon="false" 
                                class="border-b-[1.5px] !mb-2 !px-1.5">
                                {{ models.no_surat }}
                            </a-form-item>
                        </a-form>
                    </a-card>
                </div>
            </div>
        </a-col>
    </a-row>
</template>