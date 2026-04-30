<script>
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
      formPassword: {
        old_password: null,
        new_password: null,
        confirm_password: null,
      },
      formPhoto: {
        photo: null,
      },
    };
  },

  mounted() {
    this.readData();
  },

  computed: {
    breadcrumbItems() {
      return [
        { label: 'Dashboard', link: '/', icon: 'bi:grid' },
        { label: `${this.title}`, icon: 'streamline:user-profile-focus' }
      ]
    },
  },

  methods: {

    async readData() {
      const vm = this;
      vm.loadingTrue();

      const params = {
        req: "user_data"
      };

      try {
        const response = await vm.axios.get(vm.readRoute, { params });

        if (response && response.data) {
          vm.models = response.data.models;
          if (vm.models.photo) {
            vm.formPhoto.photo = vm.models.photo_object;
          } else {
            vm.formPhoto.photo = null;
          }
        }
      } catch (e) {
        vm.$onAjaxError(e);
      } finally {
        vm.loadingFalse();
      }
    },

    async updatePassword() {
      const vm = this;
      vm.loadingTrue();

      if (vm.formPassword.new_password !== vm.formPassword.confirm_password) {
        vm.openNotification("Konfirmasi password tidak cocok", "error");
        vm.loadingFalse();
        return;
      }

      const form = {
        req: 'change_password',
        ...vm.formPassword
      };
      const response = await vm.axios.post(vm.writeRoute, form).catch((e) => vm.$onAjaxError(e));
      if (response && response.data) {
        vm.openNotification("Password berhasil diubah", "success");
        vm.formPassword = vm.$options.data().formPassword;
      }
    },

    async updatePhoto() {
      const vm = this;
      vm.loadingTrue();

      if (!vm.formPhoto.photo || !vm.formPhoto.photo.originFileObj) {
        vm.openNotification("Tidak ada perubahan foto yang disimpan", "error");
        vm.loadingFalse();
        return;
      }

      const formData = new FormData();
      formData.append("photo", vm.formPhoto.photo.originFileObj);
      formData.append("req", "change_photo");

      try {
        const response = await vm.axios.post(vm.writeRoute, formData, {
          headers: { "Content-Type": "multipart/form-data" },
        });

        if (response && response.data) {
          vm.openNotification("Foto berhasil diperbarui", "success");
          setTimeout(() => {
            window.location.reload();
          }, 1000);
        } else {
          vm.openNotification(response.data.message || "Gagal update foto", "error");
        }
      } catch (error) {
        vm.openNotification(error.response?.data?.message || "Terjadi kesalahan", "error");
      } finally {
        vm.loadingFalse();
      }
    },
  },
};
</script>

<template>
  <Breadcrumb :items="breadcrumbItems" :showBackButton="true" />
  <a-row type="flex" justify="center" align="stretch" :gutter="[16, 16]">
    <!-- Card Ubah Password -->
    <a-col :xs="24" :md="12">
      <a-card class="w-full h-full">
        <h1 class="text-base font-semibold mb-4">Ubah Password</h1>
        <a-form layout="vertical" :model="formPassword">
          <a-form-item label="Password Lama" :rules="[{ required: true }]">
            <a-input-password v-model:value="formPassword.old_password" autocomplete="off" />
          </a-form-item>
          <a-form-item label="Password Baru" :rules="[{ required: true }]">
            <a-input-password v-model:value="formPassword.new_password" autocomplete="off" />
          </a-form-item>
          <a-form-item label="Konfirmasi Password" :rules="[{ required: true }]">
            <a-input-password v-model:value="formPassword.confirm_password" autocomplete="off" />
          </a-form-item>
          <div class="mt-auto">
            <a-button type="primary"
              class="bg-gradient-to-r from-purple-400 via-blue-500 to-blue-700 hover:from-yellow-400 hover:via-yellow-600 hover:to-purple-500 flex items-center justify-center text-white font-medium border-0 shadow-md transition-all duration-300"
              @click="updatePassword">
              <Icon icon="line-md:plus" class="mr-1" />
              Simpan Password
            </a-button>
          </div>
        </a-form>
      </a-card>
    </a-col>

    <!-- Card Ubah Foto Profil -->
    <a-col :xs="24" :md="12">
      <a-card class="w-full h-full">
        <h1 class="text-base font-semibold mb-4">Ubah Foto Profil</h1>
        <a-form layout="vertical" class="w-full">
          <a-form-item label="Foto Profil">
            <file-upload v-model:value="formPhoto.photo" accept=".jpg,.jpeg,.png,.gif,.bmp,.webp" />
          </a-form-item>
          <div class="mt-auto">
            <a-button type="primary"
              class="bg-gradient-to-r from-purple-400 via-blue-500 to-blue-700 hover:from-yellow-400 hover:via-yellow-600 hover:to-purple-500 flex items-center justify-center text-white font-medium border-0 shadow-md transition-all duration-300"
              @click="updatePhoto">
              <Icon icon="line-md:plus" class="mr-1" />
              Update Foto Profil
            </a-button>
          </div>
        </a-form>
      </a-card>
    </a-col>
  </a-row>

</template>