<script>
import { Icon } from '@iconify/vue';

const sortDirections = ['ascend', 'descend'];

const columns = [
  { title: '#', key: 'number', align: 'center', width: 50 },
  {
    title: 'Judul Menu',
    dataIndex: 'title',
    width: 220,
    ellipsis: true,
    sorter: (a, b) => (a.title || '').localeCompare(b.title || ''),
    sortDirections,
  },
  {
    title: 'Tipe',
    key: 'type',
    width: 120,
    align: 'center',
  },
  {
    title: 'Key',
    dataIndex: 'key',
    width: 160,
    ellipsis: true,
  },
  {
    title: 'URL',
    dataIndex: 'url',
    width: 220,
    ellipsis: true,
  },
  {
    title: 'Permission',
    key: 'permission',
    width: 250,
  },
  {
    title: 'Order',
    dataIndex: 'order',
    width: 80,
    align: 'center',
  },
  {
    title: 'Action',
    key: 'action',
    align: 'center',
    width: 110,
    fixed: 'right',
  },
];

export default {
  props: {
    title: String,
    constant: Object,
  },

  data() {
    return {
      columns,
      showModal: false,
      form: this.defaultForm(),
      filter: {
        status: 'aktif',
        search: null,
      },
    };
  },

  computed: {
    breadcrumbItems() {
      return [
        { label: 'Dashboard', link: '/', icon: 'bi:grid' },
        { label: `${this.title}`, icon: 'line-md:folder-multiple-filled' }
      ]
    },
    filteredPermissions() {
      const all = this.constant.permissions
      if (!this.form.key) return all

      const prefix = this.form.key.trim().toLowerCase()
      return all.filter(p => p.name.toLowerCase().startsWith(prefix + '.'))
    }
  },

  mounted() {
    this.readData();
  },

  methods: {
    defaultForm() {
      return {
        id: null,
        type: 'menu',
        parent_id: null,
        title: null,
        key: null,
        url: null,
        permissions: [],
        icon: null,
        order: 0,
        is_active: true,
      };
    },

    newData() {
      this.form = this.defaultForm();
      this.showModal = true;
    },

    editData(row) {
      const data = this.lodash.cloneDeep(row);
      data.permissions = row.permissions ? row.permissions.map(p => p.id) : [];
      this.form = data;
      this.showModal = true;
    },

    async writeData() {
      this.loadingTrue();

      const payload = { req: 'write', ...this.form };

      const res = await this.axios
        .post(this.writeRoute, payload)
        .catch(e => this.$onAjaxError(e));

      if (res?.data) {
        this.showModal = false;
        this.readData();
        this.openNotification('Data berhasil disimpan', 'success');
      }

      this.loadingFalse();
    },

    async readData(v) {
      this.loadingTrue();

      let params = v ?? {
        page: this._pagination.current,
        results: this._pagination.pageSize,
      };

      params = { req: 'table', ...params, ...this.filter };

      const res = await this.axios.get(this.readRoute, { params });
      if (res?.data) {
        this.models = res.data.models.data;
        this._pagination.total = res.data.models.total;
      }

      this.loadingFalse();
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
  },
};
</script>

<template>
  <Breadcrumb :items="breadcrumbItems" :showBackButton="true" />
  <a-card>
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
              placeholder="Cari Menu ...">
              <template #addonAfter>
                <span @click="readData" class="text-white text-base">
                  <Icon icon="ant-design:search-outlined" />
                </span>
              </template>
            </a-input>
          </a-col>
          <a-col class="w-full md:w-auto">
            <a-button type="primary"
              class="bg-gradient-to-r from-purple-400 via-blue-500 to-blue-700 hover:from-yellow-400 hover:via-yellow-600 hover:to-purple-500 flex items-center justify-center text-white font-medium border-0 shadow-md transition-all duration-300 w-full"
              @click="newData">
              <Icon icon="line-md:plus" class="mr-1" />
              Tambah Menu
            </a-button>
          </a-col>
        </a-row>
      </a-col>
    </a-row>
    <div class="mb-2 font-medium">
      Total: {{ _pagination.total }} Data
    </div>
    <a-table :columns="columns" :row-key="obj => obj.id" :pagination="_pagination" :loading="loadingStatus"
      :data-source="models" :scroll="{ x: 1000 }" @change="handleTableChange">
      <template #bodyCell="{ index, column, record }">
        <template v-if="column.key === 'number'">
          {{ (_pagination.current - 1) * _pagination.pageSize + (index + 1) }}
        </template>
        <template v-if="column.key === 'permission'">
          <div v-if="record.permissions?.length">
            <a-tag color="#f50" v-for="p in record.permissions" :key="p.id">
              {{ p.name }}
            </a-tag>
          </div>
          <span v-else>-</span>
        </template>
        <template v-if="column.key === 'type'">
          <a-tag :color="record.type === 'header' ? '#722ed1' : '#108ee9'">
            {{ record.type }}
          </a-tag>
        </template>
        <template v-if="column.key === 'action'">
          <a-button-group class="flex justify-center">
            <a-tooltip title="Edit">
              <a-button type="text" size="small" @click="editData(record)">
                <Icon icon="line-md:pencil-twotone" class="text-green-500 text-[22px]" />
              </a-button>
            </a-tooltip>

            <a-tooltip title="Hapus">
              <a-popconfirm title="Yakin menghapus menu?" @confirm="deleteData(record.id, 'delete')">
                <a-button type="text" size="small">
                  <Icon icon="line-md:trash" class="text-red-500 text-[22px]" />
                </a-button>
              </a-popconfirm>
            </a-tooltip>
          </a-button-group>
        </template>
      </template>
    </a-table>
  </a-card>

  <!-- MODAL -->
  <a-modal v-model:open="showModal" :title="form.id ? 'Ubah Menu' : 'Tambah Menu'" width="700px" @ok="writeData"
    :mask-closable="false">
    <a-form :label-col="{ span: 7 }" :wrapper-col="{ span: 16 }">

      <!-- TYPE -->
      <a-form-item label="Jenis">
        <a-radio-group v-model:value="form.type">
          <a-radio value="menu">Menu</a-radio>
          <a-radio value="header">Header</a-radio>
        </a-radio-group>
      </a-form-item>

      <!-- TITLE -->
      <a-form-item label="Judul" required>
        <a-input v-model:value="form.title" />
      </a-form-item>

      <!-- HEADER INFO -->
      <a-alert v-if="form.type === 'header'" type="info" show-icon
        message="Header hanya berfungsi sebagai pemisah menu, tidak memiliki URL atau permission." />

      <!-- MENU FIELDS -->
      <template v-if="form.type === 'menu'">
        <a-form-item label="Parent Menu">
          <a-select v-model:value="form.parent_id" allow-clear placeholder="Root">
            <a-select-option v-for="m in constant.parentMenus" :key="m.id" :value="m.id">
              {{ m.title }}
            </a-select-option>
          </a-select>
        </a-form-item>

        <a-form-item label="Key"
          extra="Harus sama dengan permission. Contoh: manajemen_user (key) → manajemen_user.read (permissions)">
          <a-input v-model:value="form.key" />
        </a-form-item>

        <a-form-item label="URL / Route">
          <a-input v-model:value="form.url" />
        </a-form-item>

        <a-form-item label="Permissions">
          <a-select v-model:value="form.permissions" mode="multiple" show-search option-filter-prop="label"
            placeholder="Pilih Permission">
            <a-select-option v-for="p in filteredPermissions" :key="p.id" :value="p.id" :label="p.name">
              {{ p.name }}
            </a-select-option>
          </a-select>
        </a-form-item>

        <a-form-item label="Icon">
          <a-input v-model:value="form.icon" />
        </a-form-item>
      </template>

      <!-- COMMON -->
      <a-form-item label="Urutan">
        <a-input-number v-model:value="form.order" class="w-full" />
      </a-form-item>

      <a-form-item label="Aktif">
        <a-switch v-model:checked="form.is_active" />
      </a-form-item>
    </a-form>
  </a-modal>
</template>
