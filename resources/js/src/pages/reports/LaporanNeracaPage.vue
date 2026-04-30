<script>

export default {
    props: {
        title: String,
        constant: {
            type: Object,
            default: () => ({}),
        },
    },
    data() {
        return {
            form: {
                jenis_laporan: '3',
                tahun: null,
                date_end: null,
            },
            tanggal: null,
        };
    },

    mounted() {
        this.form.tahun = this.dayjs();
        this.tanggal = this.dayjs();
        if (this.constant) {
            this.form.kode_lokasi = this.constant.KODE_LOKASI.at(0).kode
        }
    },

    methods: {
        async readDataOld(params) {
            const vm = this;
            const tahun = vm.dayjs(vm.form.tahun).year()

            vm.form.date_end = tahun + '-12-31'

            params = {
                ...vm.form,
                tahun: tahun
            };

            if (vm.constant) {
                params.tipe = 'pembantu'
            }
            const queryString = new URLSearchParams(params).toString();

            window.open(
                window._HOST +
                `/laporan/neraca/report?${queryString}`,
                'name',
                'height=500, width=800, top=50, left=50'
            );
        },
        async readData(params) {
            const vm = this;
            const tahun = vm.dayjs(vm.form.tahun).year()

            vm.form.date_end = tahun + '-12-31'

            params = {
                ...vm.form,
                tahun: tahun
            };

            if (vm.constant) {
                params.tipe = 'pembantu'
            }
            const queryString = new URLSearchParams(params).toString();

            window.open(
                window._HOST +
                `/laporan/neraca/generate?${queryString}`,
                'name',
                'height=500, width=800, top=50, left=50'
            );
        },
    },
};
</script>

<template>
    <div>
        <a-row type="flex" justify="center">
            <a-col :span="24">
                <a-card class="card" :title="title">
                    <a-form :model="form" :label-col="{ span: 4 }" :wrapper-col="{ span: 20 }">
                        <a-form-item label="Jenis Laporan" data-column="jenis_laporan" :rules="[{ required: true }]">
                            <a-radio-group v-model:value="form.jenis_laporan" name="radioGroup">
                                <a-radio value="1" :disabled="true">Intrakomptabel</a-radio>
                                <a-radio value="2" :disabled="true">Ekstrakomptabel</a-radio>
                                <a-radio value="3">Gabungan Intrakomptabel dan Ekstrakomptabel</a-radio>
                            </a-radio-group>
                        </a-form-item>
                        <a-form-item label="Tahun" data-column="tahun" :rules="[{ required: true }]">
                            <a-date-picker v-model:value="form.tahun" picker="year" :allow-clear="false" />
                        </a-form-item>
                        <a-form-item :wrapper-col="{ offset: 4, span: 20 }" class="!hidden lg:!flex">
                            <a-button type="primary"
                                class="flex items-center justify-center w-full sm:w-auto md:w-40 sm:mr-4"
                                @click="readDataOld()">Proses (Old)</a-button>
                            <a-button type="primary" class="flex items-center justify-center w-full sm:w-auto md:w-40"
                                @click="readData()">Proses</a-button>
                        </a-form-item>
                    </a-form>
                </a-card>
            </a-col>
            <a-col class="lg:hidden sm:w-auto w-full mb-4 sm:mr-4 lg:mb-0">
                <a-button type="primary" class="flex items-center justify-center w-full mb-2 sm:w-auto min-w-[6rem]"
                    @click="readDataOld()">Proses (Old)</a-button>
            </a-col>
            <a-col class="lg:hidden sm:w-auto w-full mb-4 lg:mb-0">
                <a-button type="primary" class="flex items-center justify-center w-full mb-2 sm:w-auto min-w-[6rem]"
                    @click="readData()">Proses</a-button>
            </a-col>
        </a-row>
</div></template>