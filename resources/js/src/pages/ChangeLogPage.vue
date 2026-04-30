<script>
export default {
    data() {
        return {
            filePanduanAset: null,
            filePanduanPersediaan: null,
            previewVisible: false,
            form: {
                version: null,
                text: '',
                type: [],
            },
        };
    },

    mounted() {
        this.readData();
        this.previewFile();
    },

    watch: {
        'form.type': {
            handler(newVal) {
                if (!newVal.includes('aset')) {
                    this.filePanduanAset = null;
                }
                if (!newVal.includes('persediaan')) {
                    this.filePanduanPersediaan = null;
                }
            },
            deep: true,
            immediate: true,
        },

    },

    methods: {

        async readData() {
            const vm = this;
            vm.loadingTrue();
            const params = {
                req: "change_log",
            };

            const response = await vm.axios.get(vm.readRoute, { params })
            if (response && response.data) {
                vm.models = response.data.models
                vm.loadingFalse();
            }
        },

        newData() {
            const vm = this
            Object.assign(vm.$data.form, vm.$options.data().form);
            vm.$nextTick(function () {
                vm.showModal = true;
            })
        },

        handleFileAset(file) {
            const vm = this
            vm.filePanduanAset = file
        },

        handleFilePersediaan(file) {
            const vm = this
            vm.filePanduanPersediaan = file
        },

        async writeData() {
            const vm = this;
            vm.loadingTrue();

            const formData = new FormData();
            for (const key in vm.form) {
                if (Array.isArray(vm.form[key])) {
                    vm.form[key].forEach(item => {
                        formData.append(key + '[]', item);
                    });
                } else {
                    formData.append(key, vm.form[key]);
                }
            }

            if (vm.filePanduanAset) {
                formData.append('filePanduanAset', vm.filePanduanAset, vm.filePanduanAset.name);
            }

            if (vm.filePanduanPersediaan) {
                formData.append('filePanduanPersediaan', vm.filePanduanPersediaan, vm.filePanduanPersediaan.name);
            }

            const response = await vm.axios.post(vm.writeRoute + '?req=write', formData).catch((e) => vm.$onAjaxError(e));
            if (response && response.data) {
                vm.openNotification('Berhasil menyimpan data', 'success');
                vm.showModal = false;
                vm.readData();
                vm.latestFile();
                vm.loadingFalse();
            }
        },

        togglePreview() {
            this.previewVisible = !this.previewVisible;
        },

        formattedText(text) {
            return text
                .replace(/(\*\*)(.*?)\1/g, '<strong>$2</strong>')
                .replace(/\n/g, '<br/>');
        },

        async deleteData(id) {
            const vm = this;
            vm.loadingTrue();

            const form = {
                req: 'delete',
                id: id
            };

            const response = await vm.axios.post(vm.writeRoute, form).catch((e) => vm.$onAjaxError(e));
            if (response && response.data) {
                vm.openNotification('Berhasil menghapus data', 'success');
                vm.readData();
            }
        },

        async previewFile(file) {
            const vm = this
            vm.loadingTrue();

            const params = {
                req: "preview_file",
            };

            if (file) {
                vm.activeFile = file.name;
                params.code = file.code;
                params.filename = file.name;
            }

            const config = {
                params,
                responseType: 'blob',
            };

            const response = await vm.axios.get(vm.readRoute, config);

            if (response && response.data) {
                const url = window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }));
                document.getElementById('panduan').data = url;
                document.getElementById('embed_panduan').src = url;

                vm.loadingFalse();
            }
        },

    },
};
</script>

