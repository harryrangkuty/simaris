<script>
const columns = [
    {
        title: "Action",
        key: "action",
        align: "center",
        width: 100,
    },
    {
        title: "Kode Ruang",
        dataIndex: 'kode',
    },
    {
        title: "Nama",
        dataIndex: 'nama',
    },
    {
        title: "Keterangan",
        dataIndex: 'keterangan',
    },
    {
        title: "Penanggung Jawab",
        dataIndex: 'penanggung_jawab',
    },
    {
        title: "Satuan Kerja",
        dataIndex: ["satker", "nama"],
    },
    {
        title: "Jumlah Barang",
        dataIndex: "profil_count",
        align: "center"
    },
    {
        title: "Laboratorium",
        key: "is_lab",
        align: "center",
        width: 120,
    },
    {
        key: "pindah_satker",
        align: "center"
    },
    {
        key: "pindah_ruang",
        align: "center"
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
            form: {
                id: null,
                kode: null,
                nama: null,
                is_lab: false,
                keterangan: null,
                penanggung_jawab: null,
                satker_id: null,
            },
            filters: {
                satker_id: null,
            },
            showModalEmptyRoom: false,
            showModalChangeUnit: false,
            ruangId: null,
            isKodeUsed: false,
            hasChecked: false,
            ruang_nama: false,
            oldCode: null,
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
        this.readData();
    },

    computed: {
        filteredRuangOptions() {
            if (this.filters.satker_id) {
                return this.constant.RUANG.filter(ruang => ruang.satker_id === this.filters.satker_id && ruang.id !== this.ruangId);

            } else {
                return this.constant.RUANG
            }
        },
        filteredChangeUnitOptions() {
            if (this.filters.satker_id) {
                return this.constant.ALLSATKER.filter(satker => satker.id !== this.filters.satker_id);

            } else {
                return this.constant.ALLSATKER
            }
        },
        displaySatkerName() {
            return this.filters.satker_id ? this.constant.SATKER.find(satker => satker.id === this.filters.satker_id)?.nama || '' : '';
        },
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

        newData() {
            const vm = this
            Object.assign(vm.$data.form, vm.$options.data().form);
            vm.$nextTick(function () {
                vm.showModal = true
                this.form.satker_id = this.filters.satker_id
            })
        },

        editData(m) {
            const vm = this;
            vm.form = vm.lodash.cloneDeep(m);
            vm.oldCode = m.kode;
            vm.$nextTick(function () {
                vm.showModal = true
            })
        },

        async writeData() {
            const vm = this;
            vm.loadingTrue()
            const form = { req: 'write', ...vm.form };
            const response = await vm.axios.post(vm.writeRoute, form).catch((e) => vm.$onAjaxError(e));
            if (response && response.data) {
                vm.openNotification('Berhasil mengubah data', 'success');
                vm.readData();
                vm.showModal = false;
            }
        },

        async deleteData(id, req = 'delete') {
            const vm = this;
            vm.loadingTrue()
            const form = { req: req, id: id };
            const response = await vm.axios.post(vm.writeRoute, form).catch((e) => vm.$onAjaxError(e));
            if (response && response.data) {
                vm.openNotification('Berhasil menghapus data', 'success');
                vm.readData();
                vm.showModal = false;
            }
        },

        printData() {
            const _window = window.open(window._HOST + `/aset/ruang/read?req=print_data_ruang&satker_id=${this.filters.satker_id}`, 'name', 'height=500, width=850, top=50, left=50')
            if (window.focus) {
                _window.focus()
            }
            return false;
        },

        printList(id) {
            const _window = window.open(window._HOST + `/aset/ruang?req=print_list&id=${id}`, 'name', 'height=500, width=850, top=50, left=50')
            if (window.focus) {
                _window.focus()
            }
            return false;
        },

        emptyRoom(id) {
            const vm = this;
            vm.ruangId = id;
            vm.$nextTick(function () {
                vm.showModalEmptyRoom = true;
            });
        },

        async distribute() {
            const vm = this;

            const form = {
                req: 'distribute',
                ruang_id: vm.ruangId,
                new_ruang_id: vm.form.ruang_id
            };

            const response = await vm.axios.post(vm.writeRoute, form).catch((e) => vm.$onAjaxError(e));
            if (response && response.data) {
                vm.openNotification('Berhasil memindahkan barang', 'success');
                vm.form.ruang_id = null;
                vm.readData();
                vm.showModalEmptyRoom = false;
            }
        },

        changeUnit(id) {
            const vm = this;
            vm.ruangId = id;
            vm.$nextTick(function () {
                vm.showModalChangeUnit = true;
            });
        },

        async changeUnitRoom() {
            const vm = this;

            const form = {
                req: 'change_unit',
                ruang_id: vm.ruangId,
                new_satker_id: vm.form.satker_id
            };

            const response = await vm.axios.post(vm.writeRoute, form).catch((e) => vm.$onAjaxError(e));
            if (response && response.data) {
                vm.openNotification('Berhasil memindandahkan ruang', 'success');
                vm.form.satker_id = null;
                vm.readData();
                vm.showModalChangeUnit = false;
            }
        },

        handleBlur() {
            if (this.form.kode !== this.oldCode) {
                this.checkKodeRuang();
            }
        },

        async checkKodeRuang() {
            const vm = this;
            vm.loadingTrue();

            const form = {
                req: 'cek_kode_ruang',
                satker_id: vm.form.satker_id,
                ruang_kode: vm.form.kode,
            };

            try {
                const response = await vm.axios.post(vm.writeRoute, form);
                vm.isKodeUsed = false;
            } catch (error) {
                vm.isKodeUsed = true;
                if (error.response && error.response.status === 400) {
                    vm.$message.error(error.response.data.message);
                    vm.ruang_nama = error.response.data.ruang_nama;
                }
            } finally {
                vm.hasChecked = true;
                vm.loadingFalse();
            }
        },
        resetValidation() {
            this.isKodeUsed = false;
            this.hasChecked = false;
        },
    }
};
</script>

