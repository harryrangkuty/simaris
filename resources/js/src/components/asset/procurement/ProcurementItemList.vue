<script>
import { debounce } from "lodash-es";

const sortDirections = ['ascend', 'descend'];

const columns = [
    {
        title: "#",
        key: "number",
        align: "center",
        width: 50,
    },
    {
        title: "Item",
        key: "item_code",
        align: "left",
        width: 300,
        ellipsis: true,
        sorter: (a, b) => (a.item_code || '').localeCompare(b.item_code || ''),
        sortDirections
    },
    {
        title: "Deskripsi",
        key: "description",
        dataIndex: "description",
        align: "left",
        width: 200,
        ellipsis: true,
        sorter: (a, b) => (a.description || '').localeCompare(b.description || ''),
        sortDirections
    },
    {
        title: "Tipe Kondisi",
        key: "item_type",
        align: "center",
        width: 120,
        sorter: (a, b) => a.item_type - b.item_type,
        sortDirections
    },
    {
        title: "Kondisi Fisik",
        key: "physical_condition",
        align: "center",
        width: 100,
        sorter: (a, b) => a.physical_condition - b.physical_condition,
        sortDirections
    },
    {
        title: "Kuantitas",
        key: "quantity",
        dataIndex: "quantity",
        align: "right",
        width: 90,
        sorter: (a, b) => a.quantity - b.quantity,
        sortDirections
    },
    {
        title: "Harga Satuan",
        key: "unit_price",
        dataIndex: "unit_price",
        align: "right",
        width: 130,
        sorter: (a, b) => a.unit_price - b.unit_price,
        sortDirections
    },
    {
        title: "Subtotal",
        key: "sub_total",
        align: "right",
        width: 130,
        sorter: (a, b) => a.sub_total - b.sub_total,
        sortDirections
    },
    {
        title: "Action",
        key: "action",
        align: "center",
        width: 100,
        fixed: 'right',
        className: 'column-action'
    },
];

