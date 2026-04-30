<template>
  <v-chart class="chart h-96" :option="pieOptions" autoresize />
</template>

<script>
import { use } from 'echarts/core';
import { CanvasRenderer } from 'echarts/renderers';
import { PieChart } from 'echarts/charts';
import { TitleComponent, TooltipComponent, LegendComponent} from 'echarts/components';
import { THEME_KEY } from 'vue-echarts';
import { ref, provide, watch } from 'vue';

use([
  PieChart,
  CanvasRenderer,
  TitleComponent,
  TooltipComponent,
  LegendComponent,
]);

export default {
  props: {
    title: {
      type: String,
      required: true
    },
    chartData: {
      type: Array,
      required: true
    },
    mode: {
      type: String,
    }
  },
  setup(props) {
    provide(THEME_KEY, 'light');
    const pieOptions = ref({
      title: {
        text: props.title,
        left: 'center',
      },
      tooltip: {
        trigger: 'item',
          // usual formatter
        // formatter: '{a} <br/> {b} : {c} ({d}%)',
          // formatter with params
        formatter: function (params) {
          if (props.mode == "persediaan") {
            return `${params.marker} ${params.name}<br/> Total Harga: Rp.${params.data.label} (${params.percent}%)<br/> Total Stock: ${params.data.item}`;
          } else {
          return `${params.marker} ${params.name}<br/> Total Nilai: Rp.${params.data.label} (${params.percent}%)<br/> Total Data: ${params.data.item}`;
        }
        },
      },
      legend: {
        orient: 'horizontal',
        left: 'left',
        bottom: 0, 
        // data: ['Direct', 'Email', 'Ad Networks', 'Video Ads', 'Search Engines'], //static value
        data: props.chartData.map(item => item.name)
      },
      series: [
        {
          name: props.title,
          type: 'pie',
          radius: '60%',
          center: ['50%', '50%'],
          label: {
            // formatter: '{b} ({d}%)'
            formatter: '({d}%)'
          },
            // statis data series
          // data: [
          //   { value: 335, name: 'Direct' },
          //   { value: 310, name: 'Email' },
          //   { value: 234, name: 'Ad Networks' },
          //   { value: 135, name: 'Video Ads' },
          //   { value: 1548, name: 'Search Engines' },
          // ], 
            // dinamis data series
          data: props.chartData.map(item => ({
            value: item.value,
            name: item.name,
            item: item.item,
            label: item.label,
          })),
          emphasis: {
            itemStyle: {
              shadowBlur: 10,
              shadowOffsetX: 0,
              shadowColor: 'rgba(0, 0, 0, 0.5)',
            },
          },
        },
      ],
    });

    watch(() => props.chartData, (newData) => {
      pieOptions.value = {
        ...pieOptions.value,
        legend: {
          ...pieOptions.value.legend,
          data: newData.map(item => item.name)
        },
        series: [
          {
            ...pieOptions.value.series[0],
            data: newData.map(item => ({
              value: item.value,
              name: item.name,
              item: item.item,
              label: item.label,
            })),
          },
        ],
      };
    });

    return {
      pieOptions
    };
  },
};
</script>