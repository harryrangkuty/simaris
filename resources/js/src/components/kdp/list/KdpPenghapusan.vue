<script>

export default {
    props: {
        parent: {
            type: Object,
            default: () => ({}),
        },
        mode: {
            type: String,
            default: 'Card'
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
                req: "info_profil",
                ...vm.filter,
            };

            const response = await vm.axios.get(vm.readRoute, { params })
            if (response && response.data) {
                vm.models = response.data.models;
                vm.total = response.data.total;
                vm.loadingFalse();
            }
        },

        async deleteData() {
            const vm = this;
            vm.loadingTrue()
            const form = { 
                req: 'hapus_kdp', 
                id: vm.parent.id
            };
            const response = await vm.axios.post(vm.writeRoute, form).catch((e) => vm.$onAjaxError(e));
            if (response && response.data) {
                vm.openNotification('Berhasil menghapus KDP', 'success');
                setTimeout(() => {
                    const baseUrl = window.location.href.split('?')[0];
                    window.location.href = baseUrl;
                }, 1500);
                vm.loadingFalse()
            }
        },
    },
};
</script>

<template>
    <div class="shadow-md rounded-lg overflow-hidden">
        <div class="bg-[#77828c] text-white text-lg px-6 py-2 font-medium">
            Penghapusan
        </div>
        <a-card class="card card-white !px-5 !h-full">
            <div class="flex flex-wrap justify-start md:justify-end mb-4 items-center w-full">
                <a-popconfirm title="Yakin menghapus KDP?"
                    @confirm="deleteData()">
                    <a-button type="primary" class="mb-4 w-full sm:w-auto" :disabled="loadingStatus">Hapus</a-button>
                </a-popconfirm>      
            </div>
            <h4 class="text-md font-semibold mb-6">Data KDP yang akan dihapus</h4>
            <a-form ref="form" name="info" :label-col="{ span: 9 }" :wrapper-col="{ span: 15 }">
                <a-form-item label="NUP" label-align="left" :colon="false" 
                    class="border-b-[1.5px] !mb-2 !px-1.5">
                    {{ models.nup }}
                </a-form-item>
                <a-form-item label="Deskripsi" label-align="left" :colon="false" 
                    class="border-b-[1.5px] !mb-2 !px-1.5">
                    {{ models.deskripsi }}
                </a-form-item>
                <a-form-item label="Jenis Perolehan" label-align="left" :colon="false" 
                    class="border-b-[1.5px] !mb-2 !px-1.5">
                    {{ models.jenis_perolehan ? `${models.jenis_perolehan.kode} - ${models.jenis_perolehan.uraian}` : ''}}
                </a-form-item>
                <a-form-item label="Tanggal Perolehan" label-align="left" :colon="false" 
                    class="border-b-[1.5px] !mb-2 !px-1.5">
                    {{ models.tgl_perolehan ? filterDate(models.tgl_perolehan) : '' }}
                </a-form-item>
                <a-form-item label="Nilai Buku Akhir (Rp)" label-align="left" :colon="false" 
                    class="border-b-[1.5px] !mb-2 !px-1.5">
                    <div class="text-right">{{ idCurrency(models.saldo_akhir) }}</div>
                </a-form-item>
            </a-form>
        </a-card>
    </div>
</template>