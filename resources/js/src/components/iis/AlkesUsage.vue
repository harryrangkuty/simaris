<script>
import { debounce } from "lodash-es";

const sortDirections = ["ascend", "descend"];

const columns = [
  {
    title: "#",
    key: "number",
    width: 60,
    align: "center",
    fixed: "left",
  },
  {
    title: "Kode",
    dataIndex: "code",
    width: 150,
  },
  {
    title: "Jenis",
    dataIndex: "usage_type",
    width: 140,
    sorter: (a, b) =>
      (a.usage_type || "").localeCompare(b.usage_type || ""),
    sortDirections,
  },
  {
    title: "Tanggal Mulai",
    dataIndex: "usage_date",
    width: 160,
    sorter: (a, b) =>
      new Date(a.usage_date) - new Date(b.usage_date),
    sortDirections,
  },
  {
    title: "Tanggal Selesai",
    dataIndex: "usage_end_date",
    width: 160,
    sorter: (a, b) =>
      new Date(a.usage_end_date || 0) -
      new Date(b.usage_end_date || 0),
    sortDirections,
  },
  {
    title: "Digunakan Oleh",
    key: "used_by",
    width: 220,
  },
  {
    title: "Status",
    dataIndex: "status",
    width: 120,
    align: "center",
  },
  {
    title: "Action",
    key: "action",
    width: 80,
    fixed: "right",
    align: "center",
  },
];

export default {
  name: "AlkesUsage",
  props: {
    modelData: Object,
    constant: Object,
  },
  data() {
    return {
      columns,
      usages: [],
      detailData: null,

      form: {
        id: null,
        usage_type: "operational",

        branch_id: null,
        unit_id: null,
        room_id: null,

        used_by_type: "user",
        used_by_id: null,

        usage_date: null,
        usage_end_date: null,
        activity_name: null,
        purpose: null,
        notes: null,
      },

      filter: {
        usage_type: null,
        status: null,
      },

      showModal: false,
      showDetailModal: false,
    };
  },

  mounted() {
    this.readData();
  },

  watch: {
    "form.usage_type"(val) {
      if (val !== "temporary") {
        this.form.usage_end_date = null;
      }
    },
  },

  methods: {
    async readData(v) {
      const vm = this;
      vm.loadingTrue();

      let params = {
        req: "log_usage",
        asset_type: "alkes",
        qr_code_no: vm.modelData.qr_code_no,
        results: 10,
        ...vm.filter,
        ...(v ?? {
          page: vm._pagination.current,
          total: vm._pagination.total,
        }),
      };

      const res = await vm.axios.get(vm.readRoute, { params });

      if (res?.data) {
        vm.usages = res.data.models.data;
        vm._pagination.total = res.data.models.total;
      }

      vm.loadingFalse();
    },

    newData() {
      const vm = this;
      Object.assign(vm.$data.form, vm.$options.data().form);
      vm.$nextTick(() => {
        vm.showModal = true;
      });
    },

    editData(row) {
      this.form = this.lodash.cloneDeep(row);
      this.showModal = true;
    },

    async writeData() {
      const vm = this;
      vm.loadingTrue();

      const formData = new FormData();
      formData.append("req", "write_single_usage");
      formData.append("asset_type", "alkes");
      formData.append("qr_code_no", vm.modelData.qr_code_no);

      Object.entries(vm.form).forEach(([key, value]) => {
        if (value !== null && value !== undefined) {
          formData.append(key, value);
        }
      });

      const res = await vm.axios
        .post(vm.writeRoute, formData)
        .catch((e) => vm.$onAjaxError(e));

      if (res?.data) {
        vm.openNotification(
          vm.form.id ? "Berhasil mengubah pemakaian" : "Berhasil menambah pemakaian",
          "success"
        );
        vm.readData();
        vm.showModal = false;
      }

      vm.loadingFalse();
    },

    openDetail(row) {
      this.detailData = row;
      this.showDetailModal = true;
    },

    statusColor(status) {
      return {
        draft: "default",
        submitted: "blue",
        approved: "green",
        completed: "purple",
        cancelled: "red",
      }[status];
    },

    usageLabel(type) {
      return {
        operational: "Operasional",
        temporary: "Sementara",
        consumable: "Habis Pakai",
      }[type];
    },
  },
};
</script>

