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
            list_documents: [],
            list_photos: [],
            formFile: null,
            activeFile: null,
            imageFile: null,
            documentFile: null,
            files_perolehan: [],
            files_perubahan: [],
            showModal: false,
            selectedFileName: '',
        };
    },

    mounted() {
        this.readListImage();
        this.readListDocument();
        this.readListFilePerolehan();
        this.readListFilePerubahan();
    },

    methods: {

        async readListImage() {
            const vm = this;

            const params = {
                req: "table_list_image",
                id: vm.parent.id,
            };

            try {
                const response = await vm.axios.get(vm.readRoute, { params });

                if (response && response.data) {
                    vm.list_photos = response.data;
                }
            } catch (error) {
                console.log(error);
            }
        },

        async readListDocument() {
            const vm = this;

            const params = {
                req: "table_list_document",
                id: vm.parent.id,
            };

            try {
                const response = await vm.axios.get(vm.readRoute, { params });

                if (response && response.data) {
                    vm.list_documents = response.data;
                }
            } catch (error) {
                console.log(error);
            }
        },

        handleFileChange(file) {
            const vm = this
            vm.formFile = file
        },

        async uploadImage() {
            const vm = this

            const formData = new FormData();
            if (vm.formFile) {
                formData.append('file', vm.formFile, vm.formFile.name);
            }

            const response = await vm.axios.post(vm.writeRoute + '?req=image_upload&id=' + vm.parent.id, formData).catch((e) => vm.$onAjaxError(e));
            if (response && response.data) {
                vm.imageFile = null;
                vm.openNotification('Berhasil upload gambar', 'success');
                vm.readListImage();
            }
        },

        async uploadDocument() {
            const vm = this

            const formData = new FormData();
            if (vm.formFile) {
                formData.append('file', vm.formFile, vm.formFile.name);
            }

            const response = await vm.axios.post(vm.writeRoute + '?req=document_upload&id=' + vm.parent.id, formData).catch((e) => vm.$onAjaxError(e));
            if (response && response.data) {
                vm.documentFile = null;
                vm.openNotification('Berhasil upload dokumen', 'success');
                vm.readListDocument();
            }
        },

        async previewFile(file, mode) {
            const vm = this
            vm.loadingTrue();
            
            if (window.innerWidth <= 992) {
                vm.selectedFileName = file.name; 
                vm.showModal = true;
            }

            vm.activeFile = file.name;

            const params = {
                req: "preview_file",
                code: file.code,
                filename: file.name,
                mode: mode,
                kode_pemeliharaan: file.kode_pemeliharaan
            };

            const config = {
                params,
                responseType: 'blob',
            };

            const response = await vm.axios.get(vm.readRoute, config);

            if (response && response.data) {
                const url = window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }));
    
                if (window.innerWidth <= 992) {
                    document.getElementById('pdf-modal' + vm.parent.id).data = url;
                    document.getElementById('pdfx-modal' + vm.parent.id).src = url;
                } else {
                    document.getElementById('pdf-' + vm.parent.id).data = url;
                    document.getElementById('pdfx-' + vm.parent.id).src = url;
                }
            
                vm.loadingFalse();
            }
        },

        async deleteImage(filename) {
            const vm = this
            const params = {
                req: "delete_image",
                id: vm.parent.id,
                filename: filename
            };

            const response = await vm.axios.post(vm.writeRoute, params).catch((e) => vm.$onAjaxError(e));

            if (response && response.data) {
                vm.openNotification('Berhasil menghapus gambar', 'success');
                vm.readListImage();
            }
        },

        async deleteDocument(filename) {
            const vm = this
            const params = {
                req: "delete_document",
                id: vm.parent.id,
                filename: filename
            };

            const response = await vm.axios.post(vm.writeRoute, params).catch((e) => vm.$onAjaxError(e));

            if (response && response.data) {
                vm.openNotification('Berhasil menghapus dokumen', 'success');
                vm.readListDocument();
            }
        },

        async readListFilePerolehan() {
            const vm = this;
            vm.loadingTrue();
            const params = {
                req: "table_list_files_perolehan",
                id: vm.parent.id
            };

            const response = await vm.axios.get(vm.readRoute, { params })
            if (response && response.data) {
                vm.files_perolehan = response.data
                vm.loadingFalse();
            }
        },

        async readListFilePerubahan() {
            const vm = this;
            vm.loadingTrue();
            const params = {
                req: "table_list_files_perubahan",
                id: vm.parent.id
            };

            const response = await vm.axios.get(vm.readRoute, { params })
            if (response && response.data) {
                vm.files_perubahan = response.data
                vm.loadingFalse();
            }
        },

        
    },
};
</script>

