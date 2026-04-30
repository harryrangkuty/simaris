<script>

const columns = [
    {
        title: "Action",
        key: "action",
        align: "center",
        width: 89,
    },
    {
        title: "#",
        key: "number",
        align: "center",
        width: 10,
    },
    {
        title: "Deskripsi",
        dataIndex: "deskripsi",
        align: "left",
        width: 150,
    },
    {
        title: "Jenis Perolehan",
        key: "jenis_perolehan",
        width: 150,
    },
    {
        title: "Tgl Perolehan",
        key: "tgl_perolehan",
        align: "center",
    },
    {
        title: "Nama Kategori",
        key: "kategori_nama",
        dataIndex: "kategori_nama",
        width: 150,
    },
    {
        title: "NUP",
        key: "nup",
        dataIndex: "nup",
        align: "center",
    },
    {
        title: "Kondisi",
        key: "kondisi",
        dataIndex: "kondisi",
        align: "center",
    },
    {
        title: "Nilai Buku",
        key: "nilai_buku",
        align: "center",
    },
];

const asetColumns = [
    {
        key: "action",
        align: "center",
        width: 30,
    },
    {
        title: "Deskripsi",
        key: "deskripsi",
        dataIndex: 'deskripsi',
        width: 200,
    },
    {
        title: "Jenis Perolehan",
        key: "jenis_perolehan",
        width: 150,
    },
    {
        title: "Tgl Perolehan",
        key: "tgl_perolehan",
        align: "center",
    },
    {
        title: "Nama Kategori",
        key: "kategori_nama",
        dataIndex: "kategori_nama",
        width: 150,
    },
    {
        title: "NUP",
        key: "nup",
        dataIndex: "nup",
        align: "center",
    },
    {
        title: "Kondisi",
        key: "kondisi",
        dataIndex: "kondisi",
        align: "center",
    },
    {
        title: "Nilai Buku",
        key: "nilai_buku",
        align: "center",
    },
];

export default {

    emits: ['listPenghapusanEmpty'],
    props: {
        parent: {
            type: Object,
            default: () => ({}),
        },
    },
    data() {
        return {
            columns,
            asetColumns,
            asetModels: null,
            selectAll: false,
            selectedItems: [],
        };
    },

    mounted() {
        this.readData()
    },

    computed: {
        'locked': function () {
            return this.parent.status == 'CLOSE' || this.parent.status == 'FINISH';
        }
    },

    methods: {

        async readData() {
            const vm = this;
            vm.loadingTrue();
            const params = {
                id: this.parent.id,
                req: "list_penghapusan",
            };

            const response = await vm.axios.get(vm.readRoute, { params });
            if (response && response.data) {
                vm.models = response.data.models
                vm.total = response.data.total
                vm.loadingFalse();
                vm.$emit('listPenghapusanEmpty', vm.models.length === 0);
            }
        },

        async pilihAset() {
            const vm = this;
            vm.loadingTrue();
            const params = {
                req: "pilih_aset",
                id: vm.parent.id,
                satker_id: vm.parent.satker_id,
                jenis_transaksi_id: vm.parent.jenis_transaksi_id,
                ...vm.filter,
            };

            const response = await vm.axios.get(vm.readRoute, { params });
            if (response && response.data) {
                vm.asetModels = response.data.models
                vm.total = response.data.total
                vm.loadingFalse();
            }
        },

        newData() {
            const vm = this
            vm.showModal = true
            vm.selectedItems = [];
            vm.selectAll = false;
            vm.pilihAset()
        },

        async writeData() {
            const vm = this;
            const form = {
                req: 'tambah_list_penghapusan',
                id: vm.selectedItems,
                penghapusan_id: this.parent.id,
            };

            const response = await vm.axios.post(vm.writeRoute, form).catch((e) => vm.$onAjaxError(e));
            if (response && response.data) {
                vm.showModal = false;
                vm.openNotification('Berhasil menambahkan data ...', 'success');
                vm.readData();
            }
        },

        openDetail(kategori_id, nup) {
            const paddedNup = String(nup).padStart(6, '0');

            const idToOpen = kategori_id + paddedNup;

            const _window = window.open(`/aset/profil?req=open&id=${idToOpen}`);
            if (window.focus) {
                _window.focus();
            }
            
            return false;
        },
        
        async deleteList(id) {
            const vm = this;
            vm.loadingTrue()
            const form = { req: 'delete_list', id: id };
            const response = await vm.axios.post(vm.writeRoute, form).catch((e) => vm.$onAjaxError(e));
            if (response && response.data) {
                vm.openNotification('Berhasil menghapus data', 'success');
                vm.readData();
                vm.showModal = false;
            }
        },

        selectAllItems() {
            this.loadingTrue();

            if (this.selectAll) {
                this.selectedItems = this.asetModels.map(i => i.id)
            } else {
                this.selectedItems = [];
            }
            this.loadingFalse();
        },

        toggleSelection(id) {
            if (!this.selectedItems.includes(id)) {
                this.selectedItems.push(id);
            } else {
                const index = this.selectedItems.indexOf(id);
                if (index > -1) {
                    this.selectedItems.splice(index, 1);
                }
            }
        },
    }
};
</script>

