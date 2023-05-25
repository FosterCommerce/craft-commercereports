<script>
import PaymentStatusDropdown from '../components/PaymentStatusDropdown.vue';
import JsonCSV from 'vue-json-csv';
import Vue from 'vue';

Vue.component('DownloadCsv', JsonCSV);

export default {
  name: 'CombinedSearchBar',
  components: {
    PaymentStatusDropdown,
  },
  props: {
    elementType: {
      type: String,
      required: true,
      default: '',
    },
    elements: {
      type: Object | Array,
      required: true,
      default: () => [],
    },
    allTypesLabel: {
      type: String,
      default: () => `All ${this.elementType}`,
    },
    typeOptions: {
      type: Array,
      default: () => [],
    },
    selectedTypeOption: {
      type: Object,
      required: true,
      default: () => {},
    },
    selectedPaymentTypeOption: {
      type: Object,
      required: true,
      default: () => {},
    },
  },
  data() {
    return {
      keyword: '',
    };
  },
  methods: {
    selectAllTypes() {
      this.$emit('select-all-types');
    },
    selectTypeOption(option) {
      this.$emit('select-type-option', option);
    },
    selectPaymentTypeOption(option) {
      this.$emit('select-payment-option', option);
    },
    emitKeywordChange() {
      this.$emit('keyword-change', this.keyword);
    },
    getPlaceholder() {
      switch (this.elementType) {
        case 'Orders':
          return 'Search orders';
        case 'ItemsSold':
          return 'Search items sold';
        case 'Products':
          return 'Search product name or SKU';
        case 'Customers':
          return 'Search customers';
        default:
          return 'Search';
      }
    },
    getSelectedLabel() {
      if (Object.keys(this.selectedTypeOption).length === 0) {
				if (this.elementType === 'Orders') {
					return 'All Order Statuses';
				}

				if (this.elementType === 'ItemsSold') {
					return 'All Items Sold';
				}

        return this.allTypesLabel;
      }

      return this.selectedTypeOption.label;
    },
    csvElements() {
      const self = this;
      let results = [];
      let filteredElements = self.elements;

      if (this.elementType === 'ItemsSold') {
        if (this.keyword.length) {
          // match title and sku against keyword
          filteredElements = filteredElements.filter(function(element) {
            const lowerKeyword = self.keyword.toLowerCase();
            return element.title.toLowerCase().indexOf(lowerKeyword) > -1 ||
              element.sku.toLowerCase().indexOf(lowerKeyword) > -1;
          });
        }

        if (Object.keys(this.selectedTypeOption).length !== 0) {
          filteredElements = filteredElements.filter(function(element) {
            // filter by selected status
            return element.type.title === self.selectedTypeOption.value;
          });
        }

        if (Object.keys(this.selectedPaymentTypeOption).length !== 0) {
          filteredElements = filteredElements.filter(function(element) {
            // filter by selected payment status
            return element.paymentStatus === self.selectedPaymentTypeOption.value;
          });
        }
      } else if (this.elementType === 'Orders') {
        if (this.keyword.length) {
          // match email and reference against keyword
          filteredElements = filteredElements.filter(function(element) {
            const lowerKeyword = self.keyword.toLowerCase();
            return element?.email?.toLowerCase().indexOf(lowerKeyword) > -1 ||
              element?.reference?.toLowerCase().indexOf(lowerKeyword) > -1 ||
              element?.amountPaid?.toLowerCase().indexOf(lowerKeyword) > -1 ||
              element?.paymentStatus?.toLowerCase().indexOf(lowerKeyword) > -1 ||
              element?.status?.toLowerCase().indexOf(lowerKeyword) > -1 ||
              element?.billingName?.toLowerCase().indexOf(lowerKeyword) > -1 ||
              element?.shippingName?.toLowerCase().indexOf(lowerKeyword) > -1 ||
              element?.orderId?.toString().indexOf(lowerKeyword) > -1 ||
              element?.fullDate?.toLowerCase().indexOf(lowerKeyword) > -1;
          });
        }

        if (Object.keys(this.selectedTypeOption).length !== 0) {
          filteredElements = filteredElements.filter(function(element) {
            // filter by selected status
            return element.status === self.selectedTypeOption.value;
          });
        }

        if (Object.keys(this.selectedPaymentTypeOption).length !== 0) {
          filteredElements = filteredElements.filter(function(element) {
            // filter by selected payment status
            return element.paymentStatus === self.selectedPaymentTypeOption.value;
          });
        }
      } else if (this.elementType === 'Customers') {
        if (this.keyword.length ) {
          // match email and name against keyword
          filteredElements = filteredElements.filter(function(element) {
            const lowerKeyword = self.keyword.toLowerCase();

            return element.email.toLowerCase().indexOf(lowerKeyword) > -1 ||
              element.billingName.toLowerCase().indexOf(lowerKeyword) > -1 ||
              element.shippingName.toLowerCase().indexOf(lowerKeyword) > -1;
          });
        }
      }

      if (this.elementType === 'Products' || this.elementType === 'ItemsSold') {
        for (let element of filteredElements) {
          results.push({
            'Item': element.title,
            'SKU': element.sku,
            'Product Type': element.type,
            'Total Sold': `${element.totalSold} in ${element.numOrders} orders`,
            'Total Sales': element.sales,
          });
        }
      } else if (this.elementType === 'Orders') {
        for (let element of filteredElements) {
          results.push({
            'Order #': element.reference,
            'Date Ordered': element.date,
            'Status': element.status,
            'Merchandise Total': element.merchTotal,
            'Tax': element.tax,
            'Discount': element.discount,
            'Shipping': element.shipping,
            'Total Paid': element.amountPaid,
            'Total Items Sold': element.numItems,
          });
        }
      } else if (this.elementType === 'Customers') {
        for (let element of filteredElements) {
          results.push({
            'Email': element.email,
            'Billing Name': element.billingName,
            'Shipping Name': element.shippingName,
            '# Orders': element.ordersCount,
            '# Items': element.itemsQty,
            'Total Spent': element.amountPaid,
            'AOV': element.aov,
            'First Purchase': element.firstPurchase,
            'Last Purchase': element.lastPurchase,
          });
        }
      }

      return results;
    },
  },
};
</script>

