<script>

export default {
    props: {
        parent: {
            type: Object,
            default: () => ({}),
        },
        constant: {
            type: Object,
            default: () => ({}),
        },
        mode: {
            type: String,
            default: 'Card'
        },
    },
    data() {
        return {
            form: {
                pemanfaatan_id: null,
                pemanfaatan_catatan: null,
            },
            inputFile: null,
            selectedFileName: null
        };
    },

    mounted() {
        this.readData();
    },

    computed: {
        tooltipText() {
        if (!this.operator_aset) {
            return "Tidak Dapat Mengubah Pemanfaatan Karena Anda bukan Operator Aset";
        }
        if (this.parent.tgl_penghapusan) {
            return "Aset ini telah dihapus";
        }
        if (this.parent.henti_guna) {
            return "Aset ini dalam status Henti Guna";
        }
        return null;
        }
    },

    methods: {

        async readData() {
            const vm = this;
            vm.loadingTrue();
            const params = {
                id: vm.parent.id,
                req: "log_pemanfaatan",
            };

            const response = await vm.axios.get(vm.readRoute, { params });
            if (response && response.data) {
                vm.models = response.data.models
                vm.total = response.data.total
                vm.loadingFalse();
            }
        },

        handleFileChange(file) {
            const vm = this
            vm.formFile = file

        },

        async save() {
            const vm = this;
            vm.loadingTrue();

            const formData = new FormData();
            formData.append('req', 'assign_utilization_single');
            formData.append('id', vm.parent.id);

            Object.keys(vm.form).forEach(key => {
                formData.append(key, vm.form[key]);
            });

            if (vm.formFile) {
                formData.append('file', vm.formFile, vm.formFile.name);
            }

            try {
                const response = await vm.axios.post(vm.writeRoute, formData);

                if (response && response.data) {
                    vm.openNotification('Berhasil mengubah pemanfaatan', 'success');
                    vm.form = {};
                    vm.formFile = null;
                    vm.inputFile = null;
                    vm.loadingFalse();
                    vm.readData();
                }
            } catch (error) {
                vm.$onAjaxError(error);
            } finally {
                vm.loadingFalse();
            }
        },

        async previewFile(file, mode) {
            const vm = this;
            vm.selectedFileName = file.name;
            vm.showModal = true;
            vm.loadingTrue();

            const params = {
                req: "preview_file",
                code: file.code,
                filename: file.name,
                mode: mode,
            };

            const config = {
                params,
                responseType: 'blob',
            };

            const response = await vm.axios.get(vm.readRoute, config);

            if (response && response.data) {
                const url = window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }));
                document.getElementById('obj-pemanfaatan-' + vm.parent.id).data = url;
                document.getElementById('embed-pemanfaatan-' + vm.parent.id).src = url;

                vm.loadingFalse();
            }
        },
    },
};
</script>

