<script>

const columns = [
    {
        title: "Action",
        key: "action",
        align: "center",
    },
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
    },
    {
        title: "Tipe",
        key: "tipe",
        align: "center",
        width: 175,
    },
    {
        title: "Jenis Transaksi",
        key: "jenis_transaksi",
        align: "center",
    },    
    {
        title: "Gudang Tujuan",
        dataIndex: "gudang_nama",
        align: "center",
    },
    {
        title: "Kuantitas Tambah",
        key: "kuantitas_tambah",
        align: "center",
        width: 30,
    },
    {
        title: "Kuantitas Kurang",
        key: "kuantitas_kurang",
        align: "center",
        width: 30,
    },
    {
        title: "Stok Setelah",
        key: "stok_setelah",
        align: "center",
        width: 30,
    },
    {
        title: "Harga Satuan",
        key: "harga",
        align: "end",
    },
    {
        title: "Total Harga Masuk",
        key: "harga1",
        align: "end",
    },
    {
        title: "Total Harga Keluar",
        key: "harga2",
        align: "end",
    },
    {
        title: "Saldo Setelah",
        key: "saldo_setelah",
        align: "end",
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
            filters: {
                gudang_id: null,
            }
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
                req: "data_transaksi_barang",
                ...vm.filter,
                ...vm.filters
            };

            const response = await vm.axios.get(vm.readRoute, { params })
            if (response && response.data) {
                vm.models = response.data.models
                vm.loadingFalse();
            }
        },
    }
};
</script>

<template>
    <a-card class="card" :title="'Riwayat Transaksi ' +parent.nama + ' ID: ' + parent.id">
        <a-row type="flex" justify="left">
            <a-col :span="12">
                <a-form ref="form" name="info" class="detail-parent" :label-col="{ span: 6 }"
                    :wrapper-col="{ span: 18 }">
                    <a-form-item label="Kategori">
                        <strong>{{ parent.kategori_id }} - {{ parent.kategori_nama }}</strong>
                    </a-form-item>
                    <a-form-item label="Tanggal Input Barang">
                        <strong>{{ filterDate(parent.created_at) }}</strong>
                    </a-form-item>
                    <a-form-item label="Status Masuk">
                        <Icon v-if="parent.status_masuk" icon='ant-design:check-circle-filled' color="#87d068"
                            style="font-size: 1.5rem" />
                        <Icon v-else icon='ant-design:close-circle-filled' color="#f50" style="font-size: 1.5rem" />
                    </a-form-item>
                    <a-form-item label="Status Keluar">
                        <Icon v-if="parent.status_keluar" icon='ant-design:check-circle-filled' color="#87d068"
                            style="font-size: 1.5rem" />
                        <Icon v-else icon='ant-design:close-circle-filled' color="#f50" style="font-size: 1.5rem" />
                    </a-form-item>
                </a-form>
            </a-col>
            <a-col :span="12">
                <a-form ref="form" name="info" class="detail-parent" :label-col="{ span: 6 }"
                    :wrapper-col="{ span: 18 }">
                    <a-form-item label="Status Transaksi">
                        <strong>{{ parent.nama }}</strong>
                    </a-form-item>
                    <a-form-item label="Stock Minimum">
                        <strong>{{ parent.stok_minimum ? parent.stok_minimum : '-' }}</strong>
                    </a-form-item>
                    <a-form-item label="Stock Maksimum">
                        <strong>{{ parent.stok_maksimum ? parent.stok_maksimum : '-' }}</strong>
                    </a-form-item>
                    <a-form-item label="Stock Sekarang">
                        <strong>{{ parent.stock }}</strong>
                    </a-form-item>
                </a-form>
            </a-col>
        </a-row>
        <a-card>
            <template #extra>
                <a-row type="flex" style="gap: 1em">
                    <a-col>
                        <a-select v-model:value="filters.gudang_id" placeholder="--Pilih Gudang--" @change="readData"
                            style="width: 300px">
                            <a-select-option :key="0" :value="null">
                                Semua Gudang
                            </a-select-option>
                            <a-select-option :key="gudang.id" v-for="gudang in constant.GUDANG_OPT" :value="gudang.id">
                                {{ gudang.nama }}
                            </a-select-option>
                        </a-select>
                    </a-col>
                    <a-col>
                        <a-range-picker v-model:value="date_range" :format="date_format" allow-clear style="width: 200px"
                            @change="readData()" @calendarChange="filterDateRange" />
                        </a-col>
                </a-row>
            </template>
            <a-table :scroll="{ x: 800 }" :columns="columns" :row-key="(obj) => obj.id" :pagination="false" :loading="loadingStatus"
                :data-source="models" @change="handleTableChange">
                <template #bodyCell="{ index, column, record }">
                    <template v-if="column.key === 'number'">
                        {{ index + 1 }}
                    </template>
                    <template v-if="column.key === 'action'">
                        <div v-if="record.reference_type =='PERSEDIAAN-MASUK'">
                            <a-button :href="`${host}/persediaan/masuk?req=open&kode=${record.reference.kode}`" target="_blank"
                                size="small" type="success">
                                <Icon icon="line-md:external-link-rounded"/>
                            </a-button>
                        </div>
                        <div v-if="record.reference_type =='PERSEDIAAN-KELUAR'">
                            <a-button :href="`${host}/persediaan/keluar?req=open&kode=${record.reference.kode}`" target="_blank"
                                size="small" type="success">
                                <Icon icon="line-md:external-link-rounded"/>
                            </a-button>
                        </div>
                    </template>
                    <template v-if="column.key === 'tipe'">
                        <div v-if="record.reference_type =='PERSEDIAAN-MASUK'">
                            <a-button size="small" type="success" class="pointer-events-none">Masuk</a-button>
                        </div>
                        <div v-if="record.reference_type =='PERSEDIAAN-KELUAR'">
                            <a-button type="danger" size="small" class="pointer-events-none">Keluar</a-button>
                        </div>
                    </template>
                    <template v-if="column.key === 'jenis_transaksi'">
                        {{ record.jenis_transaksi_id }} -  {{ record.jenis_transaksi_nama }}
                    </template>
                    <template v-if="column.key === 'created_at'">
                        {{ filterDate(record.created_at) }}
                    </template>
                    <template v-if="column.key === 'kuantitas_tambah' && record.kuantitas >= 0 && record.reference_type =='PERSEDIAAN-MASUK'">
                        {{ record.kuantitas }}
                    </template>  
                    <template v-if="column.key === 'kuantitas_kurang' && record.kuantitas < 0 && record.reference_type =='PERSEDIAAN-KELUAR'">
                        {{ record.kuantitas }}
                    </template> 
                    <template v-if="column.key === 'stok_setelah'">
                        {{ record.stock_setelah }}
                    </template>
                    <template v-if="column.key === 'saldo_setelah'">
                        {{ idCurrency(record.saldo_setelah) }}
                    </template>
                    <template v-if="column.key === 'harga'">
                        {{ idCurrency(record.harga) }}
                    </template>
                    <template v-if="column.key === 'harga1' && record.sub_total >= 0 && record.reference_type =='PERSEDIAAN-MASUK'">
                        {{ idCurrency(record.sub_total) }}
                    </template>  
                    <template v-if="column.key === 'harga2' && record.sub_total < 0 && record.reference_type =='PERSEDIAAN-KELUAR'">
                        {{ idCurrency(record.sub_total) }}
                    </template>  
                </template>
            </a-table>
        </a-card>
    </a-card>
</template>