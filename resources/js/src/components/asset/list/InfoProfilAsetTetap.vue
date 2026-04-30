<script>

export default {
    props: {
        parent: {
            type: Object,
            default: () => ({}),
        },
    },
    data() {
        return {
            form: {
                notes: null,
            },
        };
    },

    mounted() {
        this.readData();
    },

    computed: {
        parsedEtc() {
            try {
                return typeof this.models.etc === "string" ? JSON.parse(this.models.etc) : this.models.etc;
            } catch (e) {
                return null;
            }
        }
    },

    methods: {
        async readData() {
            const vm = this;
            vm.loadingTrue();
            const params = {
                id: vm.parent.id,
                req: "info_profil",
                ...vm.filter,
            };

            const response = await vm.axios.get(vm.readRoute, { params });
            if (response && response.data) {
                vm.models = response.data.models;
                vm.total = response.data.total;
                vm.loadingFalse();
            }
        },

        editNote() {
            const vm = this
            Object.assign(vm.$data.form, vm.$options.data().form);
            vm.$nextTick(function () {
                vm.showModal = true;
                if(vm.models.notes)
                    vm.form.notes = vm.models.notes;
            })
        },

        async writeNote() {
            const vm = this;
            vm.loadingTrue()
            const form = { req: 'write_catatan_profil', id: vm.parent.id, ...vm.form };
            const response = await vm.axios.post(vm.writeRoute, form).catch((e) => vm.$onAjaxError(e));
            if (response && response.data) {
                vm.openNotification('Berhasil menyimpan data', 'success');
                vm.readData();
                vm.showModal = false;
                vm.loadingFalse()
            }
        },

        goToAsetPenghapusan(kode) {
            window.open(`/aset/penghapusan?req=open&kode=${kode}`);
        }
    }
};
</script>