export default {
    emits: ["emptyAcquisitionList"],
    props: {
        parent: {
            type: Object,
            default: () => ({}),
        },
    },
    data() {
        return {
            columns,
            form: {
                id: null,
                item_code: null,
                description: null,
                item_type: 1,
                physical_condition: 1,
                quantity: null,
                unit_price: null,
                discount_value: null,
                shipping_value: null,
                commission_value: null,
                unit_price: null,
                sub_total: null,
            },
            total: 0,
            itemOptions: [],
            rounding: true
        };
    },

    mounted() {
        this.readData();
    },

    watch: {
        form: {
            handler() {
                this.form.sub_total = this.calculatedSubTotal;
            },
            deep: true,
        },
        rounding() {
            this.form.sub_total = this.calculatedSubTotal;
        },
    },

    computed: {
        calculatedSubTotal() {
            const unit_price = Number(this.form.unit_price || 0);
            const qty = Number(this.form.quantity || 0);
            const discount = Number(this.form.discount_value || 0);
            const shipping = Number(this.form.shipping_value || 0);
            const commission = Number(this.form.commission_value || 0);
            const ppnRate = this.parent.supplier?.is_ppn ? 0 : 0.11;

            const subtotalPerUnit = unit_price - discount + shipping + commission;
            let subtotal = subtotalPerUnit * (1 + ppnRate) * qty;

            // ====== PEMBULATAN ======
            if (this.rounding) {
                subtotal = Math.round(subtotal);
            }

            return subtotal;
        },

        dppValue() {
            const unitPrice = Number(this.form.unit_price || 0);
            const qty = Number(this.form.quantity || 0);
            return unitPrice * qty;
        },

        ppnValue() {
            const totalIncl = Number(this.calculatedSubTotal || 0);
            const dpp = this.dppValue;
            return totalIncl - dpp;
        },

        totalAfterPPN() {
            return Number(this.calculatedSubTotal || 0);
        }
    },

    methods: {

        async readData(v) {
            const vm = this;
            vm.loadingTrue();
            let params = v ?? {
                total: this._pagination.total,
                page: this._pagination.current,
            };
            params = {
                id: vm.parent.id,
                req: "procurement_detail_list",
                results: 10,
                ...params,
                ...vm.filter,
            };

            const response = await vm.axios.get(vm.readRoute, { params });
            if (response && response.data) {
                const pagination = { ...vm._pagination };
                pagination.total = response.data.models.total;
                vm.loadingFalse();
                vm.models = response.data.models.data;
                vm.total = response.data.total;
                vm._pagination = pagination;
                vm.$emit("emptyAcquisitionList", vm.models.length === 0);
            }
        },


        newData() {
            const vm = this;
            Object.assign(vm.$data.form, vm.$options.data().form);
            vm.$nextTick(function () {
                vm.showModal = true,
                    vm.fetchItems();
            });
        },

        editData(m) {
            const vm = this;
            vm.form = vm.lodash.cloneDeep(m);
            vm.$nextTick(function () {
                vm.showModal = true;

                if (m.item_code) {
                    vm.fetchItems(m.item_code);
                }
                vm.fetchItems();
            });
        },

        async writeData() {
            const vm = this;
            vm.loadingTrue();
            const form = {
                req: "add_procurement_list",
                procurement_id: vm.parent.id,
                ...vm.form,
            };
            const response = await vm.axios
                .post(vm.writeRoute, form)
                .catch((e) => vm.$onAjaxError(e));
            if (response && response.data) {
                vm.openNotification("Berhasil mengubah data", "success");
                vm.readData();
                vm.showModal = false;
            }
        },

        async deleteDetail(id) {
            const vm = this;
            vm.loadingTrue();
            const form = {
                req: "delete_detail",
                id: id,
            };
            const response = await vm.axios
                .post(vm.writeRoute, form)
                .catch((e) => vm.$onAjaxError(e));
            if (response && response.data) {
                vm.openNotification("Berhasil menghapus data", "success");
                vm.readData();
                vm.showModal = false;
            }
        },

        async fetchItems(param = "") {
            const vm = this;
            vm.loadingTrue();
            try {
                let url = "/lookups/items?";
                url += `search=${encodeURIComponent(param)}&limit=10`;

                const res = await fetch(url);
                const data = await res.json();

                vm.itemOptions = Array.isArray(data) ? data : [data];
            } finally {
                vm.loadingFalse();
            }
        },

        onSearchItem: debounce(function (val) {
            const vm = this;
            vm.fetchItems(val);
        }, 500),
    },
};
</script>

