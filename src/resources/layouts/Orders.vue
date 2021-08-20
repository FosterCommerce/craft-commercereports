<script>
import SummaryDatapoint from '../components/SummaryDatapoint.vue';
import MiniSparklinePanel from '../components/MiniSparklinePanel.vue';
import CombinedSearch from '../components/CombinedSearch.vue';
import axios from 'axios';
import qs from 'qs';

export default {
  name: 'orders',
  props: {
    elements: {
      type: Object | Array,
      default: () => [],
    },
    stats: {
      type: Object,
      default: () => {},
    },
    dateStart: {
      type: String,
      default: '',
    },
    dateEnd: {
      type: String,
      default: '',
    },
    typeOptions: {
      type: Array,
      default: () => [],
    },
  },
  components: {
    SummaryDatapoint,
    MiniSparklinePanel,
    CombinedSearch,
  },
  data() {
    return {
      filteredStats: {},
    };
  },
  computed: {
    computedStats() {
      return Object.keys(this.filteredStats).length ? this.filteredStats : this.stats;
    },
  },
  methods: {
    handleFilterChange(data) {
      const self = this;
      const postData = {
        range_start: this.dateStart,
        range_end: this.dateEnd,
      };

      postData[Craft.csrfTokenName] = Craft.csrfTokenValue;

      for (const filter in data.filters) {
        postData[filter] = data.filters[filter];
      }

      axios.post('/actions/commerceinsights/vue/get-stats', qs.stringify(postData)).then(response => {
        self.filteredStats = response.data;
      }).catch(error => {
        console.log(error);
      });
    },
  },
}
</script>

<template>
  <div>
    <div class="commerce-insights-summary">
      <p>
        Revenue is
        <summary-datapoint
          :number="stats.orders.totalOrders.revenue"
          up-down
        ></summary-datapoint>
        for the selected date range compared the previous period. You also have
        <summary-datapoint
          :data="stats.orders.totalOrders"
          format="orders"
          up-down
        ></summary-datapoint>
        .
      </p>
    </div>

    <div class="-mx-3">
      <div class="flex w-full">
        <div class="w-full p-3">
          <div class="-m-3 mini-sparkline-grid">
            <div class="w-1/3">
              <div class="p-3">
                <mini-sparkline-panel
                  title="Total Orders"
                  caption="All orders this period"
                  :trend="
                    computedStats.orders.totalOrders.percentChange === 'INF' ? '∞%' :
                    Math.abs(computedStats.orders.totalOrders.percentChange) + '%'
                  "
                  :positive-trend="
                    computedStats.orders.totalOrders.percentChange >= 0 ||
                    computedStats.orders.totalOrders.percentChange === 'INF'
                  "
                  :value="computedStats.orders.totalOrders.total"
                  :data="computedStats.orders.totalOrders.series"
                ></mini-sparkline-panel>
              </div>
            </div>

            <div class="w-1/3">
              <div class="p-3">
                <mini-sparkline-panel
                  title="Average Value"
                  caption="Average completed order value"
                  :trend="
                    computedStats.orders.averageValue.percentChange === 'INF' ? '∞%' :
                    Math.abs(computedStats.orders.averageValue.percentChange) + '%'
                  "
                  :positive-trend="
                    computedStats.orders.averageValue.percentChange >= 0 ||
                    computedStats.orders.averageValue.percentChange === 'INF'
                  "
                  :value="computedStats.orders.averageValue.total"
                  :data="computedStats.orders.averageValue.series"
                ></mini-sparkline-panel>
              </div>
            </div>

            <div class="w-1/3">
              <div class="p-3">
                <mini-sparkline-panel
                  title="Average Order Quantity"
                  caption="Average items per order"
                  :trend="
                    computedStats.orders.averageQuantity.percentChange === 'INF' ? '∞%' :
                    Math.abs(computedStats.orders.averageQuantity.percentChange) + '%'
                  "
                  :positive-trend="
                    computedStats.orders.averageQuantity.percentChange >= 0 ||
                    computedStats.orders.averageQuantity.percentChange === 'INF'
                  "
                  :value="computedStats.orders.averageQuantity.total"
                  :data="computedStats.orders.averageQuantity.series"
                ></mini-sparkline-panel>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <combined-search
      :elements="elements"
      :type-options="typeOptions"
      element-type="Orders"
      @filtersChanged="handleFilterChange"
    />
  </div>
</template>
