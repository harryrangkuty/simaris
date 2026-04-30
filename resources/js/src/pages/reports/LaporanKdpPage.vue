<script>

export default {
  props: {
    constant: {
      type: Object,
      default: () => ({}),
    },
  },
  data() {
    return {
      filters: {
        satker_id: null,
      },
    }
  },

  created() {
    if (this.operator_kdp) {
      this.filters.satker_id = this.user.satkers_id[0];
    } else if (this.reviewer_kdp && !this.sudo) {
      this.filters.satker_id = this.user.reviews_id[0];
    }
  },

  methods: {
    
    report() {
      const vm = this;
      let params = new URLSearchParams();

      Object.keys(vm.filters).forEach(key => {
        if (vm.filters[key] !== null && vm.filters[key] !== undefined) {
          params.append(key, vm.filters[key]);
        }
      });

      const url = `${vm.route}/report?${params.toString()}`;
      const _window = window.open(url, 'name', 'height=500, width=900, top=50, left=50');
      if (window.focus) {
        _window.focus();
      }
      return false;
    },

  },
};
</script>

<template>
  <div class="!w-full shadow-md rounded-[8px] overflow-hidden flex-grow lg:mb-0 mb-6">
    <a-card class="card card-white !px-5 h-full" title="Laporan KDP">
      <a-row :gutter="{ xs: 8, sm: 16, md: 24, lg: 32 }">
        <a-col class="gutter-row" :span="24" :md="12">
          <a-form :label-col="{ span: 6 }" :wrapper-col="{ span: 18 }">
            <a-form-item label="Satuan Kerja" :rules="[{ required: true }]">
              <a-select v-model:value="filters.satker_id" show-search option-filter-prop="title" style="width: 100%"
                placeholder="Pilih Satuan Kerja" :disabled="Object.keys(constant.SATKER).length < 2">
                <a-select-option :key="null" :value="null" title="Semua Satker">
                  Semua Satker
                </a-select-option>
                <a-select-option v-for="satker in constant.SATKER" :key="satker.id" :value="satker.id"
                  :title="satker.nama">
                  {{ satker.nama }}
                </a-select-option>
              </a-select>
            </a-form-item>
          </a-form>
        </a-col>
        <a-col class="gutter-row" :span="24" :md="12">
          <a-form :label-col="{ span: 6 }" :wrapper-col="{ span: 18 }">
            <!-- Kosong dulu -->
          </a-form>
        </a-col>
      </a-row>
      <a-button type="primary" class="flex items-center justify-center w-full sm:w-auto min-w-[6rem] mb-2"
        @click="report()">Report</a-button>
    </a-card>
  </div>
</template>