<template>
  <div class="commerce-reports-combined-search-bar toolbar layout-flex">
    <div class="flex w-1/2">
      <div class="">
        <div class="btn menubtn">
          {{ getSelectedLabel() }}
        </div>
        <div class="menu">
          <ul class="padded">
            <li>
              <a
                :class='{ "sel": Object.keys(selectedTypeOption).length === 0 }'
                @click="selectAllTypes()"
              >
                <span v-if="elementType !== 'Orders'">
                  {{ allTypesLabel }}
                </span>
                <span v-else>
                  All Order Statuses
                </span>
              </a>
            </li>
            <li v-for="option in typeOptions" :key="option.value">
              <a
                :class='{ "sel": selectedTypeOption.value == option.value }'
                @click="selectTypeOption(option)"
              >
                {{ option.label }}
              </a>
            </li>
          </ul>
        </div>
      </div>

      <PaymentStatusDropdown
        v-if="elementType === 'Orders'"
        :selected-option="selectedPaymentTypeOption"
        @selected-payment-type="selectPaymentTypeOption"
        @select-all-types="selectAllTypes()"
      ></PaymentStatusDropdown>

      <div class="flex-grow texticon search icon clearable">
        <input
          class="text fullwidth"
          v-model="keyword"
          @input="emitKeywordChange()"
          type="text"
          autocomplete="off"
          :placeholder="getPlaceholder()"
        />
        <div class="clear hidden" title="Clear"></div>
      </div>
      <div class="spinner invisible"></div>
    </div>

    <div class="w-1/2" style="text-align: right">
      <DownloadCsv
        :data="csvElements()"
        class="btn submit">
        Export to CSV
      </DownloadCsv>
    </div>
  </div>
</template>

<style>
.commerce-reports-combined-search-bar {
  background-color: #f1f5f8;
  border-radius: 5px;
  padding: 0.75rem 0.75rem 0.4rem 0.75rem;
}
</style>
