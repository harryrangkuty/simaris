<script>

const columns = [
  {
    title: "#",
    key: "number",
    align: "center",
    width: 10,
  },
  {
    title: 'Tipe',
    key: 'tipe',
    align: "center"
  },
  {
    title: 'Tanggal Transaksi',
    key: 'tgl_transaksi',
  },
  {
    title: 'Nilai',
    key: 'biaya',
    align: "right"
  },
  {
    title: 'Nilai Buku',
    key: 'nilai_buku',
    align: "right"
  },
  {
    title: 'File',
    key: 'file',
    align: "center"
  },
];

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
        return {
            columns,
            selectedFileName: null
        };
    },

    mounted() {
        this.readData()
    },

    methods: {

        async readData() {
            const vm = this;
            vm.loadingTrue();
            const params = {
                id: this.parent.id,
                req: "kdp_transaksi",
            };

            const response = await vm.axios.get(vm.readRoute, { params });
            if (response && response.data) {
                vm.models = response.data.models
                vm.total = response.data.total
                vm.loadingFalse();
            }
        },

        async previewFile(file, mode) {
            const vm = this;
            vm.selectedFileName = file.name; 
            vm.showModal = true;
            vm.loadingTrue();

            const params = {
                req: "preview_file",
                code: file.code,
                filename: file.name,
                mode: mode,
                no: file.no
            };

            const config = {
                params,
                responseType: 'blob',
            };

            const response = await vm.axios.get(vm.readRoute, config);

            if (response && response.data) {
                const url = window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }));
                document.getElementById('pdf-' + vm.parent.id).data = url;
                document.getElementById('pdfx-' + vm.parent.id).src = url;

                vm.loadingFalse();
            }
        },

    },
};
</script>

<template>   
 <div class="!w-full !h-full shadow-md rounded-[8px] overflow-hidden">
    <div class="bg-white text-lg px-6 py-2 font-bold">
        Riwayat Transaksi KDP
    </div>
    <a-card class="card card-white !px-5" style="height: 100%; overflow-x: auto;">
        <a-table  :scroll="{ x: 800 }" :columns="columns" :row-key="(obj) => obj.id" :pagination="false" :loading="loadingStatus" 
            :data-source="models" style="width: 100%;">
            <template #bodyCell="{ index, column, record }">
                <template v-if="column.key === 'number'">
                    {{ index + 1 }}
                </template>
                <template v-if="column.key === 'tipe'">
                    <div v-if="record.type =='KDP-PEROLEHAN'">
                        <a-button type="primary" size="small" class="pointer-events-none w-full">Perolehan</a-button>
                    </div>
                    <div v-if="record.type =='KDP-PENYELESAIAN'">
                        <a-button type="primary" size="small" class="pointer-events-none w-full">Penyelesaian</a-button>
                    </div>
                    <div v-if="record.type =='KDP-PERUBAHAN-KONDISI'">
                        <a-button size="small" type="danger" class="pointer-events-none w-full">Perubahan Kondisi</a-button>
                    </div>
                    <div v-if="record.type =='KDP-PENGEMBANGAN'">
                        <a-button type="success" size="small" class="pointer-events-none w-full">Pengembangan</a-button>
                    </div>
                </template>
                <template v-if="column.key === 'tgl_transaksi'">
                    {{ filterDate(record.created_at) }}
                </template>
                <template v-if="column.key === 'biaya'">
                    {{ idCurrency(record.nilai) }}
                </template>
                <template v-if="column.key === 'nilai_buku'">
                    {{ idCurrency(record.saldo) }}
                </template>
                <template v-if="column.key === 'file'">
                    <div v-if="record.file">
                         <a-button @click="previewFile(record.file , 'pengembangan_kdp')" type="success" size="small">
                            <Icon icon='ant-design:eye-outlined' />
                        </a-button>
                    </div>
                    <div v-else>
                        -
                    </div>
                </template>
            </template>
        </a-table>
    </a-card>
</div>
    <a-modal v-model:open="showModal" :title="selectedFileName" width="900px" :footer="null">
        <div v-if="loadingStatus" class="absolute inset-0 flex items-center justify-center bg-white gap-x-2">
            <div class="w-4 h-4 border-4 border-t-4 border-blue-500 border-solid rounded-full animate-ping"></div>
            Memuat....
        </div>
        <object :id="'pdf-' + parent.id" type="application/pdf" class="rounded-[8px] !w-full !h-96">
            <embed :id="'pdfx-' + parent.id" type="application/pdf" />
            <param name="view" value="fit" />
        </object>
    </a-modal>
</template>