<template>
    <a-card class="card">
        <a-row type="flex" justify="space-between">
            <a-col :span="24" :lg="11" class="lg:pr-2.5 !flex flex-col mb-20 lg:mb-0">
                <div class="shadow-md rounded-b-[8px] overflow-hidden">
                    <div
                        class="flex items-center bg-[#77828c] text-white text-lg px-6 py-2 font-medium rounded-t-[8px] ">
                        <span>CHANGE LOG</span>
                    </div>
                    <a-card class="card card-white h-full !px-5">
                        <div class="flex flex-wrap justify-start sm:justify-end gap-2 items-center w-full" v-if="sudo">
                            <a-button class ="mb-4 flex items-center justify-center w-full sm:w-auto min-w-[6rem]" type="primary" @click="newData">Tambah Data </a-button>
                        </div>
                        <div class="overflow-auto px-2" style="height: 60vh;">
                            <div v-for="(data, index) in models" :class="[index !== models.length - 1 ? 'mb-8 ' : '']">
                                <div class="flex justify-between border-b-2 pb-1">
                                    <h4 class="text-md font-semibold">
                                        Versi {{ data.version }} ({{ filterDate(data.created_at) }})
                                    </h4>
                                    <a-button-group style="gap: 5px;">
                                        <a-button v-if="data.file" @click="previewFile(data.file)" type="success"
                                            size="small">
                                            <Icon icon='ant-design:eye-outlined' />
                                        </a-button>
                                        <a-popconfirm v-if="sudo" title="Yakin menghapus data?"
                                            @confirm="deleteData(data.id)">
                                            <a-button size="small" type="danger">
                                                <Icon icon='ant-design:delete-outlined' />
                                            </a-button>
                                        </a-popconfirm>
                                    </a-button-group>
                                </div>
                                <div class="pl-5 mt-3">
                                    <p class="text-xs text-gray-500" v-html="formattedText(data.text)"></p>
                                </div>
                            </div>
                        </div>
                    </a-card>
                </div>
            </a-col>
            <a-col :span="24" :lg="13" class="lg:pl-2.5">
                <div v-if="loadingStatus" class="absolute inset-0 flex items-center justify-center bg-white gap-x-2">
                    <div class="w-4 h-4 border-4 border-t-4 border-blue-500 border-solid rounded-full animate-ping">
                    </div>
                    Memuat....
                </div>
                <object id="panduan" type="application/pdf" class="rounded-[8px] !w-full !h-full">
                    <embed id="embed_panduan" type="application/pdf" />
                    <param name="view" value="fit" />
                </object>
            </a-col>
        </a-row>
    </a-card>
    <a-modal v-model:open="showModal" title="Tambah Data" width="900px" @ok="writeData" :mask-closable="false">
        <a-form ref="form" name="basic" :label-col="{ span: 6 }" :wrapper-col="{ span: 18 }">
            <a-form-item label="Version" data-column="version" :rules="[{ required: true }]">
                <a-input v-model:value="form.version" placeholder="Isi Nomor Versi" />
            </a-form-item>
            <a-form-item label="Deskripsi" data-column="text" :rules="[{ required: true }]">
                <a-textarea v-if="!previewVisible" v-model:value="form.text"
                    placeholder="Isi Deskripsi Perubahan/Update Sistem" :rows="7" />
                <div v-if="previewVisible" v-html="formattedText(form.text)" class="border p-2"
                    style="min-height: 10.2rem;"></div>
                <a-button type="primary" @click="togglePreview">
                    <div v-if="previewVisible" class="flex items-center gap-x-1.5">
                        <Icon icon="line-md:pencil" />
                        <span>Tulis</span>
                    </div>
                    <div v-else class="flex items-center gap-x-1.5">
                        <Icon icon="line-md:watch" />
                        <span>Preview</span>
                    </div>
                </a-button>
            </a-form-item>
            <a-form-item label="Type" data-column="type" :rules="[{ required: true }]">
                <a-select v-model:value="form.type" placeholder="--Pilih Type Update--" mode="multiple">
                    <a-select-option value="aset">Aset</a-select-option>
                    <a-select-option value="persediaan">Persediaan</a-select-option>
                </a-select>
            </a-form-item>
            <a-form-item v-if="form.type.includes('aset')" label="File Panduan Aset" data-column="filePanduanAset"
                :rules="[{ required: true }]">
                <a-input type="file" data-column="file" accept=".pdf"
                    @change="(v) => handleFileAset(v.target.files[0])" />
                <div class="px-2">
                    <span style="font-size: 0.85rem; color: rgba(0, 0, 0, 0.5);">
                        <Icon icon="mdi:information-outline" /> Hanya file .pdf yang diizinkan
                    </span>
                </div>
            </a-form-item>
            <a-form-item v-if="form.type.includes('persediaan')" label="File Panduan Persediaan"
                data-column="filePanduanPersediaan" :rules="[{ required: true }]">
                <a-input type="file" data-column="file" accept=".pdf"
                    @change="(v) => handleFilePersediaan(v.target.files[0])" />
                <div class="px-2">
                    <span style="font-size: 0.85rem; color: rgba(0, 0, 0, 0.5);">
                        <Icon icon="mdi:information-outline" /> Hanya file .pdf yang diizinkan
                    </span>
                </div>
            </a-form-item>
        </a-form>
    </a-modal>
</template>