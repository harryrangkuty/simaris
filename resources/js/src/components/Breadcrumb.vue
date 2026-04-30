<script>
export default {
  props: {
    items: {
      type: Array,
      default: () => [],
    },
    showBackButton: {
      type: Boolean,
      default: true
    }
  },
  computed: {
    isMobile() {
      return window.innerWidth <= 768;
    }
  },
  methods: {
    goBack() {
      if (window.history.length > 1) {
        window.history.back();
      } else {
        window.location.href = '/';
      }
    }
  }
}
</script>

<template>
  <div v-if="!isMobile"
    class="relative mb-2 flex items-center justify-between px-10 py-1.5 rounded-b-lg shadow-sm bg-white overflow-hidden">
    <a-breadcrumb class="gap-x-3" separator="/">
      <a-breadcrumb-item v-for="(item, idx) in items" :key="idx" class="!flex items-center">
        <template v-if="item.link">
          <a :href="item.link" class="!flex items-center gap-1 !text-purple-600 font-medium">
            <Icon :icon="item.icon" class="text-lg mr-1" />
            <span>{{ item.label }}</span>
          </a>
        </template>
        <template v-else>
          <span class="!flex items-center gap-1 text-blue-600 font-semibold">
            <Icon :icon="item.icon" class="text-lg mr-1 text-blue-600" />
            <span>{{ item.label }}</span>
          </span>
        </template>
      </a-breadcrumb-item>
    </a-breadcrumb>
    <button @click="goBack" class="flex items-center gap-2 px-4 py-0.5 rounded-lg 
         bg-gradient-to-r from-blue-500 via-indigo-600 to-purple-600 
         text-white font-medium shadow-md 
         hover:from-blue-600 hover:via-indigo-700 hover:to-purple-700 
         transition-all duration-300 transform hover:-translate-y-0.5 hover:scale-105">
      <Icon icon="line-md:backup-restore" class="text-lg" />
      Kembali
    </button>

  </div>

</template>