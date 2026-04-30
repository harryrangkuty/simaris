<script>
  export default {
    props: {
      constant: {
          type: Object,
          default: () => ({}),
      },
    },
    data() {
      return {};
    },

    methods: {}
  };
</script>

<template>
  <a-card class="card">
    <a-tabs>
      <a-tab-pane key="1" tab="Rekapitulasi menyeluruh">
        <monitoring :constant="constant"/>
      </a-tab-pane>
      <a-tab-pane key="2" tab="Rekapitulasi per Satker">
        <rekap-per-satker :constant="constant"/>
      </a-tab-pane>
    </a-tabs>
  </a-card>
</template>
