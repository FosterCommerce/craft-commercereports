<script>
import Pane from './Pane.vue';
import Chart from './Chart.vue';

import styles from '../css/common.css';

export default {
  name: 'sparkline-panel',
  components: {
    Pane,
    Chart,
  },
  props: {
    title: String,
    trend: String,
    positiveTrend: Boolean,
    value: String,
    caption: String,
    data: Array,
  },
  data() {
    return {
      chartData: this.formatChartData(), // data formatted for Chart.js
    };
  },
  methods: {
    formatChartData() {
      return {
        labels: this.data,
        datasets: [
          {
            data: this.data,
          },
        ],
      };
    },
  },
  watch: {
    data: function(newValue, oldValue) {
      this.chartData = this.formatChartData();
    },
  },
};
</script>

<template>
  <pane
    :title="title"
    :trend="trend"
    :positive-trend="positiveTrend"
    :pad-title="false"
  >
    <div class="commerce-reports-sparkline-pane">
      <div class="sparkline-pane-left flex-grow flex-col justify-end">
        <h4 class="font-light text-tan text-2xl m-0 p-0">{{ value }}</h4>
        <p class="text-gray-500 text-xs m-0 p-0 w-full">{{ caption }}</p>
      </div>
      <div class="sparkline-pane-right">
        <div class="w-full h-16 relative">
          <chart type="sparkline" :chartData="chartData"/>
        </div>
      </div>
    </div>
  </pane>
</template>

<style>
.commerce-reports-sparkline-pane {
  display: flex;
  width: 100%;
}

.commerce-reports-sparkline-pane .sparkline-pane-left {
  display: flex;
  width: 50%;
}

.commerce-reports-sparkline-pane .sparkline-pane-right {
  display: flex;
  height: 100%;
  width: 47%;
  position: relative;
  margin-bottom: 0.5rem;
}
</style>