<template>
    <a-button v-if="operator_aset" type="primary" @click="newData()" :disabled="locked" class="mb-4">Tambah Barang</a-button>
    <a-table :scroll="{ x: 800 }" :columns="columns" :row-key="(obj) => obj.id" :pagination="_pagination"
        :loading="loadingStatus" :data-source="models" @change="handleTableChange">
        <template #bodyCell="{ index, column, record }">
            <template v-if="column.key === 'action'">
                <a-button-group>
                    <a-button size="small" type="success" @click="openDetail(record.kategori_id, record.nup)">
                        <Icon icon='ant-design:database-outlined' />
                    </a-button>
                    <a-tooltip v-if="!operator_aset" placement="topLeft" title="Tidak Dapat Menghapus List Penghapusan Karena Anda bukan Operator Aset">
                        <a-button size="small" disabled>
                            <Icon icon='ant-design:delete-outlined' />
                        </a-button>
                    </a-tooltip>
                    <a-tooltip v-else placement="topLeft" :title="locked? 'Tidak Dapat Mengubah Penghapusan karena Penghapusan Telah Dikunci' : null">
                        <a-popconfirm title="Yakin menghapus data?" @confirm="deleteList(record.id)">
                            <a-button size="small" type="danger" :disabled="locked">
                                <Icon icon='ant-design:delete-outlined' />
                            </a-button>
                        </a-popconfirm>
                    </a-tooltip>
                </a-button-group>
            </template>
            <template v-if="column.key === 'number'">
                {{ index + 1 }}
            </template>
            <template v-if="column.key === 'jenis_perolehan'">
                {{ record.jenis_perolehan.kode }} - {{ record.jenis_perolehan.uraian }}
            </template>
            <template v-if="column.key === 'tgl_perolehan'">
                {{ filterDate(record.tgl_perolehan) }}
            </template>
            <template v-if="column.key === 'nilai_buku'">
                {{ idCurrency(record.nilai_buku) }}
            </template>
            <template v-if="column.key === 'kondisi'">
                  <span
                      :style="{ backgroundColor: (record.kondisi === 1 ? 'green' : (record.kondisi === 2 ? 'orange' : 'red' )) }"
                          class="px-3 py-1 rounded text-white">
                          {{ labelKondisi(record.kondisi) }}
                  </span>
              </template>
        </template>
    </a-table>
    <a-modal v-model:open="showModal" title="Pilih Aset" width="80%" @ok="writeData" :destroy-on-close="true" :mask-closable="false">
        <a-row type="flex" justify="space-between" align="middle" class="mb-3">
          <a-col :span="12">
            <span v-if="selectedItems.length > 0">Jumlah aset yang dipilih: {{ selectedItems.length }}</span>
          </a-col>
          <a-col :span="12">
            <a-input v-model:value="filter.search" 
                @keyup.enter="pilihAset" placeholder="Masukkan Deksripsi atau Nama Kategori Aset">
                <template #addonAfter>
                    <Icon icon='ant-design:search-outlined' />
                </template>
            </a-input>
          </a-col>
        </a-row>
        <a-table :scroll="{ x: 800 }" :columns="asetColumns" :row-key="(obj) => obj.id" :pagination="_pagination"
            :loading="loadingStatus" :data-source="asetModels" @change="handleTableChange">
            <template #headerCell="{ column }">
                <template v-if="column.key === 'action'">
                    <a-tooltip placement="topLeft">
                            <template #title>
                                <span>Pilih Semua</span>
                            </template>
                            <a-checkbox v-model:checked="selectAll" @change="selectAllItems" title="Pilih Semua">
                                <span class="text-white">Checklist</span>
                            </a-checkbox>
                        </a-tooltip>
                </template>
            </template>
            <template #bodyCell="{ column, record }">
              <template v-if="column.key === 'action'">
                  <a-checkbox :checked="selectedItems.indexOf(record.id) > -1"
                      @change="toggleSelection(record.id)"></a-checkbox>
              </template>
              <template v-if="column.key === 'jenis_perolehan'">
                  {{ record.jenis_perolehan.kode }} - {{ record.jenis_perolehan.uraian }}
              </template>
              <template v-if="column.key === 'tgl_perolehan'">
                  {{ filterDate(record.tgl_perolehan) }}
              </template>
              <template v-if="column.key === 'nilai_buku'">
                  {{ idCurrency(record.nilai_buku) }}
              </template>
              <template v-if="column.key === 'kondisi'">
                  <span
                      :style="{ backgroundColor: (record.kondisi === 1 ? 'green' : (record.kondisi === 2 ? 'orange' : 'red' )) }"
                          class="px-3 py-1 rounded text-white">
                          {{ labelKondisi(record.kondisi) }}
                  </span>
              </template>
          </template>
        </a-table>
    </a-modal>
</template>