<script>
import { debounce } from "lodash-es";

const sortDirections = ['ascend', 'descend'];

const columns = [
  {
    title: "#",
    key: "number",
    align: "center",
    width: 60,
    fixed: "left",
  },
  {
    title: "Jenis",
    key: "maintenance_type",
    width: 100,
    align: "center",
    sorter: (a, b) =>
      (a.maintenance_type || '').localeCompare(b.maintenance_type || ''),
    sortDirections,
  },
  {
    title: "Tanggal Mulai",
    dataIndex: "maintenance_date",
    width: 160,
    sorter: (a, b) =>
      new Date(a.maintenance_date) - new Date(b.maintenance_date),
    sortDirections,
  },
  {
    title: "Tanggal Selesai",
    dataIndex: "completed_date",
    width: 160,
    sorter: (a, b) =>
      new Date(a.completed_date || 0) - new Date(b.completed_date || 0),
    sortDirections,
  },
  {
    title: "Deskripsi",
    dataIndex: "description",
    key: "description",
    width: 250,
    ellipsis: true,
  },
  {
    title: "Operator",
    key: "operator",
    width: 220,
    sorter: (a, b) =>
      (a.operator?.name || '').localeCompare(b.operator?.name || ''),
    sortDirections,
  },
  {
    title: "Action",
    key: "action",
    align: "center",
    width: 80,
    fixed: 'right',
    className: 'column-action'
  },
];

export default {
  name: 'InventoryMaintenanceModal',
  props: {
    modelData: Object,
  },
  data() {
    return {
      columns,
      maintenances: [],
      detailData: [],
      form: {
        id: null,
        maintenance_type: 'internal',
        service_code: null,
        supplier_id: null,
        maintenance_date: null,
        completed_date: null,
        description: null,
        attachments: null,
      },
      filter: {
        maintenance_type: null,
      },
      showDetailModal: false,
      supplierOptions: [],
    }
  },
  mounted() {
    this.readData()
  },
  watch: {
    'form.maintenance_type'(val) {
      if (val === 'internal') {
        this.form.service_code = null;
        this.form.supplier_id = null;
      }
    }
  },
  methods: {

    async readData(v) {
      const vm = this;
      vm.loadingTrue();
      let params = v ?? {
        total: this._pagination.total,
        page: this._pagination.current,
      };
      params = {
        req: "log_maintenance",
        qr_code_no: vm.modelData.qr_code_no,
        results: 10,
        ...params,
        ...vm.filter,
      };

      const response = await vm.axios.get(vm.readRoute, { params });
      if (response && response.data) {
        const pagination = { ...vm._pagination };
        pagination.total = response.data.models.total;
        vm.loadingFalse();
        vm.maintenances = response.data.models.data;
        vm._pagination = pagination;
      }
    },

    newData() {
      const vm = this
      Object.assign(vm.$data.form, vm.$options.data().form);
      vm.$nextTick(function () {
        vm.showModal = true;
        vm.fetchSuppliers();
      })
    },

    editData(m) {
      const vm = this;
      const data = vm.lodash.cloneDeep(m);

      // HANDLE FILE OBJECT (edit mode)
      Object.keys(data).forEach((key) => {
        const objectKey = `${key}_object`;
        if (data[objectKey]) {

          // MULTIPLE FILE
          if (Array.isArray(data[objectKey])) {
            data[key] = data[objectKey].map(file => ({
              originFileObj: file instanceof File ? file : null,
              name: file.name || (typeof file === 'string' ? file.split('/').pop() : 'file'),
              url: file.url || file,
            }));
          }
          // SINGLE FILE
          else {
            data[key] = {
              originFileObj: data[objectKey] instanceof File ? data[objectKey] : null,
              name: data[objectKey].name || (typeof data[objectKey] === 'string'
                ? data[objectKey].split('/').pop()
                : 'file'),
              url: data[objectKey].url || data[objectKey],
            };
          }
        }
      });

      vm.form = data;

      vm.$nextTick(() => {
        vm.showModal = true;
        if (m.supplier_id) {
          vm.fetchSuppliers(m.supplier_id);
        }
        vm.fetchSuppliers();
      });
    },

    async writeData() {
      const vm = this;
      vm.loadingTrue();

      const formData = new FormData();
      formData.append('req', 'write_single_maintenance');
      formData.append('qr_code_no', vm.modelData.qr_code_no);

      Object.entries(vm.form).forEach(([key, value]) => {
        if (value === null || value === undefined) return;

        // MULTIPLE FILE
        if (Array.isArray(value) && value[0]?.originFileObj instanceof File) {
          value.forEach(fileItem => {
            formData.append(`${key}[]`, fileItem.originFileObj);
          });
        }
        // SINGLE FILE
        else if (value?.originFileObj instanceof File) {
          formData.append(key, value.originFileObj);
        }
        // ARRAY OF OBJECT → JSON
        else if (Array.isArray(value) && typeof value[0] === 'object') {
          formData.append(key, JSON.stringify(value));
        }
        // ARRAY BIASA
        else if (Array.isArray(value)) {
          value.forEach((item, index) => {
            formData.append(`${key}[${index}]`, item);
          });
        }
        // STRING / NUMBER / BOOLEAN
        else {
          formData.append(key, value);
        }
      });

      const response = await vm.axios.post(vm.writeRoute, formData, {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      }).catch(e => vm.$onAjaxError(e));

      if (response && response.data) {
        vm.openNotification(
          vm.form.id ? 'Berhasil mengubah data' : 'Berhasil menyimpan data ...',
          'success'
        );
        vm.readData();
        vm.showModal = false;
        vm.loadingFalse();
      }
    },

    async fetchSuppliers(param = "") {
      const vm = this;
      vm.loadingTrue();
      try {
        let url = "/lookups/suppliers?";
        if (typeof param === "number" || /^[0-9]+$/.test(param)) {
          url += `id=${param}`;
        } else {
          url += `search=${encodeURIComponent(param)}&limit=10`;
        }

        const res = await fetch(url);
        const data = await res.json();

        vm.supplierOptions = Array.isArray(data) ? data : [data];
      } finally {
        vm.loadingFalse();
      }
    },

    onSearchSupplier: debounce(function (val) {
      const vm = this;
      vm.fetchSuppliers(val);
    }, 500),

    openDetail(record) {
      this.detailData = record
      this.showDetailModal = true
    },

    isImage(file) {
      return /\.(jpg|jpeg|png|gif|webp)$/i.test(file.name)
    },
    isPdf(file) {
      return /\.pdf$/i.test(file.name)
    },

  }
}
</script>

