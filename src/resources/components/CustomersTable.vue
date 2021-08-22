<script>
export default {
  name: 'CustomersTable',
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
  <table class="data fullwidth">
    <thead>
      <tr>
        <th
          scope="col"
          :class="sortBy === 'email' ? 'ordered ' + sortDirection : 'orderable'"
          @click="sort('email')"
        >
          Email
        </th>
        <th
          scope="col"
          :class="sortBy === 'ordersCount' ? 'ordered ' + sortDirection : 'orderable'"
          @click="sort('ordersCount')"
        >
          # Orders
        </th>
        <th
          scope="col"
          :class="sortBy === 'itemsQty' ? 'ordered ' + sortDirection : 'orderable'"
          @click="sort('itemsQty')"
        >
          # Items
        </th>
        <th
          scope="col"
          :class="sortBy === 'amountPaid' ? 'ordered ' + sortDirection : 'orderable'"
          @click="sort('amountPaid')"
        >
          Total Spent
        </th>
        <th
          scope="col"
          :class="sortBy === 'aov' ? 'ordered ' + sortDirection : 'orderable'"
          @click="sort('aov')"
        >
          AOV
        </th>
        <th scope="col"
          :class="sortBy === 'firstPurchase' ? 'ordered ' + sortDirection : 'orderable'"
          @click="sort('firstPurchase')"
        >
          First Purchase
        </th>
        <th scope="col"
          :class="sortBy === 'lastPurchase' ? 'ordered ' + sortDirection : 'orderable'"
          @click="sort('lastPurchase')"
        >
          Last Purchase
        </th>
      </tr>
    </thead>

    <tbody>
      <tr class="commerce-insights-ajax-loader centeralign">
        <td colspan="9" class="centeralign">
          <div class="spinner loadingmore"></div>
        </td>
      </tr>
      <tr v-for="element in elements" :key="element.email">
        <td v-if="element.customer">
          <a :href="'/admin/users/' + element.customer.id">
            {{ element.email }} ({{ element.customer.id }})
          </a>
        </td>
        <td v-else>
          {{ element.email }} (Guest)
        </td>
        <td>{{ element.ordersCount }}</td>
        <td>{{ element.itemsQty }}</td>
        <td>{{ element.amountPaid }}</td>
        <td style="padding: 12px 0 12px 14px">{{ element.aov }}</td>
        <td>{{ element.firstPurchase }}</td>
        <td>{{ element.lastPurchase }}</td>
      </tr>
    </tbody>
  </table>
</template>
