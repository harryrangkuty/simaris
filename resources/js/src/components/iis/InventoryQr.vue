<script>
export default {
    name: 'InventoryQr',
    inheritAttrs: false,

    props: {
        modelData: Object,
    },

    data() {
        return {
            pdfContent: null,
            form_preview_label: {
                mode: 'mini',
                tipe_kertas: 'stiker',
            },
        }
    },

    mounted() {
        this.updatePreview()
    },

    watch: {
        'form_preview_label.mode': 'updatePreview',
        'form_preview_label.tipe_kertas': 'updatePreview',
    },

    methods: {
        updatePreview() {
            const params = new URLSearchParams({
                req: 'single_label_preview',
                id: this.modelData.id,
                mode: this.form_preview_label.mode,
                tipe_kertas: this.form_preview_label.tipe_kertas,
            })

            this.pdfContent = `${this.readRoute}?${params.toString()}`
        },
    },
}
</script>


<template>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        <!-- LEFT : FORM -->
        <div class="md:col-span-1">
            <a-card class="rounded-2xl shadow-sm" title="Pengaturan Label" size="small">
                <a-form layout="vertical">

                    <a-form-item label="Mode Label">
                        <a-select v-model:value="form_preview_label.mode" placeholder="Pilih Mode">
                            <a-select-option value="standard">
                                Standard
                            </a-select-option>
                            <a-select-option value="mini">
                                Mini (50mm × 30mm)
                            </a-select-option>
                        </a-select>
                    </a-form-item>

                    <a-form-item label="Tipe Kertas">
                        <a-select v-model:value="form_preview_label.tipe_kertas" placeholder="Pilih Tipe Kertas">
                            <a-select-option value="kertas-biasa">
                                Kertas Biasa
                            </a-select-option>
                            <a-select-option value="stiker">
                                Kertas Stiker
                            </a-select-option>
                        </a-select>
                    </a-form-item>

                </a-form>
            </a-card>
        </div>

        <!-- RIGHT : PREVIEW -->
        <div class="md:col-span-2">
            <a-card class="rounded-2xl shadow-sm" size="small">
                <template #title>
                    <div class="flex items-center gap-2">
                        <span class="font-semibold">Preview Label</span>
                        <span class="text-xs text-gray-400">
                            (Auto update)
                        </span>
                    </div>
                </template>

                <div class="flex justify-center items-center bg-slate-100 rounded-xl p-4">
                    <iframe v-if="pdfContent" :src="pdfContent + '#toolbar=0'"
                        class="rounded-xl bg-white shadow-inner transition-all" width="600" height="300" />
                </div>
            </a-card>
        </div>

    </div>
</template>
