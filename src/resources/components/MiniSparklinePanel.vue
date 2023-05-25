<script>
import Pane from './Pane.vue';
import styles from '../css/common.css';
import Chart from 'chart.js/auto';

export default {
  name: 'mini-sparkline-panel',
  components: {
    Pane,
  },
  props: {
    title: String,
    trend: String,
    percent: Number,
    value: Number,
    caption: String,
    data: Array,
  },
  data() {
    return {
      chart: null, // chart instance
      chartData: this.formatChartData(), // data formatted for Chart.js
      fontFamily: `system-ui, BlinkMacSystemFont, -apple-system, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', 'Fira Sans', 'Droid Sans', 'Helvetica Neue', sans-serif`,
      ticks: {
        beginAtZero: true,
        fontSize: 10,
        fontColor: '#8c99a7',
        fontFamily: `system-ui, BlinkMacSystemFont, -apple-system, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', 'Fira Sans', 'Droid Sans', 'Helvetica Neue', sans-serif`,
      },
    };
  },
  mounted() {
    // draw the chart
    this.chart = new Chart(this.$refs.chart.getContext('2d'), {
      type: 'line',
      data: this.getChartData(),
      options: {
        responsive: true,
        plugins: {
          tooltip: {
            enabled: false,
          },
          legend: {
            display: false,
          }
        },
        elements: {
          line: {
            borderColor: 'rgba(211, 167, 138, 0.2)',
            borderWidth: 2,
          },
          point: {
            radius: 0,
          },
        },
        tooltips: {enabled: false},
        scales: {
          y: {
            beginAtZero: true,
            display: false,
          },
          x: {
            display: false,
          },
        },
      },
    });
  },
  methods: {
    getChartData() {
      if (this.chartData.datasets) {
        // if we have a muted-sparkline chart, set dataset bakgroundColor if missing or auto
        this.chartData.datasets.forEach(function(dataset) {
          this.styleDataset(dataset, {
            backgroundColor: 'rgba(211, 167, 138, 0.05)',
            fill: true,
          });
        }, this);
      }

      return this.chartData;
    },
    /**
     * Sets styleProps on the provided dataset if it either doesn't have each one
     * or it's explicitly set to `auto`.
     */
    styleDataset(dataset, styleProps) {
      Object.entries(styleProps).forEach(entry => {
        let key = entry[0];
        let value = entry[1];

        // property is not set, or set to `auto`
        if (typeof dataset[key] === 'undefined' || dataset[key] == 'auto') {
          dataset[key] = value;
        }
      }, this);
    },
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
    chartData: function(newValue, oldValue) {
      if (this.chartData !== null && this.chartData !== undefined) {

        /**
         * Only touch nodes that require updating to avoid redrawing the entire chart.
         */
        const updatedData = this.getChartData();

        if (!Object.is(newValue.labels, oldValue.labels)) {
          this.chart.data.labels = updatedData.labels;
        }

        if (!Object.is(newValue.datasets, oldValue.datasets)) {
          // same number of datasets or new length?
          if (newValue.datasets.length === oldValue.datasets.length || oldValue.datasets.length ===
            0 && newValue.datasets.length > 0) {

            // loop through and only update chart data and/or labels that changed
            oldValue.datasets.forEach((oldDataset, index) => {
              const newDataset = newValue.datasets[index];

              if (oldDataset.label !== newDataset.label) {
                this.chart.data.datasets[index].label = newDataset.label;
              }

              if (JSON.stringify(oldDataset.data) !== JSON.stringify(newValue.data)) {
                this.chart.data.datasets[index].data = newDataset.data;
              }
            });
          } else {
            // update the node, which will re-render the chart
            this.chart.data.datasets = updatedData.datasets;
          }
        }

        this.chart.update();
      }
    },
  },
};
</script>

<template>
  <pane
    :title="title"
    :trend="trend"
    :percent="percent"
    :pad-title="false"
    flex
    class="h-34"
  >
    <div class="commerce-reports-mini-sparkline-pane flex-grow">
      <div class="commerce-reports-mini-sparkline-pane-content flex-col justify-end flex-grow">
        <h4 class="commerce-reports-mini-sparkline-pane-value flex-grow">{{ value }}</h4>
        <p class="commerce-reports-mini-sparkline-pane-caption self-end">{{ caption }}</p>
      </div>
      <div class="chart-pane-fill-container">
        <div v-cloak ref="container" class="commerce-reports-chart-container w-full h-full relative">
          <canvas ref="chart"></canvas>
        </div>
      </div>
    </div>
  </pane>
</template>

<style>
.commerce-reports-mini-sparkline-pane {
  display: flex;
  width: 100%;
  position: relative;
}

.commerce-reports-mini-sparkline-pane-content {
  display: flex;
  width: 100%;
}

.commerce-reports-mini-sparkline-pane-value {
  color: #d3a87a;
  font-weight: 300;
  font-size: 1.5rem;
  margin: 0;
  padding: 0;
  width: 100%;
}

.commerce-reports-mini-sparkline-pane-caption {
  width: 100%;
  margin: 0;
  padding: 0;
  line-height: 1.25;
  display: flex;
  font-size: 0.75rem;
  color: #a0aec0;
}

.chart-pane-fill-container {
  position: absolute;
  z-index: 0;
  bottom: 0;
  left: 0;
  right: 0;
  margin: -16px;
}
</style>
