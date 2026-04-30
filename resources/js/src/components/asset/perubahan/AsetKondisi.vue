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
                kondisi: null,
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
                req: "log_kondisi",
            };

            const response = await vm.axios.get(vm.readRoute, { params });
            if (response && response.data) {
                vm.models = response.data.models;
                vm.total = response.data.total;
                vm.loadingFalse();
            }
        },

        async save() {
            const vm = this;
            vm.loadingTrue()
            const form = { 
                req: 'update_condition_single', 
                id: vm.parent.id, 
                ...vm.form 
            };
            const response = await vm.axios.post(vm.writeRoute, form).catch((e) => vm.$onAjaxError(e));
            if (response && response.data) {
                vm.openNotification('Berhasil mengubah kondisi barang', 'success');
                vm.loadingFalse();
                vm.readData();
            }
        },

        bulletColor(kondisi) {
            if (kondisi === 1) {
                return 'bg-green-500';
            } else if (kondisi === 2) {
                return 'bg-orange-500';
            } else if (kondisi === 3) {
                return 'bg-red-500';
            } else {
                return '';
            }
        },
    },
};
</script>

<template>
    <div class="shadow-md rounded-lg overflow-hidden !h-full">
        <div class="bg-[#77828c] text-white text-lg px-6 py-2 font-medium">
            Kondisi
        </div>
        <a-card class="card card-white !h-full !px-5 max-h-screen">
            <div class="w-full flex justify-end">
                <a-tooltip placement="topLeft" :title="!operator_aset ? 'Tidak Dapat Mengubah Kondisi Karena Anda bukan Operator Aset' : null">
                    <a-popconfirm title="Yakin mengubah kondisi?"
                        @confirm="save()">
                        <a-button type="primary" :disabled="loadingStatus || !operator_aset" class="mb-4 w-full sm:w-auto"> Save</a-button>
                    </a-popconfirm>      
                </a-tooltip>
            </div>
            <a-form ref="form" name="basic" :label-col="{ span: 6 }" :wrapper-col="{ span: 18 }"  labelAlign="left" class="w-full">
                <a-form-item label="Kondisi" data-column="kondisi" :rules="[{ required: true }]">
                    <a-select v-model:value="form.kondisi" placeholder="--Pilih Kondisi--" :disabled="!operator_aset">
                        <a-select-option :value="1">Baik</a-select-option>
                        <a-select-option :value="2">Rusak Ringan</a-select-option>
                        <a-select-option :value="3">Rusak Berat</a-select-option>
                    </a-select>
                </a-form-item>
            </a-form>
            <div v-if="models.length">
                <div class="flex items-center mb-2">
                    <span class="text-base font-semibold">Riwayat Perubahan Kondisi</span>
                    <Icon icon='ant-design:clock-circle-outlined' class="w-5 h-5 ml-1.5" />
                </div>
                <span>Kondisi awal : {{ labelKondisi(parent.etc.kondisi_awal, false) }}</span>
                <ul class="mt-3 overflow-y-auto pr-1 custom-min-scroll">
                    <li v-for="item in models" :key="item.id" class="border-b py-0.5">
                        <span :class="bulletColor(item.kondisi)" class="inline-block h-3 w-3 rounded-full mr-2"></span>
                        <span>{{ filterDate(item.created_at) }}, Kondisi : {{  labelKondisi(item.kondisi, false) }}</span>
                    </li>
                </ul>
            </div>
        </a-card>
    </div>
</template>
<style scoped>
.custom-min-scroll {
  height: calc(100vh - 450px);
}
@media (max-width: 768px) {
  .custom-min-scroll {
    height: auto;
  }
}
</style>