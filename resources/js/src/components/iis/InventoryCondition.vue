<script>
export default {
  name: 'InventoryCondition',
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
      },
      draftDeactivatedNotes: '',
    }
  },

  methods: {
    startEdit(field) {
      this.editingField = field
      this.form.id = this.modelData.id
      this.form.field = field
      this.form.value = this.modelData[field]
    },

    cancelEdit() {
      this.editingField = null
    },

    saveEdit() {
      const field = this.form.field
      const value = this.form.value

      if (field === 'is_deactivated' && value === true) {
        if (!this.draftDeactivatedNotes.trim()) {
          this.$message.warning('Alasan penonaktifan wajib diisi')
          return
        }

        this.$emit('save', {
          id: this.form.id,
          field: 'is_deactivated',
          value: true,
          done: () => {
            this.$emit('save', {
              id: this.form.id,
              field: 'is_deactivated_notes',
              value: this.draftDeactivatedNotes,
              done: () => {
                this.draftDeactivatedNotes = ''
                this.cancelEdit()
              },
            })

          },
        })

        return
      }

      this.$emit('save', {
        id: this.form.id,
        field,
        value,
        done: this.cancelEdit,
      })
    },


  },
}
</script>

<template>
  <a-card size="small" class="relative overflow-hidden
           rounded-xl
           bg-gradient-to-br
           from-white/80 via-white/70 to-rose-50/40
           backdrop-blur
           border border-slate-200/60
           shadow-sm">
    <!-- accent strip -->
    <div class="absolute left-0 top-0 h-full w-1 bg-rose-400/40"></div>

    <div class="relative space-y-4">

      <!-- HEADER -->
      <div class="flex items-center gap-2">
        <Icon icon="mdi:clipboard-check-outline" class="text-lg text-rose-600" />
        <h3 class="font-semibold text-slate-700">
          Kondisi & Status Inventaris
        </h3>
      </div>

      <!-- GRID -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">

        <!-- ================= KONDISI ================= -->
        <div class="rounded-lg bg-slate-50/80 border border-slate-200/60 p-3 space-y-1.5">

          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <Icon icon="mdi:clipboard-text-outline" class="text-blue-600 text-lg" />
              <span class="text-xs font-medium uppercase tracking-wide text-slate-500">
                Kondisi
              </span>
            </div>

            <a-button v-if="can('iis.inventories-list.update') && editingField !== 'condition'" type="text"
              @click="startEdit('condition')">
              <Icon icon="line-md:edit-twotone" class="text-lg text-blue-500" />
            </a-button>
          </div>

          <!-- VIEW -->
          <template v-if="editingField !== 'condition'">
            <div class="text-sm font-medium" :class="{
              'text-emerald-600': modelData.condition === 'Baik',
              'text-amber-600': modelData.condition === 'Rusak Ringan',
              'text-rose-600': modelData.condition === 'Rusak Berat',
            }">
              {{ modelData.condition || '-' }}
            </div>
          </template>

          <!-- EDIT -->
          <template v-else>
            <div class="flex flex-col gap-2">
              <a-select v-model:value="form.value">
                <a-select-option value="Baik">Baik</a-select-option>
                <a-select-option value="Rusak Ringan">Rusak Ringan</a-select-option>
                <a-select-option value="Rusak Berat">Rusak Berat</a-select-option>
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

        <!-- ================= STATUS ================= -->
        <div class="rounded-lg bg-slate-50/80 border border-slate-200/60 p-3 space-y-1.5">

          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <Icon icon="mdi:power" class="text-rose-600 text-lg" />
              <span class="text-xs font-medium uppercase tracking-wide text-slate-500">
                Status
              </span>
            </div>

            <a-button v-if="can('iis.inventories-list.update') && editingField !== 'is_deactivated'" type="text"
              @click="startEdit('is_deactivated')">
              <Icon icon="line-md:edit-twotone" class="text-lg text-rose-500" />
            </a-button>
          </div>

          <!-- VIEW -->
          <template v-if="editingField !== 'is_deactivated'">
            <a-tag :color="modelData.is_deactivated ? 'red' : 'green'">
              {{ modelData.is_deactivated ? 'Nonaktif' : 'Aktif' }}
            </a-tag>
          </template>

          <!-- EDIT -->
          <template v-else>
            <div class="flex flex-col gap-2">
              <a-select v-model:value="form.value">
                <a-select-option :value="false">Aktif</a-select-option>
                <a-select-option :value="true">Nonaktif</a-select-option>
              </a-select>

              <a-textarea v-if="form.value === true" v-model:value="draftDeactivatedNotes"
                placeholder="Alasan penonaktifan..." :auto-size="{ minRows: 2 }" />

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

        <!-- ================= CATATAN ================= -->
        <div v-if="modelData.is_deactivated" class="lg:col-span-2 rounded-lg
                 bg-rose-50/70
                 border border-rose-200/60
                 p-3 space-y-1.5">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <Icon icon="streamline-ultimate-color:notes-paper-text" class="text-rose-600 text-lg" />
              <span class="text-xs font-medium uppercase tracking-wide text-slate-500">
                Catatan Penonaktifan
              </span>
            </div>

            <a-button v-if="editingField !== 'is_deactivated_notes'" type="text" size="small"
              @click="startEdit('is_deactivated_notes')">
              <Icon icon="line-md:edit-twotone" class="text-base text-rose-500" />
            </a-button>
          </div>

          <template v-if="editingField !== 'is_deactivated_notes'">
            <p class="text-sm text-slate-700 leading-relaxed">
              {{ modelData.is_deactivated_notes }}
            </p>
          </template>

          <template v-else>
            <div class="flex flex-col gap-2">
              <a-textarea v-model:value="form.value" :auto-size="{ minRows: 2 }" />

              <div class="flex justify-end gap-2">
                <a-button type="primary" size="small" @click="saveEdit">
                  <Icon icon="line-md:confirm-circle" />
                </a-button>
                <a-button type="text" size="small" danger @click="cancelEdit">
                  <Icon icon="line-md:close-circle" />
                </a-button>
              </div>
            </div>
          </template>
        </div>

      </div>
    </div>
  </a-card>
</template>