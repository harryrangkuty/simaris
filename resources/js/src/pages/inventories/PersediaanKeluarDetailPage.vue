<script>
const columns = [
    {
        title: "Action",
        key: "action",
        align: "left",
        width: 60,
    },
    {
        title: "#",
        key: "number",
        align: "center",
        width: 60,
    },
    {
        title: "Kategori Id",
        key: "kategori_id",
        dataIndex: "kategori_id",
        align: "left",
        width: 200,
    },
    {
        title: "Kategori Nama",
        key: "kategori_nama",
        dataIndex: "kategori_nama",
        align: "left",
        width: 200,
    },
    {
        title: "Nama Barang",
        key: "barang_nama",
        dataIndex: "barang_nama",
        align: "left",
    },
    {
        title: "Kuantitas",
        key: "kuantitas",
        align: "right",
        width: 100,
    },
];

export default {
    props: {
        constant: {
            type: Object,
            default: () => ({}),
        },
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
                barang_id: null,
                kuantitas: null,
            },
            selectedOpt: [],
            urlDupe: null, 
            kodePk: null, 
            typeDupe: null,
            jumlah_barang: 0, 
        };
    },

    mounted() {
        this.readData()
    },

    computed: {
        'locked': function () {
            return this.parent.status == 'F'
        }
    },

    methods: {
        async readData() {
            const vm = this;
            vm.loadingTrue();
            const params = {
                id: this.parent.id,
                req: "table_detail",
            };

            const response = await vm.axios.get(vm.readRoute, { params });
            if (response && response.data) {
                vm.loadingFalse();
                vm.models = response.data.models;
                vm.jumlah_barang = response.data.jumlah_barang;
            }
        },

        newData() {
            const vm = this
            Object.assign(vm.$data.form, vm.$options.data().form);
            vm.$nextTick(function () {
                vm.showModal = true
            })
        },

        editData(m) {
            const vm = this
            vm.form = vm.lodash.cloneDeep(m)
            vm.selectedOpt = [{ id: m.barang_id, nama: m.barang_nama, kategori_id: m.kategori_id, kategori_nama: m.kategori_nama }]
            vm.$nextTick(function () {
                vm.showModal = true
            })
        },

        async writeData() {
            const vm = this;
            vm.loadingTrue();
            const form = { req: 'write_detail', reference_id: vm.parent.id, ...vm.form };

            try {
                await vm.axios.post(vm.writeRoute, form);
                vm.openNotification('Berhasil mengubah data', 'success');
                vm.readData();
                vm.showModal = false;
            } catch (error) {
                if (error.response && error.response.status === 403) {
                    vm.urlDupe = error.response.data.url;
                    vm.kodePk = error.response.data.kode;
                    vm.typeDupe = error.response.data.type;
                    vm.openNotification(error.response.data.message, 'error');
                    vm.showModal = true;
                } else {
                    vm.$onAjaxError(error);
                }
            }
            vm.loadingFalse();
        },

        async authorize() {
            const vm = this;
            vm.loadingTrue()
            const form = { req: 'authorize', id: vm.parent.id };
            const response = await vm.axios.post(vm.writeRoute, form).catch((e) => vm.$onAjaxError(e));
            if (response && response.data) {
                vm.openNotification('Berhasil mengubah status data, refreshing page ...', 'success');
                setTimeout(function () {
                    window.location.reload();
                }, 1500)
            }
        },

        async deleteData(id, req = 'toggle') {
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

        async deleteDetail(id) {
            const vm = this;
            vm.loadingTrue()
            const form = { req: 'delete_detail', id: id, parent_id: vm.parent.id };
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
    <a-row type="flex" justify="center">
        <a-col :span="24">
            <a-card class="card" :title="'Persediaan - Keluar - ' + parent.kode">
                <a-row type="flex" justify="left">
                    <a-col :span="12">
                        <a-form ref="form" name="info" class="detail-parent" :label-col="{ span: 6 }"
                            :wrapper-col="{ span: 18 }">
                            <a-form-item label="Id Transaksi">
                                <strong>{{ parent.id }}</strong>
                            </a-form-item>
                            <a-form-item label="Jenis Transaksi">
                                <strong>{{ parent.jenis_transaksi.kode }} - {{ parent.jenis_transaksi.uraian }}</strong>
                            </a-form-item>
                            <a-form-item label="Tanggal Buku">
                                <strong>{{ parent.created_at }}</strong>
                            </a-form-item>
                            <a-form-item label="Nama Gudang">
                                <strong>{{ parent.nama_gudang }}</strong>
                            </a-form-item>
                        </a-form>
                    </a-col>
                    <a-col :span="12">
                        <a-form ref="form" name="info" class="detail-parent" :label-col="{ span: 6 }"
                            :wrapper-col="{ span: 18 }">
                            <a-form-item label="Status Transaksi">
                                <strong>{{ (parent.status == 'O' ? 'Pending' : 'Finish') }}</strong>
                            </a-form-item>
                            <a-form-item label="Jenis Barang">
                                <strong>{{ fb(models.length, 0) }}</strong>
                            </a-form-item>
                            <a-form-item label="Jumlah Barang">
                                <strong>{{ fb(Math.abs(jumlah_barang), 0) }}</strong>
                            </a-form-item>
                            <a-form-item label="Catatan">
                                <strong>{{ parent.notes ? parent.notes : '-' }}</strong>
                            </a-form-item>
                        </a-form>
                    </a-col>
                </a-row>
                <a-card>
                    <template #title>
                        <a-row type="flex" style="gap: 1em">
                            <a-col>
                                <a-button type="primary" @click="newData()" :disabled="locked">Tambah Barang</a-button>
                            </a-col>
                        </a-row>
                    </template>
                    <template #extra>
                        <a-row type="flex" style="gap: 1em">
                            <a-col>
                                <a-popconfirm v-if="parent.status == 'O'" title="Yakin mengunci transaksi?"
                                    @confirm="authorize()">
                                    <a-button type="success" :disabled="loadingStatus">Selesai</a-button>
                                </a-popconfirm>
                                <a-popconfirm v-else title="Yakin membatalkan transaksi?" @confirm="authorize()">
                                    <a-button type="danger" :disabled="loadingStatus">Buka</a-button>
                                </a-popconfirm>
                            </a-col>
                            <a-col>
                                <a-popconfirm title="Yakin menghapus data?" @confirm="deleteData(record.id, 'toggle')">
                                    <a-button type="danger" :disabled="locked || loadingStatus">Hapus</a-button>
                                </a-popconfirm>
                            </a-col>
                        </a-row>
                    </template>
                    <a-table :scroll="{ x: 800 }" :columns="columns" :row-key="(obj) => obj.id" :pagination="false" :loading="loadingStatus"
                        :data-source="models">
                        <template #bodyCell="{ index, column, record }">
                            <template v-if="column.key === 'action'">
                                <a-button-group>
                                    <!-- <a-button size="small" type="primary" @click="editData(record)" :disabled="locked">
                                        <Icon icon='ant-design:form-outlined' />
                                    </a-button> -->
                                    <a-popconfirm title="Yakin menghapus data?" @confirm="deleteDetail(record.barang_id)">
                                        <a-button size="small" type="danger" :disabled="locked">
                                            <Icon icon='ant-design:delete-outlined' />
                                        </a-button>
                                    </a-popconfirm>
                                </a-button-group>
                            </template>
                            <template v-if="column.key === 'number'">
                                {{ index + 1 }}
                            </template>
                            <template v-if="column.key === 'kuantitas'">
                                {{ Math.abs(record.kuantitas) }}
                            </template>
                        </template>
                    </a-table>
                </a-card>
            </a-card>
        </a-col>
    </a-row>
    <a-modal v-model:open="showModal" title="Tambah Barang" width="700px" @ok="writeData" :mask-closable="false">
        <a-form ref="form" name="basic" :label-col="{ span: 8 }" :wrapper-col="{ span: 16 }">
            <a-form-item label="Barang" data-column="barang_id" :rules="[{ required: true }]">
                <select-barang v-model:value="form.barang_id" :options="selectedOpt" mode="keluar"
                    :gudang="parent.gudang_id" />
            </a-form-item>
            <a-form-item label="Kuantitas" data-column="kuantitas" :rules="[{ required: true }]">
                <a-input-number style="width: 50% !important" v-model:value="form.kuantitas" :min="0" :controls="false"
                    :formatter="(value) => `${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ',')"
                    :parser="(value) => value.replace(/\$\s?|(,*)/g, '')" />
            </a-form-item>
        </a-form>
        <div v-if="urlDupe" class="flex justify-end items-center gap-x-2">
            <strong>Cek di: </strong>
            <a-button type="primary" :href="urlDupe" target="_blank">Persediaan {{ typeDupe.charAt(0).toUpperCase() + typeDupe.slice(1) }} - {{ kodePk }}</a-button>
        </div>
    </a-modal>
</template>