<template>
    <a-row type="flex" justify="left" class="mb-4" :gutter="25">
        <!-- Kolom 1 -->
        <a-col :span="24" :lg="12" class="!flex flex-col">
            <div class="!w-full shadow-md rounded-[8px] overflow-hidden flex-grow lg:mb-0 mb-6">
                <div class="bg-[#77828c] text-white text-lg px-6 py-2 font-medium">
                    Info Aset 
                </div>
                <a-card class="card card-white !px-5 h-full">
                    <a-form ref="form" name="info" :label-col="{ span: 9 }" :wrapper-col="{ span: 15 }">
                        <a-form-item label="NUP" label-align="left" :colon="false" 
                            class="border-b-[1.5px] !mb-2 !px-1.5">
                            {{ models.nup }}
                        </a-form-item>
                        <a-form-item v-if="(models.kategori_id?.startsWith('4') || models.kategori_id?.startsWith('302')) && models.kib?.no_kib" label="No KIB Lama (SIMAK-BMN)" label-align="left" :colon="false" 
                            class="border-b-[1.5px] !mb-2 !px-1.5">
                            {{ models.kib?.no_kib }}
                        </a-form-item>
                        <a-form-item label="Deskripsi" label-align="left" :colon="false" 
                            class="border-b-[1.5px] !mb-2 !px-1.5">
                            {{ models.deskripsi }}
                        </a-form-item>
                        <a-form-item v-if="parsedEtc && parsedEtc.keterangan" label="Keterangan (SIMAK-BMN)" label-align="left" :colon="false" 
                            class="border-b-[1.5px] !mb-2 !px-1.5">
                            {{ parsedEtc.keterangan }}
                        </a-form-item>
                        <a-form-item label="Jenis Perolehan" label-align="left" :colon="false" 
                            class="border-b-[1.5px] !mb-2 !px-1.5">
                            {{ models.jenis_perolehan ? `${models.jenis_perolehan.kode} - ${models.jenis_perolehan.uraian}` : ''}}
                        </a-form-item>
                        <a-form-item label="Tanggal Perolehan" label-align="left" :colon="false" 
                            class="border-b-[1.5px] !mb-2 !px-1.5">
                            {{ models.tgl_perolehan ? filterDate(models.tgl_perolehan) : '' }}
                        </a-form-item>
                        <a-form-item label="Tanggal Input Perolehan" label-align="left" :colon="false" 
                            class="border-b-[1.5px] !mb-2 !px-1.5">
                            {{ models.perolehan ? filterDate(models.perolehan.created_at) : '' }}
                        </a-form-item>
                        <a-form-item label="Tanggal Register" label-align="left" :colon="false" 
                            class="border-b-[1.5px] !mb-2 !px-1.5">
                            {{ models.tgl_buku ? filterDate(models.tgl_buku) : '' }}
                        </a-form-item>
                        <a-form-item label="Satuan Kerja" label-align="left" :colon="false" 
                            class="border-b-[1.5px] !mb-2 !px-1.5">
                            {{ models.satker?.nama }}
                        </a-form-item>
                        <a-form-item label="Kategori" label-align="left" :colon="false" 
                            class="border-b-[1.5px] !mb-2 !px-1.5">
                            {{ models.kategori_id ? `${models.kategori_id} - ${models.kategori_nama}` : ''}}
                        </a-form-item>
                        <a-form-item label="Golongan" label-align="left" :colon="false" 
                            class="border-b-[1.5px] !mb-2 !px-1.5">
                            {{ ucwordFormat(models.kd_gol) }}
                        </a-form-item>
                        <a-form-item label="Bidang" label-align="left" :colon="false" 
                            class="border-b-[1.5px] !mb-2 !px-1.5">
                            {{ ucwordFormat(models.kd_bid) }}
                        </a-form-item>
                        <a-form-item label="Kelompok" label-align="left" :colon="false" 
                            class="border-b-[1.5px] !mb-2 !px-1.5">
                            {{ ucwordFormat(models.kd_kel) }}
                        </a-form-item>
                        <a-form-item label="Sub-Kelompok" label-align="left" :colon="false" 
                            class="border-b-[1.5px] !mb-2 !px-1.5">
                            {{ ucwordFormat(models.kd_skel) }}
                        </a-form-item>
                        <a-form-item label="Komptabel" label-align="left" :colon="false" 
                            class="border-b-[1.5px] !mb-2 !px-1.5">
                            {{ models.komptabel }}
                        </a-form-item>
                        <a-form-item label-align="left" :colon="false" 
                            class="border-b-[1.5px] !mb-2 !px-1.5">
                            <template #label>
                                <div class="flex items-center">
                                    <span>Catatan</span>
                                    <a @click="editNote" class="ml-1.5">
                                        <Icon icon="line-md:pencil" />
                                    </a>
                                </div>
                            </template>
                            {{ models.notes }}
                        </a-form-item>
                    </a-form>
                </a-card>
            </div>
        </a-col>

        <!-- Kolom 2 -->
        <a-col :span="24" :lg="12" class="!flex flex-col">
            <div v-if="models.penghapusan?.tgl_penghapusan" class="mb-6">
                <div class="shadow-md rounded-[8px] overflow-hidden">
                    <div class="flex items-center justify-between bg-[#77828c] text-white text-lg px-6 py-2 font-medium">
                        <span>
                            Info Penghapusan
                        </span>
                        <Icon icon="line-md:external-link" 
                            class="text-2xl cursor-pointer hover:scale-110 transition-transform duration-200 ease-in-out" 
                            @click="goToAsetPenghapusan(models.penghapusan.kode)" />
                    </div>
                    <a-card class="card card-white !px-5">
                        <a-form ref="form" name="info" :label-col="{ span: 9 }" :wrapper-col="{ span: 15 }">
                            <a-form-item label="Kode Penghapusan" label-align="left" :colon="false" 
                                class="border-b-[1.5px] !mb-2 !px-1.5">
                                <div class="text-right">{{ models.penghapusan.kode }}</div>
                            </a-form-item>
                            <a-form-item label="Tanggal Penghapusan" label-align="left" :colon="false" 
                                class="border-b-[1.5px] !mb-2 !px-1.5">
                                <div class="text-right">{{ filterDate(models.penghapusan.tgl_penghapusan) }}</div>
                            </a-form-item>
                            <a-form-item label="Catatan Penghapusan" label-align="left" :colon="false" 
                                class="border-b-[1.5px] !mb-2 !px-1.5">
                                <div class="text-right">{{ models.penghapusan.notes }}</div>
                            </a-form-item>
                        </a-form>
                    </a-card>
                </div>
            </div>

            <div class="mb-6">
                <div class="shadow-md rounded-[8px] overflow-hidden">
                    <div class="bg-[#77828c] text-white text-lg px-6 py-2 font-medium">
                        Nilai Aset
                    </div>
                    <a-card class="card card-white !px-5">
                        <a-form ref="form" name="info" :label-col="{ span: 9 }" :wrapper-col="{ span: 15 }">
                            <a-form-item label="Nilai Perolehan" label-align="left" :colon="false" 
                                class="border-b-[1.5px] !mb-2 !px-1.5">
                                <div class="text-right">{{ idCurrency(models.nilai_perolehan) }}</div>
                            </a-form-item>
                            <a-form-item label="Nilai Buku" label-align="left" :colon="false" 
                                class="border-b-[1.5px] !mb-2 !px-1.5">
                                <div class="text-right">{{ idCurrency(models.nilai_buku) }}</div>
                            </a-form-item>
                            <a-form-item label="Akumulasi Penyusutan" label-align="left" :colon="false" 
                                class="border-b-[1.5px] !mb-2 !px-1.5">
                                <div class="text-right">{{ idCurrency(models.akumulasi_penyusutan) }}</div>
                            </a-form-item>
                        </a-form>
                    </a-card>
                </div>
            </div>

            <div class="mb-6">
                <div class="shadow-md rounded-[8px] overflow-hidden">
                    <div class="bg-[#77828c] text-white text-lg px-6 py-2 font-medium">
                        Lokasi Aset
                    </div>
                    <a-card class="card card-white !px-5">
                        <a-form ref="form" name="info" :label-col="{ span: 9 }" :wrapper-col="{ span: 15 }">
                            <a-form-item label="Tipe Ruang" label-align="left" :colon="false" 
                                class="border-b-[1.5px] !mb-2 !px-1.5">
                                {{ models.tipe_ruang }}
                            </a-form-item>
                            <a-form-item label="Nama Ruang" label-align="left" :colon="false" 
                                class="border-b-[1.5px] !mb-2 !px-1.5">
                                {{ models.ruang_nama }}
                            </a-form-item>
                        </a-form>
                    </a-card>
                </div>
            </div>

            <div class="flex-grow">
                <div class="shadow-md rounded-[8px] overflow-hidden h-full">
                    <div class="bg-[#77828c] text-white text-lg px-6 py-2 font-medium">
                        Kondisi dan Pemanfaatan Aset
                    </div>
                    <a-card class="card card-white !px-5 h-full">
                        <a-form ref="form" name="info" :label-col="{ span: 9 }" :wrapper-col="{ span: 15 }">
                            <a-form-item label="Kondisi" label-align="left" :colon="false" 
                                class="border-b-[1.5px] !mb-2 !px-1.5">
                                <span v-if="!loadingStatus" 
                                    :style="{ backgroundColor: (models.kondisi == 1 ? 'green' : (models.kondisi == 2 ? 'orange' : 'red')) }"
                                    class="px-3 py-1 rounded text-white">
                                    {{ labelKondisi(models.kondisi, false) }}
                                </span>
                            </a-form-item>
                            <a-form-item label="Status" label-align="left" :colon="false" 
                                class="border-b-[1.5px] !mb-2 !px-1.5">
                                <span v-if="!loadingStatus"
                                    :style="{ backgroundColor: (models.henti_guna == 0 ? 'green' : 'red') }"
                                    class="px-3 py-1 rounded text-white">
                                    {{ models.henti_guna == 0 ? 'Aktif' : 'Henti Guna' }}
                                </span>
                            </a-form-item>
                            <a-form-item label="Pemanfaatan" label-align="left" :colon="false" 
                                class="border-b-[1.5px] !mb-2 !px-1.5">
                                {{ models.pemanfaatan_id ? `${models.pemanfaatan_id} - ${models.pemanfaatan_nama}` : '' }}
                            </a-form-item>
                            <a-form-item label="Catatan Pemanfaatan" label-align="left" :colon="false" 
                                class="border-b-[1.5px] !mb-2 !px-1.5">
                                {{ models.pemanfaatan_catatan }}
                            </a-form-item>
                        </a-form>
                    </a-card>
                </div>
            </div>
        </a-col>
    </a-row>
    <a-modal v-model:open="showModal" :title="models.notes ? 'Ubah Catatan' : 'Tambah Catatan'" width="700px" @ok="writeNote"
        :mask-closable="false">
        <a-form ref="form" name="basic" :label-col="{ span: 4 }" :wrapper-col="{ span: 20 }">
            <a-form-item label="Catatan" data-column="notes">
                <a-textarea v-model:value="form.notes" :rows="3" placeholder="Catatan" style="resize: none;" />
            </a-form-item>
        </a-form>
    </a-modal>
</template>
