<script>
import SummaryDatapoint from '../components/SummaryDatapoint.vue';
import MiniSparklinePanel from '../components/MiniSparklinePanel.vue';
import CombinedSearch from '../components/CombinedSearch.vue';
import axios from 'axios';
import qs from 'qs';

export default {
  name: 'Orders',
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
}
</script>

<template>
  <div>
    <div class="commerce-reports-summary">
      <p>
        Revenue is
        <SummaryDatapoint
          :number="stats.orders.totalOrders.revenue"
          up-down
        ></SummaryDatapoint>
        for the selected date range compared to the previous period. You also have
        <SummaryDatapoint
          :data="stats.orders.totalOrders"
          format="orders"
          up-down
        ></SummaryDatapoint>.
      </p>
    </div>

    <div class="-mx-3">
      <div class="flex w-full">
        <div class="w-full p-3">
          <div class="-m-3 mini-sparkline-grid">
            <div class="w-1/3">
              <div class="p-3">
                <MiniSparklinePanel
                  title="Total Orders"
                  caption="All orders this period"
                  :trend="
                    computedStats.orders.totalOrders.percentChange === 'INF' ? '∞%' :
                    Math.abs(computedStats.orders.totalOrders.percentChange) + '%'
                  "
                  :percent="computedStats.orders.totalOrders.percentChange"
                  :value="computedStats.orders.totalOrders.total"
                  :data="computedStats.orders.totalOrders.series"
                ></MiniSparklinePanel>
              </div>
            </div>

            <div class="w-1/3">
              <div class="p-3">
                <MiniSparklinePanel
                  title="Average Value"
                  caption="Average completed order value"
                  :trend="
                    computedStats.orders.averageValue.percentChange === 'INF' ? '∞%' :
                    Math.abs(computedStats.orders.averageValue.percentChange) + '%'
                  "
                  :percent="computedStats.orders.averageValue.percentChange"
                  :value="computedStats.orders.averageValue.total"
                  :data="computedStats.orders.averageValue.series"
                ></MiniSparklinePanel>
              </div>
            </div>

            <div class="w-1/3">
              <div class="p-3">
                <MiniSparklinePanel
                  title="Average Order Quantity"
                  caption="Average items per order"
                  :trend="
                    computedStats.orders.averageQuantity.percentChange === 'INF' ? '∞%' :
                    Math.abs(computedStats.orders.averageQuantity.percentChange) + '%'
                  "
                  :percent="computedStats.orders.averageQuantity.percentChange"
                  :neutral-trend="computedStats.orders.averageQuantity.percentChange === 0"
                  :value="computedStats.orders.averageQuantity.total"
                  :data="computedStats.orders.averageQuantity.series"
                ></MiniSparklinePanel>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <CombinedSearch
      :elements="elements"
      :type-options="typeOptions"
      element-type="Orders"
    />
  </div>
</template>
