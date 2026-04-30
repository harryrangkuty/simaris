<template>
  <v-chart class="chart h-96" :option="barOptions" autoresize />
</template>

<script>
import { use } from 'echarts/core';
import { CanvasRenderer } from 'echarts/renderers';
import { BarChart } from 'echarts/charts';
import { TitleComponent, GridComponent, TooltipComponent, LegendComponent} from 'echarts/components';
import { THEME_KEY } from 'vue-echarts';
import { ref, provide, watch } from 'vue';


use([
  BarChart,
  CanvasRenderer,
  TitleComponent,
  TooltipComponent,
  GridComponent,
  LegendComponent,
]);

export default {
  props: {
    title: {
      type: String,
      required: true
    },
    barName: {
      type: String,
      required: true
    },
    chartData: {
      type: Array,
      required: true
    }
  },
  setup(props) {
    provide(THEME_KEY, 'light');
    const isMobile = window.innerWidth <= 768;
    const formatXAxis = (data) => {
      if (data.length > 2) {
        return data.map(item => {
          if (isMobile) {
            return item.name.substring(0, 5) + '...';
          } else {
            return item.name.substring(0, 10) + '...';
          }
        });
      } else {
        return data.map(item => item.name);
      }
    };
    const barOptions = ref({
      title: {
        text: props.title,
        left: 'center',
      },
      tooltip: {
        trigger: 'item',
           // usual formatter
        // formatter: '{a} {b} <br> Total Nilai: Rp.{c}',
          // formatter with params
        formatter: function (params) {
          return `${params.marker} ${params.name}<br/> Total Nilai: Rp.${params.data.label}`;
        },
      },
      xAxis:{
        type: 'category',
        // data: ['2018', '2019', '2020', '2021', '2022', '2023', '2024']
        data: formatXAxis(props.chartData),
        axisLabel: {
          interval: 0,
        }
      },
      yAxis:{
        type: 'value',
        axisLabel: {
          formatter: function (value) {
            if (value >= 1e12) {
              return (value / 1e12) + ' T';
            } else if (value >= 1e9) {
              return (value / 1e9) + ' M';
            } else if (value >= 1e6) {
              return (value / 1e6) + ' Jt';
            } else if (value >= 1e3) {
              return (value / 1e3) + ' Rb';
            } else {
              return value.toFixed(0);
            }
          }
        }
      },
      legend: {
        orient: 'horizontal',
        left: 'left',
        bottom: 0,
        // data: ['Kategori']
        data: [props.barName]
      },
      series: [
        {
          // name: 'Kategori',
          name: props.barName,
          // data: ['300000', '400000', '500000', '10000', '50000' , '10000', '20000'],
          data: props.chartData.map(item => ({
            value: item.value,
            name: item.name,
            label: item.label,
          })),
          type: 'bar',
          showBackground: true,
          backgroundStyle: {
            // color: 'rgba(180, 180, 180, 0.2)'
            color: 'rgba(230, 230, 250, 0.5)'
          }, 
        },
      ],
    });

    watch(() => props.chartData, (newData) => {
      barOptions.value = {
        ...barOptions.value,
        //perbarui jumlah bar saat props chart-data berubah
        xAxis: {
        ...barOptions.value.xAxis,
        data: formatXAxis(newData),
      },
      //perbarui data per-bar saat props chart-data berubah
        series: [
          {
            ...barOptions.value.series[0],
            data: newData.map(item => ({
              value: item.value,
              name: item.name,
              label: item.label,
            })),
          },
        ],
      };
    });
    return {
      barOptions
    };
  },
};
</script>