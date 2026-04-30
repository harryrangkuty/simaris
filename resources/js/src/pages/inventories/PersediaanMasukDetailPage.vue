<script>
const columns = [
    {
        title: "Action",
        key: "action",
        align: "left",
        width: 120,
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
        dataIndex: "kuantitas",
        align: "right",
        width: 100,
    },
    {
        title: "Harga Satuan",
        key: "harga",
        align: "right",
        width: 150,
    },
    {
        title: "Subtotal",
        key: "subtotal",
        align: "right",
        width: 150,
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
                harga: null,
                satuan_nama: null,
                kuantitas_paket: null,
                harga_per_paket: null,
                kuantitas_per_paket: null,
            },
            total: 0,
            jumlah_barang: 0,
            selectedOpt: [],
            quantityMode: null,
        };
    },

    mounted() {
        this.readData()
    },

    computed: {
        'locked': function () {
            return this.parent.status == 'F'
        },
        'KuantitasModePaket': function () {
            return this.form.kuantitas = (this.form.kuantitas_paket && this.form.kuantitas_per_paket) ? (this.form.kuantitas_paket * this.form.kuantitas_per_paket) : null;
        },
        'hargaSatuanModePaket': function () {
            return this.form.harga = (this.form.harga_per_paket && this.form.kuantitas_per_paket) ? (this.form.harga_per_paket / this.form.kuantitas_per_paket) : null;
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
                vm.models = response.data.models
                vm.total = response.data.total
                vm.jumlah_barang = response.data.jumlah_barang
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
            vm.loadingTrue()
            const form = { req: 'write_detail', reference_id: vm.parent.id, ...vm.form };
            const response = await vm.axios.post(vm.writeRoute, form).catch((e) => vm.$onAjaxError(e));
            if (response && response.data) {
                vm.openNotification('Berhasil mengubah data', 'success');
                vm.readData();
                vm.showModal = false;
            }
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
            const form = { req: 'delete_detail', id: id };
            const response = await vm.axios.post(vm.writeRoute, form).catch((e) => vm.$onAjaxError(e));
            if (response && response.data) {
                vm.openNotification('Berhasil menghapus data', 'success');
                vm.readData();
                vm.showModal = false;
            }
        },

        quantityModeOnChange() {
            if (this.quantityMode !== 'satuan') {
                this.form.kuantitas = null;
                this.form.harga = null;
            } else if (this.quantityMode !== 'paket') {
                this.form.kuantitas = null;
                this.form.harga = null;
                this.form.satuan_nama= null;
                this.form.kuantitas_paket= null;
                this.form.harga_per_paket= null;
                this.form.kuantitas_per_paket= null;
            }
        },
    }
};
</script>

