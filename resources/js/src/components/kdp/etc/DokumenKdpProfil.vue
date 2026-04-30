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
            formFile: null,
            activeFile: null,
            inputFile: null,
            files_perolehan: [],
            files_pengembangan: [],
            showModal: false,
            selectedFileName: '',
        };
    },

    mounted() {
        this.readListFile();
        this.readListFilePerolehan();
        this.readListFilePengembangan();
    },

    methods: {

        async readListFile() {
            const vm = this;
            const params = {
                req: "table_list_files",
                id: vm.parent.id
            };

            const response = await vm.axios.get(vm.readRoute, { params })
            if (response && response.data) {
                vm.models = response.data;
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

        async readListFilePengembangan() {
            const vm = this;
            vm.loadingTrue();
            const params = {
                req: "table_list_files_pengembangan",
                id: vm.parent.id
            };

            const response = await vm.axios.get(vm.readRoute, { params })
            if (response && response.data) {
                vm.files_pengembangan = response.data
                vm.loadingFalse();
            }
        },

        handleFileChange(file) {
            const vm = this
            vm.formFile = file
        },

        async uploadFile() {
            const vm = this

            const formData = new FormData();
            if (vm.formFile) {
                formData.append('file', vm.formFile, vm.formFile.name);
            }

            const response = await vm.axios.post(vm.writeRoute + '?req=file_upload&id=' + vm.parent.id, formData).catch((e) => vm.$onAjaxError(e));
            if (response && response.data) {
                vm.inputFile = null;
                vm.openNotification('Berhasil upload file', 'success');
                vm.readListFile();
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
                no: file.no
            };

            const config = {
                params,
                responseType: 'blob',
            };

            const response = await vm.axios.get(vm.readRoute, config);

            if (response && response.data) {
                const url = window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }));
                
                if (window.innerWidth <= 992){
                    document.getElementById('pdf-modal' + vm.parent.id).data = url;
                    document.getElementById('pdfx-modal' + vm.parent.id).src = url;
                }else {
                    document.getElementById('pdf-' + vm.parent.id).data = url;
                    document.getElementById('pdfx-' + vm.parent.id).src = url;
                }

                vm.loadingFalse();
            }
        },

        async deleteFile(filename) {
            const vm = this
            const params = {
                req: "delete_file",
                id: vm.parent.id,
                filename: filename
            };

            const response = await vm.axios.post(vm.writeRoute, params).catch((e) => vm.$onAjaxError(e));

            if (response && response.data) {
                vm.openNotification('Berhasil menghapus file', 'success');
                vm.readListFile();
            }
        }
    },
};
</script>

<template>
    <a-row type="flex" justify="space-between" class="pb-6">
        <a-col :span="24" :lg="9" class="lg:pr-2.5 !flex flex-col mb-5 lg:mb-0">
            <div class="shadow-md rounded-[8px] overflow-hidden mb-4">
                <div class="bg-[#77828c] text-white text-lg px-6 py-2 font-medium">
                        UPLOAD DOKUMEN
                </div>
                <a-card class="card file-upload">
                    <a-input type="file" data-column="file" accept=".jpg, .jpeg, .png, .pdf" v-model:value="inputFile"
                        @change="(v) => handleFileChange(v.target.files[0])" class="flex flex-col sm:flex-row gap-2">
                        <template #suffix>
                            <a-button @click="uploadFile" class="true-center w-full sm:w-auto" type="primary" :disabled="!operator_kdp">
                                <template #icon>
                                    <Icon icon='ant-design:upload-outlined' />
                                </template>
                                Unggah
                            </a-button>
                        </template>
                    </a-input>
                    <div class="px-2">
                        <span style="font-size: 0.85rem; color: rgba(0, 0, 0, 0.5);">
                            <Icon icon="mdi:information-outline" /> Hanya file .jpg, .jpeg, .png, .pdf yang diizinkan
                        </span>
                    </div>
                    <div class="mt-2 px-2 overflow-x-auto" style="height: 10vh;">
                        <a-row v-for="file in models" type="flex" justify="space-between" align="middle"
                            :class="['border-2', 'rounded-md', { 'border-sky-400 bg-blue-100': file.name === activeFile }]" class="p-1.5 mt-1.5">
                               <a-col class="lg:w-10/12 w-3/4">
                                <a-tooltip :title="file.name">
                                    <div class="truncate">{{ file.name }}</div>
                                </a-tooltip>
                            </a-col>
                            <a-col>
                                <a-button-group style="gap: 5px;">
                                    <a-button @click="previewFile(file)" type="success" size="small">
                                        <Icon icon='ant-design:eye-outlined' />
                                    </a-button>
                                    <a-popconfirm v-if="operator_kdp" @confirm="deleteFile(file.name)" title="Yakin menghapus file ini?">
                                        <a-button type="danger" size="small">
                                            <Icon icon='ant-design:delete-outlined' />
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
                        DOKUMEN PEROLEHAN
                </div>
                <a-card class="card file-upload">
                    <div class="px-2 overflow-x-auto" style="height: 7vh;">
                        <a-row v-for="file in files_perolehan" type="flex" justify="space-between" align="middle"
                            :class="['border-2', 'rounded-md', { 'border-sky-400 bg-blue-100': file.name === activeFile }]"
                            class="p-1.5 mt-1.5">
                            <a-col class="lg:w-10/12 w-3/4">
                                <a-tooltip :title="file.name">
                                    <div class="truncate">{{ file.name }}</div>
                                </a-tooltip>
                            </a-col>
                            <a-col>
                                <a-button @click="previewFile(file, 'perolehan_kdp')" type="success" size="small">
                                    <Icon icon='ant-design:eye-outlined' />
                                </a-button>
                            </a-col>
                        </a-row>
                    </div>
                </a-card>
            </div>
            <div class="flex-grow shadow-md rounded-b-[8px]">
                <div class="flex items-center bg-[#77828c] text-white text-lg px-6 py-2 font-medium rounded-t-[8px] ">
                    <span>
                        DOKUMEN PENGEMBANGAN
                    </span>
                </div>
                <a-card class="card file-upload h-full">
                    <div class="px-2 overflow-x-auto" style="height: 7vh;">
                        <a-row v-for="file in files_pengembangan" type="flex" justify="space-between" align="middle"
                            :class="['border-2', 'rounded-md', { 'border-sky-400 bg-blue-100': file.name === activeFile }]"
                            class="p-1.5 mt-1.5">
                            <a-col class="lg:w-10/12 w-3/4">
                                <a-tooltip :title="file.name">
                                    <div class="truncate">{{ file.name }}</div>
                                </a-tooltip>
                            </a-col>
                            <a-col>
                                <a-button @click="previewFile(file, 'pengembangan_kdp')" type="success" size="small">
                                    <Icon icon='ant-design:eye-outlined' />
                                </a-button>
                            </a-col>
                        </a-row>
                    </div>
                </a-card>
            </div>
        </a-col>
        <a-col :span="24" :lg="15" class="lg:pl-2.5">
            <div v-if="loadingStatus" class="absolute inset-0 flex items-center justify-center bg-lavender gap-x-2">
                <div class="w-4 h-4 border-4 border-t-4 border-blue-500 border-solid rounded-full animate-ping"></div>
                Memuat....
            </div>
            <object :id="'pdf-' + parent.id" type="application/pdf" class="rounded-[8px] !w-full !h-full">
                <embed :id="'pdfx-' + parent.id" type="application/pdf" />
                <param name="view" value="fit" />
            </object>
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
