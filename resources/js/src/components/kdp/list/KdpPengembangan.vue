<script>

export default {
    props: {
        parent: {
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
                catatan: null,
                nilai: null,
                no_kontrak: null,
                nama_kontrak: null,
            },
            inputFile: null,
        };
    },

    methods: {

        handleFileChange(file) {
            const vm = this
            vm.formFile = file

        },

        async save() {
            const vm = this;
            vm.loadingTrue();
            
            const formData = new FormData();
            formData.append('req', 'record_development');
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
                    vm.openNotification('Berhasil menambah pengembangan', 'success');
                    vm.$emit('transaksiUpdated');
                    vm.form = {};
                    vm.formFile = null;
                    vm.inputFile = null;
                    vm.loadingFalse();
                    vm.readListFile();
                }
            } catch (error) {
                vm.$onAjaxError(error);
            } finally {
                vm.loadingFalse();
            }
        },
    },
};
</script>

<template>     
    <div class="shadow-md rounded-lg overflow-hidden">
        <div class="bg-[#77828c] text-white text-lg px-6 py-2 font-medium">
            Pengembangan
        </div>
        <a-card class="card card-white !px-5 !h-full">        
            <div class="flex flex-wrap justify-start sm:justify-end mb-4 gap-2 items-center w-full">
                <a-popconfirm title="Yakin menyimpan pengembangan?" 
                    @confirm="save()">
                    <a-button type="primary" class="mb-4 w-full sm:w-auto" :disabled="loadingStatus">Save</a-button>
                </a-popconfirm>    
            </div>
            <a-form ref="form" name="basic" :label-col="{ span: 7 }" :wrapper-col="{ span: 17 }">
                <a-form-item label="No Kontrak" data-column="no_kontrak" :rules="[{ required: true }]">
                    <a-input v-model:value="form.no_kontrak" placeholder="No Kontrak"/>
                </a-form-item>
                <a-form-item label="Nama Kontrak" data-column="nama_kontrak" :rules="[{ required: true }]">
                    <a-input v-model:value="form.nama_kontrak" placeholder="Nama Kontrak" />
                </a-form-item>
                <a-form-item label="Biaya" data-column="nilai" :rules="[{ required: true }]">
                    <a-input-number v-model:value="form.nilai" :min="0" :controls="false" 
                        :formatter="(value) => `${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ',')"
                        :parser="(value) => value.replace(/\$\s?|(,*)/g, '')"/>
                </a-form-item>
                <a-form-item label="File" :rules="[{ required: true }]">
                    <a-input type="file" data-column="file" accept=".jpg, .jpeg, .png, .pdf" v-model:value="inputFile"
                        @change="(v) => handleFileChange(v.target.files[0])">
                    </a-input>
                    <div class="px-2">
                        <span style="font-size: 0.85rem; color: rgba(0, 0, 0, 0.5);">
                            <Icon icon="mdi:information-outline" /> Hanya file .jpg, .jpeg, .png, .pdf yang diizinkan
                        </span>
                    </div>
                </a-form-item>
                <a-form-item label="Catatan" data-column="catatan">
                    <a-textarea v-model:value="form.catatan" :rows="3" placeholder="Catatan Pengembangan"></a-textarea>
                </a-form-item>
            </a-form>
        </a-card>
    </div>
</template>