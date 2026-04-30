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
                penyelesaian_id: null,
            }
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
                id: vm.parent.id,
                req: "log_penyelesaian",
            };

            const response = await vm.axios.get(vm.readRoute, { params });
            if (response && response.data) {
                vm.models = response.data.models
                vm.total = response.data.total
                vm.loadingFalse();
            }
        },

        async save() {
            const vm = this;
            vm.loadingTrue()
            const form = { 
                req: 'write_penyelesaian', 
                id: vm.parent.id, 
                ...vm.form 
            };
            const response = await vm.axios.post(vm.writeRoute, form).catch((e) => vm.$onAjaxError(e));
            if (response && response.data) {
                vm.openNotification('Berhasil menyelesaikan KDP', 'success');
                vm.loadingFalse();
                vm.readData();
                vm.$emit('transaksiUpdated');
            }
        },

    },
};
</script>

<template>
    <div class="shadow-md rounded-lg overflow-hidden">
        <div class="bg-[#77828c] text-white text-lg px-6 py-2 font-medium">
            Penyelesaian
        </div>
        <a-card class="card card-white !px-5 !h-full">        
            <template v-if="!models.length" #extra>
                <a-popconfirm title="Yakin menyelesaikan?"
                    @confirm="save()">
                    <a-button type="primary" disabled> Selesaikan</a-button>
                </a-popconfirm>      
            </template>
            <div v-if="models.length">
                <div class="flex items-center mb-2">
                    <span class="text-base font-semibold">Tanggal Penyelesaian</span>
                    <Icon icon='ant-design:clock-circle-outlined' class="w-5 h-5 ml-1.5" />
                </div>
                <ul class="mt-3">
                    <li v-for="item in models" :key="item.id" class="border-b py-0.5">
                        <span class="bg-green-500 inline-block h-3 w-3 rounded-full mr-2"></span>
                        <span>Diselesaikan Pada {{ filterDate(item.created_at) }}</span>
                    </li>
                </ul>
            </div>
            <div v-else>
                <a-form ref="form" name="basic" :label-col="{ span: 7 }"
                    :wrapper-col="{ span: 17 }">
                    <a-form-item label="Pilih Penyelesaian" data-column="penyelesaian_id">
                        <a-select v-model:value="form.penyelesaian_id" placeholder="--Pilih Penyelesaian--" disabled>
                            <a-select-option :value="105">Penyelesaian Pembangunan Dengan KDP</a-select-option>
                            <a-select-option :value="599">Reklasifikasi KDP menjadi Barang Jadi</a-select-option>
                        </a-select>
                    </a-form-item>
                </a-form>
            </div>
        </a-card>
    </div>
</template>