<template>
  <div>
    <a-row class="flex flex-wrap items-start justify-between mb-4 pb-4 border-b-2 gap-y-4">
      <a-col :xs="24" :sm="24" :md="6">
        <a-button v-if="can('iis.inventories-list.update')" type="primary"
          class="bg-gradient-to-r from-purple-400 via-blue-500 to-blue-700 hover:from-yellow-400 hover:via-yellow-600 hover:to-purple-500 flex items-center justify-center text-white font-medium border-0 shadow-md transition-all duration-300 w-full"
          @click="newData">
          <Icon icon="line-md:plus" class="mr-1" />
          Tambah Pemeliharaan
        </a-button>
      </a-col>
      <a-col :xs="24" :sm="24" :md="18" class="flex justify-end">
        <a-row class="flex flex-wrap gap-2 justify-start md:justify-end w-full md:w-auto">
          <a-col class="w-full md:w-auto">
            <a-select v-model:value="filter.maintenance_type" placeholder="Pilih Tipe Pemeliharaan"
              class="min-w-64 lg:w-64 w-full" @change="readData">
              <a-select-option value="internal">Internal</a-select-option>
              <a-select-option value="external">Eksternal</a-select-option>
            </a-select>
          </a-col>
        </a-row>
      </a-col>
    </a-row>

    <!-- List pemeliharaan -->
    <div class="mt-4 mb-2 font-medium">
      Total: {{ _pagination.total }} Data
    </div>
    <a-table :scroll="{ x: 800 }" :columns="columns" :row-key="(obj) => obj.id" :pagination="_pagination"
      :loading="loadingStatus" :data-source="maintenances" @change="handleTableChange">
      <template #bodyCell="{ index, column, record }">
        <template v-if="column.key === 'number'">
          {{ (_pagination.current - 1) * _pagination.pageSize + (index + 1) }}
        </template>
        <template v-else-if="column.key === 'maintenance_type'">
          <a-tag :color="record.maintenance_type === 'internal' ? 'green' : 'orange'">
            {{ record.maintenance_type === 'internal' ? 'Internal' : 'Eksternal' }}
          </a-tag>
        </template>
        <template v-else-if="column.key === 'operator'">
          <a-tag color="red">
            {{ record.operator?.name }} - {{ record.operator?.position }}
          </a-tag>
        </template>
        <template v-if="column.key === 'action'">
          <a-button-group class="flex justify-center">
            <!-- Detail -->
            <a-tooltip title="Lihat Detail">
              <a-button size="small" type="text" @click="openDetail(record)" :style="{ padding: '0 5px' }">
                <Icon icon="line-md:file-search-twotone" class="flex justify-center text-blue-500 text-[24px]" />
              </a-button>
            </a-tooltip>
          </a-button-group>
        </template>
      </template>
    </a-table>

    <!-- Modal Tambah/Update Maintenance -->
    <a-modal v-model:open="showModal" :title="form.id ? 'Ubah Data Pemeliharaan' : 'Tambah Data Pemeliharaan'"
      width="800px" @ok="writeData" :mask-closable="false" :destroy-on-close="true">
      <a-form layout="vertical">

        <a-form-item label="Jenis Pemeliharaan" data-column="maintenance_type"
          :rules="[{ required: true, message: 'Pilih jenis pemeliharaan' }]">
          <a-segmented class="maintenance-segmented" v-model:value="form.maintenance_type" size="large" block :options="[
            {
              label: 'Internal',
              value: 'internal'
            },
            {
              label: 'Eksternal',
              value: 'external'
            }
          ]" />
        </a-form-item>

        <a-form-item label="Tanggal Mulai Pemeliharaan" data-column="maintenance_date" :rules="[{ required: true }]">
          <a-date-picker v-model:value="form.maintenance_date" show-time class="w-full"
            placeholder="Pilih tanggal pemeliharaan" />
        </a-form-item>

        <a-form-item label="Tanggal Selesai Pemeliharaan" data-column="completed_date" :rules="[{ required: true }]">
          <a-date-picker v-model:value="form.completed_date" show-time class="w-full"
            placeholder="Pilih tanggal selesai" />
        </a-form-item>

        <a-form-item v-if="form.maintenance_type === 'external'" label="Kode Service" data-column="service_code"
          :rules="[{ required: true }]">
          <a-input v-model:value="form.service_code" placeholder="Service Code" />
        </a-form-item>

        <a-form-item v-if="form.maintenance_type === 'external'" label="Supplier" data-column="supplier_id"
          :rules="[{ required: true }]">
          <a-select v-model:value="form.supplier_id" placeholder="--Pilih Supplier--" show-search :filter-option="false"
            allow-clear @search="onSearchSupplier">
            <a-select-option v-for="s in supplierOptions" :key="s.id" :value="s.id" :label="s.name">
              <div class="flex items-center gap-2">
                <a-tag color="blue">{{ s.gl_code }}</a-tag>
                <span class="text-gray-700">{{ s.name }}</span>
              </div>
            </a-select-option>
          </a-select>
        </a-form-item>

        <a-form-item label="Deskripsi" data-column="description" :rules="[{ required: true }]">
          <a-textarea v-model:value="form.description" placeholder="Deskripsi pemeliharaan" :rows="4" />
        </a-form-item>

        <!-- Attachments -->
        <a-form-item label="Lampiran File" data-column="attachments">
          <file-upload multiple v-model:value="form.attachments" accept=".pdf,.jpg,.jpeg,.png,.gif,.bmp,.webp" />
        </a-form-item>

      </a-form>
    </a-modal>

    <!-- Modal detail -->
    <a-modal v-model:open="showDetailModal" title="Detail Pemeliharaan" width="700px" :footer="null" destroy-on-close>
      <div v-if="detailData" class="space-y-4">

        <div v-if="detailData" class="space-y-5">

          <!-- SUMMARY -->
          <div class="flex flex-wrap items-center justify-between gap-3
              rounded-lg border p-4 bg-gray-50">

            <div>
              <div class="text-xs text-gray-500">Barcode</div>
              <div class="text-base font-semibold">
                {{ detailData.qr_code_no }}
              </div>
            </div>

            <div class="flex items-center gap-2">
              <a-tag :color="detailData.maintenance_type === 'internal' ? 'green' : 'orange'">
                {{ detailData.maintenance_type === 'internal' ? 'Internal' : 'Eksternal' }}
              </a-tag>

              <a-tag color="red">
                {{ detailData.operator?.name }}
              </a-tag>
            </div>

          </div>

          <!-- INFO GRID -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

            <div class="rounded-lg border p-3">
              <div class="text-xs text-gray-500 mb-1">Tanggal Masuk</div>
              <div class="font-medium">
                {{ detailData.maintenance_date }}
              </div>
            </div>

            <div class="rounded-lg border p-3">
              <div class="text-xs text-gray-500 mb-1">Tanggal Selesai</div>
              <div class="font-medium">
                {{ detailData.completed_date || '-' }}
              </div>
            </div>

            <div v-if="detailData.maintenance_type === 'external'" class="rounded-lg border p-3">
              <div class="text-xs text-gray-500 mb-1">Kode Service</div>
              <div class="font-medium">
                {{ detailData.service_code }}
              </div>
            </div>

            <div v-if="detailData.maintenance_type === 'external'" class="rounded-lg border p-3">
              <div class="text-xs text-gray-500 mb-1">Supplier</div>
              <div class="font-medium">
                {{ detailData.supplier?.gl_code }} - {{ detailData.supplier?.name }}
              </div>
            </div>

          </div>

          <!-- DESKRIPSI -->
          <div class="rounded-lg border p-4">
            <div class="text-xs text-gray-500 mb-2">Deskripsi Pemeliharaan</div>
            <div class="text-gray-800 leading-relaxed whitespace-pre-line">
              {{ detailData.description || '-' }}
            </div>
          </div>

          <!-- ATTACHMENTS -->
          <div v-if="detailData.attachments_object?.length" class="rounded-lg border p-4 space-y-3">

            <div class="text-xs text-gray-500 font-medium">Lampiran</div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div v-for="(file, i) in detailData.attachments_object" :key="i"
                class="border rounded-lg p-3 flex gap-3 items-start">

                <!-- IMAGE PREVIEW -->
                <template v-if="isImage(file)">
                  <img :src="file.url" class="w-20 h-20 object-cover rounded cursor-pointer border"
                    @click="window.open(file.url, '_blank')" />
                  <div class="flex-1">
                    <div class="text-sm font-medium break-words leading-snug">
                      {{ file.name }}
                    </div>
                    <a :href="file.url" target="_blank" class="text-xs text-blue-600 hover:underline">
                      Lihat Gambar
                    </a>
                  </div>
                </template>

                <!-- PDF / OTHER FILE -->
                <template v-else>
                  <Icon icon="line-md:file-document" class="text-red-500 text-2xl mt-1" />
                  <div class="flex-1">
                    <div class="text-sm font-medium break-words leading-snug">
                      {{ file.name }}
                    </div>

                    <div class="flex gap-2 mt-1">
                      <a :href="file.url" target="_blank" class="text-xs text-blue-600 hover:underline">
                        Lihat Dokumen
                      </a>

                      <a :href="file.url" download class="text-xs text-gray-600 hover:underline">
                        Unduh
                      </a>
                    </div>
                  </div>
                </template>

              </div>
            </div>
          </div>

        </div>
      </div>

    </a-modal>
  </div>
</template>
<style>
.maintenance-segmented .ant-segmented-item-selected {
  background: linear-gradient(135deg, #3b82f6, #2563eb);
  color: white !important;
  font-weight: 600;
}

.maintenance-segmented .ant-segmented-item-selected .anticon,
.maintenance-segmented .ant-segmented-item-selected svg {
  color: white;
}
</style>