<template>
    <a-row type="flex" justify="center">
        <a-col :span="24">
            <a-card class="card">
                <a-row class="flex flex-wrap items-center justify-between border-b-2 mt-5 mb-4">
                    <h1 class="text-xl font-semibold mb-4 lg:mb-0">Manajemen Ruang</h1>
                    <div class="flex justify-end items-end w-full md:w-auto">
                        <a-row class="flex flex-wrap justify-start sm:justify-end gap-2 items-center w-full">
                            <a-col class="lg:w-auto w-full mb-4 lg:mb-0">
                                <a-select v-model:value="filters.satker_id" show-search option-filter-prop="title"
                                    class="w-full lg:w-[350px]" placeholder="Satuan Kerja" @change="readData" :title="displaySatkerName">
                                    <a-select-option v-for="satker in constant.SATKER" :key="satker.id"
                                        :value="satker.id" :title="satker.nama">
                                        {{ satker.nama }}
                                    </a-select-option>
                                </a-select>
                            </a-col>
                            <a-col class="lg:w-auto w-full mb-4 lg:mb-0">
                                <a-input v-model:value="filter.search" @keyup.enter="readData"
                                placeholder="Ketikkan kode atau nama ruang" class="w-full lg:!w-72">
                                    <template #addonAfter>
                                        <Icon icon='ant-design:search-outlined' />
                                    </template>
                                </a-input>
                            </a-col>
                            <a-col class="lg:w-auto w-full mb-4 lg:mb-0">
                                <a-tooltip placement="topLeft" class="w-full lg:w-auto"
                                    :title="!models.length > 0 ? 'Tidak dapat mencetak data ruang karena data masih kosong' : null">
                                    <a-button type="success" @click="printData()" class="flex items-center justify-center w-full"
                                        :disabled="!models.length > 0">
                                        <Icon icon="line-md:cloud-alt-print-twotone-loop" class="mr-2 items-center justify-center"/>
                                        Print Data
                                    </a-button>
                                </a-tooltip>
                            </a-col>
                            <a-col v-if="operator_aset"  class="lg:w-auto w-full mb-4 lg:mb-0">
                                <a-button type="primary" class="flex items-center justify-center w-full" @click="newData()">Tambah Ruang</a-button>
                            </a-col>
                        </a-row>
                    </div>
                </a-row>

                <a-table :scroll="{ x: 800 }" :columns="columns" :row-key="(obj) => obj.id" :pagination="_pagination"
                    :loading="loadingStatus" :data-source="models" @change="handleTableChange">
                    <template #bodyCell="{ index, column, record }">
                        <template v-if="column.key === 'action'">
                            <a-dropdown trigger="click" placement="bottomLeft">
                                <a-button size="small" type="primary">
                                    Aksi
                                    <Icon icon="ant-design:arrow-down-outlined" class="ml-1.5" />
                                </a-button>
                                <template #overlay>
                                    <a-menu>
                                        <a-menu-item>
                                            <a-tooltip
                                                :title="record.profil_count > 0 ? null : 'Tidak bisa cetak list DBR dikarenakan barang diruangan ini kosong'">
                                                <button :disabled="record.profil_count <= 0"
                                                    @click="printList(record.id)">
                                                    <Icon icon='ant-design:printer-outlined' class="mr-1" />
                                                    List DBR
                                                </button>
                                            </a-tooltip>
                                        </a-menu-item>
                                        <a-menu-item>
                                            <a-tooltip
                                                :title="record.profil_count > 0 ? null : 'Tidak bisa cetak label dikarenakan barang diruangan ini kosong'">
                                                <button :disabled="record.profil_count <= 0">
                                                    <Icon icon='ant-design:printer-outlined' class="mr-1" />
                                                    Label
                                                </button>
                                            </a-tooltip>
                                        </a-menu-item>
                                        <template v-if="operator_aset">
                                            <a-menu-item>
                                                <button @click="editData(record)">
                                                    <Icon icon='ant-design:form-outlined' class="mr-1" />
                                                    Ubah
                                                </button>
                                            </a-menu-item>
                                            <a-menu-item>
                                                <a-tooltip
                                                    :title="record.profil_count > 0 ? 'Ruang tidak bisa dihapus dikarenakan ada aset yang ditempatkan di ruang ini' : null">
                                                    <a-popconfirm :disabled="record.profil_count > 0"
                                                        :title="record.profil_count == 0 ? `Yakin menghapus ruangan ${record.nama}?` : null"
                                                        @confirm="deleteData(record.id)">
                                                        <button :disabled="record.profil_count > 0">
                                                            <Icon icon='ant-design:delete-outlined' class="mr-1" />
                                                            Hapus
                                                        </button>
                                                    </a-popconfirm>
                                                </a-tooltip>
                                            </a-menu-item>
                                        </template>
                                    </a-menu>
                                </template>
                            </a-dropdown>
                        </template>
                        <template v-if="column.key === 'is_lab'">
                            <Icon v-if="record.is_lab" icon='ant-design:check-circle-filled' color="#87d068"
                                style="font-size: 1.5rem" />
                            <Icon v-else icon='ant-design:close-circle-filled' color="#f50" style="font-size: 1.5rem" />
                        </template>
                        <!-- <template v-if="column.key === 'pindah_satker'">
                    <a-button v-if="operator_aset && record.profil_count > 0" size="small" type="success"
                        @click="changeUnit(record.id)">Pindah Satker
                    </a-button>
                </template> -->
                        <template v-if="column.key === 'pindah_ruang'">
                            <a-button v-if="operator_aset && record.profil_count > 0" size="small" type="danger"
                                @click="emptyRoom(record.id)">Pindah Ruang
                            </a-button>
                        </template>
                    </template>
                </a-table>
            </a-card>
            <a-modal v-model:open="showModal" :title="form.id ? 'Ubah Data' : 'Tambah Data'" width="700px"
                @ok="writeData" :ok-button-props="{ disabled: isKodeUsed }" :mask-closable="false"
                @cancel="resetValidation">
                <a-form ref="form" name="basic" :label-col="{ span: 8 }" :wrapper-col="{ span: 16 }">
                    <a-form-item v-if="!form.id" label="Satuan Kerja" data-column="satker_id"
                        :rules="[{ required: true }]">
                        <a-select v-model:value="form.satker_id" placeholder="--Pilih Satuan Kerja--" show-search
                            option-filter-prop="title">
                            <a-select-option :key="satker.id" v-for="satker in constant.SATKER" :value="satker.id"
                                :title="satker.nama">
                                {{ satker.nama }}
                            </a-select-option>
                        </a-select>
                    </a-form-item>
                    <a-form-item label="Kode Ruang" data-column="kode" :validateStatus="isKodeUsed ? 'error' : ''"
                        :rules="[{ required: true }]">
                        <div class="flex items-center">
                            <div class="relative w-2/6">
                                <a-input v-model:value="form.kode" placeholder="Kode Ruang" @blur="handleBlur"
                                    @focus="resetValidation" />
                                <a-spin v-if="loadingStatus"
                                    style="position: absolute; top: 50%; right: 10px; transform: translateY(-50%);" />
                            </div>
                            <div v-if="form.kode && hasChecked" class="ml-2">
                                <a-tooltip v-if="isKodeUsed" placement="topLeft"
                                    :title="'Kode Ruang Sudah Digunakan oleh ' + ruang_nama + '. Harap Ganti Kode Ruang.'">
                                    <div class="flex items-center gap-x-0.5">
                                        <Icon icon="line-md:close-circle" class="w-6 h-6 text-red-500" />
                                        <small>Digunakan oleh <strong>{{ ruang_nama }}</strong>.</small>
                                    </div>
                                </a-tooltip>
                                <a-tooltip v-else placement="topLeft"
                                    title="Kode Ruang Valid dan Belum Pernah Digunakan Sebelumnya.">
                                    <Icon icon="line-md:check-all" class="w-6 h-6 text-green-500" />
                                </a-tooltip>
                            </div>
                        </div>
                    </a-form-item>
                    <a-form-item label="Nama Ruang" data-column="nama" :rules="[{ required: true }]">
                        <a-input v-model:value="form.nama" placeholder="Nama" />
                    </a-form-item>
                    <a-form-item label="Laboratorium" data-column="is_lab">
                        <a-radio-group v-model:value="form.is_lab">
                            <a-radio :value="true">Ya</a-radio>
                            <a-radio :value="false">Tidak</a-radio>
                        </a-radio-group>
                    </a-form-item>
                    <a-form-item label="Penanggung Jawab" data-column="penanggung_jawab">
                        <a-input v-model:value="form.penanggung_jawab" placeholder="Penanggung Jawab" />
                    </a-form-item>
                    <a-form-item label="Keterangan" data-column="keterangan">
                        <a-textarea v-model:value="form.keterangan" :rows="3" placeholder="Keterangan"
                            style="resize: none;" />
                    </a-form-item>
                </a-form>
            </a-modal>
            <a-modal v-model:open="showModalChangeUnit" title="Pindah Satker" width="700px" @ok="changeUnitRoom"
                :mask-closable="false" :destroy-on-close="true">
                <a-form ref="form" name="basic" :label-col="{ span: 6 }" :wrapper-col="{ span: 18 }">
                    <a-form-item label="Satuan Kerja" data-column="satker_id" :rules="[{ required: true }]">
                        <a-select v-model:value="form.satker_id" placeholder="--Pilih Satuan Kerja--" show-search
                            option-filter-prop="title" :getPopupContainer="(trigger) => trigger.parentNode">
                            <a-select-option :key="satker.id" v-for="satker in filteredChangeUnitOptions"
                                :value="satker.id" :title="satker.nama">
                                {{ satker.nama }}
                            </a-select-option>
                        </a-select>
                    </a-form-item>
                </a-form>
            </a-modal>
            <a-modal v-model:open="showModalEmptyRoom" title="Pindah Ruang" width="700px" @ok="distribute"
                :mask-closable="false">
                <a-form ref="form" name="basic" :label-col="{ span: 6 }" :wrapper-col="{ span: 18 }">
                    <a-form-item label="Ruang" data-column="ruang_id" :rules="[{ required: true }]">
                        <a-select v-model:value="form.ruang_id" placeholder="--Pilih Ruang--" show-search
                            option-filter-prop="search">
                            <a-select-option :key="ruang.id" v-for="ruang in filteredRuangOptions" :value="ruang.id"
                                :title="ruang.nama" :search="ruang.kode + ' ' + ruang.nama">
                                {{ ruang.kode }} - {{ ruang.nama }}
                            </a-select-option>
                        </a-select>
                    </a-form-item>
                </a-form>
            </a-modal>
        </a-col>
    </a-row>
</template>