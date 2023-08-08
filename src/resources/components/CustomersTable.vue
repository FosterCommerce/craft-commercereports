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
  <div class="vue-admin-tablepane">
    <table class="vuetable data fullwidth">
      <thead>
        <tr>
          <th
            scope="col"
            :class="sortBy === 'fullName' ? 'ordered ' + sortDirection : 'orderable'"
            @click="sort('fullName')"
          >
            Name
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
            :class="sortBy === 'lastPurchase' ? 'ordered ' + sortDirection : 'orderable'"
            @click="sort('lastPurchase')"
          >
            Last Purchase
          </th>
          <th scope="col"
            :class="sortBy === 'status' ? 'ordered ' + sortDirection : 'orderable'"
            @click="sort('status')"
          >
            Status
          </th>
        </tr>
      </thead>

      <tbody>
        <tr class="commerce-reports-ajax-loader centeralign">
          <td colspan="9" class="centeralign">
            <div class="spinner loadingmore"></div>
          </td>
        </tr>
        <tr v-for="element in elements" :key="element.fullName">
          <td>
            <span v-if="element.customer">
              <a :href="'/admin/users/' + element.customer.id + '#commerce'" :title="element.email">
              {{ element.fullName }}
              </a>
            </span><span v-else>Guest</span>
          </td>
          <td>{{ element.ordersCount }}</td>
          <td>{{ element.amountPaid }}</td>
          <td style="padding: 12px 0 12px 14px">{{ element.aov }}</td>
          <td>{{ element.lastPurchase }}</td>
          <td :class="element.status === 'Active' ? 'color-green' : 'color-red'">
            {{ element.status }}
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<style>
  .color-green {
    color: #27AB83;
  }

  .color-red {
    color: #CF1124;
  }
</style>
