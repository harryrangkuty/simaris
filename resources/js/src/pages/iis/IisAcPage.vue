<script>
import { debounce } from "lodash-es";

const sortDirections = ['ascend', 'descend'];

const columns = [
  {
    title: "#",
    key: "number",
    align: "center",
    width: 90,
  },
  {
    title: "Status Print QRCode",
    key: "status_print",
    width: 200,
    sorter: (a, b) => (a.print_count || '').localeCompare(b.print_count || ''),
    sortDirections,
  },
  {
    title: "Nomor QR Code",
    key: "qr_code_no",
    width: 200,
    ellipsis: true,
    sorter: (a, b) => (a.qr_code_no || '').localeCompare(b.qr_code_no || ''),
    sortDirections,
  },
  {
    title: "Deskripsi",
    dataIndex: "description",
    width: 235,
    ellipsis: true,
    sorter: (a, b) => (a.description || '').localeCompare(b.description || ''),
    sortDirections,
  },
  {
    title: "Item",
    key: "item_code",
    width: 335,
    align: "left",
    sorter: (a, b) => (a.item_code || '').localeCompare(b.item_code || ''),
    sortDirections,
  },
  {
    title: "Posisi Barang",
    key: "position",
    width: 300,
    ellipsis: true,
    sorter: (a, b) => (a.position || '').localeCompare(b.position || ''),
    sortDirections,
  },
  {
    title: "Kategori IIS",
    key: "category_name",
    width: 180,
    sorter: (a, b) => (a.category_name || '').localeCompare(b.category_name || ''),
    sortDirections,
  },
  {
    title: "Nomor Urut Aset",
    dataIndex: "asset_number",
    width: 100,
    align: "center",
    sorter: (a, b) => (a.asset_number || 0) - (b.asset_number || 0),
    sortDirections,
  },
  {
    title: "Gedung",
    key: "building_id",
    width: 140,
    sorter: (a, b) => (a.building_id ?? 0) - (b.building_id ?? 0),
    sortDirections,
  },
  {
    title: "Lantai",
    key: "floor",
    width: 120,
    align: "center",
    sorter: (a, b) => (a.floor || '').localeCompare(b.floor || ''),
    sortDirections,
  },
  {
    title: "Unit",
    key: "unit",
    width: 170,
    ellipsis: true,
    sorter: (a, b) => (a.unit || '').localeCompare(b.unit || ''),
    sortDirections,
  },
  {
    title: "Ruang",
    key: "room",
    width: 200,
    ellipsis: true,
    sorter: (a, b) => (a.room || '').localeCompare(b.room || ''),
    sortDirections,
  },
  {
    title: "Penanggung Jawab (PJ)",
    key: "pj",
    width: 250,
    align: "left",
    ellipsis: true,
    sorter: (a, b) => (a.pj_nik || '').localeCompare(b.pj_nik || ''),
    sortDirections,
  },
  {
    title: "Kondisi",
    dataIndex: "condition",
    width: 130,
    align: "center",
    sorter: (a, b) => (a.condition || '').localeCompare(b.condition || ''),
    sortDirections,
  },
  {
    title: "Nomor PO",
    dataIndex: "po_number",
    width: 150,
    align: "center",
    sorter: (a, b) => (a.po_number ?? 0) - (b.po_number ?? 0),
    sortDirections,
  },
  {
    title: "Nomor Receive",
    dataIndex: "received_number",
    width: 150,
    align: "center",
    sorter: (a, b) => (a.received_number ?? 0) - (b.received_number ?? 0),
    sortDirections,
  },
  {
    title: "Harga Pembelian",
    key: "unit_price",
    width: 180,
    align: "center",
    sorter: (a, b) => (a.unit_price ?? 0) - (b.unit_price ?? 0),
    sortDirections,
  },
  {
    title: "Tahun Pembelian",
    dataIndex: "purchase_year",
    width: 130,
    align: "center",
    sorter: (a, b) => (a.purchase_year ?? 0) - (b.purchase_year ?? 0),
    sortDirections,
  },
  {
    title: "Status Keaktifan",
    key: "is_deactivated",
    align: "center",
    width: 120,
    sorter: (a, b) => (a.is_deactivated || 0) - (b.is_deactivated || 0),
    sortDirections,
  },
  {
    title: "Status Serah Terima QR",
    key: "is_handed_over",
    align: "center",
    width: 220,
    sorter: (a, b) => (a.is_handed_over || 0) - (b.is_handed_over || 0),
    sortDirections,
  },
  {
    title: "Sumber Data",
    key: "data_source",
    width: 140,
    align: "center",
    sorter: (a, b) => (a.data_source || '').localeCompare(b.data_source || ''),
    sortDirections,
  },
  {
    title: "Aksi",
    key: "action",
    align: "center",
    width: 150,
    fixed: "right",
    className: "column-action",
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
      filter: {
        branch_id: 1,
        status: "active",
        unit: null,
        user: null,
        handed_over: null,
        printed: null,
        distributed: null,
      },
      form: {
        id: null,
        branch_id: 1,
        item_code: null,
        description: null,
        building_id: null,
        floor: null,
        unit_id: null,
        room_id: null,
        pj_nik: null,
        condition: null,
        po_number: null,
        received_number: null,
        unit_price: null,
        purchase_year: null,
        notes: null,
        category_name: null,
        etc: [
          { key: 'Serial Number', value: '' },
          { key: 'Merek', value: '' },
        ],
        location_type: 'distributed',
        warehouse_id: null,
      },
      userOptions: [],
      roomOptions: [],
      selectionMode: false,
      selectedRowKeys: [],
      selectedRows: [],
      //state untuk print single label
      showPrintLabelModal: false,
      form_preview_label: {
        id: null,
        mode: 'mini',
        tipe_kertas: 'stiker',
      },
      showPDFModal: false, // modal PDF viewer
      pdfContent: null, // HTML dari backend
      // state untuk multi select
      //confirm print
      showPrintConfirm: false,
      selectedQR: null,
      showActionModal: false,
      actionOptions: [
        {
          title: 'Cetak Label',
          key: 'print_label',
          icon: 'mdi:barcode',
          code: 'PRINT',
          gradient: 'background: linear-gradient(135deg, #7C3AED, #C084FC);'
        },
        {
          title: 'Ubah Kondisi',
          key: 'change_condition',
          icon: 'mdi:wrench',
          code: 'EDIT',
          gradient: 'background: linear-gradient(135deg, #16A34A, #4ADE80);'
        },
        {
          title: 'Ubah PJ',
          key: 'change_pj',
          icon: 'mdi:account',
          code: 'USER',
          gradient: 'background: linear-gradient(135deg, #2563EB, #60A5FA);'
        },
        {
          title: 'Status Keaktifan',
          key: 'toggle_status',
          icon: 'mdi:toggle-switch',
          code: 'STATUS',
          gradient: 'background: linear-gradient(135deg, #4B5563, #9CA3AF);'
        }
      ],
      printMode: 'single',
      itemOptions: [],
      // bulk action modal
      showBulkActionModal: false,
      actionType: null, // 'condition' | 'pj' | 'toggle_status'
      bulkForm: {
        condition: null,
        pj_nik: null,
        is_active: null,
        is_deactivated_notes: null,
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
    rowSelection() {
      return this.selectionMode
        ? {
          selectedRowKeys: this.selectedRowKeys,
          onChange: (keys, rows) => {
            this.selectedRowKeys = keys;
            this.selectedRows = rows;
          },
          preserveSelectedRowKeys: true, // simpan antar pagination
        }
        : null;
    },
    selectedRowsData() {
      return this.selectedRows || [];
    },
    totalPrintCount() {
      return this.selectedRowsData.length;
    },
    exceededLimitCount() {
      return this.selectedRowsData.filter(r => r.print_count >= 2).length;
    },

    singlePrintExceeded() {
      if (!this.selectedQR) return false;
      return this.selectedQR.print_count >= 2;
    },

    multiPrintExceeded() {
      return this.exceededLimitCount > 0;
    },

    canPrintUnlimited() {
      return this.can('iis.inventory.print.unlimited');
    },

    disablePrintButton() {
      if (this.canPrintUnlimited) return false;
      if (this.printMode === 'single') {
        return this.singlePrintExceeded;
      }
      return this.multiPrintExceeded;
    },

    filteredBuildings() {
      if (!this.form.branch_id) return [];

      return this.constant.BUILDINGS.filter(
        b => b.branch_id === this.form.branch_id
      );
    },

    filteredWarehouses() {
      if (!this.form.branch_id) {
        return []
      }

      return this.constant.WAREHOUSES.filter(w =>
        w.branch_id === this.form.branch_id
      )
    },

    selectedWarehouse() {
      return this.constant.WAREHOUSES.find(
        w => w.id === this.form.warehouse_id
      )
    },

    isCurrentYearPurchase() {
      if (!this.form.purchase_year) return false
      const currentYear = new Date().getFullYear()
      return Number(this.form.purchase_year) === currentYear
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

    modalTitle() {
      switch (this.actionType) {
        case 'change_condition':
          return 'Ubah Kondisi Barang'
        case 'change_pj':
          return 'Ubah Penanggung Jawab'
        case 'toggle_status':
          return 'Ubah Status Keaktifan'
        default:
          return 'Aksi Massal'
      }
    }
  },

  mounted() {
    this.readData();
    this.fetchUsers();
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
      if (val !== oldVal) {
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
        total: this._pagination.total,
        page: this._pagination.current,
      };

      params = {
        req: "table",
        results: 10,
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

    newData() {
      const vm = this;
      Object.assign(vm.$data.form, vm.$options.data().form);
      vm.$nextTick(function () {
        vm.showModal = true;
        vm.fetchItems();
        vm.fetchUsers();
      })
    },

    editData(m) {
      const vm = this;
      vm.form = vm.lodash.cloneDeep(m);
      vm.$nextTick(function () {
        vm.showModal = true;

        if (m.item_code) {
          vm.fetchItems(m.item_code);
        }
        vm.fetchItems();

        if (m.pj_nik) {
          vm.fetchUsers(m.pj_nik);
        }
        vm.fetchUsers();
      });
    },

    async writeData() {
      const vm = this;
      vm.loadingTrue()
      const form = {
        req: 'write',
        ...vm.form
      };
      const response = await vm.axios.post(vm.writeRoute, form).catch((e) => vm.$onAjaxError(e));
      if (response && response.data) {
        if (!form.id) {
          vm.openNotification('Berhasil menambahkan data inventaris', 'success');
        }
        else {
          vm.openNotification('Berhasil mengubah data inventaris', 'success');
        }
        vm.readData();
        vm.showModal = false;
      }
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

    clearSelection() {
      this.selectedRowKeys = [];
      this.selectedRows = [];
    },

    // Handle untuk Single atau Multilabel
    handlePrintLabel() {
      if (this.printMode === 'single') {
        this.openLabelPreview();
      } else {
        this.openMultiLabelPreview();
      }
    },

    // Action Print Single Label
    openPreviewModalLabel(record) {
      const vm = this;
      vm.selectedQR = record;
      vm.$nextTick(function () {
        vm.showPrintLabelModal = true;
        vm.form_preview_label.id = record.id;
      })
    },

    openLabelPreview() {
      const vm = this;
      const pdfUrl = `${vm.readRoute}?req=single_label_preview&id=${vm.form_preview_label.id}&mode=${vm.form_preview_label.mode}&tipe_kertas=${vm.form_preview_label.tipe_kertas}`;
      vm.showPrintLabelModal = false;
      vm.pdfContent = pdfUrl;
      vm.showPDFModal = true;
    },

    // Action Print Multi Label
    openMultiLabelPreview() {
      const vm = this;

      const pdfUrl = `${vm.readRoute}?req=multi_label_preview`
        + `&ids=${vm.selectedRowKeys.join(',')}`
        + `&mode=${vm.form_preview_label.mode}`
        + `&tipe_kertas=${vm.form_preview_label.tipe_kertas}`;

      vm.showPrintLabelModal = false;
      vm.pdfContent = pdfUrl;
      vm.showPDFModal = true;
    },

    openPrintConfirm() {
      if (this.printMode === 'single') {
        if (!this.form_preview_label.id) return;
      } else {
        if (!this.selectedRowKeys.length) {
          this.$message.warning('Tidak ada data dipilih');
          return;
        }
      }

      this.showPrintConfirm = true;
    },

    async printPDF() {
      const vm = this;
      vm.showPrintConfirm = false;
      vm.showPDFModal = false;

      try {
        let params = {};

        if (vm.printMode === 'single') {
          params = {
            req: 'single_label_print',
            id: vm.form_preview_label.id,
          };
        } else {
          params = {
            req: 'multi_label_print',
            ids: vm.selectedRowKeys.join(','),
          };
        }

        await vm.axios.get(vm.readRoute, { params });

        const iframe = document.querySelector('iframe');
        if (!iframe) return;

        iframe.contentWindow.focus();
        iframe.contentWindow.print();
        vm.readData();

      } catch (e) {
        vm.$message.error(
          e?.response?.data?.message || 'Gagal mencetak label'
        );
      }
    },

    positionText(record) {
      return [
        record.building?.name,
        record.floor ? `Lantai ${record.floor}` : null,
        record.unit?.name || record.unit_legacy,
        record.room?.name || record.room_legacy,
      ]
        .filter(v => v && v !== '')
        .join(' / ');
    },

    unitText(record) {
      return record.unit?.name || record.unit_legacy;
    },

    roomText(record) {
      return record.room?.name || record.room_legacy;
    },

    handleActionClick(key) {
      this.showActionModal = false;

      if (!this.selectedRowKeys.length) {
        this.$message.warning('Pilih minimal 1 data');
        return;
      }

      if (key === 'print_label') {

        this.printMode = 'multi';
        this.form_preview_label.id = null;
        this.showPrintLabelModal = true;
        return;
      }

      if (['change_condition', 'change_pj', 'toggle_status'].includes(key)) {
        this.actionType = key;
        this.resetBulkForm();
        this.showBulkActionModal = true;
      }
    },

    addEtc() {
      this.form.etc.push({ key: '', value: '' })
    },

    removeEtc(index) {
      this.form.etc.splice(index, 1)
    },

    resetEtcDefault() {
      this.form.etc = [
        { key: 'Serial Number', value: '' },
        { key: 'Merek', value: '' },
      ]
    },

    async fetchItems(param = "") {
      const vm = this;
      vm.loadingTrue();
      try {
        let url = "/lookups/items?";
        url += `search=${encodeURIComponent(param)}&limit=10`;
        url += `&type=asset_non_alk`;

        const res = await fetch(url);
        const data = await res.json();

        vm.itemOptions = Array.isArray(data) ? data : [data];
      } finally {
        vm.loadingFalse();
      }
    },

    onSearchItem: debounce(function (val) {
      const vm = this;
      vm.fetchItems(val);
    }, 500),

    async deleteData(id) {
      const vm = this;
      vm.loadingTrue()
      const form = {
        req: 'delete',
        id: id
      };
      const response = await vm.axios.post(vm.writeRoute, form).catch((e) => vm.$onAjaxError(e));
      if (response && response.data) {
        vm.openNotification('Berhasil menghapus data', 'success');
        vm.readData();
        vm.showModal = false;
      }
    },

    disableFutureYear(current) {
      return current && current.year() > this.dayjs().year()
    },

    resetBulkForm() {
      this.bulkForm = {
        condition: null,
        pj_nik: null,
        is_active: null,
      }
    },

    async handleBulkSubmit() {
      const vm = this;

      if (!vm.selectedRowKeys.length) return;

      vm.loadingTrue();

      const form = {
        req: 'bulk_action',
        ids: vm.selectedRowKeys,
        action: vm.actionType,
        ...vm.bulkForm,
      };

      const response = await vm.axios
        .post(vm.writeRoute, form)
        .catch((e) => vm.$onAjaxError(e));

      if (response && response.data) {

        switch (vm.actionType) {
          case 'change_condition':
            vm.openNotification('Berhasil mengubah kondisi inventaris', 'success');
            break;

          case 'change_pj':
            vm.clearSelection();
            vm.openNotification('Berhasil mengubah penanggung jawab', 'success');
            break;

          case 'toggle_status':
            vm.openNotification('Berhasil mengubah status keaktifan inventaris', 'success');
            break;

          default:
            vm.openNotification('Aksi berhasil dilakukan', 'success');
        }

        vm.showBulkActionModal = false;
        vm.resetBulkForm();
        vm.actionType = null;

        vm.readData();
      }

      vm.loadingFalse();
    }

  },
};
</script>

<template>
  <Breadcrumb :items="breadcrumbItems" :showBackButton="true" />
  <a-card>
    <!-- Header -->
    <a-row class="flex flex-wrap items-start justify-between mb-4 pb-4 border-b-2 gap-y-4">
      <a-col :xs="24" :sm="24" :md="6">
        <h1 class="text-base font-semibold">{{ title }}</h1>
      </a-col>
      <a-col :xs="24" :sm="24" :md="18" class="flex justify-end">
        <a-row class="flex flex-wrap gap-2 justify-start md:justify-end w-full md:w-auto">

          <!-- Branch -->
          <a-col class="w-full md:w-auto">
            <a-select v-model:value="filter.branch_id" placeholder="--Pilih Cabang--" show-search
              option-label-prop="label" option-filter-prop="label" class="w-full lg:w-56" @change="readData">
              <a-select-option v-for="u in constant.BRANCHES" :key="u.id" :value="u.id" :label="`${u.name}`">
                <div class="flex items-center gap-2">
                  <a-tag color="blue">{{ u.name }}</a-tag>
                </div>
              </a-select-option>
            </a-select>
          </a-col>

          <!-- Data Source -->
          <a-col class="w-full md:w-auto">
            <a-select v-model:value="filter.data_source" placeholder="Pilih Source" class="min-w-32 lg:w-32 w-full"
              @change="readData" allow-clear>
              <a-select-option value="legacy_iis">Data IIS</a-select-option>
              <a-select-option value="system">Data Simaris</a-select-option>
            </a-select>
          </a-col>

          <!-- Status -->
          <a-col class="w-full md:w-auto">
            <a-select v-model:value="filter.status" class="min-w-32 lg:w-32 w-full" @change="readData">
              <a-select-option value="active">Aktif Guna</a-select-option>
              <a-select-option value="inactive">Henti Guna</a-select-option>
            </a-select>
          </a-col>

          <!-- Status Distribusi -->
          <a-col class="w-full md:w-auto">
            <a-select v-model:value="filter.distributed" class="min-w-42 lg:w-42 w-full"
              placeholder="--Status Distribusi--" @change="readData">
              <a-select-option :value="1">Sudah Distribusi</a-select-option>
              <a-select-option :value="0">Masih di Gudang</a-select-option>
            </a-select>
          </a-col>

          <!-- Unit -->
          <a-col class="w-full md:w-auto">
            <a-select v-model:value="filter.unit" placeholder="--Pilih Unit--" show-search allow-clear
              option-label-prop="label" option-filter-prop="label" class="w-full lg:w-72" @change="readData">
              <a-select-option v-for="u in constant.UNITS" :key="u.name" :value="u.name"
                :label="`${u.name} - ${u.department}`">
                <div class="flex items-center gap-2">
                  <a-tag color="blue">{{ u.name }}</a-tag>
                  <span class="text-gray-700">{{ u.department }}</span>
                </div>
              </a-select-option>
            </a-select>
          </a-col>

          <!-- PJ -->
          <a-col class="w-full md:w-auto">
            <a-select v-model:value="filter.user" placeholder="--Pilih PJ--" show-search allow-clear
              option-label-prop="label" option-filter-prop="label" class="w-full lg:w-96" @search="onSearchUser"
              @change="readData">
              <a-select-option v-for="pj in userOptions" :key="pj.identifier" :value="pj.identifier"
                :label="`${pj.identifier} - ${pj.name} - ${pj.position}`">
                <div class="flex items-center gap-2">
                  <a-tag color="blue">{{ pj.identifier }} - {{ pj.name }}</a-tag>
                  <span class="text-gray-700">{{ pj.position }}</span>
                </div>
              </a-select-option>
            </a-select>
          </a-col>

          <!-- Serah Terima -->
          <a-col class="w-full md:w-auto">
            <a-select v-model:value="filter.handed_over" placeholder="Serah Terima" class="min-w-40 lg:w-40 w-full"
              @change="readData" allow-clear>
              <a-select-option value="n">Belum Serah Terima</a-select-option>
              <a-select-option value="y">Sudah Serah Terima</a-select-option>
            </a-select>
          </a-col>

          <!-- Status Print -->
          <a-col class="w-full md:w-auto">
            <a-select v-model:value="filter.printed" placeholder="Status Barcode" class="min-w-48 lg:w-48 w-full"
              @change="readData" allow-clear>
              <a-select-option value="n">Belum Dibarcode</a-select-option>
              <a-select-option value="y">Sudah Dibarcode</a-select-option>
            </a-select>
          </a-col>

          <!-- Search -->
          <a-col class="w-full md:w-auto">
            <a-input v-model:value="filter.search" @keyup.enter="readData" placeholder="Ketikkan deskripsi ...">
              <template #addonAfter>
                <span @click="readData" class="text-white text-base">
                  <Icon icon="ant-design:search-outlined" />
                </span>
              </template>
            </a-input>
          </a-col>
          <!-- <a-col class="w-full md:w-auto">
            <a-button type="primary"
              class="bg-gradient-to-r from-purple-400 via-blue-500 to-blue-700 hover:from-yellow-400 hover:via-yellow-600 hover:to-purple-500 flex items-center justify-center text-white font-medium border-0 shadow-md transition-all duration-300 w-full"
              @click="newData()">
              <Icon icon="line-md:plus" class="mr-1" />
              Tambah Data AC
            </a-button>
          </a-col> -->
        </a-row>
      </a-col>
    </a-row>

    <!-- Toolbar -->
    <div class="mb-2 font-medium flex gap-x-2 items-center">
      <span>Total: {{ _pagination.total }} Data</span>
      <a-button type="primary"
        class="bg-gradient-to-r from-purple-400 via-blue-500 to-blue-700 hover:from-yellow-400 hover:via-yellow-600 hover:to-purple-500 flex items-center justify-center text-white font-medium border-0 shadow-md transition-all duration-300"
        @click="selectionMode = !selectionMode">
        <Icon :icon="selectionMode ? 'line-md:close' : 'streamline-sharp:select-all-remix'" class="mr-1" />
        {{ selectionMode ? "Batalkan Pilihan Data" : "Pilih Data" }}
      </a-button>
    </div>

    <!-- Info jumlah terpilih -->
    <div v-if="selectionMode && selectedRowKeys.length"
      class="mt-4 mb-3 text-sm font-semibold text-blue-600 flex gap-2 items-center">
      <span>Terpilih: {{ selectedRowKeys.length }} Data</span>
      <a-button type="primary" @click="showActionModal = true"
        class="bg-gradient-to-r from-red-400 via-red-500 to-red-600 hover:from-yellow-400 hover:via-yellow-600 hover:to-purple-500 flex items-center justify-center">
        <Icon icon="mdi:call-to-action" class="mr-1" />
        Lakukan Aksi
      </a-button>
      <a-button danger @click="clearSelection" class="flex items-center">
        <Icon icon="ic:sharp-clear" class="mr-1" />
        Hapus Pilihan
      </a-button>
    </div>

    <!-- Table -->
    <a-table :scroll="{ x: 800 }" :columns="columns" :row-key="(obj) => obj.qr_code_no" :pagination="_pagination"
      :loading="loadingStatus" :data-source="models" :row-selection="rowSelection" @change="handleTableChange">
      <template #bodyCell="{ index, column, record }">
        <template v-if="column.key === 'number'">
          {{ (_pagination.current - 1) * _pagination.pageSize + (index + 1) }}
        </template>

        <template v-if="column.key === 'qr_code_no'">
          <a-tag color="#2db7f5">
            <span class="text-sm">{{ record.qr_code_no }}</span>
          </a-tag>
        </template>

        <template v-if="column.key === 'item_code'">
          <a-tag v-if="record.item && record.item?.name" color="blue">
            <span class="text-sm">{{ record.item?.code }} - {{ record.item?.name }}</span>
          </a-tag>
          <a-tag v-else color="red">
            <span>{{ record.item_code }}</span>
          </a-tag>
        </template>

        <template v-if="column.key === 'pj'">
          <a-tag color="blue">
            <span class="text-sm">
              {{ record.b_user?.identifier }} - {{ record.b_user?.name }}
            </span>
          </a-tag>
        </template>

        <template v-if="column.key === 'category_name'">
          <a-tag v-if="record.category_name" color="pink">
            <span class="text-sm">{{ record.category_name }}</span>
          </a-tag>
          <span v-else>-</span>
        </template>

        <template v-if="column.key === 'is_deactivated'">
          <a-tag v-if="record.is_deactivated" color="red">
            <span class="text-sm">Tidak Aktif</span>
          </a-tag>
          <a-tag v-else color="green">
            <span class="text-sm">Aktif</span>
          </a-tag>
        </template>

        <template v-if="column.key === 'is_handed_over'">
          <a-tag v-if="record.is_handed_over" color="green">
            <span class="text-sm">Sudah Serah Terima</span>
          </a-tag>
          <a-tag v-else color="red">
            <span class="text-sm">Belum Serah Terima</span>
          </a-tag>
        </template>

        <template v-if="column.key === 'position'">
          <a-tag color="#8C5AE6">
            <span class="text-sm">
              <span>{{ positionText(record) }}</span>
            </span>
          </a-tag>
        </template>

        <template v-if="column.key === 'building_id'">
          <a-tag color="#108ee9">
            <span class="text-sm">
              <span>{{ record.building?.name }}</span>
            </span>
          </a-tag>
        </template>

        <template v-if="column.key === 'floor'">
          <a-tag color="cyan">
            <span>
              {{ record.floor ? `Lantai ${record.floor}` : '-' }}
            </span>
          </a-tag>
        </template>

        <template v-if="column.key === 'unit'">
          <a-tag color="blue">
            <span class="text-sm">
              <span>{{ unitText(record) }}</span>
            </span>
          </a-tag>
        </template>

        <template v-if="column.key === 'room'">
          <a-tag color="blue">
            <span class="text-sm">
              <span>{{ roomText(record) }}</span>
            </span>
          </a-tag>
        </template>

        <template v-if="column.key === 'unit_price'">
          {{ record.unit_price ? 'Rp ' + idCurrency(record.unit_price) : '-' }}
        </template>

        <template v-if="column.key === 'status_print'">
          <div v-if="record.print_count > 0" class="flex items-center justify-center gap-2">
            <Icon icon="line-md:circle-to-confirm-circle-transition" class="text-green-500 text-lg" />
            <a-tag color="green" class="font-semibold">
              {{ record.print_count }}x
            </a-tag>
            <a-tooltip :title="`Terakhir dicetak ${record.last_print_at} oleh ${record.last_print_by?.name ?? '-'}`">
              <span class="text-sm text-gray-700 truncate max-w-[120px]">
                {{ record.last_print_by?.name ?? 'System' }}
              </span>
            </a-tooltip>
          </div>

          <div v-else class="flex items-center justify-center gap-2 text-gray-400">
            <Icon icon="line-md:close-circle" class="text-red-400 text-lg" />
            <span class="italic text-sm">
              Belum pernah dicetak
            </span>
          </div>
        </template>

        <template v-if="column.key === 'data_source'">
          <a-tag :color="record.data_source == 'legacy_iis' ? 'red' : 'blue'" class="font-semibold">
            {{ record.data_source == "legacy_iis" ? 'Data IIS' : 'Data Simaris' }}
          </a-tag>
        </template>

        <!-- Aksi -->
        <template v-if="column.key === 'action' && !selectionMode">
          <a-button-group class="flex justify-center">
            <!-- QRCode -->
            <a-tooltip title="Cetak Barcode">
              <a-button size="small" type="text" @click="openPreviewModalLabel(record)" :style="{ padding: '0 5px' }">
                <Icon icon="streamline-sharp-color:qr-code-flat"
                  class="flex justify-center text-green-500 text-[24px]" />
              </a-button>
            </a-tooltip>

            <!-- Detail -->
            <a-tooltip title="Lihat Detail">
              <a :href="`${route}?req=open&code=${record.encrypt_code}`">
                <a-button size="small" type="text" :style="{ padding: '0 5px' }">
                  <Icon icon="line-md:file-search-twotone" class="flex justify-center text-blue-500 text-[24px]" />
                </a-button>
              </a>
            </a-tooltip>

            <!-- Hapus -->
            <a-tooltip title="Hapus Data">
              <a-popconfirm title="Yakin menghapus data?" @confirm="deleteData(record.id)">
                <a-button type="text" size="small" :style="{ padding: '0 5px' }">
                  <Icon icon="line-md:trash" class="flex justify-center text-red-500 text-[24px]" />
                </a-button>
              </a-popconfirm>
            </a-tooltip>
          </a-button-group>
        </template>
      </template>
    </a-table>
  </a-card>

  <!-- Modal Print Label -->
  <a-modal v-model:open="showPrintLabelModal" :title="'Cetak Label'" width="700px" :mask-closable="false"
    @ok="handlePrintLabel" ok-text="Cetak Label">
    <a-form ref="form" name="basic" :label-col="{ span: 5 }" :wrapper-col="{ span: 19 }">
      <a-form-item label="Mode" data-column="mode" :rules="[{ required: true }]">
        <a-select v-model:value="form_preview_label.mode" placeholder="--Pilih Mode--"
          title="Mode akan menentukan ukuran sticker">
          <a-select-option value="standard"
            title="Ini adalah mode standard yang akan mencetak label dengan ukuran standard berbentuk persegi panjang">Standard</a-select-option>
          <a-select-option value="mini"
            title="Ini adalah mode yang akan mencetak label dengan ukuran mini berbentuk persegi">Mini (50mm x
            30mm)</a-select-option>
        </a-select>
      </a-form-item>
      <a-form-item label="Tipe Kertas" data-column="tipe_kertas" :rules="[{ required: true }]">
        <a-select v-model:value="form_preview_label.tipe_kertas" placeholder="--Pilih Tipe Kertas--">
          <a-select-option value="kertas-biasa"
            title="Kertas biasa adalah kertas yang berukuran a4, f4 dan ukuran lainnya">Kertas
            Biasa</a-select-option>
          <a-select-option value="stiker" title="Kertas stiker adalah kertas stiker label yang telah terpotong">Kertas
            Stiker</a-select-option>
        </a-select>
      </a-form-item>
    </a-form>
  </a-modal>

  <!-- Modal Preview PDF -->
  <a-modal v-model:open="showPDFModal" title="Pratinjau Label Inventaris IIS" width="900px" :mask-closable="false">
    <iframe v-if="pdfContent" :src="pdfContent + '#toolbar=0'" width="100%" height="300px" class="rounded-xl"></iframe>
    <template #footer>
      <div class="flex justify-end gap-2">
        <a-button type="default" @click="showPDFModal = false">
          Tutup
        </a-button>
        <a-button type="primary"
          class="bg-gradient-to-r from-purple-400 via-blue-500 to-blue-700 hover:from-yellow-400 hover:via-yellow-600 hover:to-purple-500 flex items-center justify-center text-white font-medium border-0 shadow-md transition-all duration-300"
          @click="openPrintConfirm">
          <Icon icon="line-md:cloud-alt-print-loop" class="mr-1" />
          Print
        </a-button>
      </div>
    </template>
  </a-modal>

  <!-- Modal Warning Sebelum Print -->
  <a-modal v-model:open="showPrintConfirm" title="Konfirmasi Cetak Label Inventaris" :mask-closable="false"
    width="520px">

    <!-- ALERT -->
    <a-alert type="warning" show-icon class="mb-4" :message="printMode === 'single'
      ? 'Perhatian!'
      : 'Perhatian Cetak Massal!'" :description="printMode === 'single'
        ? 'Label inventaris memiliki batas maksimal cetak.'
        : 'Anda akan mencetak beberapa label sekaligus. Kuota akan dikurangi meskipun dibatalkan.'" />


    <!-- INFO CARD -->
    <div class="bg-gray-50 rounded-lg p-4 space-y-4">


      <!-- SINGLE -->
      <template v-if="printMode === 'single' && selectedQR">

        <!-- HEADER -->
        <div class="flex items-center justify-between bg-white rounded-md p-3 shadow-sm">
          <div class="flex items-center gap-3">
            <Icon icon="line-md:qr-code" class="text-2xl text-blue-500" />
            <div>
              <div class="text-xs text-gray-500">Barcode</div>
              <div class="font-semibold text-gray-800">
                {{ selectedQR.qr_code_no }}
              </div>
            </div>
          </div>

          <a-tag :color="selectedQR.print_count >= 2 ? 'red' : 'green'" class="text-sm font-semibold">
            {{ selectedQR.print_count >= 2 ? 'Batas Tercapai' : 'Siap Cetak' }}
          </a-tag>
        </div>

        <!-- STATS -->
        <div class="grid grid-cols-2 gap-3">

          <div class="bg-white rounded-md p-3 shadow-sm text-center">
            <div class="text-xs text-gray-500">Jumlah Cetak</div>
            <div class="text-lg font-bold" :class="selectedQR.print_count >= 2 ? 'text-red-600' : 'text-green-600'">
              {{ selectedQR.print_count }} / 2
            </div>
          </div>

          <div class="bg-white rounded-md p-3 shadow-sm text-center">
            <div class="text-xs text-gray-500">Status</div>
            <div class="text-sm font-semibold" :class="selectedQR.print_count >= 2 ? 'text-red-600' : 'text-green-600'">
              {{ selectedQR.print_count >= 2
                ? 'Tidak Dapat Dicetak'
                : 'Masih Bisa Dicetak' }}
            </div>
          </div>

        </div>

        <!-- FOOT NOTE -->
        <div class="text-xs text-gray-500 italic pt-1">
          Pastikan label sudah benar sebelum mencetak. Proses cetak akan mengurangi kuota.
        </div>

      </template>

      <!-- MULTI -->
      <template v-else>

        <!-- SUMMARY -->
        <div class="grid grid-cols-3 gap-3 text-center">
          <div class="bg-white rounded-md p-3 shadow-sm">
            <div class="text-xs text-gray-500">Dipilih</div>
            <div class="text-lg font-bold text-blue-600">
              {{ totalPrintCount }}
            </div>
          </div>

          <div class="bg-white rounded-md p-3 shadow-sm">
            <div class="text-xs text-gray-500">Aman</div>
            <div class="text-lg font-bold text-green-600">
              {{ totalPrintCount - exceededLimitCount }}
            </div>
          </div>

          <div class="bg-white rounded-md p-3 shadow-sm">
            <div class="text-xs text-gray-500">Melebihi Batas</div>
            <div class="text-lg font-bold text-red-600">
              {{ exceededLimitCount }}
            </div>
          </div>
        </div>

        <!-- DIVIDER -->
        <div class="border-t pt-3 text-sm text-gray-600 font-medium">
          Daftar Barcode
        </div>

        <!-- LIST -->
        <div class="max-h-48 overflow-y-auto space-y-2">

          <div v-for="r in selectedRowsData" :key="r.qr_code_no"
            class="flex justify-between items-center bg-white rounded-md px-3 py-2 shadow-sm">
            <div class="flex items-center gap-2">
              <Icon :icon="r.print_count >= 2
                ? 'line-md:alert-circle'
                : 'line-md:confirm-circle'" :class="r.print_count >= 2
                  ? 'text-red-500'
                  : 'text-green-500'" class="text-lg" />
              <span class="font-medium text-gray-800">
                {{ r.qr_code_no }}
              </span>
            </div>

            <a-tag :color="r.print_count >= 2 ? 'red' : 'green'" class="text-xs">
              {{ r.print_count }} / 2
            </a-tag>
          </div>

        </div>

        <!-- WARNING -->
        <div v-if="exceededLimitCount > 0" class="text-xs text-red-500 italic pt-2">
          Beberapa label sudah mencapai batas maksimal cetak dan berpotensi ditolak sistem.
        </div>

      </template>

    </div>

    <!-- FOOTER -->
    <template #footer>
      <a-button @click="showPrintConfirm = false">
        Batal
      </a-button>

      <a-button type="primary" danger @click="printPDF" class="flex items-center" :disabled="disablePrintButton">
        <Icon icon="line-md:cloud-alt-print-loop" class="mr-1" />
        Ya, Cetak Label
      </a-button>
    </template>

  </a-modal>

  <!-- Modal Action -->
  <a-modal v-model:open="showActionModal" title="Pilih Aksi" :footer="null" width="600px">
    <div class="grid lg:grid-cols-2 gap-4 lg:mt-4 mb-4">

      <div v-for="a in actionOptions" :key="a.key" @click="handleActionClick(a.key)" class="flex flex-col items-center p-4 rounded-lg cursor-pointer
             transition transform hover:scale-105 hover:shadow-xl" :style="a.gradient">
        <!-- INNER ICON BOX -->
        <div class="w-28 h-28 border-2 border-white rounded-lg
                  flex flex-col items-center justify-center">
          <Icon :icon="a.icon" class="text-4xl mb-1 text-white" />
          <span class="text-xs font-bold text-white">
            {{ a.code }}
          </span>
        </div>

        <!-- TITLE -->
        <span class="mt-3 font-semibold text-center text-white">
          {{ a.title }}
        </span>
      </div>

    </div>
  </a-modal>

  <!-- Modal action bulk -->
  <a-modal v-model:open="showBulkActionModal" :title="modalTitle" width="600px" @ok="handleBulkSubmit"
    :destroy-on-close="true">

    <!-- === CHANGE CONDITION === -->
    <template v-if="actionType === 'change_condition'">
      <a-form layout="vertical">
        <a-form-item label="Kondisi" required>
          <a-select v-model:value="bulkForm.condition" placeholder="Pilih kondisi">
            <a-select-option value="Baik">Baik</a-select-option>
            <a-select-option value="Rusak Ringan">Rusak Ringan</a-select-option>
            <a-select-option value="Rusak Berat">Rusak Berat</a-select-option>
          </a-select>
        </a-form-item>
      </a-form>
    </template>

    <!-- === CHANGE PJ === -->
    <template v-else-if="actionType === 'change_pj'">
      <a-form layout="vertical">
        <a-form-item label="Penanggung Jawab (PJ)" required>
          <a-select v-model:value="bulkForm.pj_nik" placeholder="-- Pilih PJ --" show-search allow-clear
            option-label-prop="label" option-filter-prop="label" @search="onSearchUser">
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
        </a-form-item>
      </a-form>
    </template>

    <!-- === TOGGLE STATUS === -->
    <template v-else-if="actionType === 'toggle_status'">
      <a-form layout="vertical">

        <a-form-item label="Status Keaktifan" required>
          <a-select v-model:value="bulkForm.is_active" placeholder="Pilih status">
            <a-select-option :value="1">Aktif</a-select-option>
            <a-select-option :value="0">Nonaktif</a-select-option>
          </a-select>
        </a-form-item>

        <a-form-item v-if="bulkForm.is_active === 0" label="Catatan Penonaktifan" required>
          <a-textarea v-model:value="bulkForm.is_deactivated_notes" placeholder="Alasan penonaktifan" :rows="3" />
        </a-form-item>

      </a-form>
    </template>

  </a-modal>

  <!-- Modal New & Update Data -->
  <a-modal v-model:open="showModal" :title="form.id ? 'Ubah Data Inventaris' : 'Tambah Data Inventaris'" width="1000px"
    @ok="writeData" :mask-closable="false" :destroy-on-close="true">
    <a-form ref="formRef" layout="vertical">

      <!-- ================= CABANG ================= -->
      <div class="mb-4 p-4 rounded-xl border bg-white">
        <a-form-item label="Cabang" data-column="branch_id" :rules="[{ required: true }]">
          <a-select v-model:value="form.branch_id" placeholder="--Pilih Cabang--" option-label-prop="label"
            option-filter-prop="label" show-search allow-clear disabled>
            <a-select-option v-for="u in constant.BRANCHES" :key="u.id" :value="u.id" :label="`${u.name}`">
              <div class="flex items-center gap-2">
                <a-tag color="blue">{{ u.name }}</a-tag>
              </div>
            </a-select-option>
          </a-select>
        </a-form-item>
      </div>

      <!-- ================= IDENTITAS BARANG ================= -->
      <div class="mb-4 p-4 rounded-xl border bg-blue-50">
        <h3 class="font-semibold text-slate-700 mb-3">Identitas Barang</h3>

        <a-form-item label="Kode Item" data-column="item_code" :rules="[{ required: true }]">
          <a-select v-model:value="form.item_code" placeholder="--Pilih Item--" show-search :filter-option="false"
            allow-clear @search="onSearchItem">
            <a-select-option v-for="item in itemOptions" :key="item.code" :value="item.code" :label="item.name">
              <div class="flex items-center gap-2">
                <a-tag color="blue">{{ item.code }}</a-tag>
                <span class="text-gray-700">{{ item.name }}</span>
              </div>
            </a-select-option>
          </a-select>
        </a-form-item>

        <a-form-item label="Nama Barang / Deskripsi Barang" data-column="description" :rules="[{ required: true }]">
          <a-textarea v-model:value="form.description" :rows="3" />
        </a-form-item>

        <a-form-item label="Kategori IIS (Opsional)">
          <a-select v-model:value="form.category_number" placeholder="--Pilih Kategori IIS--" show-search allow-clear
            option-filter-prop="label" class="w-full lg:w-96"
            @change="v => form.category_name = constant.CATEGORIES.find(c => c.category_number === v)?.category_name ?? null">
            <a-select-option v-for="cat in constant.CATEGORIES" :key="cat.category_number" :value="cat.category_number"
              :label="cat.category_name">
              {{ cat.category_name }}
            </a-select-option>
          </a-select>
        </a-form-item>
      </div>

      <!-- ================= ETC ================= -->
      <div class="mb-4 p-4 rounded-xl border bg-white">
        <div class="flex items-center justify-between mb-3">
          <h3 class="font-semibold text-slate-700 flex items-center gap-2">
            <Icon icon="mdi:playlist-edit" class="text-indigo-500 text-xl" />
            Atribut Detail
          </h3>

          <a-button type="primary" class="
            bg-gradient-to-r
            from-indigo-400 via-blue-500 to-sky-600
            hover:from-emerald-400 hover:via-teal-500 hover:to-cyan-600
            text-white
            font-medium
            border-0
            shadow-md
            transition-all
            duration-300
            flex
            items-center
            justify-center
            gap-1
          " @click="addEtc">
            <Icon icon="line-md:plus" class="text-lg" />
            Tambah Atribut
          </a-button>
        </div>

        <div v-for="(row, index) in form.etc" :key="index"
          class="flex items-center gap-2 mb-2 p-2 rounded-lg bg-white border shadow-sm">
          <!-- KEY -->
          <a-input v-model:value="row.key" placeholder="Nama atribut" class="w-1/3" />

          <!-- VALUE -->
          <a-input v-model:value="row.value" placeholder="Isi ... Contoh : Xiaomi123" class="flex-1" />

          <!-- REMOVE -->
          <a-tooltip title="Hapus atribut">
            <a-button danger type="text" @click="removeEtc(index)">
              <Icon icon="mdi:trash-can-outline" class="text-lg" />
            </a-button>
          </a-tooltip>
        </div>

        <!-- EMPTY STATE -->
        <div v-if="!form.etc.length" class="text-sm text-slate-500 italic text-center py-3">
          Belum ada atribut tambahan
        </div>
      </div>

      <!-- ================= LOKASI ================= -->
      <div class="mb-4 p-4 rounded-xl border bg-blue-50">
        <h3 class="font-semibold text-slate-700 mb-3">Lokasi & Penanggung Jawab</h3>

        <a-form-item label="Jenis Lokasi" data-column="location_type"
          :rules="[{ required: true, message: 'Pilih jenis lokasi' }]">
          <a-segmented v-model:value="form.location_type" size="large" block :options="[
            { label: 'Gudang', value: 'warehouse' },
            { label: 'Didistribusikan', value: 'distributed' }
          ]" />
        </a-form-item>

        <!-- ================= DISTRIBUSI ================= -->
        <div v-if="form.location_type === 'distributed'" class="grid lg:grid-cols-2 lg:gap-4">
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

        </div>

        <a-form-item v-if="form.location_type === 'distributed'" label="Unit" data-column="unit_id"
          :rules="[{ required: true }]">
          <a-select v-model:value="form.unit_id" show-search allow-clear class="w-full" placeholder="--Pilih Unit--"
            option-label-prop="label" option-filter-prop="label">
            <a-select-option v-for="u in constant.UNITS" :key="u.id" :value="u.id" :label="`${u.name}`">
              <div class="flex flex-col">
                <span class="font-medium">{{ u.name }}</span>
                <span class="text-xs text-gray-400">{{ u.department
                }}</span>
              </div>
            </a-select-option>
          </a-select>
        </a-form-item>

        <a-form-item v-if="form.location_type === 'distributed'" label="Ruangan" data-column="room_id">
          <a-select v-model:value="form.room_id" placeholder="--Pilih Ruangan--" show-search allow-clear
            :disabled="!form.building_id || !form.floor" :filter-option="false" @search="onSearchRoom">
            <a-select-option v-for="r in roomOptions" :key="r.id" :value="r.id" :label="r.name">
              <div class="flex flex-col">
                <span class="font-medium">{{ r.name }}</span>
                <span class="text-xs text-gray-400">
                  Gedung {{ r.building?.name || r.building_id }}
                  • Lantai {{ r.floor }}
                </span>
              </div>
            </a-select-option>
          </a-select>
        </a-form-item>

        <a-form-item v-if="form.location_type === 'distributed'" label="Penanggung Jawab (PJ)" data-column="pj_nik"
          :rules="[{ required: true }]">
          <a-select v-model:value="form.pj_nik" placeholder="--Pilih PJ--" show-search allow-clear
            class="w-full lg:w-96" option-label-prop="label" option-filter-prop="label" @search="onSearchUser">
            <a-select-option v-for="u in userOptions" :key="u.identifier" :value="u.identifier"
              :label="`${u.identifier} - ${u.name} - ${u.position || ''}`">
              <div class="flex items-start gap-3">
                <Icon icon="mdi:account-circle-outline" class="text-sky-500 text-xl mt-0.5" />
                <div class="flex flex-col leading-tight">
                  <span class="font-medium text-slate-800">{{ u.name }}</span>
                  <span class="text-xs text-slate-400">
                    {{ u.identifier }}<span v-if="u.position"> • {{ u.position }}</span>
                  </span>
                </div>
              </div>
            </a-select-option>
          </a-select>
        </a-form-item>

        <!-- ================= GUDANG ================= -->
        <a-form-item v-if="form.location_type === 'warehouse'" label="Pilih Gudang" data-column="warehouse_id"
          :rules="[{ required: true, message: 'Pilih gudang' }]">
          <a-select v-model:value="form.warehouse_id" placeholder="--Pilih Gudang--" allow-clear show-search
            option-label-prop="display" option-filter-prop="label" class="w-full lg:w-96">
            <a-select-option v-for="w in filteredWarehouses" :key="w.id" :value="w.id"
              :label="`${w.code} - ${w.name} - ${w.branch?.name || ''} ${w.person_in_charge?.identifier || ''}  ${w.person_in_charge?.name || ''}  ${w.person_in_charge?.position || ''}`"
              :display="`${w.code} - ${w.name} - ${w.branch?.name || '-'}`">
              <div class="flex items-start gap-3">
                <Icon icon="streamline-plump-color:warehouse-1" class="text-amber-500 text-xl mt-0.5" />
                <div class="flex flex-col leading-tight">
                  <span class="font-medium text-slate-800">{{ w.code }} - {{ w.name }} - {{ w.branch?.name || '-'
                  }}</span>
                  <span class="text-xs text-gray-500">
                    PJ : {{ w.person_in_charge?.identifier || '-' }} - {{ w.person_in_charge?.name || '-' }}<span
                      v-if="w.person_in_charge?.position"> • {{ w.person_in_charge.position }}</span>
                  </span>
                </div>
              </div>
            </a-select-option>
          </a-select>
        </a-form-item>

        <!-- === TAMPILAN GUDANG (READ ONLY) === -->
        <div v-if="form.location_type === 'warehouse' && selectedWarehouse"
          class="mt-4 rounded-2xl border border-blue-200 bg-gradient-to-br from-blue-50 via-sky-50 to-white p-5">

          <!-- Header -->
          <div class="flex items-center justify-between mb-4">
            <h4 class="font-semibold text-slate-700 flex items-center gap-2">
              <Icon icon="mdi:warehouse" class="text-blue-500 text-lg" />
              Data Gudang
            </h4>

            <a-tag color="blue" class="!rounded-full">
              {{ selectedWarehouse.code }}
            </a-tag>
          </div>

          <!-- Content -->
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 text-sm">

            <!-- Nama Gudang -->
            <div class="flex items-start gap-3">
              <Icon icon="mdi:home-city-outline" class="text-indigo-500 text-xl mt-0.5" />
              <div>
                <div class="text-xs text-slate-400">Nama Gudang</div>
                <div class="font-medium text-slate-800">
                  {{ selectedWarehouse.name }}
                </div>
                <div class="text-xs text-slate-500">
                  {{ selectedWarehouse.branch?.name || '-' }}
                </div>
              </div>
            </div>

            <!-- Penanggung Jawab -->
            <div class="flex items-start gap-3">
              <Icon icon="mdi:account-badge-outline" class="text-sky-500 text-xl mt-0.5" />
              <div>
                <div class="text-xs text-slate-400">Penanggung Jawab</div>
                <div class="font-medium text-slate-800">
                  {{ selectedWarehouse.person_in_charge?.name || '-' }}
                </div>
                <div class="text-xs text-slate-500">
                  {{ selectedWarehouse.person_in_charge?.identifier || '' }}
                  <span v-if="selectedWarehouse.person_in_charge?.position">
                    • {{ selectedWarehouse.person_in_charge.position }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Status Gudang -->
            <div class="flex items-start gap-3">
              <Icon icon="mdi:truck-check-outline" class="text-teal-500 text-xl mt-0.5" />
              <div>
                <div class="text-xs text-slate-400">Status</div>
                <a-tag :color="selectedWarehouse.can_receive ? 'green' : 'red'" class="!rounded-full mt-0.5">
                  {{ selectedWarehouse.can_receive ? 'Dapat Menerima Barang' : 'Tidak Menerima Barang' }}
                </a-tag>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ================= KONDISI & PENGADAAN ================= -->
      <div class="mb-4 p-4 rounded-xl border bg-white">
        <h3 class="font-semibold text-slate-700 mb-3">Kondisi & Pengadaan</h3>

        <div class="grid lg:grid-cols-2 gap-4">
          <a-form-item label="Kondisi" data-column="condition" :rules="[{ required: true }]">
            <a-select v-model:value="form.condition" show-search>
              <a-select-option value="Baik">Baik</a-select-option>
              <a-select-option value="Rusak Ringan">Rusak Ringan</a-select-option>
              <a-select-option value="Rusak Berat">Rusak Berat</a-select-option>
            </a-select>
          </a-form-item>

          <a-form-item label="Tahun Pembelian" data-column="purchase_year" :rules="[{ required: true }]">
            <a-date-picker v-model:value="form.purchase_year" picker="year" :value-format="year_format" class="w-full"
              placeholder="Pilih Tahun" :disabled-date="disableFutureYear" />
          </a-form-item>
        </div>

        <div v-if="isCurrentYearPurchase">
          <div class="grid lg:grid-cols-2 gap-4">
            <a-form-item label="No. PO" data-column="po_number" :rules="[{ required: true }]">
              <a-input v-model:value="form.po_number" />
            </a-form-item>

            <a-form-item label="No. Receive Gudang" data-column="received_number" :rules="[{ required: true }]">
              <a-input v-model:value="form.received_number" />
            </a-form-item>
          </div>

          <a-form-item label="Total Harga" data-column="unit_price" :rules="[{ required: true }]">
            <a-input-number class="w-full" v-model:value="form.unit_price" :min="0" :controls="false"
              :formatter="(value) => `${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ',')"
              :parser="(value) => value.replace(/\$\s?|(,*)/g, '')" />
          </a-form-item>
        </div>
      </div>

      <!-- ================= CATATAN ================= -->
      <div class="mb-4 p-4 rounded-xl border bg-blue-50">
        <h3 class="font-semibold text-slate-700 mb-3">Catatan Inventaris</h3>

        <a-form-item data-column="notes" :rules="[{ required: true }]">
          <a-textarea v-model:value="form.notes" :rows="5" />
        </a-form-item>
      </div>

    </a-form>
  </a-modal>

</template>