<template>
    <div class="shadow-md rounded-lg overflow-hidden !h-full">
        <div class="bg-[#77828c] text-white text-lg px-6 py-2 font-medium">
            Pemanfaatan
        </div>
        <a-card class="card card-white !h-full !px-5 max-h-screen">
            <div class="w-full flex justify-end">
                <a-tooltip placement="topLeft"
                    :title="tooltipText">
                    <a-popconfirm title="Yakin menyimpan pemanfaatan?" @confirm="save()">
                        <a-button type="primary" :disabled="loadingStatus || !operator_aset || parent.tgl_penghapusan || parent.henti_guna"
                            class="mb-4 w-full sm:w-auto">Save</a-button>
                    </a-popconfirm>
                </a-tooltip>
            </div>
            <a-form ref="form" name="basic" :label-col="{ span: 6 }" :wrapper-col="{ span: 18 }" labelAlign="left"
                class="w-full">
                <a-form-item label="Pemanfaatan" data-column="pemanfaatan_id" :rules="[{ required: true }]">
                    <a-select v-model:value="form.pemanfaatan_id" placeholder="--Pilih Pemanfaatan--"
                        :disabled="!operator_aset || parent.tgl_penghapusan || parent.henti_guna">
                        <a-select-option v-for="(value, key) in constant.PEMANFAATAN" :key="key" :value="key">
                            {{ value }}
                        </a-select-option>
                    </a-select>
                </a-form-item>
                <a-form-item label="Catatan" data-column="pemanfaatan_catatan" :rules="[{ required: true }]">
                    <a-textarea v-model:value="form.pemanfaatan_catatan" :rows="3"
                        placeholder="Catatan Pemanfaatan, cth nama orang/nama pengguna"
                        :disabled="!operator_aset || parent.tgl_penghapusan || parent.henti_guna"></a-textarea>
                </a-form-item>
                <a-form-item label="File" :rules="[{ required: true }]">
                    <a-input type="file" data-column="file" accept=".jpg, .jpeg, .png, .pdf" v-model:value="inputFile"
                        @change="(v) => handleFileChange(v.target.files[0])"  :disabled="!operator_aset || parent.tgl_penghapusan || parent.henti_guna">
                    </a-input>
                    <div class="px-2">
                        <span style="font-size: 0.85rem; color: rgba(0, 0, 0, 0.5);">
                            <Icon icon="mdi:information-outline" /> Hanya file .jpg, .jpeg, .png, .pdf yang diizinkan
                        </span>
                    </div>
                </a-form-item>
            </a-form>

            <div class="flex items-center mb-2">
                <span class="text-base font-semibold">Riwayat Pemanfaatan</span>
                <Icon icon='ant-design:clock-circle-outlined' class="w-5 h-5 ml-1.5" />
            </div>
            <div v-if="models.length">
                <div class="mt-3 overflow-y-auto pr-1 custom-min-scroll">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr>
                                <th class="text-left px-2 py-1 border-b">Tanggal</th>
                                <th class="text-left px-2 py-1 border-b">Detail Pemanfaatan</th>
                                <th class="text-left px-2 py-1 border-b">File</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in models" :key="item.id" class="border-b">
                                <td class="px-2 py-1 w-[15%]">
                                    {{ filterDate(item.created_at) }}
                                </td>
                                <td class="px-2 py-1 w-[60%]">
                                    <div>
                                        <div><strong>Pemanfaatan:</strong> {{ item.pemanfaatan_id }} - {{
                                            item.pemanfaatan_nama }}</div>
                                        <div><strong>Catatan:</strong> {{ item.pemanfaatan_catatan }}</div>
                                    </div>
                                </td>
                                <td class="px-2 py-1 w-[20%]">
                                    <div v-if="item.file">
                                        <a-button @click="previewFile(item.file, 'file_pemanfaatan')" type="success"
                                            size="small" class="true-center gap-x-1">
                                            <Icon icon="ant-design:eye-outlined" />
                                            <span>Lihat File</span>
                                        </a-button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div v-else>
                <span>- Belum ada riwayat pemeliharaan</span>
            </div>
        </a-card>
    </div>
    <a-modal v-model:open="showModal" :title="selectedFileName" width="900px" :footer="null">
        <div v-if="loadingStatus" class="absolute inset-0 flex items-center justify-center bg-white gap-x-2">
            <div class="w-4 h-4 border-4 border-t-4 border-blue-500 border-solid rounded-full animate-ping"></div>
            Memuat....
        </div>
        <object :id="'obj-pemanfaatan-' + parent.id" type="application/pdf" class="rounded-[8px] !w-full !h-96">
            <embed :id="'embed-pemanfaatan-' + parent.id" type="application/pdf" />
            <param name="view" value="fit" />
        </object>
    </a-modal>
</template>
<style scoped>
.custom-min-scroll {
    max-height: calc(100vh - 600px);
}

@media (max-width: 768px) {
    .custom-min-scroll {
        height: auto;
    }
}
</style>