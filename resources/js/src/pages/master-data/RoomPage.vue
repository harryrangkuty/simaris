<script>
import { debounce } from "lodash-es";

const sortDirections = ['ascend', 'descend'];
const columns = [
  {
    title: "#",
    key: "number",
    align: "center",
    width: 50,
  },
  {
    title: "ID dr API",
    key: "legacy_id",
    width: 80,
    align: "center",
    sorter: (a, b) => (a.legacy_id || '').toString().localeCompare((b.legacy_id || '').toString()),
    sortDirections,
  },
  {
    title: "Kode Barcode",
    key: "code",
    width: 120,
    align: "center",
    sorter: (a, b) => (a.code || '').localeCompare(b.code || ''),
    sortDirections,
  },
  {
    title: "Nama Ruangan",
    dataIndex: "name",
    width: 220,
    ellipsis: true,
    sorter: (a, b) => (a.name || '').localeCompare(b.name || ''),
    sortDirections,
  },
  {
    title: "Gedung",
    key: "building_id",
    width: 335,
    align: "left",
    sorter: (a, b) => (a.building_id ?? 0) - (b.building_id ?? 0),
    sortDirections,
  },
  {
    title: "Lantai",
    key: "floor",
    width: 120,
    align: "left",
    sorter: (a, b) => (a.floor || '').toString().localeCompare((b.floor || '').toString()),
    sortDirections,
  },
  {
    title: "Penanggung Jawab",
    key: "person_in_charge",
    width: 180,
    align: "center",
    sorter: (a, b) => (a.person_in_charge?.name || '').toString().localeCompare((b.person_in_charge?.name || '').toString()),
    sortDirections,
  },
  {
    title: "Tanggal Registrasi",
    dataIndex: "registered_at",
    width: 180,
    align: "center",
    sorter: (a, b) => new Date(a.registered_at || 0) - new Date(b.registered_at || 0),
    sortDirections,
  },
  {
    title: "Lab?",
    key: "is_lab",
    width: 90,
    align: "center",
    sorter: (a, b) => (a.is_lab === b.is_lab ? 0 : a.is_lab ? -1 : 1),
    sortDirections,
  },
  {
    title: "Action",
    key: "action",
    align: "center",
    width: 110,
    fixed: 'right',
    className: 'column-action'
  },
];

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
      columns,
      form: {
        id: null,
        legacy_id: null,
        code: '',
        name: '',
        building_id: '',
        floor: '',
        person_in_charge_id: null,
        registered_at: null,
        is_lab: false,
        branch_id: 1,
      },
      filter: {
        branch_id: 1,
        status: "aktif",
        roles: [],
      },
      userOptions: [],
    };
  },

  computed: {
    breadcrumbItems() {
      return [
        { label: 'Dashboard', link: '/', icon: 'bi:grid' },
        { label: `${this.title}`, icon: 'line-md:folder-multiple-filled' }
      ]
    },

    filteredBuildings() {
      if (!this.form.branch_id) return [];

      return this.constant.BUILDINGS.filter(
        b => b.branch_id === this.form.branch_id
      );
    },

    filteredWarehouses() {
      if (!this.form.branch_id) return [];

      return this.constant.WAREHOUSES.filter(w =>
        w.branch_id === this.form.branch_id
      )
    },

    selectedWarehouse() {
      return this.constant.WAREHOUSES.find(
        w => w.id === this.form.warehouse_id
      )
    },

    floorList() {
      const building = this.constant.BUILDINGS.find(
        b => b.id === this.form.building_id
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

  mounted() {
    this.readData();
  },

  watch: {
    'form.branch_id'(val, oldVal) {
      if (val !== oldVal) {
        this.form.warehouse_id = null
        this.form.building_id = null
        this.form.floor = null
        this.form.unit_id = null
        this.form.room_id = null
        this.form.pj_nik = null
      }
    },
    'form.location_type': {
      immediate: true,
      handler(val) {
        if (val === 'warehouse') {
          const gudang = this.filteredWarehouses.find(w =>
            w.name?.trim().toLowerCase() === 'gudang perbekalan'
          )

          const unitGudang = this.constant.UNITS.find(u =>
            u.name?.trim().toLowerCase() === 'gudang perbekalan'
          )

          this.form.warehouse_id = gudang?.id || null

          this.form.building_id = null
          this.form.floor = null
          // this.form.pj_nik = gudang?.person_in_charge?.identifier || null
          this.form.unit_id = unitGudang?.id || null
          this.form.room_id = null
        } else {
          this.form.warehouse_id = null
          this.form.building_id = null
          this.form.floor = null
          this.form.unit_id = null
          this.form.room_id = null
          this.form.pj_nik = null
        }
      }
    },
    'form.building_id'(val, oldVal) {
      if (val !== oldVal && !this._isEditing) {
        this.form.floor = null;
        this.form.room_id = null;
        this.roomOptions = [];
      }
    },
    'form.floor'(val, oldVal) {
      if (val !== oldVal) {
        this.form.room_id = null;
        this.fetchRooms();
      }
    },
  },

  methods: {
    async readData(v) {
      const vm = this;
      vm.loadingTrue();
      let params = v ?? {
        total: vm._pagination.total,
        page: vm._pagination.current,
        results: vm._pagination.pageSize,
      };

      params = {
        req: "table",
        ...params,
        ...vm.filter,
      };

      const response = await vm.axios.get(vm.readRoute, { params });
      if (response && response.data) {
        const pagination = { ...vm._pagination };
        pagination.total = response.data.models.total;
        vm.loadingFalse();
        vm.models = response.data.models.data;
        vm._pagination = pagination;
      }
    },

    editData(m) {
      const vm = this;
      vm._isEditing = true;
      vm.form = vm.lodash.cloneDeep(m);
      vm.form.branch_id = m.building?.branch_id ?? 1;

      vm.$nextTick(() => {
        vm._isEditing = false;
        vm.showModal = true;
        vm.fetchUsers('');
        vm.fetchUsers(m.person_in_charge_id, true);
      });
    },

    async writeData() {
      const vm = this;
      vm.loadingTrue();
      const form = {
        req: 'write',
        ...vm.form
      };
      const response = await vm.axios.post(vm.writeRoute, form).catch((e) => vm.$onAjaxError(e));
      if (response && response.data) {
        vm.showModal = false;
        vm.readData();
        vm.loadingFalse();
        vm.openNotification(vm.form.id ? 'Berhasil mengubah data' : 'Berhasil menyimpan data', 'success');
      }
    },

    async deleteData(id, req) {
      const vm = this;
      const param = { req, id };
      const response = await vm.axios
        .post(vm.writeRoute, param)
        .catch((error) => vm.$onAjaxError(error));

      if (response && response.data) {
        vm.readData();
        if (req == "restore") {
          vm.openNotification("Data berhasil dikembalikan", "success");
        } else {
          vm.openNotification("Data berhasil dihapus", "success");
        }
      }
    },

    async syncData() {
      const vm = this;
      vm.loadingTrue();

      try {
        const response = await vm.axios.get(vm.readRoute, {
          params: { req: "sync" }
        });

        if (response && response.data) {
          vm.openNotification("Sinkronisasi berhasil!", "success");
          vm.readData();
        }
      } catch (e) {
        vm.$onAjaxError(e);
      } finally {
        vm.loadingFalse();
      }
    },

    newData() {
      const vm = this;
      vm.form = vm.$options.data().form;
      vm.$nextTick(function () {
        vm.showModal = true;
        vm.fetchUsers()
      })
    },

    async fetchRooms(param = "") {
      if (!this.form.building_id || !this.form.floor) {
        this.roomOptions = [];
        return;
      }

      this.loadingTrue();

      try {
        let url = "/lookups/rooms?";
        url += `building_id=${this.form.building_id}`;
        url += `&floor=${encodeURIComponent(this.form.floor)}`;

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

    async fetchUsers(param = "", append = false) {
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
      const result = Array.isArray(data) ? data : [];

      if (append) {
        const existingIds = new Set(this.userOptions.map(u => u.id));
        this.userOptions = [...this.userOptions, ...result.filter(u => !existingIds.has(u.id))];
      } else {
        this.userOptions = result;
      }
    },

    onSearchUser: debounce(function (val) {
        const vm = this;
        vm.fetchUsers(val);
    }, 500),
  },
};
</script>

<template>
  <Breadcrumb :items="breadcrumbItems" :showBackButton="true" />
  <a-card class="card">
    <a-row class="flex flex-wrap items-start justify-between mb-4 pb-4 border-b-2 gap-y-4">
      <a-col :xs="24" :sm="24" :md="6">
        <h1 class="text-base font-semibold">
          {{ title }}
        </h1>
      </a-col>
      <a-col :xs="24" :sm="24" :md="18" class="flex justify-end">
        <a-row class="flex flex-wrap gap-2 justify-start md:justify-end w-full md:w-auto">
          <a-col class="w-full md:w-auto">
            <a-select v-model:value="filter.status" class="min-w-32 lg:w-32 w-full" @change="readData">
              <a-select-option value="aktif">Aktif</a-select-option>
              <a-select-option value="non_aktif">Non Aktif</a-select-option>
            </a-select>
          </a-col>
          <a-col class="w-full md:w-auto">
            <a-input v-model:value="filter.search" @keyup.enter="readData" class="min-w-32 lg:w-64 w-full"
              placeholder="Cari ruangan ...">
              <template #addonAfter>
                <span @click="readData" class="text-white text-base">
                  <Icon icon="ant-design:search-outlined" />
                </span>
              </template>
            </a-input>
          </a-col>
          <a-col class="w-full md:w-auto">
            <a-button type="primary"
              class="bg-gradient-to-r from-red-400 via-red-500 to-red-600 hover:from-yellow-400 hover:via-yellow-600 hover:to-purple-500 flex items-center justify-center w-full"
              @click="syncData">
              <Icon icon="la:sync" class="mr-1" />
              Sinkronisasi ke API Barcode Ruangan
            </a-button>
          </a-col>
          <a-col class="w-full md:w-auto">
            <a-button type="primary"
              class="bg-gradient-to-r from-purple-400 via-blue-500 to-blue-700 hover:from-yellow-400 hover:via-yellow-600 hover:to-purple-500 flex items-center justify-center text-white font-medium border-0 shadow-md transition-all duration-300 w-full"
              @click="newData">
              <Icon icon="line-md:plus" class="mr-1" />
              Tambah Ruangan
            </a-button>
          </a-col>
        </a-row>
      </a-col>
    </a-row>
    <div class="mb-2 font-medium">
      Total: {{ _pagination.total }} Data
    </div>
    <a-table :scroll="{ x: 800 }" :columns="columns" :row-key="(obj) => obj.id" :pagination="_pagination"
      :loading="loadingStatus" :data-source="models" @change="handleTableChange">
      <template #bodyCell="{ index, column, record }">
        <template v-if="column.key == 'action' && filter.status == 'aktif'">
          <a-button-group class="flex justify-center">
            <!-- Edit -->
            <a-tooltip title="Edit Data">
              <a-button size="small" type="text" @click="editData(record)" :style="{ padding: '0 5px' }">
                <Icon icon="line-md:pencil-twotone" class="flex justify-center text-green-500 text-[24px]" />
              </a-button>
            </a-tooltip>
            <!-- Detail -->
            <!-- <a-tooltip title="Lihat Detail">
              <a-button size="small" type="text" @click="openDetail(record.id)" :style="{ padding: '0 5px' }">
                <Icon icon="line-md:file-search-twotone" class="flex justify-center text-blue-500 text-[24px]" />
              </a-button>
            </a-tooltip> -->
            <!-- Hapus -->
            <a-tooltip title="Hapus Data">
              <a-popconfirm title="Yakin menghapus data?" @confirm="deleteData(record.id, 'delete')">
                <a-button type="text" size="small" :style="{ padding: '0 5px' }">
                  <Icon icon="line-md:trash" class="flex justify-center text-red-500 text-[24px]" />
                </a-button>
              </a-popconfirm>
            </a-tooltip>
          </a-button-group>
        </template>
        <template v-if="column.key == 'action' && filter.status == 'non_aktif'">
          <a-popconfirm title="Yakin merestore data?" @confirm="deleteData(record.id, 'restore')">
            <a-button size="small" type="primary">
              <Icon icon="ant-design:rollback-outlined" />
            </a-button>
          </a-popconfirm>
        </template>
        <template v-if="column.key === 'number'">
          {{ (_pagination.current - 1) * _pagination.pageSize + (index + 1) }}
        </template>
        <template v-if="column.key === 'legacy_id'">
          <a-tag color="pink">
            {{ record.legacy_id }}
          </a-tag>
        </template>
        <template v-if="column.key === 'code'">
          <a-tag color="blue">
            {{ record.code }}
          </a-tag>
        </template>
        <template v-if="column.key === 'building_id'">
          <a-tag color="#108ee9">
            <span class="text-sm">
              {{ record.building?.name }} - {{ record.building?.branch?.name }}
            </span>
          </a-tag>
        </template>
        <template v-if="column.key === 'floor'">
          <a-tag color="blue">
            <span class="text-sm">
              Lantai {{ record.floor }}
            </span>
          </a-tag>
        </template>
        <template v-if="column.key === 'person_in_charge'">
          <a-tag color="green">
            {{ record.person_in_charge?.name || '-' }}
          </a-tag>
        </template>
        <template v-if="column.key === 'is_lab'">
          <div class="flex items-center justify-center">
            <Icon :icon="record.is_lab ? 'line-md:check-all' : 'line-md:close-circle'" class="text-[20px]"
              :class="record.is_lab ? 'text-green-600' : 'text-red-600'" />
          </div>
        </template>
      </template>
    </a-table>
  </a-card>
  <a-modal v-model:open="showModal" :title="form.id ? 'Ubah Ruangan' : 'Tambah Ruangan'" @ok="writeData"
    :mask-closable="false" :destroy-on-close="true" width="800px">
    <a-form ref="formRef" :model="form" name="roomForm" :label-col="{ span: 7 }" :wrapper-col="{ span: 16 }">
      <!-- <a-form-item label="ID Lama (API)" data-column="legacy_id">
        <a-input v-model:value="form.legacy_id" placeholder="Masukkan ID dari API (opsional)" autocomplete="off" />
      </a-form-item>

      <a-form-item label="Kode Barcode" data-column="code"
        :rules="[{ required: true, message: 'Kode barcode wajib diisi' }]">
        <a-input v-model:value="form.code" placeholder="Masukkan kode barcode" autocomplete="off" />
      </a-form-item> -->

      <a-form-item label="Nama Ruangan" data-column="name"
        :rules="[{ required: true, message: 'Nama ruangan wajib diisi' }]">
        <a-input v-model:value="form.name" placeholder="Masukkan nama ruangan" autocomplete="off" />
      </a-form-item>

      <a-form-item label="Gedung" data-column="building_id" :rules="[{ required: true }]">
        <a-select v-model:value="form.building_id" placeholder="--Pilih Gedung--" allow-clear show-search
          class="w-full" option-label-prop="label">
          <a-select-option v-for="b in filteredBuildings" :key="b.id" :value="b.id" :label="b.name">
            {{ b.name }}
          </a-select-option>
        </a-select>
      </a-form-item>

      <a-form-item label="Lantai" data-column="floor" :rules="[{ required: true, message: 'Pilih lantai' }]">
        <a-select v-model:value="form.floor" placeholder="--Pilih Lantai--" allow-clear show-search class="w-full"
          :disabled="!form.building_id" option-label-prop="label">
          <a-select-option v-for="f in floorList" :key="f.value" :value="f.value" :label="f.label">
            {{ f.label }}
          </a-select-option>
        </a-select>
      </a-form-item>

      <a-form-item label="Penanggung Jawab" data-column="person_in_charge_id" :rules="[{ required: true }]">
          <a-select v-model:value="form.person_in_charge_id" placeholder="--Pilih PJ--" show-search
              :filter-option="false" allow-clear @search="onSearchUser">
              <a-select-option v-for="s in userOptions" :key="s.id" :value="s.id" :label="s.name">
                  <div class="flex items-center gap-2">
                      <a-tag color="blue">{{ s.identifier }}</a-tag>
                      <span class="text-gray-700">{{ s.name }}</span>
                  </div>
              </a-select-option>
          </a-select>
      </a-form-item>

      <!-- <a-form-item label="Tanggal Registrasi" data-column="registered_at">
        <a-date-picker v-model:value="form.registered_at" show-time format="YYYY-MM-DD HH:mm:ss"
          placeholder="Pilih tanggal registrasi" style="width: 100%" />
      </a-form-item> -->

      <a-form-item label="Laboratorium" data-column="is_lab">
        <a-radio-group v-model:value="form.is_lab">
          <a-radio :value="true">Ya</a-radio>
          <a-radio :value="false">Tidak</a-radio>
        </a-radio-group>
      </a-form-item>
    </a-form>
  </a-modal>


</template>