<template>
  <div>
    <!-- HEADER -->
    <a-row class="flex flex-wrap items-start justify-between mb-4 pb-4 border-b-2 gap-y-4">
      <a-col :xs="24" :sm="24" :md="6">
        <a-button v-if="can('iis.inventories-list.update')" type="primary"
          class="bg-gradient-to-r from-purple-400 via-blue-500 to-blue-700 hover:from-yellow-400 hover:via-yellow-600 hover:to-purple-500 flex items-center justify-center text-white font-medium border-0 shadow-md transition-all duration-300 w-full"
          @click="newData">
          <Icon icon="line-md:plus" class="mr-1" />
          Tambah Pemakaian
        </a-button>
      </a-col>

      <a-col :xs="24" :md="18" class="flex justify-end gap-2">
        <a-select v-model:value="filter.usage_type" placeholder="Jenis Pemakaian" class="min-w-52" allow-clear
          @change="readData">
          <a-select-option value="operational">Operasional</a-select-option>
          <a-select-option value="temporary">Sementara</a-select-option>
          <a-select-option value="consumable">Habis Pakai</a-select-option>
        </a-select>

        <a-select v-model:value="filter.status" placeholder="Status" class="min-w-44" allow-clear @change="readData">
          <a-select-option value="draft">Draft</a-select-option>
          <a-select-option value="submitted">Submitted</a-select-option>
          <a-select-option value="approved">Approved</a-select-option>
          <a-select-option value="completed">Completed</a-select-option>
          <a-select-option value="cancelled">Cancelled</a-select-option>
        </a-select>
      </a-col>
    </a-row>

    <!-- TABLE -->
    <div class="mb-2 font-medium">
      Total: {{ _pagination.total }} Data
    </div>

    <a-table :columns="columns" :data-source="usages" :row-key="(r) => r.id" :pagination="_pagination"
      :loading="loadingStatus" @change="handleTableChange" :scroll="{ x: 900 }">
      <template #bodyCell="{ index, column, record }">
        <template v-if="column.key === 'number'">
          {{ (_pagination.current - 1) * _pagination.pageSize + index + 1 }}
        </template>

        <template v-else-if="column.dataIndex === 'usage_type'">
          <a-tag color="blue">
            {{ usageLabel(record.usage_type) }}
          </a-tag>
        </template>

        <template v-else-if="column.key === 'used_by'">
          <span>
            {{ record.used_by?.name || "-" }}
          </span>
        </template>

        <template v-else-if="column.dataIndex === 'status'">
          <a-tag :color="statusColor(record.status)">
            {{ record.status.toUpperCase() }}
          </a-tag>
        </template>

        <template v-else-if="column.key === 'action'">
          <a-tooltip title="Detail">
            <a-button size="small" type="text" @click="openDetail(record)">
              <Icon icon="line-md:file-search-twotone" class="text-xl text-blue-500" />
            </a-button>
          </a-tooltip>
        </template>
      </template>
    </a-table>

    <!-- MODAL FORM -->
    <a-modal v-model:open="showModal" :title="form.id ? 'Ubah Pemakaian' : 'Tambah Pemakaian'" width="800px"
      @ok="writeData" destroy-on-close>
      <a-form layout="vertical">
        <a-form-item label="Jenis Pemakaian">
          <a-segmented v-model:value="form.usage_type" block :options="[
            { label: 'Operasional', value: 'operational' },
            { label: 'Sementara', value: 'temporary' },
            { label: 'Habis Pakai', value: 'consumable' },
          ]" />
        </a-form-item>

        <a-form-item label="Tanggal Mulai">
          <a-date-picker v-model:value="form.usage_date" show-time class="w-full" />
        </a-form-item>

        <a-form-item v-if="form.usage_type === 'temporary'" label="Tanggal Selesai">
          <a-date-picker v-model:value="form.usage_end_date" show-time class="w-full" />
        </a-form-item>

        <a-form-item label="Nama Kegiatan">
          <a-input v-model:value="form.activity_name" />
        </a-form-item>

        <a-form-item label="Tujuan Pemakaian">
          <a-textarea v-model:value="form.purpose" rows="3" />
        </a-form-item>

        <a-form-item label="Catatan">
          <a-textarea v-model:value="form.notes" rows="3" />
        </a-form-item>
      </a-form>
    </a-modal>

    <!-- MODAL DETAIL -->
    <a-modal v-model:open="showDetailModal" title="Detail Pemakaian" width="700px" :footer="null">
      <div v-if="detailData" class="space-y-3">
        <div class="flex justify-between">
          <div>
            <div class="text-xs text-gray-500">Kode</div>
            <div class="font-semibold">{{ detailData.code }}</div>
          </div>
          <a-tag :color="statusColor(detailData.status)">
            {{ detailData.status }}
          </a-tag>
        </div>

        <div>
          <div class="text-xs text-gray-500">Jenis</div>
          <div>{{ usageLabel(detailData.usage_type) }}</div>
        </div>

        <div>
          <div class="text-xs text-gray-500">Tujuan</div>
          <div>{{ detailData.purpose || "-" }}</div>
        </div>

        <div>
          <div class="text-xs text-gray-500">Catatan</div>
          <div>{{ detailData.notes || "-" }}</div>
        </div>
      </div>
    </a-modal>
  </div>
</template>

<style>
.ant-segmented-item-selected {
  background: linear-gradient(135deg, #22c55e, #3b82f6);
  color: white !important;
}
</style>
