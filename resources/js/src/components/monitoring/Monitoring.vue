<script>
import dayjs from 'dayjs';

const transaksi_registered = [
  {
    title: "Total Data",
    dataIndex: "total_perolehan_registered",
    align: "center",
    width: '30%',
  },
  {
    title: "Total Item",
    align: "center",
    dataIndex: "total_item_registered",
    width: '30%',
  },
  {
    title: "Total Nilai",
    key: "total_nilai",
    align: "right",
    width: '40%'
  },
];

const transaksi_unregistered = [
  {
    title: "Total Data",
    dataIndex: "total_perolehan_unregistered",
    align: "center",
    width: '30%',
  },
  {
    title: "Total Item",
    align: "center",
    dataIndex: "total_item_unregistered",
    width: '30%',
  },
  {
    title: "Total Nilai",
    key: "total_nilai",
    align: "right",
    width: '40%'
  },
];

export default {
  props: {
    constant: {
      type: Object,
      default: () => ({}),
    },
  },
  data() {
    return {
      transaksi_registered,
      transaksi_unregistered,
      filters: {
        year: null,
        satker_id: null,
      },
      selectedDate: dayjs(),
      data_perolehan: [],
      data_profil: [],
      data_kondisi: [],
      data_operator: [],
      data_reviewer: [],
      opt_status: ['OPEN', 'CLOSE', 'FINISH'],
      opt_kondisi: ['BAIK', 'RUSAK RINGAN', 'RUSAK BERAT'],
    };
  },
  created() {
    if (this.operator_aset && this.user.active_role_id) {
      this.filters.satker_id = this.user.satkers_id[0];
    } else if (this.reviewer_aset && this.user.active_role_id) {
      this.filters.satker_id = this.user.reviews_id[0];
    }
  },

  mounted() {
    this.updateYearFilter(this.selectedDate);
  },

  methods: {
    async totalPerolehan() {
      const vm = this;
      vm.loadingTrue();

      const params = {
        req: "total_perolehan",
        ...vm.filters
      };

      const response = await vm.axios.get(vm.readRoute, { params })
      if (response && response.data) {
        vm.data_perolehan = response.data
        vm.loadingFalse();
      }
    },
    async totalProfil() {
      const vm = this;
      vm.loadingTrue();

      const params = {
        req: "aset_teregister",
        ...vm.filters
      };

      const response = await vm.axios.get(vm.readRoute, { params })
      if (response && response.data) {
        vm.data_profil = response.data.models;
        vm.loadingFalse();
      }
    },
    async totalKondisi() {
      const vm = this;
      vm.loadingTrue();

      const params = {
        req: "aset_kondisi",
        ...vm.filters
      };

      const response = await vm.axios.get(vm.readRoute, { params })
      if (response && response.data) {
        vm.data_kondisi = response.data.models;
        vm.loadingFalse();
      }
    },
    async asetUser() {
      const vm = this;
      vm.loadingTrue();

      const params = {
        req: "aset_user",
        ...vm.filters
      };

      const response = await vm.axios.get(vm.readRoute, { params })
      if (response && response.data) {
        vm.data_operator = response.data.operator;
        vm.data_reviewer = response.data.reviewer;
        vm.loadingFalse();
      }
    },
    updateYearFilter(selectedDate) {
      if (selectedDate) {
        const selectedYear = new Date(selectedDate).getFullYear();
        this.filters.year = selectedYear;
        this.changeSatker()
      } else {
        this.filters.year = null;
      }
    },
    changeSatker() {
      const vm = this;
      vm.totalPerolehan();
      vm.totalProfil();
      vm.totalKondisi();
      vm.asetUser();
    },
    getSatkerName(satkerId) {
      const satker = this.constant.SATKER.find(s => s.id === satkerId);
      return satker ? satker.nama : '';
    },
  }
};
</script>

