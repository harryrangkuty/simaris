<script>
export default {
    props: {
        parent: {
            type: Object,
            default: () => ({}),
        },
        mode: {
            type: String,
            default: "Card",
        },
    },
    data() {
        return {
            form: {
                henti_guna: null,
            },
            inputFile: null,
            selectedFileName: null,
        };
    },

    mounted() {
        this.readData();
    },

    methods: {
        async readData() {
            const vm = this;
            vm.loadingTrue();
            const params = {
                id: this.parent.id,
                req: "log_henti_guna",
            };

            const response = await vm.axios.get(vm.readRoute, { params });
            if (response && response.data) {
                vm.models = response.data.models;
                vm.total = response.data.total;
                vm.loadingFalse();
            }
        },

        handleFileChange(file) {
            const vm = this;
            vm.formFile = file;
        },

        async save() {
            const vm = this;
            vm.loadingTrue();

            const formData = new FormData();
            formData.append("req", "asset_deactivate_single");
            formData.append("id", vm.parent.id);

            Object.keys(vm.form).forEach((key) => {
                formData.append(key, vm.form[key]);
            });

            if (vm.formFile) {
                formData.append("file", vm.formFile, vm.formFile.name);
            }

            try {
                const response = await vm.axios.post(vm.writeRoute, formData);

                if (response && response.data) {
                    vm.openNotification(
                        "Berhasil mengubah penghentigunaan",
                        "success"
                    );
                    vm.form = {};
                    vm.formFile = null;
                    vm.inputFile = null;
                    (vm.pertambahan_masa_manfaat = null), vm.loadingFalse();
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
                responseType: "blob",
            };

            const response = await vm.axios.get(vm.readRoute, config);

            if (response && response.data) {
                const url = window.URL.createObjectURL(
                    new Blob([response.data], { type: "application/pdf" })
                );
                document.getElementById("obj-henti-guna" + vm.parent.id).data =
                    url;
                document.getElementById(
                    "embed-henti-guna-" + vm.parent.id
                ).src = url;

                vm.loadingFalse();
            }
        },

        bulletColor(type) {
            if (type === "henti-guna") {
                return "bg-red-500";
            } else {
                return "bg-green-500";
            }
        },
    },
};
</script>

<template>
    <div class="shadow-md rounded-lg overflow-hidden !h-full">
        <div class="bg-[#77828c] text-white text-lg px-6 py-2 font-medium">
            Penghentian Penggunaan
        </div>
        <a-card class="card card-white !h-full !px-5 max-h-screen">
            <div class="w-full flex justify-end">
                <a-tooltip placement="topLeft" :title="!operator_aset
                        ? 'Tidak Dapat Mengubah Pemeliharaan Karena Anda bukan Operator Aset'
                        : null
                    ">
                    <a-popconfirm title="Yakin menyimpan?" @confirm="save()">
                        <a-button type="primary" :disabled="!operator_aset" class="mb-4 w-full sm:w-auto">Save</a-button>
                    </a-popconfirm>
                </a-tooltip>
            </div>
            <a-form ref="form" name="basic" :label-col="{ span: 6 }" :wrapper-col="{ span: 18 }" labelAlign="left"
                class="w-full">
                <a-form-item label="Status" data-column="kondisi" :rules="[{ required: true }]">
                    <a-select v-model:value="form.henti_guna" placeholder="--Pilih Status--" :disabled="!operator_aset">
                        <a-select-option :value="0">Aktif Dipakai</a-select-option>
                        <a-select-option :value="1">Dihentigunakan</a-select-option>
                    </a-select>
                </a-form-item>
                <a-form-item label="File" :rules="[{ required: true }]">
                    <a-input type="file" data-column="file" accept=".jpg, .jpeg, .png, .pdf" v-model:value="inputFile"
                        @change="(v) => handleFileChange(v.target.files[0])">
                    </a-input>
                    <div class="px-2">
                        <span style="
                                font-size: 0.85rem;
                                color: rgba(0, 0, 0, 0.5);
                            ">
                            <Icon icon="mdi:information-outline" /> Hanya file
                            .jpg, .jpeg, .png, .pdf yang diizinkan
                        </span>
                    </div>
                </a-form-item>
            </a-form>
            <div class="flex items-center mb-2">
                <span class="text-base font-semibold">Riwayat Penghentian Penggunaan</span>
                <Icon icon="ant-design:clock-circle-outlined" class="w-5 h-5 ml-1.5" />
            </div>
            <div v-if="models.length">
                <div class="mt-3 overflow-y-auto pr-1 custom-min-scroll">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr>
                                <th class="text-left px-2 py-1 border-b"></th>
                                <th class="text-left px-2 py-1 border-b">
                                    Tanggal
                                </th>
                                <th class="text-left px-2 py-1 border-b">
                                    Status
                                </th>
                                <th class="text-left px-2 py-1 border-b">
                                    File
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in models" :key="item.id" class="border-b">
                                <td :class="bulletColor(item.type)" class="inline-block h-3 w-3 rounded-full"></td>
                                <td class="px-2 py-1 w-[20%]">
                                    {{ filterDate(item.created_at) }}
                                </td>
                                <td class="px-2 py-1 w-[60%]">
                                    {{
                                        item.type == "henti-guna"
                                        ? "Diberhentigunakan"
                                        : "Aktif dipakai"
                                    }}
                                </td>
                                <td class="px-2 py-1 w-[20%]">
                                    <div v-if="item.file">
                                        <a-button @click="
                                            previewFile(
                                                item.file,
                                                'file_penghentigunaan'
                                            )
                                            " type="success" size="small"
                                              class="true-center gap-x-1">
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
                <span>- Belum ada riwayat penghentigunaan</span>
            </div>
        </a-card>
    </div>
    <a-modal v-model:open="showModal" :title="selectedFileName" width="900px" :footer="null">
        <div v-if="loadingStatus" class="absolute inset-0 flex items-center justify-center bg-white gap-x-2">
            <div class="w-4 h-4 border-4 border-t-4 border-blue-500 border-solid rounded-full animate-ping"></div>
            Memuat....
        </div>
        <object :id="'obj-henti-guna' + parent.id" type="application/pdf" class="rounded-[8px] !w-full !h-96">
            <embed :id="'embed-henti-guna-' + parent.id" type="application/pdf" />
            <param name="view" value="fit" />
        </object>
    </a-modal>
</template>
<style scoped>
.custom-min-scroll {
    max-height: calc(100vh - 450px);
}

@media (max-width: 768px) {
    .custom-min-scroll {
        height: auto;
    }
}
</style>
