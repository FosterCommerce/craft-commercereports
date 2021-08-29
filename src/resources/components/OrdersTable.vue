<script>
export default {
  name: 'OrdersTable',
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
    productQuery: {
      type: Boolean,
      required: true,
      default: false,
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
          class="vuetable-th-slot-title"
          :class="sortBy === 'reference' ? 'ordered ' + sortDirection : 'orderable'"
          @click="sort('reference')"
        >
          Order #
        </th>
        <th
          scope="col"
          class="vuetable-th-slot-title"
          :class="sortBy === 'dateStamp' ? 'ordered ' + sortDirection : 'orderable'"
          @click="sort('dateStamp')"
        >
          Date Ordered
        </th>
        <th
          scope="col"
          class="vuetable-th-slot-title"
          :class="sortBy === 'status' ? 'ordered ' + sortDirection : 'orderable'"
          @click="sort('status')"
        >
          Status
        </th>
        <th
          scope="col"
          class="vuetable-th-slot-title"
          :class="sortBy === 'merchTotal' ? 'ordered ' + sortDirection : 'orderable'"
          @click="sort('merchTotal')"
        >
          Merchandise Total
        </th>
        <th
          scope="col"
          class="vuetable-th-slot-title"
          :class="sortBy === 'tax' ? 'ordered ' + sortDirection : 'orderable'"
          @click="sort('tax')"
        >
          Tax
        </th>
        <th
          scope="col"
          class="vuetable-th-slot-title"
          :class="sortBy === 'discount' ? 'ordered ' + sortDirection : 'orderable'"
          @click="sort('discount')"
        >
          Discount
        </th>
        <th
          scope="col"
          class="vuetable-th-slot-title"
          :class="sortBy === 'shipping' ? 'ordered ' + sortDirection : 'orderable'"
          @click="sort('shipping')"
        >
          Shipping
        </th>
        <th
          scope="col"
          class="vuetable-th-slot-title"
          :class="sortBy === 'amountPaid' ? 'ordered ' + sortDirection : 'orderable'"
          @click="sort('amountPaid')"
        >
          Total Paid
        </th>
        <th
          v-if="productQuery"
          scope="col"
          class="orderable vuetable-th-slot-title"
        >
          Email
        </th>
        <th
          scope="col"
          v-else
          class="vuetable-th-slot-title"
          :class="sortBy === 'numItems' ? 'ordered ' + sortDirection : 'orderable'"
          @click="sort('numItems')"
        >
          Total Items Sold
        </th>
        <th>
          Payment Status
        </th>
      </tr>
      </thead>

      <tbody class="vuetable-body">
      <tr class="commerce-insights-ajax-loader centeralign">
        <td colspan="9" class="vuetable-slot centeralign">
          <div class="spinner loadingmore"></div>
        </td>
      </tr>
      <tr v-for="element in elements" :key="element.id">
        <td class="vuetable-slot" data-title="Order" data-titlecell="">
          <div
            class="element small"
            data-type="craft\commerce\elements\Order"
            :data-id="element.orderId"
            data-site-id="1"
            :data-status="element.status"
            :data-label="element.reference"
            data-url=""
            data-level=""
            :title="'Billing Name: ' + element.billingName + '; Shipping Name: ' + element.shippingName"
            data-editable=""
          >
            <div class="label">
              <span class="title">
                <a
                  :href="'/admin/commerce/orders/' + element.orderId"
                  target="_blank"
                >
                  {{ element.reference }}
                </a>
              </span>
            </div>
          </div>
        </td>
        <td :title="element.fullDate" class="vuetable-slot">
          {{ element.date }}
        </td>
        <td class="vuetable-slot">
          <span class="status" :class="element.color"></span>{{ element.status }}
        </td>
        <td class="vuetable-slot">{{ element.merchTotal }}</td>
        <td class="vuetable-slot">{{ element.tax }}</td>
        <td class="vuetable-slot">{{ element.discount }}</td>
        <td class="vuetable-slot">{{ element.shipping }}</td>
        <td class="vuetable-slot">{{ element.amountPaid }}</td>
        <td v-if="productQuery" class="vuetable-slot">{{ element.email }}</td>
        <td v-else class="vuetable-slot">{{ element.numItems }}</td>
        <td class="vuetable-slot">
          <span class="status" :class="element.paidColor"></span>
          {{ element.paymentStatus }}
        </td>
      </tr>
      </tbody>
    </table>
  </div>
</template>

<style>
  .vue-admin-tablepane {
    overflow-x: auto;
  }
</style>
