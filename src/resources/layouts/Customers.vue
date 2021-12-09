<script>
import Pane from '../components/Pane.vue';
import SummaryDatapoint from '../components/SummaryDatapoint.vue';
import MiniSparklinePanel from '../components/MiniSparklinePanel.vue';
import CombinedSearch from '../components/CombinedSearch.vue';

export default {
  name: 'Customers',
  components: {
    Pane,
    SummaryDatapoint,
    MiniSparklinePanel,
    CombinedSearch,
  },
  props: {
    stats: {
      type: Object,
      required: true,
      default: () => {},
    },
    customers: {
      type: Object | Array,
      default: () => [],
    },
  }
}
</script>

<template>
  <div>
    <div class="commerce-insights-summary">
      <p>
        You've had
        <SummaryDatapoint
          :number="stats.orders.newCustomers.total"
          format="number"
          up-down
        ></SummaryDatapoint>
        new customers,
        <SummaryDatapoint
          :data="stats.orders.newCustomers"
          up-down
        ></SummaryDatapoint>
        since the previous period.
      </p>
    </div>

    <div class="-mx-3">
      <div class="w-full commerce-insights-charts" style="display: flex;">
        <div class="w-1/3 p-3">
          <Pane title="Top Shipping Locations">
            <table class="data fullwidth mini-table short-bottom">
              <tbody>
              <tr v-for="(item, index) in stats.orders.topLocations">
                <td>{{ index +1  }}.</td>
                <td>{{ item.country }}</td>
                <td>{{ item.destination }}</td>
                <td class="text-right text-gray-500">{{ item.total }}</td>
              </tr>
              </tbody>
            </table>
          </Pane>
        </div>
        <div class="w-2/3 p-3">
          <div class="-m-3 mini-sparkline-grid">
            <div class="w-1/3">
              <div class="p-3">
                <MiniSparklinePanel
                  title="Total Customers"
                  caption="Customers with orders"
                  :trend="
                    stats.orders.totalCustomers.percentChange === 'INF' ? '∞%' :
                    Math.abs(stats.orders.totalCustomers.percentChange) + '%'
                  "
                  :percent="stats.orders.totalCustomers.percentChange"
                  :value="stats.orders.totalCustomers.total"
                  :data="stats.orders.totalCustomers.series"
                ></MiniSparklinePanel>
              </div>
            </div>

            <div class="w-1/3">
              <div class="p-3">
                <MiniSparklinePanel
                  title="New Customers"
                  caption="New customers with orders"
                  :trend="
                    stats.orders.newCustomers.percentChange === 'INF' ? '∞%' :
                    Math.abs(stats.orders.newCustomers.percentChange) + '%'
                  "
                  :percent="stats.orders.newCustomers.percentChange"
                  :value="stats.orders.newCustomers.total"
                  :data="stats.orders.newCustomers.series"
                ></MiniSparklinePanel>
              </div>
            </div>

            <div class="w-1/3">
              <div class="p-3">
                <MiniSparklinePanel
                  title="Returning Customers"
                  caption="Returning customers with orders"
                  :trend="
                    stats.orders.returningCustomers.percentChange === 'INF' ? '∞%' :
                    Math.abs(stats.orders.returningCustomers.percentChange) + '%'
                  "
                  :percent="stats.orders.returningCustomers.percentChange"
                  :value="stats.orders.returningCustomers.total"
                  :data="stats.orders.returningCustomers.series"
                ></MiniSparklinePanel>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <CombinedSearch
      element-type="Customers"
      :elements="customers"
    />
  </div>
</template>
