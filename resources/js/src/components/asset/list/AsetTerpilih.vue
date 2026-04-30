<script>

const columns = [
    {
        title: "Kategori",
        key: "kategori",
        align: "center"
    },
    {
        title: "Deskripsi",
        key: "deskripsi",
        dataIndex: 'deskripsi',
    },
    {
        title: "Tgl Perolehan",
        key: "tgl_perolehan",
        align: "center",
    },
    {
        title: "Tgl Buku",
        key: "tgl_buku",
        align: "center",
    },
    {
        title: "NUP",
        key: "nup",
        dataIndex: "nup",
        align: 'center',
    },
    {
        title: "Kondisi",
        key: "kondisi",
        align: "center",
        width: 70
    },
    {
        title: "Nilai Buku",
        key: "nilai_buku",
        align: "right"
    },
    {
        title: "Komptabel",
        key: "komptabel",
        dataIndex: "komptabel",
    },
    {
        title: "Nama Ruang",
        key: "ruang_nama",
        width: 200,
        dataIndex: "ruang_nama",
    },
    {
        title: "Status",
        key: "status",
        align: 'center'
    },
];

export default {
    props: {
        selectedId: {
            type: Array,
            default: () => [],
        },
    },
    data() {
        return {
            columns,
            _pagination: { 
                showSizeChanger: true,
                pageSize: 10,
            },
        };
    },

    mounted() {
        this.readData();
    },

    watch: {
        selectedId: {
            handler() {
                this.readData();
            },
            immediate: true,
            deep: true,
        },
    },

    methods: {
        
        async readData(v) {
            const vm = this;
            vm.showModal = true

            let params = v ?? {
                total: this._pagination.total,
                page: this._pagination.current,
                results: this._pagination.pageSize ?? 10,
            };

            vm.loadingTrue();

            params = {
                req: "view_checked_data",
                id: vm.selectedId,
                ...params,
                ...vm.filter,
                ...vm.filters
            };

            const response = await vm.axios.get(vm.readRoute, { params })
            if (response && response.data) {
                const pagination = { ...vm._pagination };
                pagination.total = response.data.models.total;
                vm.models = response.data.models.data;
                vm._pagination = pagination;
                vm.loadingFalse();
            }
        },
    },
};
</script>

<template>
    <div class="!mb-4"> 
        Jumlah Aset Terpilih : {{ selectedId.length }} Aset
    </div>
    <a-table :columns="columns" :row-key="(obj) => obj.id" :pagination="_pagination"
        :loading="loadingStatus" :data-source="models" @change="handleTableChange">
        <template #bodyCell="{ column, record }">
            <template v-if="column.key === 'kategori'">
                {{ record.kategori_id }} <br> {{ record.kategori_nama }}
            </template>
            <template v-if="column.key === 'tgl_perolehan'">
                {{ filterDate(record.tgl_perolehan) }}
            </template>
            <template v-if="column.key === 'tgl_buku'">
                {{ filterDate(record.tgl_buku) }}
            </template>
            <template v-if="column.key === 'nilai_buku'">
                {{ idCurrency(record.nilai_buku) }}
            </template>
            <template v-if="column.key === 'tgl_penghapusan'">
                {{ record.tgl_penghapusan ? filterDate(record.tgl_penghapusan) : '-' }}
            </template>
            <template v-if="column.key === 'kondisi'">
                <span
                    :style="{ backgroundColor: (record.kondisi === 1 ? 'green' : (record.kondisi === 2 ? 'orange' : 'red')) }"
                    class="px-3 py-1 rounded text-white">
                    {{ labelKondisi(record.kondisi) }}
                </span>
            </template>
            <template v-if="column.key === 'status'">
                <a-tag :color="record.henti_guna == 0 ? 'green' : 'red'">
                    <span>{{ record.henti_guna == 0 ? 'Aktif Guna': 'Henti Guna'}}</span>
                </a-tag>
            </template>
        </template>
    </a-table>
</template>