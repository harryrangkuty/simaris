<script>

const columns = [
    {
        title: "#",
        key: "number",
        align: "center",
        width: 50,
    },
    {
        title: "Tanggal Transaksi",
        key: "created_at",
        align: "center",
        width: 200,
    },
    {
        title: "Jenis Transaksi",
        key: "jenis_transaksi",
    },
    {
        title: "Tambah",
        key: "nilai1",
        align: "end",
        width: 200,
    },
    {
        title: "Kurang",
        key: "nilai2",
        align: "end",
        width: 200,
    },
    {
        title: "Nilai Buku",
        key: "nilai3",
        align: "end",
        width: 200,
    },
];

export default {
    props: {
        parent: {
            type: Object,
            default: () => ({}),
        },
    },
    data() {
        return {
            columns,
            nilai_buku: 0
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
                req: "aset_transaksi",
            };

            const response = await vm.axios.get(vm.readRoute, { params })
            if (response && response.data) {
                vm.models = response.data.models
                vm.total = response.data.total
                vm.loadingFalse();
            }
        },

        getSaldo(curr, idx) {
            this.nilai_buku = this.nilai_buku + (parseFloat(curr))

            // this.nilai_buku = parseFloat(this.nilai_buku) + parseFloat(obj.nilai);
            // return this.nilai_buku;
        },

        getMonthName(month) {
            const months = [
                'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
            ];
            return months[month - 1];
        },
    },
};
</script>

<template>
    <a-row type="flex" justify="center">
        <a-col :span="24">
            <a-card class="card">
                <a-table :scroll="{ x: 800 }" :columns="columns" :row-key="(obj) => obj.id" :pagination="false"
                    :loading="loadingStatus" :data-source="models" @change="handleTableChange">
                    <template #bodyCell="{ index, column, record }">
                        <template v-if="column.key === 'number'">
                            {{ index + 1 }}
                        </template>
                        <template v-if="column.key === 'created_at'">
                            {{ filterDate(record.created_at) }}
                        </template>
                        <template v-if="column.key === 'type'">
                            {{ (record.type) }}
                        </template>
                        <template v-if="column.key === 'jenis_transaksi'">
                            {{ record.jenis_transaksi.kode }} - {{ record.jenis_transaksi.uraian }}
                            <span v-if="record.type == 'Penyusutan'">{{ ("(" + getMonthName(record.bulan) + " - " +
                                record.tahun + ")") }}</span>
                            <span v-if="record.jenis_transaksi.kode == 100 && record.reference">(Tgl Perolehan : {{
                                idDate(record.reference.tgl_perolehan) }})</span>
                        </template>
                        <template v-if="column.key === 'nilai1' && record.nilai >= 0">
                            {{ idCurrency(record.nilai) }}
                        </template>
                        <template v-if="column.key === 'nilai2' && record.nilai < 0">
                            {{ idCurrency(record.nilai) }}
                        </template>
                        <template v-if="column.key === 'nilai3'">
                            {{ idCurrency(record.saldo) }}
                        </template>
                    </template>
                </a-table>
            </a-card>
        </a-col>
    </a-row>
</template>
