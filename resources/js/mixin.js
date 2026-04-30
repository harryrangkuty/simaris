import { Auth } from "@/services/auth";
import axios from 'axios';
import dayjs from 'dayjs';
import lodash from 'lodash';
import accounting, { toFixed } from "accounting";

dayjs.locale('id');

export default {

  data() {
    return {
      Auth,
      axios,
      lodash,
      dayjs,
      host: window._HOST,
      date_format: "YYYY-MM-DD",
      date_range: [],
      year_format: "YYYY",
      validation: null,
      filter: {
        date_start: null,
        date_end: null,
        search: null
      },
      models: [],
      _pagination: {
        current: 1,
        showSizeChanger: true,
        pageSize: 10,
      },
      showModal: false,
      upload_headers: {
        _token: document.querySelector('meta[name="csrf-token"]').content
      },
      APP_NAME: window.APP_NAME,
      loading_status: false,
    };
  },

  computed: {
    user() {
      return this.Auth.user;
    },
    fullRoute() {
      return (window.location.pathname + window.location.search).substr(1)
    },
    route() {
      return window.location.pathname;
    },
    readRoute() {
      return window.location.pathname + '/read';
    },
    writeRoute() {
      return window.location.pathname + '/write';
    },
    loadingStatus() {
      return this.loading_status;
    },
    isDesktop() {
      return window.innerWidth > 1024;
    },
    currentYear() {
      return this.dayjs().year();
    }
  },

  watch: {
    //WATCHER
  },

  methods: {
    can(permission) {
      return this.user?.permissions?.includes(permission);
    },

    filterDateRange(val) {
      if (val && Array.isArray(val) && val.length === 2) {
        this.filter.date_start = dayjs(val[0]).format('YYYY-MM-DD');
        this.filter.date_end = dayjs(val[1]).format('YYYY-MM-DD');
      } else {
        this.filter.date_start = null;
        this.filter.date_end = null;
      }
    },

    filterDate(date) {
      return dayjs(date).format('YYYY-MM-DD');
    },

    filterDates(filter, array) {
      filter.date_start = array ? dayjs(array[0]).format('YYYY-MM-DD') : null;
      filter.date_end = array ? dayjs(array[1]).format('YYYY-MM-DD') : null;
      return filter;
    },

    filterDateTime(date) {
      return dayjs(date).format('DD MMM YYYY HH:mm');
    },

    filterDatesUnix(filter, array) {
      filter.date_start = array ? dayjs(array[0]).unix() : null;
      filter.date_end = array ? dayjs(array[1]).unix() : null;
      return filter;
    },

    currencyFormatter(value) {
      return `${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, '.')
    },

    currencyParser(value) {
      return value.replace(/[.]/g, '')
    },

    idCurrency(bill) {
      if (bill == null) return '';
      return accounting.format(bill, 2, '.', ',');
    },

    numRound(bill) {
      return bill.toFixed(0);
    },

    publicPath(url = null) {
      return window._HOST + '/' + (url ? url : '');
    },

    assetUrl(url) {
      return window.ASSET_URL.replace(/^\/+/, '').replace(/\/+$/, '') + '/' + url.replace(/^\/+/, '').replace(/\/+$/, '');
    },

    fb() {
      let len = arguments.length;
      for (var i = 0; i < len; i++) {
        if (arguments[i] !== null && arguments[i] !== undefined) {
          return arguments[i];
        }
      }
      return '';
    },

    getLocalStorage(key, _default = null) {
      const val = localStorage.getItem(key);
      return val ? JSON.parse(val) : _default;
    },

    setLocalStorage(key, val) {
      localStorage.setItem(key, JSON.stringify(val));
    },

    handleTableChange(pagination, filters, sorter) {
      const pager = { ...this._pagination };
      pager.current = pagination.current;
      pager.pageSize = pagination.pageSize;
      this._pagination = pager;
      this.readData({
        results: pagination.pageSize,
        page: pagination.current,
        sort_field: sorter.field,
        sort_order: sorter.order,
        ...filters
      });
    },

    resetValidation() {
      // Hapus error dari elemen input biasa
      document
        .querySelectorAll('.ant-input-status-error')
        .forEach((el) => {
          el.classList.remove('ant-input-status-error');
          el.removeAttribute('status');
        });

      // Hapus error dari elemen ant-select
      document
        .querySelectorAll('.ant-select-status-error')
        .forEach((el) => {
          el.classList.remove('ant-select-status-error');
        });

      // Hapus error dari elemen ant-picker
      document
        .querySelectorAll('.ant-picker-status-error')
        .forEach((el) => {
          el.classList.remove('ant-picker-status-error');
        });

      this.validation = null;
    },


    checkValidation(arr, id = '') {
      const vm = this;
      vm.resetValidation();
      for (let key in arr) {
        let el = document.querySelectorAll('[data-column="' + key + (id ? '_' + id : '') + '"]');
        if (el) {
          el.forEach((child) => {

            // Merah input : validasi a-input
            const inputElement = child.querySelector('input');
            if (inputElement) {
              inputElement.setAttribute('status', 'error');
              inputElement.classList.add('ant-input-status-error');
            }

            // Merah select : validasi a-select
            const selectElement = child.querySelector('.ant-select');
            if (selectElement) {
              selectElement.classList.add('ant-select-status-error');
            }

            // Merah datepicker : validasi a-date-picker
            const pickerElement = child.querySelector('.ant-picker');
            if (pickerElement) {
              pickerElement.classList.add('ant-picker-status-error');
            }

            // Tambahkan error message
            const controlElement = child.querySelector('.ant-form-item-control');
            if (controlElement) {
              // Hapus existing error
              controlElement.querySelectorAll('.error-text').forEach(e => e.remove());

              const errorDiv = document.createElement('div');
              errorDiv.className = 'error-text text-xs text-red-500 mt-1';
              errorDiv.innerText = arr[key];
              controlElement.appendChild(errorDiv);

              // Tambahkan auto-reset error saat user input
              const input = child.querySelector('input');
              const select = child.querySelector('.ant-select');
              const picker = child.querySelector('.ant-picker');

              if (input) {
                input.addEventListener('input', function () {
                  input.classList.remove('ant-input-status-error');
                  controlElement.querySelectorAll('.error-text').forEach(e => e.remove());
                }, { once: true });
              }

              if (select) {
                select.addEventListener('click', function () {
                  select.classList.remove('ant-select-status-error');
                  controlElement.querySelectorAll('.error-text').forEach(e => e.remove());
                }, { once: true });
              }

              if (picker) {
                picker.addEventListener('click', function () {
                  picker.classList.remove('ant-picker-status-error');
                  controlElement.querySelectorAll('.error-text').forEach(e => e.remove());
                }, { once: true });
              }
            }
          });
        }
      }
    },

    $onAjaxError(error, id) {
      const vm = this;
      let description = '';

      console.log(error)

      vm.loadingFalse();
      if (error.response && error.response.status == 422 && error.response.data.field) {
        vm.openNotification(`Error ${error.response.status}: ${error.response.data.text}`, 'error');
        const validation = {};
        const field = error.response.data.field;
        if (Array.isArray(field)) {
          for (let f in field) {
            validation[field[f]] = true;
          }
        } else {
          validation[error.response.data.field] = true;
        }

        vm.checkValidation(validation, id);
        throw new Error(validation);
      }
      if (error.response && error.response.status == 422) {
        const validation = {};

        for (let err in error.response.data.errors) {
          validation[err] = error.response.data.errors[err][0];
        }
        vm.validation = validation;
        vm.checkValidation(validation, id);
        vm.openNotification(
          `Error ${error.response.status}: ${error.response.data.message}`,
          'error'
        );
        throw new Error(validation);
      }
      if (error.response && error.response.data) {
        description = error.response.data;
        if (typeof description == 'object') {
          description = JSON.stringify(error);
        }

        vm.openNotification(
          description,
          'error'
        );
      }
    },

    openNotification(description, type = 'info') {
      let config = { description: description, placement: 'topRight' };

      switch (type) {
        case 'info':
          config = { ...config, message: 'Informasi' /* class: 'ant-notif-info' */ };
          break;
        case 'warning':
          config = { ...config, message: 'Peringatan' /* class: 'ant-notif-warning' */ };
          break;
        case 'success':
          config = { ...config, message: 'Berhasil' /* class: 'ant-notif-success' */ };
          break;
        case 'error':
          config = { ...config, message: 'Terjadi Kesalahan' /* class: 'ant-notif-error' */ };
          break;
      }

      this.$notification[type](config);
    },

    loadingTrue() {
      this.loading_status = true;
    },

    loadingFalse() {
      this.loading_status = false;
    },

    getUrlParameterByName(name, url) {
      if (!url) {
        url = window.location.href;
      }
      name = name.replace(/[[\]]/g, '\\$&');
      var regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)'),
        results = regex.exec(url);
      if (!results) return null;
      if (!results[2]) return '';
      return decodeURIComponent(results[2].replace(/\+/g, ' '));
    },

    labelKondisi(val, tags = true) {
      switch (val) {
        case 1:
          return tags ? "BAIK" : "BAIK";
        case 2:
          return tags ? "RUSAK RINGAN" : "RUSAK RINGAN";
        case 3:
          return tags ? "RUSAK BERAT" : "RUSAK BERAT";
        default:
          return "";
      }
    },
  }
}
