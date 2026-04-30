<script>

const columns = [
    {
        title: "Name",
        dataIndex: 'name',
    },
    {
        title: "Action",
        key: "action",
        align: "center",
        width: 100,
        fixed: 'right',
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
            form: {
                id: null,
                name: null,
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
            const vm = this;
            vm.loadingTrue();

            let params = v ?? {
                total: vm._pagination.total,
                page: vm._pagination.current,
                results: vm._pagination.pageSize,
            };

            params = {
                req: "table",
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
            }

        },

        newData() {
            const vm = this;
            Object.assign(vm.$data.form, vm.$options.data().form);
            vm.$nextTick(function () {
                vm.showModal = true;
            })
        },

        editData(m) {
            const vm = this;
            vm.form = vm.lodash.cloneDeep(m);
            vm.$nextTick(function () {
                vm.showModal = true;
            })
        },

        async writeData() {
            const vm = this;
            vm.loadingTrue()
            const form = { req: 'write', ...vm.form };
            const response = await vm.axios.post(vm.writeRoute, form).catch((e) => vm.$onAjaxError(e));
            if (response && response.data) {
                vm.openNotification('Berhasil menyimpan data', 'success');
                vm.readData();
                vm.showModal = false;
            }
        },

        async deleteData(id, req = 'delete') {
            const vm = this;
            vm.loadingTrue();
            const form = { req: req, id: id };
            const response = await vm.axios.post(vm.writeRoute, form).catch((e) => vm.$onAjaxError(e));
            if (response && response.data) {
                vm.openNotification('Berhasil menghapus data', 'success');
                vm.readData();
                vm.showModal = false;
            }
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
                        <a-input v-model:value="filter.search" @keyup.enter="readData" class="min-w-32 lg:w-64 w-full"
                            placeholder="Cari Permission ...">
                            <template #addonAfter>
                                <span @click="readData" class="text-white text-base">
                                    <Icon icon="ant-design:search-outlined" />
                                </span>
                            </template>
                        </a-input>
                    </a-col>
                    <a-col class="w-full md:w-auto">
                        <a-button class="flex items-center justify-center w-full" type="primary" @click="newData()">
                            Tambah Permission
                        </a-button>
                    </a-col>
                </a-row>
            </a-col>
        </a-row>
        <a-table :scroll="{ x: 800 }" :columns="columns" :row-key="(obj) => obj.id" :pagination="_pagination"
            :loading="loadingStatus" :data-source="models" @change="handleTableChange">
            <template #bodyCell="{ record, column }">
                <template v-if="column.key === 'action'">
                    <a-button-group class="flex justify-center">
                        <a-button size="small" type="text" @click="editData(record)" :style="{ padding: '0 5px' }">
                            <Icon icon="line-md:pencil-twotone"
                                class="flex justify-center text-green-500 text-[24px]" />
                        </a-button>
                        <a-popconfirm title="Yakin menghapus data?" @confirm="deleteData(record.id)">
                            <a-button type="text" danger size="small" :style="{ padding: '0 5px' }">
                                <Icon icon="line-md:trash" class="flex justify-center text-red-500 text-[24px]" />
                            </a-button>
                        </a-popconfirm>
                    </a-button-group>
                </template>
            </template>
        </a-table>
    </a-card>
    <a-modal v-model:open="showModal" :title="form.id ? 'Ubah Data' : 'Tambah Data'" width="700px" @ok="writeData"
        :mask-closable="false" :destroy-on-close="true">
        <a-form ref="form" name="basic" :label-col="{ span: 8 }" :wrapper-col="{ span: 16 }">
            <a-form-item label="Name" data-column="name" extra="Harus sama dengan key menu. Contoh: manajemen_user.read">
                <a-input v-model:value="form.name" />
            </a-form-item>
        </a-form>
    </a-modal>
</template>