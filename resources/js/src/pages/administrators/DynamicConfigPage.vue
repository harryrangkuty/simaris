<script>

const sortDirections = ['ascend', 'descend'];
const columns = [
    {
        title: "#",
        key: "number",
        align: "center",
        width: 50,
    },
    {
        title: "Key",
        dataIndex: 'key',
        ellipsis: true,
        sorter: (a, b) => (a.key || '').localeCompare(b.key || ''),
        sortDirections
    },
    {
        title: "Label",
        dataIndex: 'label',
        ellipsis: true,
        sorter: (a, b) => (a.label || '').localeCompare(b.label || ''),
        sortDirections
    },
    {
        title: "Value",
        key: 'value',
        ellipsis: true,
    },
    {
        title: "Message",
        dataIndex: "message",
        ellipsis: true,
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
            form: {
                key: null,
                value: 0,
                message: null
            },
            value_type: 'switch'
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

    watch: {
        "value_type": function (newVal) {
            if (newVal === "switch" && typeof this.form.value !== 'number') {
                this.form.value = 0;
            }
            if (newVal === "text_input" && typeof this.form.value !== 'string') {
                this.form.value = '';
            }
        }
    },

    methods: {
        async readData() {
            const vm = this;
            vm.loadingTrue();

            const params = {
                req: "table",
                ...vm.filter
            };

            const response = await vm.axios.get(vm.readRoute, { params });
            if (response && response.data) {
                vm.models = response.data.models.map(model => ({
                    ...model,
                    value: ["0", "1"].includes(model.value) ? Number(model.value) : model.value
                }));
                vm.loadingFalse();
            }
        },

        newData() {
            const vm = this
            Object.assign(vm.$data.form, vm.$options.data().form);
            vm.value_type = 'switch';
            vm.$nextTick(function () {
                vm.showModal = true
            })
        },

        editData(m) {
            const vm = this;

            vm.form = vm.lodash.cloneDeep(m);

            if (vm.form.value !== undefined) {
                vm.form.value = ["0", "1"].includes(String(vm.form.value)) ? Number(vm.form.value) : vm.form.value;
                vm.value_type = typeof vm.form.value === 'number' ? 'switch' : 'text_input';
            }
            vm.$nextTick(function () {
                vm.showModal = true
            })
        },

        async writeData() {
            const vm = this;
            vm.loadingTrue();
            const form = {
                req: 'write',
                ...vm.form
            };
            const response = await vm.axios.post(vm.writeRoute, form).catch((e) => vm.$onAjaxError(e));
            if (response && response.data) {
                vm.openNotification('Berhasil menyimpan data', 'success');
                vm.readData();
                vm.showModal = false;
                vm.loadingFalse();
            }
        },

        async deleteData(key) {
            const vm = this;
            vm.loadingTrue();
            const form = {
                req: 'delete',
                key: key
            };
            const response = await vm.axios.post(vm.writeRoute, form).catch((e) => vm.$onAjaxError(e));
            if (response && response.data) {
                vm.openNotification('Berhasil menghapus data', 'success');
                vm.readData();
                vm.showModal = false;
                vm.loadingFalse();
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
                        <a-input v-model:value="filter.search" @keyup.enter="readData" placeholder="Ketikkan Key ...">
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
                            Tambah Data Konfigurasi
                        </a-button>
                    </a-col>
                </a-row>
            </a-col>
        </a-row>
        <div class="mb-2 font-medium">
            Total: {{ models.length }} Data
        </div>
        <a-table :scroll="{ x: 800 }" :columns="columns" :row-key="(obj) => obj.id" :pagination="false"
            :loading="loadingStatus" :data-source="models" @change="handleTableChange">
            <template #bodyCell="{ index, column, record }">
                <template v-if="column.key === 'action'">
                    <a-button-group class="flex justify-center">
                        <!-- Edit -->
                        <a-tooltip title="Edit Data">
                            <a-button size="small" type="text" @click="editData(record)" :style="{ padding: '0 5px' }">
                                <Icon icon="line-md:pencil-twotone"
                                    class="flex justify-center text-green-500 text-[24px]" />
                            </a-button>
                        </a-tooltip>
                        <!-- Hapus -->
                        <a-tooltip title="Hapus Data">
                            <a-popconfirm title="Yakin menghapus data?" @confirm="deleteData(record.key)">
                                <a-button type="text" size="small" :style="{ padding: '0 5px' }">
                                    <Icon icon="line-md:trash" class="flex justify-center text-red-500 text-[24px]" />
                                </a-button>
                            </a-popconfirm>
                        </a-tooltip>
                    </a-button-group>
                </template>
                <template v-if="column.key === 'number'">
                    {{ index + 1 }}
                </template>
                <template v-if="column.key === 'value'">
                    {{ typeof record.value === 'number' ? (record.value === 1 ? 'Aktif' : 'Tidak Aktif') :
                        record.value }}
                </template>
            </template>
        </a-table>
    </a-card>
    <a-modal v-model:open="showModal" :title="form.key ? 'Ubah Data' : 'Tambah Data'" width="700px" @ok="writeData"
        :mask-closable="false">
        <a-form ref="form" name="basic" :label-col="{ span: 4 }" :wrapper-col="{ span: 20 }">
            <a-form-item label="Key">
                <a-input v-model:value="form.key" placeholder="Isi Key" />
            </a-form-item>
            <a-form-item label="Jenis Value">
                <a-radio-group v-model:value="value_type">
                    <a-radio value="switch">Switch</a-radio>
                    <a-radio value="text_input">Input Bebas</a-radio>
                </a-radio-group>
            </a-form-item>
            <a-form-item v-if="value_type == 'switch'" label="Value">
                <a-switch v-model:checked="form.value" :checked-value="1" :un-checked-value="0" />
            </a-form-item>
            <a-form-item v-if="value_type == 'text_input'" label="Value">
                <a-input v-model:value="form.value" placeholder="Isi Value" />
            </a-form-item>
            <a-form-item label="Message">
                <a-textarea v-model:value="form.message" placeholder="Isi pesan" />
            </a-form-item>
        </a-form>
    </a-modal>
</template>