<template>
    <a-row type="flex" justify="space-between" class="pb-6">
        <a-col :span="24" :lg="9" class="lg:pr-2.5 !flex flex-col mb-5 lg:mb-0">
            <div class="shadow-md rounded-[8px] overflow-hidden mb-4">
                <div class="bg-[#77828c] text-white text-lg px-6 py-2 font-medium">
                    UPLOAD FOTO
                </div>
                <a-card class="card file-upload">
                    <a-input type="file" data-column="file" accept=".jpg, .jpeg, .png" v-model:value="imageFile"
                        @change="(v) => handleFileChange(v.target.files[0])" class="flex flex-col sm:flex-row gap-2">
                        <template #suffix>
                            <a-tooltip placement="topLeft" class="w-full sm:w-auto"
                                :title="!operator_aset ? 'Tidak Dapat Mengunggah Dokumen Karena Anda bukan Operator Aset' : 
                                (list_photos.length ? 'Hanya bisa mengupload 1 photo saja. Harap hapus foto yang telah diupload jika ingin mengupload foto lain' :null) ">
                                <a-button @click="uploadImage" class="true-center w-full sm:w-auto" type="primary"
                                    :disabled="!operator_aset || list_photos.length > 0">
                                    <template #icon>
                                        <Icon icon='ant-design:upload-outlined' />
                                    </template>
                                    Unggah Foto
                                </a-button>
                            </a-tooltip>
                        </template>
                    </a-input>
                    <div class="px-2">
                        <span style="font-size: 0.85rem; color: rgba(0, 0, 0, 0.5);">
                            <Icon icon="mdi:information-outline" /> Hanya file .jpg, .jpeg, .png yang diizinkan
                        </span>
                    </div>
                    <div class="pt-3 px-2 overflow-x-auto">
                        <a-row v-for="file in list_photos" type="flex" justify="space-between" align="middle"
                            :class="['border-2', 'rounded-md', { 'border-sky-400 bg-blue-100': file.name === activeFile }]"
                            class="p-1.5 mt-1.5 cursor-pointer">
                            <a-col class="lg:w-10/12 w-3/4">
                                <a-tooltip :title="file.name">
                                    <div class="truncate">{{ file.name }}</div>
                                </a-tooltip>
                            </a-col>
                            <a-col>
                                <a-button-group style="gap: 5px;">
                                    <a-button @click="previewFile(file, 'file_profil')" type="success" size="small">
                                        <Icon icon='ant-design:eye-outlined' />
                                    </a-button>
                                    <a-popconfirm v-if="operator_aset" @confirm="deleteImage(file.name)" title="Yakin menghapus file ini?">
                                        <a-button type="danger" size="small">
                                            <Icon icon='ant-design:delete-outlined'/>
                                        </a-button>
                                    </a-popconfirm>
                                </a-button-group>
                            </a-col>
                        </a-row>
                    </div>
                </a-card>
            </div>
            <div class="shadow-md rounded-[8px] overflow-hidden mb-4">
                <div class="bg-[#77828c] text-white text-lg px-6 py-2 font-medium">
                    UPLOAD DOKUMEN
                </div>
                <a-card class="card file-upload ">
                    <a-input type="file" data-column="file" accept=".pdf" v-model:value="documentFile"
                        @change="(v) => handleFileChange(v.target.files[0])" class="flex flex-col sm:flex-row gap-2">
                        <template #suffix>
                            <a-tooltip placement="topLeft" class="w-full sm:w-auto" :title="!operator_aset ? 'Tidak Dapat Mengunggah Dokumen Karena Anda bukan Operator Aset': null">
                                <a-button @click="uploadDocument" class="true-center w-full sm:w-auto" type="primary"
                                    :disabled="!operator_aset">
                                    <template #icon>
                                        <Icon icon='ant-design:upload-outlined' />
                                    </template>
                                    Unggah Dokumen
                                </a-button>
                            </a-tooltip>
                        </template>
                    </a-input>
                    <div class="px-2">
                        <span style="font-size: 0.85rem; color: rgba(0, 0, 0, 0.5);">
                            <Icon icon="mdi:information-outline" /> Hanya file .pdf yang diizinkan
                        </span>
                    </div>
                    <div class="pt-3 px-2 overflow-y-auto" style="min-height: 10vh; max-height: 24vh;">
                        <a-row v-for="file in list_documents" type="flex" justify="space-between" align="middle"
                            :class="['border-2', 'rounded-md', { 'border-sky-400 bg-blue-100': file.name === activeFile }]"
                            class="p-1.5 mt-1.5">
                            <a-col class="lg:w-10/12 w-3/4">
                                <a-tooltip :title="file.name">
                                    <div class="truncate">{{ file.name }}</div>
                                </a-tooltip>
                            </a-col>
                            <a-col>
                                <a-button-group style="gap: 5px;">
                                    <a-button @click="previewFile(file, 'file_profil')" type="success" size="small">
                                        <Icon icon='ant-design:eye-outlined' />
                                    </a-button>
                                    <a-popconfirm v-if="operator_aset" @confirm="deleteDocument(file.name)" title="Yakin menghapus file ini?">
                                        <a-button type="danger" size="small">
                                            <Icon icon='ant-design:delete-outlined'/>
                                        </a-button>
                                    </a-popconfirm>
                                </a-button-group>
                            </a-col>
                        </a-row>
                    </div>
                </a-card>
            </div>
            <div class="shadow-md rounded-[8px] overflow-hidden mb-4">
                <div class="flex items-center bg-[#77828c] text-white text-lg px-6 py-2 font-medium rounded-t-[8px] ">
                    <span>
                        DOKUMEN PEROLEHAN
                    </span>
                </div>
                <a-card class="card file-upload h-full">
                    <div class="px-2">
                        <div v-if="files_perolehan.length > 0">
                            <a-row v-for="file in files_perolehan" type="flex" justify="space-between" align="middle"
                                :class="['border-2', 'rounded-md', { 'border-sky-400 bg-blue-100': file.name === activeFile }]"
                                class="p-1.5 mt-1.5">
                                <a-col class="lg:w-10/12 w-3/4">
                                    <a-tooltip :title="file.name">
                                        <div class="truncate">{{ file.name }}</div>
                                    </a-tooltip>
                                </a-col>
                                <a-col>
                                    <a-button @click="previewFile(file, 'file_perolehan')" type="success" size="small">
                                        <Icon icon='ant-design:eye-outlined' />
                                    </a-button>
                                </a-col>
                            </a-row>
                        </div>
                        <div v-else class="flex flex-col items-center justify-center bg-gray-50 border border-dashed border-gray-300 p-5 rounded-md">
                            <Icon icon="ant-design:folder-open-outlined" class="text-xl text-gray-400 mb-3" />
                            <p class="text-gray-600 font-medium">Tidak ada Dokumen Perolehan</p>
                        </div>
                    </div>
                </a-card>
            </div>
            <div class="shadow-md rounded-[8px] overflow-hidden">
                <div class="flex items-center bg-[#77828c] text-white text-lg px-6 py-2 font-medium rounded-t-[8px] ">
                    <span>
                        DOKUMEN PERUBAHAN
                    </span>
                </div>
                <a-card class="card file-upload h-full">
                    <div class="px-2">
                        <div v-if="files_perubahan.length > 0">
                            <a-row v-for="file in files_perubahan" type="flex" justify="space-between" align="middle"
                                :class="['border-2', 'rounded-md', { 'border-sky-400 bg-blue-100': file.name === activeFile }]"
                                class="p-1.5 mt-1.5">
                                <a-col class="md:w-7/12 w-3/6 flex justify-end">
                                    <a-tooltip :title="file.name">
                                        <div class="truncate">{{ file.name }}</div>
                                    </a-tooltip>
                                </a-col>
                                <a-col class="md:w-3/12 w-2/6  flex justify-end">
                                    <a-tag 
                                        :color="file.type === 'file_pemeliharaan' ? 'green' 
                                                : file.type === 'file_pemanfaatan' ? 'blue' 
                                                : 'red'">
                                        {{ file.type === 'file_pemeliharaan' ? 'Pemeliharaan' 
                                        : file.type === 'file_pemanfaatan' ? 'Pemanfaatan' 
                                        : 'Henti Guna' 
                                        }}
                                    </a-tag>
                                </a-col>
                                <a-col>
                                    <a-button @click="previewFile(file, file.type)" type="success" size="small">
                                        <Icon icon='ant-design:eye-outlined' />
                                    </a-button>
                                </a-col>
                            </a-row>
                        </div>
                        <div v-else class="flex flex-col items-center justify-center bg-gray-50 border border-dashed border-gray-300 p-5 rounded-md">
                            <Icon icon="ant-design:folder-open-outlined" class="text-xl text-gray-400 mb-3" />
                            <p class="text-gray-600 font-medium">Tidak ada Dokumen Perubahan</p>
                        </div>
                    </div>
                </a-card>
            </div>
        </a-col>
        <a-col :span="24" :lg="15" class="lg:pl-2.5">
            <div class="hidden lg:flex lg:h-full">
                <div v-if="loadingStatus" class="absolute inset-0 flex items-center justify-center bg-lavender gap-x-2">
                    <div class="w-4 h-4 border-4 border-t-4 border-blue-500 border-solid rounded-full animate-ping"></div>
                    Memuat....
                </div>
                <object :id="'pdf-' + parent.id" type="application/pdf" class="rounded-[8px] !w-full !h-full">
                    <embed :id="'pdfx-' + parent.id" type="application/pdf" />
                    <param name="view" value="fit" />
                </object>
            </div>
        </a-col>
        <a-modal v-model:open="showModal" :title="selectedFileName" width="900px" :footer="null">
            <div v-if="loadingStatus" class="absolute inset-0 flex items-center justify-center bg-white gap-x-2">
                <div class="w-4 h-4 border-4 border-t-4 border-blue-500 border-solid rounded-full animate-ping"></div>
                Memuat....
            </div>
            <object :id="'pdf-modal' + parent.id" type="application/pdf" class="rounded-[8px] !w-full !h-96">
                <embed :id="'pdfx-modal' + parent.id" type="application/pdf" />
                <param name="view" value="fit" />
            </object>
        </a-modal>
    </a-row>
</template>