<template>
  <div class="lg:flex justify-between items-center mt-5">
    <div class="hidden lg:flex justify-start font-bold text-xl">
      {{ getSatkerName(filters.satker_id) }} - {{ filters.year }}
    </div>
    <div class="lg:flex justify-end">
      <div class="lg:flex gap-x-1.5">
        <div class="mb-1.5 lg:mb-0">
          <a-date-picker v-model:value="selectedDate" picker="year" :allow-clear="false" @change="updateYearFilter"
            class="lg:w-40 :w-full font-bold">
          </a-date-picker>
        </div>
        <div>
          <a-select v-model:value="filters.satker_id" show-search option-filter-prop="title" class="lg:w-80 w-full z-40"
            placeholder="Satuan Kerja" :getPopupContainer="(trigger) => trigger.parentNode" @change="changeSatker">
            <a-select-option :key="null" :value="null" title="Semua Satker">
              Semua Satker
            </a-select-option>
            <a-select-option v-for="satker in constant.SATKER" :key="satker.id" :value="satker.id" :title="satker.nama">
              {{ satker.nama }}
            </a-select-option>
          </a-select>
        </div>
      </div>
    </div>
  </div>
  <div class="mt-10 px-4">
    <div>
      <h1 class="text-lg font-semibold">1. Data Transaksi Perolehan - {{ filters.year }}</h1>
      <a-row :gutter="16">
        <a-col :span="24" :md="6" :sm="12" :xs="24" v-for="(items, key) in data_perolehan.models" :key="key" class="mb-4">
          <a-card :title="key" bordered class="card-white !px-2 shadow-lg !h-full xl:!h-auto"
            style=" border-radius: 1rem !important;">
            <div v-for="(item, key2) in items" :key="key2" class="mb-2.5">
              <div class="relative">
                <div class=" w-full">
                  <a-tag v-if="fb(key2) === 'OPEN'" color="#22c55e" class="w-full text-center !rounded-t-md">
                    OPEN
                  </a-tag>
                  <a-tag v-if="fb(key2) === 'CLOSE'" color="#ef4444" class="w-full text-center !rounded-t-md">
                    CLOSE
                  </a-tag>
                  <a-tag v-if="fb(key2) === 'FINISH'" color="#3b82f6" class="w-full text-center !rounded-t-md">
                    FINISH
                  </a-tag>
                </div>
                <div :class="{
                  '-mt-1 border-2 !rounded-b-md p-4 relative': true,
                  'border-green-500 bg-green-50': fb(key2) === 'OPEN',
                  'border-red-500 bg-red-50': fb(key2) === 'CLOSE',
                  'border-blue-500 bg-blue-50': fb(key2) === 'FINISH'
                }">
                  <a-form ref="form" name="info" :label-col="{ span: 9 }" :wrapper-col="{ span: 15 }">
                    <a-form-item label="Total Data" label-align="left" :colon="false" class="!mb-2 !px-1.5">
                      {{ fb(item.total_perolehan, 0) + ' Perolehan' }}
                    </a-form-item>
                    <a-form-item label="Total Item" label-align="left" :colon="false" class="!mb-2 !px-1.5 break-all">
                      {{ fb(item.total_item, 0) + ' Item' }}
                    </a-form-item>
                    <a-form-item label="Total Nilai" label-align="left" :colon="false" class="!mb-2 !px-1.5 break-all">
                      Rp.{{ idCurrency(fb(item.total_nilai, 0)) }}
                    </a-form-item>
                  </a-form>
                </div>
              </div>
            </div>
          </a-card>
        </a-col>
      </a-row>
      <a-card style="margin-top: 16px; border-radius: 1rem !important;"
        :title="`Ringkasan Transaksi Perolehan Aset Tahun ${filters.year}`"
        class="card-white !px-2 shadow-lg flex flex-col justify-between h-full !mt-8">
        <a-tag color="#1890ff">
          Telah Registerasi
        </a-tag>
        <a-table :scroll="{ x: false }" :columns="transaksi_registered" :row-key="(obj) => obj.id" :pagination="false"
          :data-source="[data_perolehan]" @change="handleTableChange" class="mt-2 mb-2">
          <template #headerCell="{ column }">
            <div style="font-size: 16px; font-weight: bold;">{{ column.title }}</div>
          </template>
          <template #bodyCell="{ column, record }">
            <template v-if="column.key === 'total_nilai'">
              Rp. {{ idCurrency(record.total_nilai_registered) }}
            </template>
          </template>
        </a-table>
        <a-tag color="#ff4d4f">
          Belum Registrasi
        </a-tag>
        <a-table :scroll="{ x: false }" :columns="transaksi_unregistered" :row-key="(obj) => obj.id" :pagination="false"
          :data-source="[data_perolehan]" @change="handleTableChange" class="mt-2">
          <template #headerCell="{ column }">
            <div style="font-size: 16px; font-weight: bold;">{{ column.title }}</div>
          </template>
          <template #bodyCell="{ column, record }">
            <template v-if="column.key === 'total_nilai'">
              Rp. {{ idCurrency(record.total_nilai_unregistered) }}
            </template>
          </template>
        </a-table>
      </a-card>
    </div>
    <div class="!mt-8">
      <h1 class="text-lg font-semibold mt-7">2. Data Aset Teregister - s/d {{ filters.year }}</h1>
      <a-row :gutter="16" v-if="data_profil.length > 0">
        <a-col :span="24" :md="8" v-for="(item, index) in data_profil" :key="index" class="mb-4">
          <a-card bordered class="card-white !px-2 shadow-lg" style="border-radius: 1rem !important;">
            <div class="border-2 border-blue-500 bg-blue-50 rounded-lg p-4">
              <h3 class="text-lg font-bold">{{ item.nama_kategori }}</h3>
              <a-form ref="form" name="info" :label-col="{ span: 9 }" :wrapper-col="{ span: 15 }">
                <a-form-item label="Total Data" label-align="left" :colon="false" class="!mb-2 !px-1.5">
                  {{ item.count }} Profil
                </a-form-item>
                <a-form-item label="Total Nilai Buku" label-align="left" :colon="false" class="!mb-2 !px-1.5 break-all">
                  Rp.{{ idCurrency(item.total_nilai) }}
                </a-form-item>
              </a-form>
            </div>
          </a-card>
        </a-col>
      </a-row>
      <a-empty v-else />
    </div>
    <div class="!mt-8">
      <h1 class="text-lg font-semibold mt-7">3. Rekap Kondisi Aset - s/d {{ filters.year }}</h1>
      <a-row :gutter="16">
        <a-col :span="24" :md="8" v-for="(kondisi) in opt_kondisi" :key="kondisi" class="mb-4">
          <a-card bordered class="card-white !px-2 shadow-lg flex flex-col justify-between h-full"
            style="border-radius: 1rem !important;">
            <div class="relative">
              <div class=" w-full">
                <a-tag v-if="kondisi === 'BAIK'" color="#22c55e" class="w-full text-center !rounded-t-md">
                  BAIK
                </a-tag>
                <a-tag v-if="kondisi === 'RUSAK RINGAN'" color="#f97316" class="w-full text-center !rounded-t-md">
                  RUSAK RINGAN
                </a-tag>
                <a-tag v-if="kondisi === 'RUSAK BERAT'" color="#ef4444" class="w-full text-center !rounded-t-md">
                  RUSAK BERAT
                </a-tag>
              </div>
              <div :class="{
                '-mt-1 border-2 !rounded-b-md p-4 relative': true,
                'border-green-500 bg-green-50': kondisi === 'BAIK',
                'border-orange-500 bg-orange-50': kondisi === 'RUSAK RINGAN',
                'border-red-500 bg-red-50': kondisi === 'RUSAK BERAT'
              }">

                <a-form ref="form" name="info" :label-col="{ span: 9 }" :wrapper-col="{ span: 15 }">
                  <a-form-item label="Total Data" label-align="left" :colon="false" class="!mb-2 !px-1.5">
                    {{ data_kondisi[kondisi] ? data_kondisi[kondisi].total_item + ' Profil' : '-' }}
                  </a-form-item>
                  <a-form-item label="Total Nilai Buku" label-align="left" :colon="false" class="!mb-2 !px-1.5 break-all">
                    Rp.{{ idCurrency(data_kondisi[kondisi] ? data_kondisi[kondisi].total_nilai : 0) }}
                  </a-form-item>
                </a-form>
              </div>
            </div>
          </a-card>
        </a-col>
      </a-row>
    </div>
    <div v-if="filters.satker_id !== 53 && filters.satker_id !== 54" class="!mt-8">
      <h1 class="text-lg font-semibold mt-7">4. Operator Satker</h1>
      <a-row :gutter="16" v-if="data_operator.length > 0">
        <a-col :span="24" :sm="12" :md="6" :lg="4" :xl="3" v-for="(obj) in data_operator">
          <a-card bordered class="card-white !px-2 flex flex-col justify-between h-full shadow-lg"
            style="border-radius: 1rem !important;">
            <template #cover>
              <div class="p-3">
                <img :src="obj.photo" onerror="this.onerror=null;this.src='/public/images/img_not_available.png'"
                  class="w-full h-full object-contain mx-auto" />
              </div>
            </template>
            <strong class="text-md text-center block break-all">{{ obj.name }}</strong>
            <span class="text-md text-center block break-all">{{ obj.email }}</span>
          </a-card>
        </a-col>
      </a-row>
      <a-empty v-else />
    </div>
    <div v-if="filters.satker_id !== 53 && filters.satker_id !== 54" class="!my-8">
      <h1 class="text-lg font-semibold mt-7 ">5. Reviewer Satker</h1>
      <a-row :gutter="16" v-if="data_reviewer.length > 0">
        <a-col :span="24" :sm="12" :md="6" :lg="4" :xl="3" v-for="(obj) in data_reviewer">
          <a-card bordered class="card-white !px-2 flex flex-col justify-between h-full shadow-lg"
            style="border-radius: 1rem !important;">
            <template #cover>
              <div class="p-3">
                <img :src="obj.photo" onerror="this.onerror=null;this.src='/public/images/img_not_available.png'"
                  class="w-full object-contain mx-auto" />
              </div>
            </template>
            <strong class="text-md text-center block break-all">{{ obj.name }}</strong>
            <span class="text-md text-center block break-all">{{ obj.email }}</span>
          </a-card>
        </a-col>
      </a-row>
      <a-empty v-else />
    </div>
  </div>
</template>
