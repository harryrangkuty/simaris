<script>

const sortDirections = ['ascend', 'descend'];
const columns = [
    {
        title: "#",
        key: "number",
        align: "center",
        width: 60,
        ellipsis: true,
    },
    {
        title: "Kode GL Kategori",
        key: 'code',
        width: 120,
        ellipsis: true,
        sorter: (a, b) => (a.code || '').localeCompare(b.code || ''),
        sortDirections
    },
    {
        title: "Nama Kategori",
        dataIndex: 'name',
        width: 300,
        ellipsis: true,
        sorter: (a, b) => (a.name || '').localeCompare(b.name || ''),
        sortDirections
    },
    {
        title: "Status",
        key: "is_active",
        align: "center",
        width: 90,
        ellipsis: true,
        sorter: (a, b) => (a.is_active || '').localeCompare(b.is_active || ''),
        sortDirections
    },
    {
        title: "Action",
        key: "action",
        align: "center",
        width: 110,
        fixed: 'right',
        className: 'column-action'
    },
];
export default {
    props: {
        title: String,
        constant: {
            type: Object,
            default: () => ({}),
        },
    },
    data() {
        return {
            columns,
            totalData: null,
            form: {
                code: null,
                name: null,
                is_active: 1,
            },
            option_kategori: [],
            keywordsArray: [],
            inputKeywords: null,
            filter: {
                status: "aktif",
            },
        };
    },

    computed: {
        breadcrumbItems() {
            return [
                { label: 'Dashboard', link: '/', icon: 'bi:grid' },
                { label: `${this.title}`, icon: 'line-md:folder-multiple-filled' }
            ]
        },
    },

    mounted() {
        this.readData();
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
                req: "table",
                results: 10,
                ...params,
                ...vm.filter
            };

            const response = await vm.axios.get(vm.readRoute, { params })
            if (response && response.data) {
                const pagination = { ...vm._pagination };
                pagination.total = response.data.models.total;
                vm.loadingFalse();
                vm.models = response.data.models.data;
                vm._pagination = pagination;
                vm.totalData = response.data.models.total;
            }
        },

        newData() {
            const vm = this;
            Object.assign(vm.$data.form, vm.$options.data().form);
            vm.inputKeywords = null;
            vm.$nextTick(function () {
                vm.showModal = true;
            })
        },

        editData(m) {
            const vm = this;
            vm.form = vm.lodash.cloneDeep(m);
            vm.keywordsArray = [];
            if (vm.form.keywords) {
                vm.convertKeywordToArray(vm.form.keywords);
            }
            vm.$nextTick(function () {
                vm.showModal = true;
            })
        },

        async writeData() {
            const vm = this;
            vm.loadingTrue()
            const form = {
                req: 'write',
                ...vm.form
            };
            const response = await vm.axios.post(vm.writeRoute, form).catch((e) => vm.$onAjaxError(e));
            if (response && response.data) {
                vm.openNotification('Berhasil menyimpan data', 'success');
                vm.readData();
                vm.showModal = false;
            }
        },

        async toggleStatus(code, req) {
            const vm = this;
            vm.loadingTrue();
            const form = {
                req: req,
                code: code
            };

            const response = await vm.axios.post(vm.writeRoute, form).catch((e) => vm.$onAjaxError(e));
            if (response && response.data) {
                if (req == 'deactivate') {
                    vm.openNotification('Berhasil menonaktifkan data', 'success');
                } else {
                    vm.openNotification('Berhasil mengaktifkan data', 'success');
                }
                vm.readData();
                vm.showModal = false;
                vm.loadingFalse();
            }
        },

        handleKeyup(event) {
            if (event.key === "," || event.key === "Enter") {
                this.addKeyword();
            }
        },

        addKeyword() {
            const vm = this;
            if (vm.inputKeywords.trim() !== "") {
                const newKeywords = vm.inputKeywords
                    .split(",")
                    .map((keyword) => keyword.trim())
                    .filter((keyword) => keyword !== "");

                vm.keywordsArray = [...new Set([...vm.keywordsArray, ...newKeywords])];
                vm.form.keywords = vm.keywordsArray.join(", ");
                vm.inputKeywords = "";
            }
        },

        removeKeyword(index) {
            const vm = this;
            vm.keywordsArray.splice(index, 1);
            vm.form.keywords = vm.keywordsArray.join(", ");
        },

        convertKeywordToArray(keywords) {
            const vm = this;
            if (keywords.trim() !== "") {
                vm.keywordsArray = keywords
                    .split(",")
                    .map((keyword) => keyword.trim())
                    .filter((keyword) => keyword !== "");
            } else {
                vm.keywordsArray = [];
            }
        },

        splitKeywords(keywords) {
            return keywords.split(',')
                .map(keyword => keyword.trim())
                .filter(keyword => keyword.length > 0);
        },
    }
};
</script>

