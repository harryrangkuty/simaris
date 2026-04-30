<script>
const columns = [
    {
        title: "#",
        key: "number",
        align: "center",
        width: 10,
    },
    {
        title: "kode Distribusi",
        dataIndex: "code",
    },
    {
        title: "Deskripsi",
        dataIndex: "deskripsi",
    },
    {
        title: "Tipe Ruang",
        dataIndex: 'tipe_ruang',
    },
    {
        title: "Nama Ruang",
        dataIndex: 'ruang_nama',
    },
    {
        title: "Keterangan Lokasi",
        dataIndex: 'keterangan_lokasi',
    },
    {
        title: "Catatan",
        dataIndex: 'catatan',
    },
    {
        title: "Tanggal Dibuat",
        key: "created_at",
        dataIndex: 'created_at',
    },
];

export default {
    props: {
        constant: {
            type: Object,
            default: () => ({}),
        },
    },
    data() {
        return {
            columns,
            user: window._USER,
            filters: {
                satker_id: null,
            },
        };
    },

    created() {
        if (this.operator_aset && this.user.active_role_id) {
            this.filters.satker_id = this.user.satkers_id[0];
        } else if (this.reviewer_aset && this.user.active_role_id) {
            this.filters.satker_id = this.user.reviews_id[0];
        }
    },

    mounted() {
        this.readData()
    },

    computed: {
        displaySatkerName() {
            return this.filters.satker_id && this.constant.SATKER[this.filters.satker_id] ? this.constant.SATKER[this.filters.satker_id].nama : '';
        }
    },

    methods: {

        async readData(v) {
            let params = v ?? {
                total: this._pagination.total,
                page: this._pagination.current,
            };
            const vm = this;
            vm.loadingTrue();

            params = {
                req: "room_distribution_history",
                results: 10,
                ...params,
                ...vm.filter,
                ...vm.filters,
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
    <div style="overflow-x: auto;">
        <a-row type="flex" justify="end" style="gap: 1em" class="mb-3">
            <a-col class="lg:w-auto w-full">
                <a-select v-model:value="filters.satker_id" show-search allow-clear
                    option-filter-prop="title" class="w-full lg:w-[350px]"
                    placeholder="Satuan Kerja" @change="readData"
                    :getPopupContainer="(trigger) => trigger.parentNode" :title="displaySatkerName">
                    <a-select-option v-for="satker in constant.SATKER" :key="satker.id"
                        :value="satker.id" :title="satker.nama">
                        {{ satker.nama }}
                    </a-select-option>
                </a-select>
            </a-col>
            <a-col class="lg:w-auto w-full mb-4 lg:mb-0">
                <a-input v-model:value="filter.search" class="w-full lg:!w-72" @keyup.enter="readData"
                    placeholder="Masukkan Deskripsi">
                    <template #addonAfter>
                        <Icon icon='ant-design:search-outlined' />
                    </template>
                </a-input>
            </a-col>
        </a-row>
        <a-table :scroll="{ x: 800 }" :columns="columns" :row-key="(obj) => obj.id" :pagination="_pagination"
            :loading="loadingStatus" :data-source="models" @change="handleTableChange">
            <template #bodyCell="{ index, column, record }">
                <template v-if="column.key === 'number'">
                    {{ index + 1 }}
                </template>
            </template>
        </a-table>
    </div>
</template>