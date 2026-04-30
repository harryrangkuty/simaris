<script>
import { debounce } from "lodash-es";

export default {
  name: 'AlkesLocation',
  inheritAttrs: false,
  props: {
    modelData: Object,
    constant: Object,
  },

  data() {
    return {
      editingField: null,
      roomOptions: [],
      form: {
        id: null,
        field: null,
        value: null,
      },
    }
  },

  computed: {
    filteredBuildings() {
      return this.constant.BUILDINGS.filter(
        b => b.branch_id === this.modelData.branch_id
      );
    },

    floorList() {
      const building = this.constant.BUILDINGS.find(
        b => b.id === this.modelData.building_id
      );

      if (!building) return [];

      const floors = [];

      // BASEMENT FLOOR
      floors.push({
        value: 'BASEMENT',
        label: 'Lantai Basement',
      });

      // Lantai atas (1, 2, 3, ...)
      for (let i = 1; i <= building.floors_count; i++) {
        floors.push({
          value: String(i),
          label: `Lantai ${i}`,
        });
      }

      return floors;
    },
  },

  methods: {
    startEdit(field) {
      this.editingField = field
      this.form.id = this.modelData.id
      this.form.field = field
      this.form.value = this.modelData[field]

      if (field === 'room_id') {
        this.fetchRooms()
      }

    },

    cancelEdit() {
      this.editingField = null
    },

    saveEdit() {
      this.$emit('save', {
        id: this.form.id,
        field: this.form.field,
        value: this.form.value,
        done: this.cancelEdit,
      })
    },

    async fetchRooms(param = "") {
      if (!this.modelData.building_id || !this.modelData.floor) {
        this.roomOptions = [];
        return;
      }

      this.loadingTrue();

      try {
        let url = "/lookups/rooms?";
        url += `building_id=${this.modelData.building_id}`;
        url += `&floor=${encodeURIComponent(this.modelData.floor)}`;

        if (param) {
          url += `&search=${encodeURIComponent(param)}`;
        }

        url += `&limit=10`;

        const res = await fetch(url);
        const data = await res.json();

        this.roomOptions = Array.isArray(data) ? data : [data];
      } finally {
        this.loadingFalse();
      }
    },

    onSearchRoom: debounce(function (val) {
      this.fetchRooms(val);
    }, 500),
  },
}
</script>

