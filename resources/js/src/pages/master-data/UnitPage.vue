<script>

const sortDirections = ['ascend', 'descend'];
const columns = [
  {
    title: "#",
    key: "number",
    align: "center",
    width: 50,
  },
  {
    title: "Nama Unit",
    dataIndex: "name",
    width: 300,
    ellipsis: true,
    sorter: (a, b) => (a.name || '').localeCompare(b.name || ''),
    sortDirections,
  },
  {
    title: "Bagian / Departemen",
    key: "department",
    ellipsis: true,
    sorter: (a, b) => (a.department || '').localeCompare(b.department || ''),
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
        name: null,
        department: null,
      },
      filter: {
        status: "aktif",
        roles: [],
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
  },

  mounted() {
    this.readData();
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
      vm.form = vm.lodash.cloneDeep(m);
      vm.$nextTick(function () {
        vm.showModal = true;
      })
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

    newData() {
      const vm = this;
      vm.form = vm.$options.data().form;
      vm.$nextTick(function () {
        vm.showModal = true;
      })
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
              placeholder="Cari unit atau departemen...">
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
              Tambah Unit
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
        <template v-if="column.key === 'department'">
          <a-tag :color="record.department === 'Administrasi dan Umum'
              ? 'blue'
              : record.department === 'Medik dan Keperawatan'
                ? 'green'
                : record.department === 'Bidang Keuangan dan Akuntansi'
                  ? 'orange'
                  : 'default'
            ">
            {{ record.department }}
          </a-tag>
        </template>
      </template>
    </a-table>
  </a-card>
  <a-modal v-model:open="showModal" :title="form.id ? 'Ubah Unit' : 'Tambah Unit'" @ok="writeData"
    :mask-closable="false" :destroy-on-close="true" width="800px">
    <a-form ref="formRef" :model="form" name="unitForm" :label-col="{ span: 7 }" :wrapper-col="{ span: 16 }">
      <a-form-item label="Nama Unit" data-column="name" :rules="[{ required: true }]">
        <a-input v-model:value="form.name" placeholder="Masukkan nama unit" autocomplete="off" />
      </a-form-item>

      <a-form-item label="Departemen / Bagian" data-column="department" :rules="[{ required: true }]">
        <a-input v-model:value="form.department" placeholder="Masukkan nama departemen" autocomplete="off" />
      </a-form-item>
    </a-form>
  </a-modal>

</template>