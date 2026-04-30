<script>

import { message } from 'ant-design-vue'

export default {
    name: 'AlkesDetail',
    inheritAttrs: false,
    props: {
        modelData: Object,
    },

    data() {
        return {
            editingField: null,
            form: {
                id: null,
                field: null,
                value: null,
                index: null,
            },
            newKey: '',
            newValue: '',
        }
    },

    methods: {
        startEdit(field) {
            this.editingField = field
            const match = field.match(/^etc-(\d+)$/)
            if (match) {
                const index = parseInt(match[1])
                this.form.id = this.modelData.id
                this.form.field = 'etc'
                this.form.index = index
                this.form.value = { ...this.modelData.etc[index] }
            } else {
                this.form.id = this.modelData.id
                this.form.field = field
                this.form.value = this.modelData[field]
            }
        },

        addEtc() {
            if (!this.newKey && !this.newValue) {
                message.warning('Harap isi key dan value')
                return
            }

            if (!this.newKey) {
                message.warning('Harap isi key')
                return
            }

            if (!this.newValue) {
                message.warning('Harap isi value')
                return
            }

            const updated = [
                ...(this.modelData.etc || []),
                {
                    key: this.newKey.trim(),
                    value: this.newValue.trim()
                }
            ]

            this.$emit('save', {
                id: this.modelData.id,
                field: 'etc',
                value: updated
            })

            this.newKey = ''
            this.newValue = ''
        },

        removeEtc(index) {
            const updated = [...(this.modelData.etc || [])]
            updated.splice(index, 1)
            this.$emit('save', { id: this.modelData.id, field: 'etc', value: updated })
        },

        saveEdit() {
            if (this.form.field === 'etc') {
                const updated = [...(this.modelData.etc || [])]
                updated[this.form.index] = { ...this.form.value }
                this.$emit('save', { id: this.modelData.id, field: 'etc', value: updated })
            } else {
                this.$emit('save', {
                    id: this.form.id,
                    field: this.form.field,
                    value: this.form.value
                })
            }
            this.cancelEdit()
        },

        cancelEdit() {
            this.editingField = null
            this.form.value = null
            this.form.index = null
        },
    }
}
</script>

