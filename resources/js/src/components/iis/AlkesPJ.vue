<script>
import { debounce } from "lodash-es";

export default {
    name: 'InventoryPJ',
    inheritAttrs: false,

    props: {
        modelData: Object,
    },

    data() {
        return {
            userOptions: [],
            editingField: null,
            form: {
                id: null,
                field: null,
                value: null,
            },
        }
    },

    computed: {
        canEditPJ() {
            return this.can('iis.alkes-list.update') &&
                this.editingField !== 'pj_nik' &&
                !this.modelData.is_handed_over &&
                !this.modelData.is_handover_active
        }
    },

    methods: {
        startEdit() {
            if (this.modelData.pj_nik) {
                this.fetchUsers(this.modelData.pj_nik);
            }
            this.fetchUsers();
            this.editingField = 'pj_nik'
            this.form.id = this.modelData.id
            this.form.field = 'pj_nik'
            this.form.value = this.modelData.pj_nik
        },

        cancelEdit() {
            this.editingField = null
        },

        saveEdit() {
            this.$emit('save', {
                id: this.form.id,
                field: 'pj_nik',
                value: this.form.value,
                done: () => {
                    this.cancelEdit()
                    window.location.reload()
                },
            })
        },

        async fetchUsers(param = "") {

            let url = "/lookups/users?";

            if (typeof param === "number") {
                url += `id=${param}`;
            } else if (/^[0-9]{6,}$/.test(param)) {
                url += `identifier=${encodeURIComponent(param)}`;
            } else {
                url += `search=${encodeURIComponent(param)}&limit=10`;
            }

            const res = await fetch(url);
            const data = await res.json();

            this.userOptions = Array.isArray(data) ? data : [];
        },

        onSearchUser: debounce(function (val) {
            const vm = this;
            vm.fetchUsers(val);
        }, 500),
    },
}
</script>

<template>

    <a-card size="small" class="relative overflow-hidden
           rounded-xl
           bg-gradient-to-br
           from-white/80 via-white/70 to-sky-50/40
           backdrop-blur
           border border-slate-200/60
           shadow-sm">
        <!-- accent strip -->
        <div class="absolute left-0 top-0 h-full w-1 bg-sky-400/40"></div>

        <div class="relative space-y-4">

            <!-- HEADER -->
            <div class="flex items-center gap-2">
                <Icon icon="mdi:account-tie-outline" class="text-lg text-sky-600" />
                <h3 class="font-semibold text-slate-700">
                    Penanggung Jawab ALKES
                </h3>
            </div>

            <!-- GRID -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">

                <!-- ================= PJ UTAMA ================= -->
                <div class="rounded-lg bg-slate-50/80 border border-slate-200/60 p-3 space-y-1.5">

                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <Icon icon="mdi:account-outline" class="text-sky-600 text-lg" />
                            <span class="text-xs font-medium uppercase tracking-wide text-slate-500">
                                Penanggung Jawab
                            </span>
                        </div>

                        <a-button v-if="canEditPJ" type="text" @click="startEdit">
                            <Icon icon="line-md:edit-twotone" class="text-lg text-sky-500" />
                        </a-button>
                    </div>

                    <!-- VIEW -->
                    <template v-if="editingField !== 'pj_nik'">
                        <div class="flex flex-col">
                            <span class="font-medium text-slate-800">
                                {{ modelData.b_user?.name || '-' }}
                            </span>
                            <span class="text-xs text-slate-400">
                                {{ modelData.pj_nik || '' }}
                            </span>
                        </div>
                    </template>

                    <!-- EDIT -->
                    <template v-else>
                        <div class="flex flex-col gap-2">
                            <a-select v-model:value="form.value" show-search option-label-prop="label"
                                option-filter-prop="label" placeholder="Pilih Penanggung Jawab" @search="onSearchUser">
                                <a-select-option v-for="u in userOptions" :key="u.identifier" :value="u.identifier"
                                    :label="`${u.identifier} - ${u.name} - ${u.position || ''}`">
                                    <div class="flex items-start gap-3">
                                        <Icon icon="mdi:account-circle-outline" class="text-sky-500 text-xl mt-0.5" />
                                        <div class="flex flex-col leading-tight">
                                            <span class="font-medium text-slate-800">
                                                {{ u.name }}
                                            </span>
                                            <span class="text-xs text-slate-400">
                                                {{ u.identifier }}
                                                <span v-if="u.position"> • {{ u.position }}</span>
                                            </span>
                                        </div>
                                    </div>
                                </a-select-option>
                            </a-select>

                            <div class="flex justify-end gap-2">
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
                        </div>
                    </template>
                </div>

                <!-- ================= PROFIL PJ ================= -->
                <div v-if="modelData.b_user" class="
                    rounded-xl
                    border border-slate-200/70
                    bg-gradient-to-br from-white via-slate-50 to-slate-100/60
                    p-4
                    space-y-3
                    shadow-sm
                ">
                    <!-- HEADER -->
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg
                   bg-indigo-100 text-indigo-600
                   flex items-center justify-center">
                            <Icon icon="mdi:account-details-outline" class="text-lg" />
                        </div>

                        <div class="flex flex-col leading-tight">
                            <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Profil User
                            </span>
                            <span class="text-[11px] text-slate-400">
                                Penanggung Jawab Inventaris
                            </span>
                        </div>
                    </div>

                    <!-- CONTENT -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">

                        <!-- Nama -->
                        <div class="flex items-start gap-2">
                            <Icon icon="mdi:account-outline" class="text-sky-500 text-lg mt-0.5" />
                            <div>
                                <div class="text-xs text-slate-400">Nama</div>
                                <div class="font-medium text-slate-800">
                                    {{ modelData.b_user.name }}
                                </div>
                            </div>
                        </div>

                        <!-- NIK -->
                        <div class="flex items-start gap-2">
                            <Icon icon="mdi:card-account-details-outline" class="text-indigo-500 text-lg mt-0.5" />
                            <div>
                                <div class="text-xs text-slate-400">NIK</div>
                                <div class="font-medium text-slate-700">
                                    {{ modelData.b_user.identifier }}
                                </div>
                            </div>
                        </div>

                        <!-- Jabatan -->
                        <div class="flex items-start gap-2">
                            <Icon icon="mdi:briefcase-outline" class="text-emerald-500 text-lg mt-0.5" />
                            <div>
                                <div class="text-xs text-slate-400">Jabatan</div>
                                <div class="font-medium text-slate-700">
                                    {{ modelData.b_user.position || '-' }}
                                </div>
                            </div>
                        </div>

                        <!-- Divisi -->
                        <div class="flex items-start gap-2">
                            <Icon icon="mdi:office-building-outline" class="text-amber-500 text-lg mt-0.5" />
                            <div>
                                <div class="text-xs text-slate-400">Divisi</div>
                                <div class="font-medium text-slate-700">
                                    {{ modelData.b_user.division || '-' }}
                                </div>
                            </div>
                        </div>

                        <!-- Departemen -->
                        <div class="flex items-start gap-2 sm:col-span-2">
                            <Icon icon="mdi:domain" class="text-rose-500 text-lg mt-0.5" />
                            <div>
                                <div class="text-xs text-slate-400">Departemen</div>
                                <div class="font-medium text-slate-700">
                                    {{ modelData.b_user.department || '-' }}
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </a-card>
</template>