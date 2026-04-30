<script>
import { theme } from 'ant-design-vue'
import { QrcodeStream } from 'vue-qrcode-reader'

export default {
  components: {
    QrcodeStream
  },
  props: {
    menu: {
      type: Object,
      default: null
    }
  },

  data() {
    return {
      collapsed: false,
      drawerVisible: false,
      openKeys: [],
      selectedKeys: [],
      isMobile: window.innerWidth <= 768,
      notifications: [],
      isDark: false,
      showScanTypeModal: false,
      scanTarget: null,
      showScanModal: false,
      error: null,
      hasCamera: true,
      scanPreparing: false,
      scanned: false,
      result: null,

      darkAlgorithm: theme.darkAlgorithm,
      defaultAlgorithm: theme.defaultAlgorithm
    };
  },

  watch: {
    collapsed(value) {
      this.setLocalStorage('sidebar-collapsed', value)
    },
    showScanModal(val) {
      if (!val) {
        this.error = null
        this.hasCamera = true
        this.scanned = false
      }
    }
  },

  created() {
    this.openKeys = [this.route.substr(1)]
    this.selectedKeys = [this.fullRoute]

    if (this.isMobile) {
      this.collapsed = true // mobile default collapsed
    } else {
      this.collapsed = this.getLocalStorage('sidebar-collapsed', false)
    }

    const darkSaved = localStorage.getItem("darkmode")
    this.isDark = darkSaved === "true"
  },

  mounted() {
    window.addEventListener("resize", this.handleResize)
  },

  beforeUnmount() {
    window.removeEventListener("resize", this.handleResize)
  },

  computed: {
    antTheme() {
      const light = {
        colorPrimary: '#581c87',
        colorBgLayout: '#ddd6fe',
      }

      const dark = {
        colorPrimary: '#7C3AED',
        colorBgBase: '#1a1528',
        colorTextBase: '#f5f3ff',
        colorBorder: '#4338CA'
      }

      // Components
      const lightComponents = {
        Card: {
          colorBorderSecondary: 'transparent',
        },
        Modal: {
          colorBorder: 'transparent'
        }
      }

      const darkComponents = {
        Card: {
          colorBorderSecondary: '#7C3AED',
        },
        Modal: {
          colorBorder: '#4338CA'
        }
      }

      return {
        algorithm: this.isDark ? this.darkAlgorithm : this.defaultAlgorithm,
        token: this.isDark ? dark : light,
        components: this.isDark ? darkComponents : lightComponents
      }
    },

    headerStyle() {
      return {
        background: this.isDark
          ? `linear-gradient(90deg, #0b132b, #3a0ca3, #240046)`
          : `linear-gradient(90deg, #3b82f6 , #5145cd, #22023a)`,
        color: '#fff',
        transition: 'background 0.3s ease'
      }
    },

    footerStyle() {
      return {
        backgroundImage: this.isDark
          ? `linear-gradient(
            90deg,
            rgba(36, 0, 70, 0) 0%,
            rgba(58, 12, 163, 0.65) 33%,
            rgba(11, 19, 43, 0.5) 66%,
            rgba(36, 0, 70, 0) 100%
          )`
          : `linear-gradient(
            90deg,
              rgba(135, 150, 210, 0) 0%,
              rgba(135, 150, 210, 0.6) 33%,
              rgba(135, 150, 210, 0.3) 66%,
              rgba(135, 150, 210, 0) 100%
          )`,
        color: this.isDark ? '#f8fafc' : '#374151',
        transition: 'all 0.3s ease'
      }
    }
  },

  methods: {

    handleResize() {
      this.isMobile = window.innerWidth <= 768
      if (this.isMobile) {
        this.collapsed = true
      }
    },

    accordion() {
      if (this.openKeys.length > 1)
        this.openKeys.shift()
    },

    toggleLogoClick() {
      if (this.isMobile) {
        this.drawerVisible = true
      } else {
        this.collapsed = !this.collapsed
      }
    },

    async switchRole(id) {
      const vm = this;
      vm.loadingTrue()

      if (vm.user.active_role_id === id) return;

      const params = {
        id: vm.user.id,
        req: 'switch_role',
        role_id: id
      };

      const response = await vm.axios.post(`/user-profile/write`, params).catch((e) => vm.$onAjaxError(e))
      if (response && response.data) {
        vm.openNotification('Berhasil mengubah Role, refreshing page...', 'success');
        setTimeout(() => {
          window.location.replace('/dashboard')
        }, 500)
        vm.showModal = false
      }
    },

    handleCancel() {
      if (!this.user.fiscal_year) {
        this.$message.warning('Fiscal Year harus diisi terlebih dahulu!')
        this.showModal = true
        return
      }
      this.showModal = false
    },

    disableYear(date) {
      const year = date.year()
      if (!Array.isArray(this.tahun_perolehan_aktif)) {
        this.tahun_perolehan_aktif = [new Date().getFullYear()]
      }
      return !this.tahun_perolehan_aktif.includes(year)
    },

    toggleDark() {
      this.isDark = !this.isDark
      localStorage.setItem("darkmode", this.isDark ? "true" : "false")
    },

    selectScanTarget(type) {
      this.scanTarget = type
      this.showScanTypeModal = false

      this.scanned = false

      setTimeout(() => {
        this.openScanModal()
      }, 250)
    },

    // =========================
    // SCAN QRCODE (FINAL)
    // =========================
    async openScanModal() {
      // browser tidak support
      if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        this.openNotification('Perangkat ini tidak mendukung kamera', 'error')
        return
      }

      try {
        this.scanPreparing = true

        // cek device kamera
        const devices = await navigator.mediaDevices.enumerateDevices()
        const hasVideoInput = devices.some(d => d.kind === 'videoinput')

        if (!hasVideoInput) {
          this.scanPreparing = false
          this.openNotification('Perangkat ini tidak ada kamera', 'error')
          return
        }

        await this.$nextTick()

        // request permission kamera
        const stream = await navigator.mediaDevices.getUserMedia({
          video: { facingMode: 'environment' }
        })

        stream.getTracks().forEach(track => track.stop())

        this.scanPreparing = false
        this.showScanModal = true

      } catch (err) {
        this.scanPreparing = false

        if (err.name === 'NotAllowedError') {
          this.openNotification('Akses kamera ditolak', 'error')
        } else {
          this.openNotification('Tidak dapat mengakses kamera', 'error')
        }
      }
    },

    onDetect(detectedCodes) {
      if (this.scanned) return
      if (!detectedCodes || !detectedCodes.length) return

      this.scanned = true

      const result = detectedCodes[0].rawValue

      const allowedTargets = [
        'iis-inventaris',
        'inventaris',
        'iis-alkes',
        'alkes'
      ]

      if (!allowedTargets.includes(this.scanTarget)) {
        this.openNotification('Target scan tidak valid', 'error')
        this.scanned = false
        return
      }

      const url =
        `/scan/resolve?target=${this.scanTarget}&code=${encodeURIComponent(result)}`

      this.showScanModal = false

      this.openNotification('Scan Berhasil!! Mengalihkan ke halaman detail...', 'success')

      setTimeout(() => {
        window.location.href = url
      }, 1000)
    },


    paintBoundingBox(detectedCodes, ctx) {
      for (const detectedCode of detectedCodes) {
        const { x, y, width, height } = detectedCode.boundingBox

        ctx.lineWidth = 3
        ctx.strokeStyle = 'red'
        ctx.strokeRect(x, y, width, height)
      }
    },

    onError(err) {
      this.error = err.message
      console.error(err)
    }

  }
};
</script>
<template>
  <a-config-provider :theme="antTheme">
    <a-layout class="min-h-screen h-full">
      <!-- Menu Desktop -->
      <a-layout-sider v-if="!isMobile" v-model:collapsed="collapsed" :trigger="null" :theme="isDark ? 'dark' : 'light'" width="250"
        collapsed-width="65" class="shadow-md overflow-auto h-screen !fixed left-0 top-0 bottom-0" collapsible>
        <a class="flex flex-col mt-4 mb-1 cursor-pointer" @click.prevent="toggleLogoClick">
          <div class="flex flex-col items-center relative">
            <div v-if="!collapsed && !isDark">
              <!-- Big subtle background bubbles -->
              <div
                class="absolute -top-10 -left-10 w-32 h-32 bg-gradient-to-tr from-yellow-200 via-blue-200 to-purple-500 opacity-40 rounded-full animate-pulse-slow blur-md">
              </div>
              <div
                class="absolute -top-6 -left-6 w-48 h-48 bg-gradient-to-tr from-yellow-200 via-blue-200 to-purple-100 opacity-15 rounded-full animate-pulse-slow blur-md">
              </div>
              <div
                class="absolute -top-8 -right-8 w-32 h-32 bg-gradient-to-br from-yellow-200 via-red-500 to-purple-500 opacity-15 rounded-full animate-pulse-slow blur-md">
              </div>

              <!-- Medium bubbles -->
              <div
                class="absolute -top-4 left-20 w-16 h-16 bg-gradient-to-tr from-blue-200 via-purple-300 to-pink-300 opacity-30 rounded-full animate-pulse-slow blur-md">
              </div>

              <!-- Small bubbles / “dots” -->
              <div
                class="absolute top-5 left-10 w-6 h-6 bg-gradient-to-tr from-purple-200 via-blue-200 to-pink-300 opacity-35 rounded-full animate-pulse-slow">
              </div>
              <div
                class="absolute bottom-5 right-15 w-10 h-10 bg-gradient-to-br from-pink-200 via-red-300 to-purple-400 opacity-40 rounded-full animate-pulse-slow">
              </div>
            </div>

            <div class="relative inline-block p-2">
              <template v-if="!collapsed">
                <span
                  class="absolute top-0 left-0 w-4 h-4 border-t-2 border-l-2 border-purple-400 rounded-tl-md transition duration-300"></span>
                <span
                  class="absolute top-0 right-0 w-4 h-4 border-t-2 border-r-2 border-purple-400 rounded-tr-md transition duration-300"></span>
                <span
                  class="absolute bottom-0 left-0 w-4 h-4 border-b-2 border-l-2 border-purple-400 rounded-bl-md transition duration-300"></span>
                <span
                  class="absolute bottom-0 right-0 w-4 h-4 border-b-2 border-r-2 border-purple-400 rounded-br-md transition duration-300"></span>
              </template>
              <img :src="publicPath('images/main-app-logo.webp')" :class="[
                'transition-all duration-500 ease-in-out',
                collapsed
                  ? ''
                  : 'h-16 w-16'
              ]" />
            </div>
            <div v-if="!collapsed" class="mt-2 text-center">
              <h1
                class="text-2xl font-extrabold bg-gradient-to-r from-purple-700 via-purple-500 to-pink-500 bg-clip-text text-transparent tracking-wide drop-shadow-sm">
                SIMARIS
              </h1>
              <p class="text-base font-medium uppercase tracking-wider">
                <span class="text-purple-400">RSU </span>
                <span class="text-yellow-500 font-extrabold">Bunda Thamrin</span>
              </p>
            </div>
          </div>
          <div v-if="!collapsed"
            class="mt-2 h-[1.5px] bg-gradient-to-r from-yellow-500/70 via-yellow-300/80 to-transparent">
          </div>
        </a>
        <AppMenu :theme="isDark ? 'dark' : 'light'" :menu="menu" v-model:selectedKeys="selectedKeys"
          v-model:openKeys="openKeys" :collapsed="collapsed" :accordion="accordion" />
        <div class="p-3 text-center text-xs font-bold text-purple-700 border-t border-purple-200">
          versi 1.0
        </div>
      </a-layout-sider>
      <!-- Menu Mobile -->
      <a-drawer v-model:open="drawerVisible" placement="left" :width="250" :closable="false"
        :body-style="{ padding: 0 }" :class="isDark ? 'a-drawer-dark' : 'a-drawer-light'">
        <template #title>
          <div class="flex items-center justify-between">
            <span class="font-bold text-yellow-500">📂 Menu Aplikasi</span>
            <button @click="drawerVisible = false" class="p-2 rounded-full hover:bg-purple-200 transition">
              <Icon icon="bi:x-lg" class="text-xl text-purple-900" />
            </button>
          </div>
        </template>
        <AppMenu :theme="isDark ? 'dark' : 'light'" :menu="menu" v-model:selectedKeys="selectedKeys"
          v-model:openKeys="openKeys" :collapsed="collapsed" :accordion="accordion" />
        <template #footer>
          <div class="text-center font-bold text-purple-700">versi 1.0</div>
        </template>
      </a-drawer>
      <a-layout :class="[
        isMobile
          ? 'ml-0'
          : (collapsed ? 'ml-[65px]' : 'ml-[250px]'),
        'transition-all duration-400 ease-in-out'
      ]">
        <a-layout-header :style="headerStyle"
          class="!px-2 md:!px-10 !h-[52px] !text-white sticky top-0 z-50 flex gap-x-2 justify-between items-center shadow-lg lg:ml-1.5 lg:rounded-bl-md mb-1.5 lg:mb-2 relative">
          <div class="flex items-center justify-between w-full">
            <div class="flex md:gap-x-6 items-center">
              <Icon :icon="collapsed ? 'line-md:menu-fold-right' : 'line-md:menu-fold-left'"
                @click.prevent="toggleLogoClick" class="!z-10 text-2xl cursor-pointer" />
              <a-tag v-if="!isMobile" @click="toggleDark"
                class="cursor-pointer from-blue-500 to-blue-800 !bg-gradient-to-r hover:!bg-yellow-500 hover:!bg-none !rounded-full !text-white !text-xs sm:!text-sm !px-4 !py-0.5 hover:scale-110">
                <span class="hidden sm:inline-block">{{ isDark ? 'Dark Mode' : 'Light Mode' }}</span>
              </a-tag>
            </div>
            <div class="flex items-center min-w-0 gap-x-4">
              <a class="relative cursor-pointer" @click="toggleDark">
                <Icon :icon="isDark ? 'line-md:sunny-twotone-loop' : 'line-md:moon-twotone'"
                  class="text-2xl text-white hover:scale-110 transition duration-300" />
              </a>
              <a-dropdown>
                <a class="relative cursor-pointer ant-dropdown-link">
                  <Icon icon="line-md:bell-twotone-loop" class="text-2xl text-white hover:scale-110 transition" />
                  <span
                    class="absolute -top-1 -right-1 w-3 h-3 bg-red-500 rounded-full border-2 border-white animate-pulse"></span>
                </a>
                <template #overlay>
                  <a-menu class="w-56">
                    <div class="px-4 py-2 border-b border-gray-200 flex justify-between items-center">
                      <span class="font-semibold text-gray-800">Notifikasi</span>
                      <a class="text-xs text-blue-600 hover:underline cursor-pointer">Tandai semua dibaca</a>
                    </div>

                    <a-menu-item v-if="notifications.length === 0" class="text-center text-gray-400 py-6">
                      Tidak ada notifikasi baru
                    </a-menu-item>

                    <a-menu-item v-for="(notif, i) in notifications" :key="i" class="!p-0 hover:!bg-gray-50">
                      <div class="flex items-start px-4 py-3 gap-x-3 cursor-pointer">
                        <Icon :icon="notif.icon" class="text-lg text-purple-600 mt-0.5" />
                        <div class="flex-1">
                          <div class="text-sm font-semibold text-gray-800">{{ notif.title }}</div>
                          <div class="text-xs text-gray-500">{{ notif.message }}</div>
                          <div class="text-[11px] text-gray-400 mt-0.5">{{ notif.time }}</div>
                        </div>
                        <span v-if="!notif.read" class="w-2.5 h-2.5 bg-blue-500 rounded-full mt-1"></span>
                      </div>
                    </a-menu-item>
                  </a-menu>
                </template>
              </a-dropdown>
              <a-dropdown>
                <a class="ant-dropdown-link">
                  <div v-if="!isMobile" class=" flex items-center">
                    <img :src="user.photo ? '/storage/' + user.photo : '/images/user-icon.webp'"
                      class="h-10 w-10 rounded-full mr-2.5 object-cover object-center ring-2 ring-white/30 hover:ring-white/60 transition" />
                    <div class="flex flex-wrap items-center gap-x-1">
                      <span
                        class="md:text-lg text-base text-white font-semibold whitespace-nowrap overflow-hidden text-ellipsis">
                        {{ user.name }}
                      </span>
                      <span class="text-xs text-white bg-white/20 px-2 py-0.5 rounded-full font-medium">
                        {{ user.position }} - {{ user.division }}
                      </span>
                    </div>
                  </div>
                  <div v-else>
                    <div
                      class="flex items-center gap-x-2 px-2 py-1 rounded-lg hover:bg-white/20 transition-all duration-300 max-w-[150px]">

                      <span class="flex-1 min-w-0 text-sm text-white truncate font-medium tracking-wide">
                        {{ user.name }}
                      </span>

                      <img :src="user.photo ? '/storage/' + user.photo : '/images/user-icon.webp'"
                        class="h-9 w-9 flex-shrink-0 rounded-full object-cover object-center ring-2 ring-white/30 hover:ring-white/60 transition" />

                    </div>
                  </div>
                </a>
                <template #overlay>
                  <a-menu class="w-80">
                    <div class="px-3 py-4 bg-violet-200 text-center border-b border-gray-200">
                      <img :src="user.photo ? '/storage/' + user.photo : '/images/user-icon.webp'"
                        class="h-16 w-16 mx-auto rounded-full object-cover object-center ring-2 ring-purple-300" />
                      <div class="mt-2">
                        <div class="text-sm font-semibold text-gray-800 truncate">
                          {{ user.name }}
                        </div>
                        <a-tag color="blue">
                          {{ user.position }} - {{ user.division }}
                        </a-tag>
                      </div>
                    </div>
                    <a-menu-item key="user-profile">
                      <a href="/user-profile" class="flex items-center font-medium">
                        <Icon icon="line-md:account" class="mr-2 text-blue-500" />
                        Profil Saya
                      </a>
                    </a-menu-item>
                    <a-menu-item key="logout">
                      <a href="/logout" class="flex items-center font-medium text-red-500 hover:text-red-600">
                        <Icon icon="ant-design:logout-outlined" class="mr-2 text-red-500" />
                        Logout
                      </a>
                    </a-menu-item>
                  </a-menu>
                </template>
              </a-dropdown>
            </div>
          </div>
        </a-layout-header>
        <a-layout-content class="px-1 lg:px-2">
          <slot></slot>
        </a-layout-content>
        <a-layout-footer class="!p-0 !bg-transparent">
          <div class="w-full text-center text-sm md:text-base" :style="footerStyle">
            <div class="px-3 py-2 md:py-2.5 flex flex-col md:flex-row justify-center items-center"
              :class="isDark ? 'text-slate-100' : 'text-gray-700'">
              <span class="font-semibold">
                SIMARIS ©{{ currentYear }}
              </span>
              <span class="hidden md:inline mx-2">•</span>
              <span class="flex flex-wrap justify-center items-center gap-x-1">
                <span>Developed by</span>
                <span class="font-semibold">Harry Rangkuti</span>
                <span>&</span>
                <span class="font-semibold">Rafli Zocky</span>
              </span>
            </div>
          </div>
        </a-layout-footer>
      </a-layout>
    </a-layout>

    <!-- Sticky Scan QR -->
    <a-tooltip title="Scan QR Code">
      <button @click="showScanTypeModal = true" class="p-2.5 fixed bottom-4 right-4 z-50 bg-yellow-500 backdrop-blur-md shadow-lg
         rounded-full hover:scale-105 hover:shadow-2xl transition">
        <Icon icon="streamline-sharp:qr-code-solid" class="text-5xl cursor-pointer" />
      </button>
    </a-tooltip>

    <!-- MODAL PILIH JENIS SCAN -->
    <a-modal v-model:open="showScanTypeModal" title="Pilih Jenis Scan" :footer="null">
      <div class="grid grid-cols-2 gap-4 mt-4 mb-4">

        <!-- Inventaris IIS -->
        <div class="flex flex-col items-center p-4 rounded-lg cursor-pointer
             transition transform hover:scale-105 hover:shadow-xl" @click="selectScanTarget('iis-inventaris')"
          style="background: linear-gradient(135deg, #4F46E5, #A78BFA); color: white;">
          <div class="w-28 h-28 border-2 border-white rounded-lg
                  flex flex-col items-center justify-center">
            <Icon icon="mdi:warehouse" class="text-4xl mb-1" />
            <span class="text-xs font-bold">IIS</span>
          </div>
          <span class="mt-3 font-semibold text-center">
            Inventaris<br />(IIS)
          </span>
        </div>

        <!-- Inventaris -->
        <div class="flex flex-col items-center p-4 rounded-lg cursor-pointer
             transition transform hover:scale-105 hover:shadow-xl" @click="selectScanTarget('inventaris')"
          style="background: linear-gradient(135deg, #2563EB, #60A5FA); color: white;">
          <div class="w-28 h-28 border-2 border-white rounded-lg
                  flex flex-col items-center justify-center">
            <Icon icon="mdi:clipboard-text" class="text-4xl mb-1" />
            <span class="text-xs font-bold">NON IIS</span>
          </div>
          <span class="mt-3 font-semibold text-center">
            Inventaris
          </span>
        </div>

        <!-- Alkes IIS -->
        <div class="flex flex-col items-center p-4 rounded-lg cursor-pointer
             transition transform hover:scale-105 hover:shadow-xl" @click="selectScanTarget('iis-alkes')"
          style="background: linear-gradient(135deg, #16A34A, #4ADE80); color: white;">
          <div class="w-28 h-28 border-2 border-white rounded-lg
                  flex flex-col items-center justify-center">
            <Icon icon="mdi:medical-bag" class="text-4xl mb-1" />
            <span class="text-xs font-bold">IIS</span>
          </div>
          <span class="mt-3 font-semibold text-center">
            Alkes<br />(IIS)
          </span>
        </div>

        <!-- Alkes -->
        <div class="flex flex-col items-center p-4 rounded-lg cursor-pointer
             transition transform hover:scale-105 hover:shadow-xl" @click="selectScanTarget('alkes')"
          style="background: linear-gradient(135deg, #0D9488, #5EEAD4); color: white;">
          <div class="w-28 h-28 border-2 border-white rounded-lg
                  flex flex-col items-center justify-center">
            <Icon icon="mdi:heart-pulse" class="text-4xl mb-1" />
            <span class="text-xs font-bold">NON IIS</span>
          </div>
          <span class="mt-3 font-semibold text-center">
            Alkes
          </span>
        </div>

      </div>
    </a-modal>

    <!-- MODAL SCANNER -->
    <a-modal v-model:open="showScanModal" title="Scan Barcode / QR Code" :footer="null" width="100%"
      :destroyOnClose="true">
      <div class="scanner-container">
        <qrcode-stream :constraints="{ facingMode: 'environment' }" :track="paintBoundingBox" @detect="onDetect"
          @camera-on="onCameraReady" @error="onError" />
      </div>
      <p v-if="error" class="text-center text-red-500 mt-4">
        {{ error }}
      </p>
    </a-modal>
    <a-modal :open="scanPreparing" :footer="null" :closable="false" centered>
      <div class="flex flex-col items-center justify-center py-10">
        <a-spin size="large" />
        <p class="mt-4 font-medium text-gray-600">
          Menyiapkan kamera...
        </p>
      </div>
    </a-modal>

  </a-config-provider>
</template>

<style scoped>
::-webkit-scrollbar {
  display: none;
}

@keyframes zoomIn {
  0% {
    transform: scale(0.5);
    opacity: 0;
  }

  100% {
    transform: scale(1);
    opacity: 1;
  }
}

.icon-animate {
  animation: zoomIn 1s ease-out forwards;
}

:deep(.ant-dropdown-menu-submenu-title) {
  display: flex !important;
  align-items: center !important;
}

.scanner-container {
  width: 100%;
  max-width: 420px;
  height: 420px;
  margin: 0 auto;
  overflow: hidden;
  border-radius: 12px;
}
</style>