<template>
    <div class="space-y-4">

        <!-- ===================== -->
        <!-- DESKRIPSI -->
        <!-- ===================== -->
        <a-card size="small" class="relative overflow-hidden
                   rounded-xl
                   bg-gradient-to-br
                   from-white/80 via-white/70 to-amber-50/40
                   backdrop-blur
                   border border-slate-200/60
                   shadow-sm">

            <!-- accent strip -->
            <div class="absolute left-0 top-0 h-full w-1 bg-purple-400/40"></div>

            <div class="relative">

                <!-- HEADER -->
                <div class="flex items-center gap-x-2 mb-3">
                    <h3 class="font-semibold text-slate-700">
                        Deskripsi Alkes
                    </h3>
                    <Icon icon="mdi:note-text-outline" class="text-lg text-amber-600 mt-0.5" />
                </div>

                <!-- VIEW MODE -->
                <template v-if="editingField !== 'description'">
                    <div class="flex gap-2 items-start">
                        <p class="flex-1 text-sm text-slate-700 whitespace-pre-line">
                            {{ modelData.description || '-' }}
                        </p>

                        <a-button v-if="can('iis.inventories-list.update')" type="text"
                            @click="startEdit('description')">
                            <Icon icon="line-md:edit-twotone" class="text-lg text-purple-500" />
                        </a-button>
                    </div>
                </template>

                <!-- EDIT MODE -->
                <template v-else>
                    <a-textarea v-model:value="form.value" rows="2" placeholder="Isi deskripsi..." />

                    <div class="flex gap-2 mt-2">
                        <a-button type="primary"
                            class="bg-gradient-to-r from-purple-400 via-blue-500 to-blue-700 hover:from-yellow-400 hover:via-yellow-600 hover:to-purple-500 flex items-center justify-center text-white font-medium border-0 shadow-md transition-all duration-300"
                            @click="saveEdit">
                            <Icon icon="line-md:confirm-circle" class="text-lg mr-1" />
                            Simpan
                        </a-button>
                        <a-button type="primary"
                            class="bg-gradient-to-r from-red-400 via-red-500 to-red-600 hover:from-yellow-400 hover:via-yellow-600 hover:to-purple-500 flex items-center justify-center text-white font-medium border-0 shadow-md transition-all duration-300"
                            @click="cancelEdit">
                            <Icon icon="line-md:close-circle" class="text-lg mr-1" />
                            Batal
                        </a-button>
                    </div>
                </template>

            </div>
        </a-card>

        <!-- ===================== -->
        <!-- KETERANGAN DETAIL -->
        <!-- ===================== -->
        <a-card size="small" class="relative overflow-hidden
                   rounded-xl
                   bg-gradient-to-br
                   from-white/80 via-white/70 to-blue-50/40
                   backdrop-blur
                   border border-slate-200/60
                   shadow-sm">

            <!-- accent strip -->
            <div class="absolute left-0 top-0 h-full w-1 bg-blue-400/40"></div>

            <div class="relative">

                <!-- HEADER -->
                <div class="flex items-center gap-x-2 mb-3">
                    <h3 class="font-semibold text-slate-700">
                        Keterangan Detail
                    </h3>
                    <Icon icon="icon-park-outline:view-grid-detail" class="text-lg text-blue-500 mt-0.5" />
                </div>

                <!-- LIST ETC -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">

                    <div v-for="(item, index) in modelData.etc || []" :key="index" class="
                        rounded-lg
                        bg-blue-100
                        border border-slate-200/50
                        py-2.5
                        px-6
                        flex flex-col
                        gap-1.5
                        transition
                        hover:bg-red-100
                        ">

                        <!-- ================= VIEW MODE ================= -->
                        <template v-if="editingField !== `etc-${index}`">
                            <div class="flex flex-col gap-1">

                                <!-- ROW ATAS: KEY + ACTION -->
                                <div class="flex items-center justify-between gap-2">
                                    <div class="text-xs text-slate-500 font-medium uppercase tracking-wide">
                                        {{ item.key }}
                                    </div>

                                    <div v-if="can('iis.alkes-list.update')" class="flex items-center">
                                        <a-button type="text" @click="startEdit(`etc-${index}`)">
                                            <Icon icon="line-md:edit-twotone" class="text-lg text-sky-500" />
                                        </a-button>

                                        <a-button type="text" danger @click="removeEtc(index)">
                                            <Icon icon="mdi:delete-outline" class="text-lg" />
                                        </a-button>
                                    </div>
                                </div>

                                <!-- VALUE -->
                                <div class="text-sm text-slate-700 leading-snug">
                                    {{ item.value }}
                                </div>

                            </div>
                        </template>

                        <!-- ================= EDIT MODE ================= -->
                        <template v-else>
                            <div class="flex flex-col gap-2">
                                <a-input v-model:value="form.value.key" placeholder="Key" />

                                <a-input v-model:value="form.value.value" placeholder="Value" />
                            </div>

                            <div class="flex gap-2 justify-end">
                                <a-button type="primary"
                                    class="bg-gradient-to-r from-purple-400 via-blue-500 to-blue-700 hover:from-yellow-400 hover:via-yellow-600 hover:to-purple-500 flex items-center justify-center text-white font-medium border-0 shadow-md transition-all duration-300"
                                    @click="saveEdit">
                                    <Icon icon="line-md:confirm-circle" class="text-lg mr-1" />
                                    Simpan
                                </a-button>
                                <a-button type="primary"
                                    class="bg-gradient-to-r from-red-400 via-red-500 to-red-600 hover:from-yellow-400 hover:via-yellow-600 hover:to-purple-500 flex items-center justify-center text-white font-medium border-0 shadow-md transition-all duration-300"
                                    @click="cancelEdit">
                                    <Icon icon="line-md:close-circle" class="text-lg mr-1" />
                                    Batal
                                </a-button>
                            </div>
                        </template>

                    </div>

                </div>

                <!-- ADD NEW -->
                <div v-if="can('iis.alkes-list.update')" class="mt-3 flex flex-col lg:flex-row gap-2">
                    <a-input v-model:value="newKey" placeholder="Key" />

                    <a-input v-model:value="newValue" placeholder="Value" />

                    <a-button type="primary"
                        class="bg-gradient-to-r from-purple-400 via-blue-500 to-blue-700 hover:from-yellow-400 hover:via-yellow-600 hover:to-purple-500 flex items-center justify-center text-white font-medium border-0 shadow-md transition-all duration-300"
                        @click="addEtc">
                        <Icon icon="line-md:plus" class="text-lg mr-1" />
                        Tambah
                    </a-button>
                </div>

            </div>
        </a-card>

        <!-- ===================== -->
        <!-- CATATAN -->
        <!-- ===================== -->
        <a-card size="small" class="relative overflow-hidden
                   rounded-xl
                   bg-gradient-to-br
                   from-white/80 via-white/70 to-amber-50/40
                   backdrop-blur
                   border border-slate-200/60
                   shadow-sm">

            <!-- accent strip -->
            <div class="absolute left-0 top-0 h-full w-1 bg-amber-400/40"></div>

            <div class="relative">

                <!-- HEADER -->
                <div class="flex items-center gap-x-2 mb-3">
                    <h3 class="font-semibold text-slate-700">
                        Catatan
                    </h3>
                    <Icon icon="mdi:note-text-outline" class="text-lg text-amber-600 mt-0.5" />
                </div>

                <!-- VIEW MODE -->
                <template v-if="editingField !== 'notes'">
                    <div class="flex gap-2 items-start">
                        <p class="flex-1 text-sm text-slate-700 whitespace-pre-line">
                            {{ modelData.notes || '-' }}
                        </p>

                        <a-button v-if="can('iis.alkes-list.update')" type="text" @click="startEdit('notes')">
                            <Icon icon="line-md:edit-twotone" class="text-lg text-amber-500" />
                        </a-button>
                    </div>
                </template>

                <!-- EDIT MODE -->
                <template v-else>
                    <a-textarea v-model:value="form.value" auto-size placeholder="Isi catatan..." />

                    <div class="flex gap-2 mt-2">
                        <a-button type="primary"
                            class="bg-gradient-to-r from-purple-400 via-blue-500 to-blue-700 hover:from-yellow-400 hover:via-yellow-600 hover:to-purple-500 flex items-center justify-center text-white font-medium border-0 shadow-md transition-all duration-300"
                            @click="saveEdit">
                            <Icon icon="line-md:confirm-circle" class="text-lg mr-1" />
                            Simpan
                        </a-button>
                        <a-button type="primary"
                            class="bg-gradient-to-r from-red-400 via-red-500 to-red-600 hover:from-yellow-400 hover:via-yellow-600 hover:to-purple-500 flex items-center justify-center text-white font-medium border-0 shadow-md transition-all duration-300"
                            @click="cancelEdit">
                            <Icon icon="line-md:close-circle" class="text-lg mr-1" />
                            Batal
                        </a-button>
                    </div>
                </template>

            </div>
        </a-card>

    </div>
</template>