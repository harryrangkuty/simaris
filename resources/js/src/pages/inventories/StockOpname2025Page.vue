<script>
import { debounce } from "lodash";

const columns = [
    {
        title: 'Kode Barang',
        dataIndex: 'kd_brg',
    },
    {
        title: 'Uraian',
        dataIndex: 'uraian',
    },
    {
        title: 'Total Kuantitas',
        dataIndex: 'total_kuantitas',
    },
    {
        title: 'Total Harga',
        key: 'total_harga',
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
            filter: {
                gudang_id: null,
                kategori_id: null,
            },
            form: {
                id: null,
                gudang_id: null,
                kategori_id: null,
                kategori_nama: null,
                nama_barang: null,
                kuantitas: null,
                harga: null,
                sub_total: null,
            },
            expandedRowKeys: [],
            namaBarangOptions: [],
            loading_expand: false,
            editingId: null,
            editingRecord: {},
        };
    },

    created() {
        if (this.constant.GUDANG_OPT.length > 0) {
            this.filter.gudang_id = this.constant.GUDANG_OPT[0].id;
        }
    },

    mounted() {
        this.readData();
    },

    computed: {
        gudangNama() {
            const gudang = this.constant.GUDANG_OPT.find(g => g.id === this.form.gudang_id);
            return gudang ? gudang.nama : '';
        }
    },

    watch: {
        'filter.gudang_id'(newVal) {
            // Tutup semua expand
            this.expandedRowKeys = [];
            // Bersihkan _details agar nanti ambil ulang saat expand
            if (Array.isArray(this.models)) {
                this.models.forEach(item => {
                    if (item._details) {
                        delete item._details;
                    }
                });
            }
        },
        form: {
            handler() {
                this.form.sub_total = (Number(this.form.kuantitas) || 0) * (Number(this.form.harga) || 0);
            },
            deep: true
        },
        editingRecord: {
            handler() {
                this.editingRecord.sub_total = (Number(this.editingRecord.kuantitas) || 0) * (Number(this.editingRecord.harga) || 0);
            },
            deep: true
        }
    },

    methods: {

        async readData() {
            const vm = this;
            vm.loadingTrue();
            const params = {
                req: "table",
                ...vm.filter,
            };
            const response = await this.axios.get(this.readRoute, { params });
            if (response && response.data) {
                vm.models = response.data.models;
                vm.loadingFalse();
            }
        },

        async readDataDetails(kat_id) {
            const vm = this;
            const params = {
                req: "detail_stock_opname",
                kat_id,
                gudang_id: vm.filter.gudang_id,
                ...vm.filter,
            };

            const response = await vm.axios.get(vm.readRoute, { params }).catch((e) => vm.$onAjaxError(e));

            return response?.data?.models || [];
        },

        async onExpand(expanded, record) {
            const vm = this;
            vm.loading_expand = true;
            const key = record.kd_brg;

            if (expanded) {
                if (!vm.expandedRowKeys.includes(key)) {
                    vm.expandedRowKeys.push(key);
                }
                const targetRecord = vm.models.find(item => item.kd_brg === key);

                if (targetRecord && !targetRecord._details) {
                    targetRecord._details = await vm.readDataDetails(key);
                }
            } else {
                vm.expandedRowKeys = vm.expandedRowKeys.filter(n => n !== key);
            }
            vm.loading_expand = false;
        },

        newData(i) {
            const vm = this
            Object.assign(vm.$data.form, vm.$options.data().form);
            vm.form.kategori_id = i.kd_brg;
            vm.form.kategori_nama = i.uraian;
            vm.form.gudang_id = vm.filter.gudang_id;
            vm.$nextTick(function () {
                vm.showModal = true
            })
        },

        backupDetails() {
            const backup = {};
            this.models.forEach(item => {
                if (item._details) {
                    backup[item.kd_brg] = item._details;
                }
            });
            return backup;
        },

        restoreDetails(backup) {
            this.models.forEach(item => {
                if (backup[item.kd_brg]) {
                    item._details = backup[item.kd_brg];
                }
            });
        },

        async writeData() {
            const vm = this;
            vm.loading_expand = true;
            const form = { req: 'write', ...vm.form };
            const response = await vm.axios.post(vm.writeRoute, form).catch((e) => vm.$onAjaxError(e));

            if (response && response.data) {
                vm.openNotification('Berhasil menyimpan data', 'success');
                vm.showModal = false;
                // backup dan restore detail nya
                const backup = vm.backupDetails();
                await vm.readData();
                vm.restoreDetails(backup);
                // Tambahkan ke _details secara lokal
                const target = vm.models.find(item => item.kd_brg === vm.form.kategori_id);
                if (target) {
                    target._details = await vm.readDataDetails(vm.form.kategori_id);
                }
                vm.loading_expand = false;
            }
        },

        async deleteData(id) {
            const vm = this;
            vm.loading_expand = true;
            const form = { req: 'delete', id };
            const response = await vm.axios.post(vm.writeRoute, form).catch((e) => vm.$onAjaxError(e));
            if (response && response.data) {
                vm.openNotification('Berhasil menghapus data', 'success');
                // backup dan restore detail nya
                const backup = vm.backupDetails();
                await vm.readData();
                vm.restoreDetails(backup);
                // Hapus dari _details
                const target = vm.models.find(model =>
                    Array.isArray(model._details) && model._details.some(detail => detail.id === id)
                );
                if (target) {
                    target._details = await vm.readDataDetails(target.kd_brg);
                }
                vm.loading_expand = false;
            }
        },

        searchNamaBarang: debounce(function (keyword) {
            const vm = this;

            if (!keyword) {
                vm.namaBarangOptions = [];
                return;
            }

            const params = {
                req: 'search_nama_barang',
                keyword: keyword,
            };

            vm.axios.get(vm.readRoute, { params })
                .then(response => {
                    if (response && response.data) {
                        vm.namaBarangOptions = response.data.models.map(nama => ({
                            value: nama,
                        }));
                    }
                })
                .catch(e => vm.$onAjaxError(e));
        }, 300),

        printData() {
            const vm = this;

            let params = {
                req: 'print_data',
                ...vm.filter,
            };

            params = Object.fromEntries(
                Object.entries(params).filter(([_, v]) => v !== null && v !== false && v !== undefined)
            );

            const _window = window.open(`${window._HOST}/stock-opname-2025/read?${new URLSearchParams(params).toString()}`, 'name', 'height=500, width=850, top=50, left=50');
            if (window.focus) {
                _window.focus()
            }
            return false;
        },

        editRow(item) {
            const vm = this;
            vm.editingId = item.id;
            vm.editingRecord = { ...item };
        },

        async editData(item) {
            const vm = this;
            vm.form = {
                ...vm.editingRecord,
                kategori_id: item.kategori_id,
                kategori_nama: item.kategori_nama,
                gudang_id: vm.filter.gudang_id,
            };
            await vm.writeData();
            vm.cancelEditRow();
        },

        cancelEditRow() {
            const vm = this;
            vm.editingId = null;
            vm.editingRecord = {};
        }
    }
};
</script>