<template>
    <a-row type="flex" justify="center">
        <a-col :span="24">
            <a-card class="card" :title="'Persediaan - Masuk - ' + parent.kode">
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
                            <a-form-item label="Nama Suplier">
                                <strong>{{ parent.nama_suplier }}</strong>
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
                                <strong>{{ fb(jumlah_barang, 0) }}</strong>
                            </a-form-item>
                            <a-form-item label="Total Harga">
                                <strong>{{ idCurrency(total) }}</strong>
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
                                    <a-button size="small" type="primary" @click="editData(record)" :disabled="locked">
                                        <Icon icon='ant-design:form-outlined' />
                                    </a-button>
                                    <a-popconfirm title="Yakin menghapus data?" @confirm="deleteDetail(record.id)">
                                        <a-button size="small" type="danger" :disabled="locked">
                                            <Icon icon='ant-design:delete-outlined' />
                                        </a-button>
                                    </a-popconfirm>
                                </a-button-group>
                            </template>
                            <template v-if="column.key === 'number'">
                                {{ index + 1 }}
                            </template>
                            <template v-if="column.key === 'harga'">
                                {{ idCurrency(record.harga) }}
                            </template>
                            <template v-if="column.key === 'subtotal'">
                                {{ idCurrency(record.sub_total) }}
                            </template>
                        </template>
                    </a-table>
                </a-card>
            </a-card>
        </a-col>
    </a-row>
    <a-modal v-model:open="showModal" :title="form.id ? 'Ubah Barang' : 'Tambah Barang'" width="700px" @ok="writeData" 
        :ok-button-props="{ disabled: !quantityMode }" :mask-closable="false">
        <a-form ref="form" name="basic" :label-col="{ span: 8 }" :wrapper-col="{ span: 16 }">
            <a-form-item label="Barang" data-column="barang_id" :rules="[{ required: true }]">
                <select-barang v-model:value="form.barang_id" :options="selectedOpt" mode="masuk"
                    :gudang="parent.gudang_id" :getPopupContainer="(trigger) => trigger.parentNode"/>
            </a-form-item>
            <a-form-item v-if="!form.id" label="Mode Kuantitas">
                <a-radio-group v-model:value="quantityMode" button-style="solid" @change="quantityModeOnChange">
                    <a-radio-button value="satuan">Satuan</a-radio-button>
                    <a-radio-button value="paket">Paket</a-radio-button>
                </a-radio-group>
            </a-form-item>
             <!-- Jika pilihan kuantitas adalah per satuan -->
            <template v-if="quantityMode === 'satuan' || form.id">
                <a-form-item label="Kuantitas" data-column="kuantitas" :rules="[{ required: true }]">
                    <a-input-number class="custom-width" v-model:value="form.kuantitas" :min="0" :controls="false"
                        :formatter="(value) => `${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ',')"
                        :parser="(value) => value.replace(/\$\s?|(,*)/g, '')" />
                </a-form-item>
                <a-form-item label="Harga Satuan" data-column="harga" :rules="[{ required: true }]">
                    <a-input-number class="custom-width" v-model:value="form.harga" :min="0" :controls="false"
                        :formatter="(value) => `${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ',')"
                        :parser="(value) => value.replace(/\$\s?|(,*)/g, '')" />
                </a-form-item>
            </template>
            <!-- Jika pilihan kuantitas adalah per paket -->
            <template v-else-if="quantityMode === 'paket' && !form.id">
                <a-form-item label="Satuan Paket" :rules="[{ required: true }]">
                    <a-select class="!w-1/2" v-model:value="form.satuan_nama" placeholder="--Pilih Satuan Paket--" show-search
                        option-filter-prop="title" :getPopupContainer="(trigger) => trigger.parentNode">
                        <a-select-option :key="satuan.id" v-for="satuan in constant.SATUAN" :value="satuan.nama"
                            :title="satuan.nama">
                            {{ satuan.nama }}
                        </a-select-option>
                    </a-select>
                </a-form-item>
                <a-form-item v-if="form.satuan_nama" label="Kuantitas Paket" :rules="[{ required: true }]">
                    <a-input-number class="custom-width" v-model:value="form.kuantitas_paket" :min="0" :controls="false"
                        :formatter="(value) => `${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ',')"
                        :parser="(value) => value.replace(/\$\s?|(,*)/g, '')" />
                </a-form-item>
                <a-form-item v-if="form.kuantitas_paket" label="Harga Per Paket" :rules="[{ required: true }]">
                    <a-input-number class="custom-width" v-model:value="form.harga_per_paket" :min="0" :controls="false"
                        :formatter="(value) => `${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ',')"
                        :parser="(value) => value.replace(/\$\s?|(,*)/g, '')" /> <span class="ml-2.5">Harga 1 {{form.satuan_nama}}</span>
                </a-form-item>
                <a-form-item v-if="form.harga_per_paket" label="Kuantitas Per Paket" :rules="[{ required: true }]">
                    <a-input-number class="custom-width" v-model:value="form.kuantitas_per_paket" :min="0" :controls="false"
                        :formatter="(value) => `${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ',')"
                        :parser="(value) => value.replace(/\$\s?|(,*)/g, '')" /> <span class="ml-2.5">Isi dari 1 {{form.satuan_nama}}</span>
                </a-form-item>
                <a-form-item v-if="form.kuantitas_paket && form.kuantitas_per_paket" label="Kuantitas">
                    <a-input-number class="custom-width !bg-white !text-black !opacity-100" v-model:value="KuantitasModePaket" :min="0" :controls="false"
                        :formatter="(value) => `${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ',')"
                        :parser="(value) => value.replace(/\$\s?|(,*)/g, '')" disabled/>
                </a-form-item>
                <a-form-item v-if="form.harga_per_paket && form.kuantitas_per_paket" label="Harga Satuan">
                    <a-input-number class="custom-width !bg-white !text-black !opacity-100" v-model:value="hargaSatuanModePaket" :min="0" :controls="false"
                        :formatter="(value) => `${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ',')"
                        :parser="(value) => value.replace(/\$\s?|(,*)/g, '')" disabled/>
                </a-form-item>
            </template>
            <a-form-item label="Sub Total">
                {{ idCurrency(fb(form.kuantitas, 0) * fb(form.harga, 0)) }}
            </a-form-item>
        </a-form>
    </a-modal>
</template>
<style scoped>
    .custom-width {
        @apply w-full sm:w-4/12 !important;
    }
</style>