<template>
    <a-row class="flex justify-between items-center mb-4">
        <a-col :xs="24" :sm="24" :md="18" class="flex">
            <a-row class="flex flex-wrap gap-2 justify-start md:justify-end w-full md:w-auto">
                <a-col class="w-full md:w-auto">
                    <a-button type="primary"
                        class="bg-gradient-to-r from-purple-400 via-blue-500 to-blue-700 hover:from-yellow-400 hover:via-yellow-600 hover:to-purple-500 flex items-center justify-center text-white font-medium border-0 shadow-md transition-all duration-300 w-full"
                        @click="newData">
                        <Icon icon="line-md:plus" class="mr-1" />
                        Tambah Barang
                    </a-button>
                </a-col>
                <a-col class="w-full md:w-auto">
                    <a-input v-model:value="filter.search" @keyup.enter="readData" placeholder="Ketikkan deskripsi ...">
                        <template #addonAfter>
                            <span @click="readData" class="text-white text-base">
                                <Icon icon="ant-design:search-outlined" />
                            </span>
                        </template>
                    </a-input>
                </a-col>
            </a-row>
        </a-col>
        <a-col>
            Total Harga : <strong>IDR{{ idCurrency(total) }}</strong>
        </a-col>
    </a-row>
    <a-table :scroll="{ x: 800 }" :columns="columns" :row-key="(obj) => obj.id" :pagination="_pagination"
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
                        <a-popconfirm title="Yakin menghapus data?" @confirm="deleteDetail(record.id)">
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
            <template v-if="column.key === 'item_code'">
                <div class="flex items-center gap-x-1">
                    <a-tag color="blue">
                        <span class="text-sm">
                            {{ record.item?.code }}
                        </span>
                    </a-tag>
                    <span>
                        {{ record.item.name }}
                    </span>
                </div>
            </template>
            <template v-if="column.key === 'unit_price'">
                {{ idCurrency(record.unit_price) }}
            </template>
            <template v-if="column.key === 'sub_total'">
                {{ idCurrency(record.sub_total) }}
            </template>
            <template v-if="column.key === 'item_type'">
                <a-tag :color="record.item_type == 1 ? 'blue' : 'red'">
                    <span class="text-sm">
                        {{ record.item_type == 1 ? 'BARU (NEW)' : 'BEKAS (SECOND)'  }}
                    </span>
                </a-tag>
            </template>
            <template v-if="column.key === 'physical_condition'">
                <span :style="{
                    backgroundColor:
                        record.physical_condition === 1
                            ? 'green'
                            : record.physical_condition === 2
                                ? 'orange'
                                : 'red',
                }" class="px-3 py-1 rounded text-white">
                    {{ labelKondisi(record.physical_condition) }}
                </span>
            </template>
        </template>
    </a-table>
    <a-modal v-model:open="showModal" :title="form.id ? 'Ubah Data Barang' : 'Tambah Data Barang'" width="700px"
        @ok="writeData" :destroy-on-close="true" :mask-closable="false">

        <!-- Supplier Info -->
        <template v-if="parent.supplier">
            <div
                class="mb-4 p-3 rounded-lg border border-blue-200 bg-gradient-to-r from-blue-50 to-indigo-50 shadow-sm flex items-center gap-3">
                <Icon icon="streamline-plump-color:factory-plant" class="text-blue-600 text-xl" />
                <div class="flex flex-col leading-tight">
                    <span class="text-xs font-semibold text-blue-500 uppercase tracking-wide">
                        Supplier
                    </span>
                    <span class="text-base font-semibold text-gray-800">
                        {{ parent.supplier.name }}
                        <a-tag :color="parent.supplier.is_ppn ? 'green' : 'volcano'" class="ml-2 !text-xs !font-bold">
                            {{ parent.supplier.is_ppn ? 'PPN 11%' : 'NON-PPN' }}
                        </a-tag>
                    </span>
                </div>
            </div>
        </template>
        <a-form ref="form" name="basic" :label-col="{ span: 8 }" :wrapper-col="{ span: 16 }">
            <a-form-item label="Item" data-column="item_code" :rules="[{ required: true }]">
                <a-select v-model:value="form.item_code" placeholder="--Pilih Item--" show-search :filter-option="false"
                    allow-clear @search="onSearchItem">
                    <a-select-option v-for="item in itemOptions" :key="item.code" :value="item.code" :label="item.name">
                        <div class="flex items-center gap-2">
                            <a-tag color="blue">{{ item.code }}</a-tag>
                            <span class="text-gray-700">{{ item.name }}</span>
                        </div>
                    </a-select-option>
                </a-select>
            </a-form-item>
            <a-form-item label="Deskripsi" data-column="description" :rules="[{ required: true }]">
                <a-textarea v-model:value="form.description" :rows="4" placeholder="description" style="resize: none" />
            </a-form-item>
            <a-form-item label="Tipe Barang" data-column="item_type" :rules="[{ required: true }]">
                <a-select v-model:value="form.item_type" placeholder="--Pilih Tipe Barang--"
                    style="width: 50% !important">
                    <a-select-option :value="1">Baru/New</a-select-option>
                    <a-select-option :value="2">Bekas/Second</a-select-option>
                    <a-select-option :value="3">Temuan Stock Opname</a-select-option>
                </a-select>
            </a-form-item>
            <a-form-item label="Kondisi Fisik" data-column="physical_condition" :rules="[{ required: true }]">
                <a-select v-model:value="form.physical_condition" placeholder="--Pilih Kondisi Fisik--"
                    style="width: 50% !important">
                    <a-select-option :value="1">Baik</a-select-option>
                    <a-select-option :value="2">Rusak Ringan</a-select-option>
                    <a-select-option :value="3">Rusak Berat</a-select-option>
                </a-select>
            </a-form-item>
            <a-form-item label="Kuantitas" data-column="quantity" :rules="[{ required: true }]">
                <a-input-number style="width: 50% !important" v-model:value="form.quantity" :min="0" :controls="false"
                    :formatter="(value) =>
                        `${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ',')
                        " :parser="(value) => value.replace(/\$\s?|(,*)/g, '')" />
            </a-form-item>
            <!-- Harga Satuan -->
            <a-form-item label="Harga Satuan" data-column="unit_price" :rules="[{ required: true }]">
                <a-input-number style="width: 50% !important" v-model:value="form.unit_price" :min="0" :controls="false"
                    :formatter="(value) => `${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ',')"
                    :parser="(value) => value.replace(/\$\s?|(,*)/g, '')" />

                <!-- Jika supplier PPN, tampilkan estimasi harga sebelum pajak -->
                <template v-if="parent.supplier?.is_ppn && form.unit_price">
                    <div class="mt-2 text-sm text-gray-600">
                        Nilai sebelum PPN 11%:
                        <strong class="text-blue-600">
                            {{ idCurrency(form.unit_price / 1.11) }}
                        </strong>
                    </div>
                </template>
            </a-form-item>

            <!-- Diskon -->
            <a-form-item label="Diskon (Rp)" data-column="discount_value">
                <a-input-number style="width: 50% !important" v-model:value="form.discount_value" :min="0"
                    :controls="false" :formatter="val => `${val}`.replace(/\B(?=(\d{3})+(?!\d))/g, ',')"
                    :parser="val => val.replace(/\$\s?|(,*)/g, '')" />
            </a-form-item>

            <!-- Ongkir -->
            <a-form-item label="Ongkir (Rp)" data-column="shipping_value">
                <a-input-number style="width: 50% !important" v-model:value="form.shipping_value" :min="0"
                    :controls="false" :formatter="val => `${val}`.replace(/\B(?=(\d{3})+(?!\d))/g, ',')"
                    :parser="val => val.replace(/\$\s?|(,*)/g, '')" />
            </a-form-item>

            <!-- Komisi -->
            <a-form-item label="Komisi (Rp)" data-column="commission_value">
                <a-input-number style="width: 50% !important" v-model:value="form.commission_value" :min="0"
                    :controls="false" :formatter="val => `${val}`.replace(/\B(?=(\d{3})+(?!\d))/g, ',')"
                    :parser="val => val.replace(/\$\s?|(,*)/g, '')" />
            </a-form-item>

            <a-form-item label="Pembulatan">
                <a-switch v-model:checked="rounding" checked-children="Ya" un-checked-children="Tidak" />
            </a-form-item>

            <!-- Subtotal -->
            <a-form-item label="Subtotal">
                <a-input-number style="width: 50% !important" :value="calculatedSubTotal"
                    :formatter="val => `${val}`.replace(/\B(?=(\d{3})+(?!\d))/g, ',')"
                    :parser="val => val.replace(/\$\s?|(,*)/g, '')" disabled />
            </a-form-item>

            <!-- Informasi DPP & PPN -->
            <div v-if="!parent.supplier?.is_ppn && form.unit_price"
                class="border-t pt-3 mt-3 text-sm text-gray-700 space-y-1">
                <div class="flex justify-between">
                    <span>DPP (Dasar Pengenaan Pajak)</span>
                    <strong>{{ idCurrency(dppValue) }}</strong>
                </div>

                <div class="flex justify-between">
                    <span>PPN 11%</span>
                    <strong>{{ idCurrency(ppnValue) }}</strong>
                </div>

                <!-- Untuk supplier NON-PPN, tampilkan total setelah PPN -->
                <template v-if="!parent.supplier?.is_ppn">
                    <div class="flex justify-between border-t pt-2 mt-2">
                        <span>Total Setelah PPN</span>
                        <strong class="text-blue-600">{{ idCurrency(totalAfterPPN) }}</strong>
                    </div>
                </template>
            </div>

        </a-form>
    </a-modal>
</template>