<template>

  <a-card size="small" class="relative overflow-hidden
           rounded-xl
           bg-gradient-to-br
           from-white/80 via-white/70 to-indigo-50/40
           backdrop-blur
           border border-slate-200/60
           shadow-sm">
    <!-- accent strip -->
    <div class="absolute left-0 top-0 h-full w-1 bg-indigo-400/40"></div>

    <div class="relative space-y-4">

      <!-- HEADER -->
      <div class="flex items-center gap-2">
        <Icon icon="mdi:map-marker-outline" class="text-lg text-indigo-600" />
        <h3 class="font-semibold text-slate-700">
          Lokasi ALKES
        </h3>
      </div>

      <!-- GRID -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">

        <!-- ================= GEDUNG ================= -->
        <div class="rounded-lg bg-slate-50/80 border border-slate-200/60 p-3 space-y-1.5">

          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <Icon icon="mdi:office-building-outline" class="text-purple-600 text-lg" />
              <span class="text-xs font-medium uppercase tracking-wide text-slate-500">
                Gedung
              </span>
            </div>

            <a-button v-if="can('iis.alkes-list.update') && editingField !== 'building_id'" type="text"
              @click="startEdit('building_id')">
              <Icon icon="line-md:edit-twotone" class="text-lg text-purple-500" />
            </a-button>
          </div>

          <template v-if="editingField !== 'building_id'">
            <div class="text-sm text-slate-700">
              {{ modelData.building?.name || '-' }}
            </div>
          </template>

          <template v-else>
            <div class="flex flex-col gap-2">
              <a-select v-model:value="form.value" placeholder="Pilih Gedung">
                <a-select-option v-for="g in filteredBuildings" :key="g.id" :value="g.id">
                  {{ g.name }}
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

        <!-- ================= LANTAI ================= -->
        <div class="rounded-lg bg-slate-50/80 border border-slate-200/60 p-3 space-y-1.5">

          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <Icon icon="mdi:stairs" class="text-indigo-600 text-lg" />
              <span class="text-xs font-medium uppercase tracking-wide text-slate-500">
                Lantai
              </span>
            </div>

            <a-button v-if="can('iis.alkes-list.update') && editingField !== 'floor'" type="text"
              @click="startEdit('floor')">
              <Icon icon="line-md:edit-twotone" class="text-lg text-indigo-500" />
            </a-button>
          </div>

          <template v-if="editingField !== 'floor'">
            <div class="text-sm text-slate-700">
              {{ modelData.floor ? `Lantai ${modelData.floor}` : '-' }}
            </div>
          </template>

          <template v-else>
            <div class="flex flex-col gap-2">
              <a-select v-model:value="form.value" placeholder="Pilih Lantai" show-search>
                <a-select-option v-for="f in floorList" :key="f.value" :value="f.value" :label="f.label">
                  {{ f.label }}
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

        <!-- ================= UNIT ================= -->
        <div class="rounded-lg bg-slate-50/80 border border-slate-200/60 p-3 space-y-1.5">

          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <Icon icon="mdi:home-group" class="text-emerald-600 text-lg" />
              <span class="text-xs font-medium uppercase tracking-wide text-slate-500">
                Unit
              </span>
            </div>

            <a-button v-if="can('iis.alkes-list.update') && editingField !== 'unit_id'" type="text"
              @click="startEdit('unit_id')">
              <Icon icon="line-md:edit-twotone" class="text-lg text-emerald-500" />
            </a-button>
          </div>

          <template v-if="editingField !== 'unit_id'">
            <div class="text-sm text-slate-700">
              {{ modelData.unit?.name || '-' }}
            </div>
          </template>

          <template v-else>
            <div class="flex flex-col gap-2">
              <a-select v-model:value="form.value" show-search option-filter-prop="label" placeholder="Pilih Unit">
                <a-select-option v-for="u in constant.UNITS" :key="u.id" :value="u.id" :label="u.name">
                  <div class="flex flex-col">
                    <span class="font-medium">{{ u.name }}</span>
                    <span class="text-xs text-gray-400">
                      {{ u.department }}
                    </span>
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

        <!-- ================= RUANGAN ================= -->
        <div class="rounded-lg bg-slate-50/80 border border-slate-200/60 p-3 space-y-1.5">

          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <Icon icon="mdi:door-open" class="text-amber-600 text-lg" />
              <span class="text-xs font-medium uppercase tracking-wide text-slate-500">
                Ruangan
              </span>
            </div>

            <a-button v-if="can('iis.alkes-list.update') && editingField !== 'room_id'" type="text"
              @click="startEdit('room_id')">
              <Icon icon="line-md:edit-twotone" class="text-lg text-amber-500" />
            </a-button>
          </div>

          <template v-if="editingField !== 'room_id'">
            <div class="text-sm text-slate-700">
              {{ modelData.room?.name || '-' }}
            </div>
          </template>

          <template v-else>
            <div class="flex flex-col gap-2">
              <a-select v-model:value="form.value" show-search placeholder="Pilih Ruangan" :filter-option="false"
                :loading="loadingRoom" @search="onSearchRoom">
                <a-select-option v-for="r in roomOptions" :key="r.id" :value="r.id">
                  <div class="flex flex-col">
                    <span class="font-medium">{{ r.name }}</span>
                    <span class="text-xs text-gray-400">
                      Gedung {{ r.building?.name || r.building_id }}
                      • Lantai {{ r.floor }}
                    </span>
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

      </div>
    </div>
  </a-card>
</template>
