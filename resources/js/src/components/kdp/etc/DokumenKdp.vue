<script>
export default {
    emits: ['dokumenEmpty','detailDokumenEmpty'],
    props: {
        parent: {
            type: Object,
            default: () => ({}),
        },
    },

    data() {
        return {
            document_detail: [],
            formFile: null,
            activeFile: null,
            inputFile: null,
            form: {
                tgl_surat: null,
                no_surat: null,
            },
            showModal: false,
            selectedFileName: '',
        };
    },

    mounted() {
        this.readData();
        this.readListFile();
    },

    computed: {
        'locked': function () {
            return this.parent.status == 'CLOSE' || this.parent.status == 'FINISH';
        },
    },

    methods: {

        async readData() {
            const vm = this;
            vm.loadingTrue();
            const params = {
                id: vm.parent.id,
                req: "document_detail",
            };

            const response = await vm.axios.get(vm.readRoute, { params });
            if (response && response.data) {
                vm.document_detail = response.data.models;
                vm.form = { 
                    ...vm.form,
                    ...vm.document_detail };
                vm.loadingFalse();
                vm.checkFormValues();
            }
        },

        async readListFile() {
            const vm = this;

            const params = {
                req: "table_list_files",
                id: vm.parent.id
            };

            const response = await vm.axios.get(vm.readRoute, { params });

            if (response && response.data) {
                vm.models = response.data;
                vm.$emit('dokumenEmpty', vm.models.length === 0);
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

        async previewFile(file) {
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
                filename: file.name
            };

            const config = {
                params,
                responseType: 'blob',
            };

            const response = await vm.axios.get(vm.readRoute, config);

            if (response && response.data) {
                const url = window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }));
               
                if(window.innerWidth <= 992) {
                document.getElementById('pdf-modal' + vm.parent.id).data = url;
                document.getElementById('pdfx-modal' + vm.parent.id).src = url;
                }else {
                    document.getElementById('pdf-'+vm.parent.id).data = url;
                    document.getElementById('pdfx-'+vm.parent.id).data = url;
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
        },

        async save() {
            const vm = this;
            vm.loadingTrue()
            const form = { 
                req: 'write_dokumen', 
                id: vm.parent.id, 
                ...vm.form };
            const response = await vm.axios.post(vm.writeRoute, form).catch((e) => vm.$onAjaxError(e));
            if (response && response.data) {
                vm.openNotification('Berhasil menyimpan detail dokumen', 'success');
                vm.loadingFalse();
                vm.readData();
            }
        },

        checkFormValues() {
            const vm = this;
            if (!vm.document_detail.tgl_surat || !vm.document_detail.no_surat) {
                vm.$emit('detailDokumenEmpty', true);
            } else {
                vm.$emit('detailDokumenEmpty', false);
            }
        },

        getTooltipMessage(actionType) {
            if (!this.operator_kdp) {
                return `Tidak Dapat ${actionType} Karena Anda bukan Operator Aset`;
            } else if (this.locked) {
                return `Tidak Dapat ${actionType} Karena Status telah ${this.parent.status === 'FINISH' ? 'Teregistrasi' : 'Terkunci'}`;
            }
            return null;
        },
    },
};
</script>

