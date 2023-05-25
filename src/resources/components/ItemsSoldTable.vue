<script>
export default {
  name: 'ItemsSoldTable',
  props: {
    sortBy: {
      type: String,
      required: true,
      default: '',
    },
    sortDirection: {
      type: String,
      required: true,
      default: '',
    },
    elements: {
      type: Array,
      required: true,
      default: () => [],
    },
  },
	methods: {
    sort(column) {
      this.$emit('sort-table', column);
    },
  },
};
</script>

<template>
  <div class="vue-admin-tablepane">
    <table class="vuetable data fullwidth">
      <thead>
        <tr>
          <th
            scope="col"
            :class="sortBy === 'title' ? 'ordered ' + sortDirection : 'orderable'"
            @click="sort('title')"
          >
            Item
          </th>
          <th
            scope="col"
            :class="sortBy === 'sku' ? 'ordered ' + sortDirection : 'orderable'"
            @click="sort('sku')"
          >
            SKU
          </th>
          <th
            scope="col"
            :class="sortBy === 'type' ? 'ordered ' + sortDirection : 'orderable'"
            @click="sort('type')"
          >
            Product Type
          </th>
          <th
            scope="col"
            :class="sortBy === 'totalSold' ? 'ordered ' + sortDirection : 'orderable'"
            @click="sort('totalSold')"
          >
            Total Sold
          </th>
          <th
            scope="col"
            :class="sortBy === 'sales' ? 'ordered ' + sortDirection : 'orderable'"
            @click="sort('sales')"
          >
            Total Sales
          </th>
        </tr>
      </thead>

      <tbody>
        <tr class="commerce-reports-ajax-loader centeralign">
          <td colspan="9" class="centeralign">
            <div class="spinner loadingmore"></div>
          </td>
        </tr>
        <tr v-for="element in elements" :key="element.id">
          <td>
            <div
              class="element small"
              data-site-id="1"
              :title="element.title"
              data-editable=""
            >
              <div class="label">
                <span class="status" :class="element.status"></span>
                <span class="title">{{ element.title }}</span>
              </div>
            </div>
          </td>
          <td>{{ element.sku }}</td>
          <td>{{ element.type }}</td>
          <td>
            {{ element.totalSold }} sold in

						<span v-if="!element.hideOrders">
							<a :href="'/admin/commercereports/orders/product/' + element.id">
								{{ element.numOrders }} {{ element.numOrders > 1 ? 'orders' : 'order' }}
							</a>
						</span>

						<span v-else>
							{{ element.numOrders }} {{ element.numOrders > 1 ? 'orders' : 'order' }}
						</span>
          </td>
          <td>{{ element.sales }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
