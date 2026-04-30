<script>

export default {
  props: {
    constant: {
      type: Object,
      default: () => ({}),
    },
  },
  data() {
    return {}
  },


  methods: {
      reportPDF() {
        const url = `${this.route}/report`;
        const _window = window.open(url, "name", "height=500, width=900, top=50, left=50");
        if (window.focus) {
          _window.focus();
        }
        return false;
      },

      reportExcel() {
        const url = `${this.route}/report?req=data_in_excel`;
        window.location.href = url;
      },
    },
  };
      
</script>

<template>
  <div class="!w-full shadow-md rounded-[8px] overflow-hidden flex-grow lg:mb-0 mb-6">
    <a-card class="card card-white !px-5 h-full" title="Laporan Kinerja">
      <a-button type="primary" class="flex items-center justify-center w-full sm:w-auto min-w-[6rem] mb-2"
        @click="reportPDF()">Report
      </a-button>
      <a-button type="success" class="flex items-center justify-center w-full sm:w-auto min-w-[6rem] mb-2 ml-4"
      @click="reportExcel()">Export to Excel
    </a-button>
    </a-card>
  </div>
</template>