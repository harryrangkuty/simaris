<script>
export default {
  name: 'InventoryDataChange',
  props: {
    modelData: Object,
  }
}
</script>

<template>
  <a-card size="small"
    class="rounded-xl border border-slate-200/60 shadow-sm bg-white/80 backdrop-blur">

    <!-- HEADER -->
    <div class="flex items-center gap-2 mb-4">
      <Icon icon="mdi:history" class="text-lg text-indigo-600" />
      <h3 class="font-semibold text-slate-700">
        Riwayat Perubahan Data
      </h3>
    </div>

    <!-- CONTENT -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

      <!-- CREATED -->
      <div class="p-3 rounded-lg bg-green-50 border border-green-100 space-y-1">
        <div class="flex items-center gap-2">
          <Icon icon="mdi:plus-circle-outline" class="text-green-600" />
          <span class="text-xs uppercase text-green-700 font-semibold">
            Dibuat Oleh
          </span>
        </div>

        <div class="text-sm font-medium text-slate-800">
          {{ modelData.creator?.name || '- (Inisialisasi Awal Program)' }}
        </div>

        <div class="text-xs text-slate-500">
          {{ modelData.created_at ? filterDateTime(modelData.created_at) : '-' }}
        </div>
      </div>

      <!-- UPDATED -->
      <div class="p-3 rounded-lg bg-yellow-50 border border-yellow-100 space-y-1">
        <div class="flex items-center gap-2">
          <Icon icon="mdi:update" class="text-yellow-600" />
          <span class="text-xs uppercase text-yellow-700 font-semibold">
            Terakhir Diubah
          </span>
        </div>

        <div class="text-sm font-medium text-slate-800">
          {{ modelData.updater?.name || '-' }}
        </div>

        <div class="text-xs text-slate-500">
          {{ modelData.updated_at ? filterDateTime(modelData.updated_at) : '-' }}
        </div>
      </div>

    </div>
  </a-card>
</template>