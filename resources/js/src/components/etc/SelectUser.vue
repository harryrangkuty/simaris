<template>
    <a-select v-model="model" :options="optDefault" show-search @search="searchData" :show-arrow="false"
        :filter-option="false" :allow-clear="true" :getPopupContainer="(trigger) => trigger.parentNode">
    </a-select>
</template>
  
<script>
export default {
    props: ['modelValue', 'options'],

    emits: ['update:modelValue'],

    data() {
        return {
            optDefault: [],
            model: null
        };
    },

    created() {
        if (this.options) {
            this.optDefault = [{ value: this.options.id, label: this.options.name }];
            this.model = this.options.id;
        }
    },

    watch: {
        model: function (val) {
            this.$emit('update:modelValue', val);
        },
        options: function (val) {
            this.optDefault = [{ value: val.id, label: val.name }];
            this.model = val.id;
        }
    },

    methods: {
        searchData(val) {
            const vm = this;
            vm.axios.get(window._HOST + '/api/user?search=' + val).then((response) => {
                if (!response) vm.optDefault = [];
                else vm.optDefault = response.data;
            });
        }
    }
};
</script>
  