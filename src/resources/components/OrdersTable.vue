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
  <table class="data fullwidth">
    <thead>
      <tr>
        <th
          scope="col"
          :class="sortBy === 'reference' ? 'ordered ' + sortDirection : 'orderable'"
          @click="sort('reference')"
        >
          Order #
        </th>
        <th
          scope="col"
          :class="sortBy === 'dateStamp' ? 'ordered ' + sortDirection : 'orderable'"
          @click="sort('dateStamp')"
        >
          Date Ordered
        </th>
        <th
          scope="col"
          :class="sortBy === 'status' ? 'ordered ' + sortDirection : 'orderable'"
          @click="sort('status')"
        >
          Status
        </th>
        <th
          scope="col"
          :class="sortBy === 'merchTotal' ? 'ordered ' + sortDirection : 'orderable'"
          @click="sort('merchTotal')"
        >
          Merchandise Total
        </th>
        <th
          scope="col"
          :class="sortBy === 'tax' ? 'ordered ' + sortDirection : 'orderable'"
          @click="sort('tax')"
        >
          Tax
        </th>
        <th
          scope="col"
          :class="sortBy === 'discount' ? 'ordered ' + sortDirection : 'orderable'"
          @click="sort('discount')"
        >
          Discount
        </th>
        <th
          scope="col"
          :class="sortBy === 'shipping' ? 'ordered ' + sortDirection : 'orderable'"
          @click="sort('shipping')"
        >
          Shipping
        </th>
        <th
          scope="col"
          :class="sortBy === 'amountPaid' ? 'ordered ' + sortDirection : 'orderable'"
          @click="sort('amountPaid')"
        >
          Total Paid
        </th>
        <th
          v-if="productQuery"
          scope="col"
          class="orderable"
        >
          Email
        </th>
        <th
          scope="col"
          v-else
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

    <tbody>
      <tr class="commerce-insights-ajax-loader centeralign">
        <td colspan="9" class="centeralign">
          <div class="spinner loadingmore"></div>
        </td>
      </tr>
      <tr v-for="element in elements" :key="element.id">
        <td data-title="Order" data-titlecell="">
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
        <td :title="element.fullDate">
          {{ element.date }}
        </td>
        <td>
          <span class="status" :class="element.color"></span>{{ element.status }}
        </td>
        <td>{{ element.merchTotal }}</td>
        <td>{{ element.tax }}</td>
        <td>{{ element.discount }}</td>
        <td>{{ element.shipping }}</td>
        <td>{{ element.amountPaid }}</td>
        <td v-if="productQuery">{{ element.email }}</td>
        <td v-else>{{ element.numItems }}</td>
        <td>
          <span class="status" :class="element.paidColor"></span>
          {{ element.paymentStatus }}
        </td>
      </tr>
    </tbody>
  </table>
</template>