<template>
    <div class="!w-full shadow-md rounded-[8px] overflow-hidden flex-grow lg:mb-0 mb-6">
        <a-card class="card card-white !px-5 h-full">
            <a-row class="flex flex-wrap items-center justify-between mt-5 mb-4">
                <h1 class="text-xl font-semibold mb-4 lg:mb-0">Stock Opname 2025</h1>
                <div class="flex justify-end items-end w-full md:w-auto">
                    <a-row class="flex flex-wrap justify-start sm:justify-end gap-2 items-center w-full">
                        <a-col class="lg:w-auto w-full mb-4 lg:mb-0">
                            <a-select v-model:value="filter.gudang_id" show-search option-filter-prop="title"
                                class="lg:w-[350px] w-full" placeholder="Pilih Gudang">
                                <a-select-option :key="gudang.id" v-for="gudang in constant.GUDANG_OPT"
                                    :value="gudang.id">
                                    {{ gudang.nama }}
                                </a-select-option>
                            </a-select>
                        </a-col>
                        <!-- <a-col flex="auto">
                            <a-input v-model:value="filter.search" @keyup.enter="readData" placeholder="Cari ...">
                                <template #addonAfter>
                                    <Icon icon='ant-design:search-outlined' />
                                </template>
                        </a-input>
                        </a-col> -->
                        <a-col flex="auto" class="lg:w-[350px] w-full">
                            <select-kategori v-model:kategori_id="filter.kategori_id" @update:kategori_id="readData"
                                :is_filter="true" mode="persediaan" />
                        </a-col>
                        <a-col class="lg:w-auto w-full mb-4 lg:mb-0">
                            <a-button class="flex items-center justify-center w-full" type="primary"
                                @click="printData()">Cetak Data</a-button>
                        </a-col>
                    </a-row>
                </div>
            </a-row>
            <!-- Ini versi langsung kategori 7 digit lalu di grouping ke 10 digit -->
            <a-table :columns="columns" :data-source="models" :row-key="record => record.kd_brg"
                :rowExpandable="record => record.kd_brg.length === 10" :loading="loadingStatus" :pagination="false"
                :expandedRowKeys="expandedRowKeys" @expand="onExpand">
                <template #bodyCell="{ column, record }">
                    <template v-if="column.dataIndex === 'kd_brg' || column.dataIndex === 'uraian'">
                        <span :style="String(record.kd_brg).length === 7 ? 'font-weight: bold' : ''">
                            {{ record[column.dataIndex] }}
                        </span>
                    </template>
                    <template v-if="column.key === 'total_harga'">
                        {{ idCurrency(record.total_harga).replace(',00', '') }}
                    </template>
                </template>
                <template #expandedRowRender="{ record }">
                    <div v-if="String(record.kd_brg).length === 10">
                        <div class="flex justify-end py-1.5">
                            <a-button type="primary"
                                @click="newData({ kd_brg: record.kd_brg, uraian: record.uraian })">Tambah</a-button>
                        </div>
                        <a-spin v-if="loading_expand" />
                        <table v-else class="w-full text-sm border-collapse">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="border px-2 py-1">Aksi</th>
                                    <th class="border px-2 py-1">Nama Barang</th>
                                    <th class="border px-2 py-1">Kuantitas</th>
                                    <th class="border px-2 py-1">Harga Satuan</th>
                                    <th class="border px-2 py-1">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="!record._details || record._details.length === 0">
                                    <td class="border px-2 py-1 text-center" :colspan="5">Data Tidak Ditemukan</td>
                                </tr>
                                <tr v-else v-for="item in record._details" :key="item.id">
                                    <td class="border px-2 py-1 w-8">
                                        <a-button-group>
                                            <a-button size="small" type="primary" @click="editRow(item)">
                                                <Icon icon='ant-design:form-outlined' />
                                            </a-button>
                                            <a-popconfirm title="Yakin ingin menghapus data?"
                                                @confirm="deleteData(item.id)">
                                                <a-button size="small" type="danger">
                                                    <Icon icon='ant-design:delete-outlined' />
                                                </a-button>
                                            </a-popconfirm>
                                        </a-button-group>
                                    </td>
                                    <td class="border px-2 py-1">{{ item.nama_barang }}</td>
                                    <td class="border px-2 py-1">
                                        <template v-if="editingId === item.id">
                                            <div class="flex items-center gap-2">
                                                <a-input-number v-model:value="editingRecord.kuantitas" :min="0"
                                                    :controls="false"
                                                    :formatter="(value) => `${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ',')"
                                                    :parser="(value) => value.replace(/\$\s?|(,*)/g, '')" />
                                                <div v-if="editingRecord.kuantitas !== item.kuantitas">
                                                    <a-button-group>
                                                        <a-button type="success" size="small" @click="editData(item)">
                                                            <Icon icon="ant-design:check-outlined" />
                                                        </a-button>
                                                        <a-button type="danger" size="small" @click="cancelEditRow()">
                                                            <Icon icon="ant-design:close-outlined" />
                                                        </a-button>
                                                    </a-button-group>
                                                </div>
                                            </div>
                                        </template>
                                        <template v-else>
                                            <span @click="editRow(item)" class="cursor-pointer hover:underline">
                                                {{ item.kuantitas }}
                                            </span>
                                        </template>
                                    </td>
                                    <td class="border px-2 py-1 w-[350px]">
                                        <template v-if="editingId === item.id">
                                            <div class="flex items-center gap-2">
                                                <a-input-number v-model:value="editingRecord.harga" :min="0"
                                                    :controls="false"
                                                    :formatter="(value) => `${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ',')"
                                                    :parser="(value) => value.replace(/\$\s?|(,*)/g, '')" />
                                                <div v-if="editingRecord.harga !== item.harga">
                                                    <a-button-group>
                                                        <a-button type="success" size="small" @click="editData(item)">
                                                            <Icon icon="ant-design:check-outlined" />
                                                        </a-button>
                                                        <a-button type="danger" size="small" @click="cancelEditRow()">
                                                            <Icon icon="ant-design:close-outlined" />
                                                        </a-button>
                                                    </a-button-group>
                                                </div>
                                            </div>
                                        </template>
                                        <template v-else>
                                            <span @click="editRow(item)" class="cursor-pointer hover:underline">
                                                {{ idCurrency(item.harga) }}
                                            </span>
                                        </template>
                                    </td>
                                    <td class="border px-2 py-1 w-[350px]">
                                        <template v-if="editingId === item.id">
                                            <a-input-number v-model:value="editingRecord.sub_total" readOnly
                                                :controls="false" />
                                        </template>
                                        <template v-else>
                                            {{ idCurrency(item.sub_total) }}
                                        </template>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </template>
            </a-table>
            <!-- Ini versi langsung kategori 10 digit -->
            <!-- <a-table :columns="columns" :data-source="models" :row-key="record => record.kd_brg"
                :loading="loadingStatus" :pagination="false" :expandedRowKeys="expandedRowKeys" @expand="onExpand">
                <template #expandedRowRender="{ record }">
                    <div class="flex justify-end py-1.5">
                        <a-button type="primary"
                            @click="newData({ kd_brg: record.kd_brg, uraian: record.uraian })">Tambah</a-button>
                    </div>
                    <a-spin v-if="loadingStatus" />
                    <table v-else class="w-full text-sm border-collapse">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="border px-2 py-1">Aksi</th>
                                <th class="border px-2 py-1">Nama Barang</th>
                                <th class="border px-2 py-1">Kuantitas</th>
                                <th class="border px-2 py-1">Harga Satuan</th>
                                <th class="border px-2 py-1">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="!record._details || record._details.length === 0">
                                <td class="border px-2 py-1 text-center" :colspan="5">Data Tidak Ditemukan</td>
                            </tr>
                            <tr v-else v-for="item in record._details" :key="item.id">
                                <td class="border px-2 py-1 w-1">
                                    <a-popconfirm title="Yakin ingin menghapus data?" @confirm="deleteData(item.id)">
                                        <a-button size="small" type="danger">
                                            <Icon icon='ant-design:delete-outlined' />
                                        </a-button>
                                    </a-popconfirm>
                                </td>
                                <td class="border px-2 py-1">{{ item.nama_barang }}</td>
                                <td class="border px-2 py-1">{{ item.kuantitas }}</td>
                                <td class="border px-2 py-1">{{ idCurrency(item.harga) }}</td>
                                <td class="border px-2 py-1">{{ idCurrency(item.sub_total) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </template>
            </a-table> -->
        </a-card>
    </div>
    <a-modal v-model:open="showModal" title="Tambah Data" width="700px" @ok="writeData" :destroy-on-close="true"
        :mask-closable="false">
        <a-form ref="form" name="basic" :label-col="{ span: 8 }" :wrapper-col="{ span: 16 }">
            <a-form-item label="Gudang" data-column="gudang_id">
                <a-input :value="gudangNama" readonly />
            </a-form-item>
            <a-form-item label="Kategori" data-column="kategori">
                <a-input :value="form.kategori_id + ' - ' + form.kategori_nama" readonly />
            </a-form-item>
            <a-form-item label="Nama Barang" data-column="nama_barang" :rules="[{ required: true }]">
                <a-auto-complete v-model:value="form.nama_barang" :options="namaBarangOptions"
                    placeholder="Masukkan Nama Barang" @search="searchNamaBarang" />
            </a-form-item>
            <a-form-item label="Kuantitas" data-column="kuantitas" :rules="[{ required: true }]">
                <a-input-number style="width: 50% !important" v-model:value="form.kuantitas" :min="0" :controls="false"
                    :formatter="(value) =>
                        `${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ',')
                        " :parser="(value) => value.replace(/\$\s?|(,*)/g, '')" />
            </a-form-item>
            <a-form-item label="Harga Satuan" data-column="harga" :rules="[{ required: true }]">
                <a-input-number style="width: 50% !important" v-model:value="form.harga" :min="0" :controls="false"
                    :formatter="(value) =>
                        `${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ',')
                        " :parser="(value) => value.replace(/\$\s?|(,*)/g, '')" />
            </a-form-item>
            <a-form-item label="Subtotal">
                <a-input-number style="
                        width: 50% !important;
                        color: rgba(0, 0, 0, 0.85) !important;
                    " v-model:value="form.sub_total" :min="0" :controls="false" :formatter="(value) =>
                        `${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ',')
                        " :parser="(value) => value.replace(/\$\s?|(,*)/g, '')" readOnly />
            </a-form-item>
        </a-form>
    </a-modal>
</template>