<template>
    <a-row type="flex" justify="space-between" class="pb-6">
        <a-col :span="24" :lg="9" class="pr-2.5 !flex flex-col mb-5 lg:mb-0">
                <div class="shadow-md rounded-[8px] overflow-hidden mb-4">
                    <div class="bg-[#77828c] text-white text-lg px-6 py-2 font-medium">
                        DETAIL DOKUMEN
                    </div>
                    <a-tooltip placement="topRight" :title="!this.form.tgl_surat || !this.form.no_surat ? 'Isi detail dokumen' : null">
                        <Icon v-if="!this.form.tgl_surat || !this.form.no_surat" icon="line-md:alert-circle-twotone" class="text-red-400 ml-1 h-5 w-5" />
                    </a-tooltip>

                <a-card class="card file-upload">
                    <div class="w-full flex justify-end">
                        <a-tooltip placement="topLeft" class="w-full sm:w-auto" :title="getTooltipMessage('Mengubah Detail Dokumen')">
                            <a-popconfirm title="Yakin menyimpan detail dokumen?"
                                @confirm="save()" :disabled="!operator_kdp || locked">
                                <a-button type="primary" class="w-full sm:w-auto mb-4" :disabled="locked || !operator_kdp"> Simpan</a-button>
                            </a-popconfirm>      
                        </a-tooltip>
                    </div>
                    <a-form ref="form" name="basic" :label-col="{ span: 5 }" :wrapper-col="{ span: 19 }">
                        <a-form-item label="Tanggal Dokumen" data-column="tgl_surat">
                            <a-date-picker v-model:value="form.tgl_surat" :value-format="date_format" :allow-clear="true"
                                placeholder="Tanggal Dokumen" />
                        </a-form-item>
                        <a-form-item label="Nomor Dokumen" data-column="no_surat">
                            <a-input v-model:value="form.no_surat" placeholder="Isi Nomor Dokumen Utama" />
                        </a-form-item>
                    </a-form>
                </a-card>
            </div>
            <div class="shadow-md rounded-[8px] overflow-hidden mb-4">
                    <div class="bg-[#77828c] text-white text-lg px-6 py-2 font-medium">
                        UPLOAD DOKUMEN
                    </div>
                    <a-tooltip placement="topRight" :title="!models.length > 0 ? 'Upload dokumen' : null">
                        <Icon v-if="!models.length > 0" icon="line-md:alert-circle-twotone" class="text-red-400 ml-1 h-5 w-5" />
                    </a-tooltip>
                    
                <a-card class="card file-upload">
                    <a-input type="file" data-column="file" accept=".jpg, .jpeg, .png, .pdf" v-model:value="inputFile"
                        @change="(v) => handleFileChange(v.target.files[0])" class="flex flex-col sm:flex-row gap-2">
                        <template #suffix>
                            <a-tooltip placement="topLeft" class="w-full sm:w-auto" :title="getTooltipMessage('Mengunggah Dokumen')">
                                <a-button @click="uploadFile" class="w-full sm:w-auto" type="primary"
                                    :disabled="locked || !operator_kdp">
                                    <template #icon>
                                        <Icon icon='ant-design:upload-outlined' />
                                    </template>
                                    Unggah
                                </a-button>
                            </a-tooltip>
                        </template>
                    </a-input>
                    <div class="px-2">
                        <span style="font-size: 0.85rem; color: rgba(0, 0, 0, 0.5);">
                            <Icon icon="mdi:information-outline" /> Hanya file .jpg, .jpeg, .png, .pdf yang diizinkan
                        </span>
                    </div>
                    <div class="mt-4 px-2 overflow-x-auto" style="max-height: 20vh;">
                        <a-row v-for="file in models" type="flex" justify="space-between" align="middle"
                            :class="['border-2', 'rounded-md', { 'border-sky-400 bg-blue-100': file.name === activeFile }]"
                            class="p-1.5 mt-1.5">
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
                                    <a-tooltip v-if="operator_kdp" placement="topLeft" :title="locked ? `Tidak Dapat Menghapus Dokumen Karena Status sudah ${this.parent.status === 'FINISH' ? 'Teregistrasi' : 'Terkunci'}` : null ">
                                        <a-popconfirm @confirm="deleteFile(file.name)" :disabled="locked" title="Yakin menghapus file ini?">
                                            <a-button type="danger" size="small" :disabled="locked">
                                                <Icon icon='ant-design:delete-outlined'/>
                                            </a-button>
                                        </a-popconfirm>
                                    </a-tooltip>
                                </a-button-group>
                            </a-col>
                        </a-row>
                    </div>
                </a-card>
            </div>
        </a-col>
        <a-col :span="15" class="px-2">
            <div v-if="loadingStatus" class="absolute inset-0 flex items-center justify-center bg-lavender gap-x-2">
                <div class="w-4 h-4 border-4 border-t-4 border-blue-500 border-solid rounded-full animate-ping"></div>
                Memuat....
            </div>
            <object :id="'pdf-' + parent.id" type="application/pdf" class="rounded-[8px] !h-full w-full">
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