<template>
    <Breadcrumb :items="breadcrumbItems" :showBackButton="true" />
    <a-card>
        <a-row class="flex flex-wrap items-start justify-between mb-4 pb-4 border-b-2 gap-y-4">
            <a-col :xs="24" :sm="24" :md="6">
                <h1 class="text-base font-semibold">
                    {{ title }}
                </h1>
            </a-col>
            <a-col :xs="24" :sm="24" :md="18" class="flex justify-end">
                <a-row class="flex flex-wrap gap-2 justify-start md:justify-end w-full md:w-auto">
                    <a-col class="w-full md:w-auto">
                        <a-select v-model:value="filter.status" class="min-w-32 lg:w-32 w-full" @change="readData">
                            <a-select-option value="aktif">Aktif</a-select-option>
                            <a-select-option value="non_aktif">Non Aktif</a-select-option>
                        </a-select>
                    </a-col>
                    <a-col class="w-full md:w-auto">
                        <a-input v-model:value="filter.search" @keyup.enter="readData"
                            placeholder="Ketikkan Uraian atau Keyword ...">
                            <template #addonAfter>
                                <span @click="readData" class="text-white text-base">
                                    <Icon icon="ant-design:search-outlined" />
                                </span>
                            </template>
                        </a-input>
                    </a-col>
                    <a-col class="w-full md:w-auto">
                        <a-button type="primary"
                            class="bg-gradient-to-r from-purple-400 via-blue-500 to-blue-700 hover:from-yellow-400 hover:via-yellow-600 hover:to-purple-500 flex items-center justify-center text-white font-medium border-0 shadow-md transition-all duration-300 w-full"
                            @click="newData()">
                            <Icon icon="line-md:plus" class="mr-1" />
                            Tambah Data Kategori
                        </a-button>
                    </a-col>
                </a-row>
            </a-col>
        </a-row>
        <div class="mb-2 font-medium">
            Total: {{ _pagination.total }} Data
        </div>
        <a-table :scroll="{ x: 800 }" :columns="columns" :row-key="(obj) => obj.id" :pagination="_pagination"
            :loading="loadingStatus" :data-source="models" @change="handleTableChange">
            <template #bodyCell="{ index, column, record }">
                <template v-if="column.key === 'action' && filter.status == 'aktif'">
                    <a-button-group class="flex justify-center">
                        <!-- Edit -->
                        <a-tooltip title="Edit Data">
                            <a-button size="small" type="text" @click="editData(record)" :style="{ padding: '0 5px' }">
                                <Icon icon="line-md:pencil-twotone"
                                    class="flex justify-center text-green-500 text-[24px]" />
                            </a-button>
                        </a-tooltip>
                        <!-- Nonaktifkan Data -->
                        <a-tooltip title="Nonaktifkan data">
                            <a-popconfirm title="Nonaktifkan data?" @confirm="toggleStatus(record.code, 'deactivate')">
                                <a-button type="text" size="small" :style="{ padding: '0 5px' }">
                                    <Icon icon="icon-park-solid:switch-button"
                                        class="flex justify-center text-red-500 text-[24px]" />
                                </a-button>
                            </a-popconfirm>
                        </a-tooltip>
                    </a-button-group>
                </template>
                <template v-if="column.key == 'action' && filter.status == 'non_aktif'">
                    <a-popconfirm title="Aktifkan data?" @confirm="toggleStatus(record.code, 'activate')">
                        <a-button size="small" type="primary">
                            <Icon icon="ant-design:rollback-outlined" />
                        </a-button>
                    </a-popconfirm>
                </template>
                <template v-if="column.key === 'number'">
                    {{ (_pagination.current - 1) * _pagination.pageSize + (index + 1) }}
                </template>
                <template v-if="column.key === 'code'">
                    <a-tag color="#2db7f5">
                        <span class="text-sm">
                            {{ record.code }}
                        </span>
                    </a-tag>
                </template>
                <template v-if="column.key === 'is_active'">
                    <a-tag v-if="fb(record.is_active, true)" color="#87d068">Aktif</a-tag>
                    <a-tag v-else color="#f50">Non Aktif</a-tag>
                </template>
            </template>
        </a-table>
    </a-card>
    <a-modal v-model:open="showModal" :title="form.id ? 'Ubah Data' : 'Tambah Data'" width="700px" @ok="writeData"
        :mask-closable="false" :destroy-on-close="true">
        <a-form ref="form" name="basic" :label-col="{ span: 8 }" :wrapper-col="{ span: 16 }">
            <a-form-item label="Golongan" data-column="kd_gol" :rules="[{ required: true }]">
                <a-select v-model:value="form.kd_gol" placeholder="--Pilih Golongan--" show-search
                    option-filter-prop="title" :getPopupContainer="(trigger) => trigger.parentNode">
                    <template v-for="item in option_kategori?.golongan" :key="item.kd_gol">
                        <a-select-option :value="item.kd_gol" :title="item.uraian">
                            {{ item.kd_gol }} - {{ item.uraian }}
                        </a-select-option>
                    </template>
                </a-select>
            </a-form-item>
            <a-form-item label="Bidang" data-column="kd_bid" :rules="[{ required: true }]">
                <a-select v-model:value="form.kd_bid" placeholder="--Pilih Bidang--" show-search
                    option-filter-prop="title" :getPopupContainer="(trigger) => trigger.parentNode">
                    <template v-for="item in option_kategori?.bidang" :key="item.code">
                        <a-select-option :value="item.kd_bid" :title="item.uraian">
                            {{ item.kd_bid }} - {{ item.uraian }}
                        </a-select-option>
                    </template>
                </a-select>
            </a-form-item>
            <a-form-item label="Kelompok" data-column="kd_kel" :rules="[{ required: true }]">
                <a-select v-model:value="form.kd_kel" placeholder="--Pilih Kelompok--" show-search
                    option-filter-prop="title" :getPopupContainer="(trigger) => trigger.parentNode">
                    <template v-for="item in option_kategori?.kelompok" :key="item.kd_kel">
                        <a-select-option :value="item.kd_kel" :title="item.uraian">
                            {{ item.kd_kel }} - {{ item.uraian }}
                        </a-select-option>
                    </template>
                </a-select>
            </a-form-item>
            <a-form-item label="Sub Kelompok" data-column="kd_skel" :rules="[{ required: true }]">
                <a-select v-model:value="form.kd_skel" placeholder="--Pilih Kelompok--" show-search
                    option-filter-prop="title" :getPopupContainer="(trigger) => trigger.parentNode">
                    <template v-for="item in option_kategori?.sub_kelompok" :key="item.kd_skel">
                        <a-select-option :value="item.kd_skel" :title="item.uraian">
                            {{ item.kd_skel }} - {{ item.uraian }}
                        </a-select-option>
                    </template>
                </a-select>
            </a-form-item>
            <a-form-item label="Sub Sub Kelompok" data-column="kd_skel" :rules="[{ required: true }]">
                <a-input v-model:value="form.kd_sskel" placeholder="Isi 3 Digit Angka" :maxlength="3"
                    style="width: 30% !important" />
            </a-form-item>
            <a-form-item label="Uraian" data-column="uraian" :rules="[{ required: true }]">
                <a-input v-model:value="form.uraian" placeholder="Isi Uraian Kategori" />
            </a-form-item>
            <a-form-item label="Keywords" data-column="keywords">
                <a-input v-model:value="inputKeywords" @keyup="handleKeyup"
                    placeholder="Isi Keyword, pisahkan dengan tanda koma untuk tiap Keyword" />
                <div v-if="keywordsArray && keywordsArray.length > 0" class="mt-2 flex flex-wrap gap-x-2 gap-y-2">
                    <a-tag v-for="(keyword, index) in keywordsArray" :key="keyword" closable
                        @close="removeKeyword(index)" class="!rounded-xl !py-0.5 !px-2 !bg-gray-100 !border-gray-400">
                        {{ keyword }}
                    </a-tag>
                </div>
            </a-form-item>
            <a-form-item label="Akun Neraca" data-column="kd_perk">
                <a-input v-model:value="form.kd_perk" placeholder="Isi 6 Digit Angka" :maxlength="6"
                    style="width: 30% !important" />
            </a-form-item>
            <a-form-item label="Dasar" data-column="dasar" :rules="[{ required: true }]">
                <a-input v-model:value="form.dasar" placeholder="Isi Dasar" />
            </a-form-item>
            <a-form-item label="Satuan" data-column="satuan">
                <a-input v-model:value="form.satuan" placeholder="Isi Satuan" />
            </a-form-item>
            <a-form-item label="Status" data-column="is_active" :rules="[{ required: true }]">
                <a-select v-model:value="form.is_active" placeholder="--Pilih Status--">
                    <a-select-option :value="0">Tidak Aktif</a-select-option>
                    <a-select-option :value="1">Aktif</a-select-option>
                </a-select>
            </a-form-item>
        </a-form>
    </a-modal>
</template>