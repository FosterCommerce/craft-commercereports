<script>
import Vue from 'vue';
import styles from '../css/common.css';
import moment from 'moment';
import JsonCSV from 'vue-json-csv';

Vue.component('downloadCsv', JsonCSV);

export default {
  name: 'combined-search',
  props: {
    elements: {
      type: Object | Array,
      default: function() {
        return [];
      },
    },
    elementType: String, // `Products` or `Orders`
    type: {
      type: String,
      default: '',
    },
    typeOptions: {
      type: Array,
      default: function() {
        return [];
      },
    },
    productQuery: {
      type: Boolean,
      default: false,
    },
    page: {
      type: Number,
      default: 1,
    },
    allTypesLabel: {
      type: String,
      default: function() {
        return `All ${this.elementType}`;
      },
    },
  },
  data() {
    return {
      sort_by: '',
      sort_direction: 'desc',
      page_size: 100,
      current_page: 1,
      keyword: '',
      previousKeywordLen: 0,
      selectedTypeOption: function() {
        return {};
      },
      selectedPaymentTypeOption: function() {
        return {};
      },
    };
  },
  mounted() {
    this.getSortBy();
  },
  methods: {
    getSortBy() {
      switch (this.elementType) {
        case 'Orders':
          this.sort_by = 'dateStamp';
          break;
        case 'Sales':
          this.sort_by = 'totalSold';
          break;
        case 'Products':
          this.sort_by = 'title';
          break;
        case 'Customers':
          this.sort_by = 'lastPurchase';
          break;
      }
    },
    getPlaceholder() {
      switch (this.elementType) {
        case 'Orders':
          return 'Search orders';
        case 'Sales':
          return 'Search sales';
        case 'Products':
          return 'Search product name or SKU';
        case 'Customers':
          return 'Search customers';
        default:
          return 'Search';
      }
    },
    selectTypeOption(typeOption) {
      this.keyword = '';
      this.selectedPaymentTypeOption = {};
      this.selectedTypeOption = typeOption;
      this.emitChange();
    },
    selectPaymentTypeOption(typeOption) {
      this.keyword = '';
      this.selectedTypeOption = {};
      this.selectedPaymentTypeOption = typeOption;
      this.emitChange();
    },
    selectAllTypes() {
      this.selectedPaymentTypeOption = {};
      this.selectedTypeOption = {};
      this.emitChange();
    },
    getSelectedLabel() {
      if (Object.keys(this.selectedTypeOption).length === 0) {
        if (this.elementType === 'Orders') {
          return 'All Order Statuses';
        }

        return this.allTypesLabel;
      }

      return this.selectedTypeOption.label;
    },
    getSelectedPaymentLabel() {
      if (Object.keys(this.selectedPaymentTypeOption).length === 0) {
        return 'All Payment Statuses';
      }

      return this.selectedPaymentTypeOption.label;
    },
    sort: function(col) {
      if (col === this.sort_by) {
        this.sort_direction = this.sort_direction === 'asc' ? 'desc' : 'asc';
      }

      this.sort_by = col;
    },
    prevPage: function() {
      if (this.current_page > 1) this.current_page--;
    },
    nextPage: function() {
      if ((this.current_page * this.page_size) < this.elements.length) this.current_page++;
    },
    resetPage: function() {
      this.current_page = 1;
    },
    showLoader: function() {
      const loaders = document.getElementsByClassName('commerce-insights-ajax-loader');

      if (loaders.length) {
        for (let loader of loaders) {
          loader.classList.remove('hidden');
        }
      }
    },
    hideLoader: function() {
      const loaders = document.getElementsByClassName('commerce-insights-ajax-loader');

      if (loaders.length) {
        for (let loader of loaders) {
          loader.classList.add('hidden');
        }
      }
    },
    emitChange() {
      this.$emit('filtersChanged', {
        filters: {
          keyword: this.keyword.length > 2 ? this.keyword : '',
          orderType: this.selectedTypeOption?.value,
          paymentType: this.selectedPaymentTypeOption?.value,
        }
      });
    },
    emitKeywordChange() {
      if (this.keyword.length > 2) {
        this.previousKeywordLen = this.keyword.length;
        this.emitChange();
      } else {
        if (this.previousKeywordLen > 2) {
          this.previousKeywordLen = 0;
          this.emitChange();
        }
      }
    },
  },
  computed: {
    filteredElements() {
      const self = this;
      let filteredElements = this.elements;

      this.showLoader();

      if (this.elementType === 'Products' || this.elementType === 'Sales') {
        if (this.keyword.length > 2) {
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
            return element.type.title === self.selectedPaymentTypeOption.value;
          });
        }
      } else if (this.elementType === 'Orders') {
        if (this.keyword.length > 2) {
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
        if (this.keyword.length > 2) {
          // match email and name against keyword
          filteredElements = filteredElements.filter(function(element) {
            const lowerKeyword = self.keyword.toLowerCase();

            return element.email.toLowerCase().indexOf(lowerKeyword) > -1 ||
              element.billingName.toLowerCase().indexOf(lowerKeyword) > -1 ||
              element.shippingName.toLowerCase().indexOf(lowerKeyword) > -1;
          });
        }
      }

      filteredElements = filteredElements.sort((a, b) => {
        let a_sort_by = a[this.sort_by];
        let b_sort_by = b[this.sort_by];

        if (typeof a_sort_by === 'string') {
          a_sort_by = a_sort_by.toLowerCase();
          b_sort_by = b_sort_by.toLowerCase();

          switch (this.sort_by) {
            case 'amountPaid':
            case 'aov':
            case 'merchTotal':
            case 'tax':
            case 'discount':
            case 'shipping':
            case 'sales':
              a_sort_by = a_sort_by.replace(/[^\d.-]/g, '');
              b_sort_by = b_sort_by.replace(/[^\d.-]/g, '');
              break;
            default:
              if (this.sort_direction === 'desc') {
                if (a_sort_by < b_sort_by) {
                  return 1;
                }
                if (a_sort_by > b_sort_by) {
                  return -1;
                }
              } else {
                if (a_sort_by < b_sort_by) {
                  return -1;
                }
                if (a_sort_by > b_sort_by) {
                  return 1;
                }
              }

              return 0;
          }
        }

        return this.sort_direction === 'desc' ? b_sort_by - a_sort_by : a_sort_by - b_sort_by;
      });

      filteredElements = filteredElements.filter((row, idx) => {
        let start = (this.current_page - 1) * this.page_size;
        let end = this.current_page * this.page_size;

        if (idx >= start && idx < end) return true;
      });

      this.hideLoader();
      return filteredElements;
    },
    csvElements() {
      const self = this;
      let results = [];
      let filteredElements = self.elements;

      if (this.elementType === 'Products' || this.elementType === 'Sales') {
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

      if (this.elementType === 'Products' || this.elementType === 'Sales') {
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
  <div class="commerce-insights-combined-search w-full mt-6">
    <div>
      <div class="commerce-insights-combined-search-bar toolbar layout-flex">
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

          <div v-if="elementType === 'Orders'">
            <div class="btn menubtn">{{ getSelectedPaymentLabel() }}</div>
            <div class="menu">
              <ul class="padded">
                <li>
                  <a
                    :class='{ "sel": Object.keys(selectedPaymentTypeOption).length === 0 }'
                    @click="selectAllTypes()"
                  >
                    All Payment Statuses
                  </a>
                </li>
                <li>
                  <a
                    :class="{ 'sel': selectedPaymentTypeOption.value === 'Paid' }"
                    @click="selectPaymentTypeOption({label: 'Paid', value: 'Paid'})"
                  >
                    Paid
                  </a>
                </li>
                <li>
                  <a
                    :class="{ 'sel': selectedPaymentTypeOption.value === 'Partial' }"
                    @click="selectPaymentTypeOption({label: 'Partial', value: 'Partial'})"
                  >
                    Partial
                  </a>
                </li>
                <li>
                  <a
                    :class="{ 'sel': selectedPaymentTypeOption.value === 'Unpaid' }"
                    @click="selectPaymentTypeOption({label: 'Unpaid', value: 'Unpaid'})"
                  >
                    Unpaid
                  </a>
                </li>
              </ul>
            </div>
          </div>
          <div class="flex-grow texticon search icon clearable">
            <input
              class="text fullwidth"
              v-model="keyword"
              @input="resetPage(); emitKeywordChange()"
              type="text"
              autocomplete="off"
              :placeholder="getPlaceholder()"
            />
            <div class="clear hidden" title="Clear"></div>
          </div>
          <div class="spinner invisible"></div>
        </div>

        <div class="w-1/2" style="text-align: right">
          <!--<a class="btn submit">Export to CSV</a>-->
          <download-csv :data="csvElements" class="btn submit">
            Export to CSV
          </download-csv>
        </div>
      </div>

      <div class="elements">
        <div class="tableview">
          <table class="data fullwidth">
            <thead>
            <tr v-if="elementType === 'Orders'">
              <th
                scope="col"
                :class="this.sort_by === 'reference' ? 'ordered ' + this.sort_direction : 'orderable'"
                @click="sort('reference')"
              >
                Order #
              </th>
              <th
                scope="col"
                :class="this.sort_by === 'dateStamp' ? 'ordered ' + this.sort_direction : 'orderable'"
                @click="sort('dateStamp')"
              >
                Date Ordered
              </th>
              <th
                scope="col"
                :class="this.sort_by === 'status' ? 'ordered ' + this.sort_direction : 'orderable'"
                @click="sort('status')"
              >
                Status
              </th>
              <th
                scope="col"
                :class="this.sort_by === 'merchTotal' ? 'ordered ' + this.sort_direction : 'orderable'"
                @click="sort('merchTotal')"
              >
                Merchandise Total
              </th>
              <th
                scope="col"
                :class="this.sort_by === 'tax' ? 'ordered ' + this.sort_direction : 'orderable'"
                @click="sort('tax')"
              >
                Tax
              </th>
              <th
                scope="col"
                :class="this.sort_by === 'discount' ? 'ordered ' + this.sort_direction : 'orderable'"
                @click="sort('discount')"
              >
                Discount
              </th>
              <th
                scope="col"
                :class="this.sort_by === 'shipping' ? 'ordered ' + this.sort_direction : 'orderable'"
                @click="sort('shipping')"
              >
                Shipping
              </th>
              <th
                scope="col"
                :class="this.sort_by === 'amountPaid' ? 'ordered ' + this.sort_direction : 'orderable'"
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
                :class="this.sort_by === 'numItems' ? 'ordered ' + this.sort_direction : 'orderable'"
                @click="sort('numItems')"
              >
                Total Items Sold
              </th>
              <th>
                Payment Status
              </th>
            </tr>

            <tr v-if="elementType === 'Sales'">
              <th
                scope="col"
                :class="this.sort_by === 'title' ? 'ordered ' + this.sort_direction : 'orderable'"
                @click="sort('title')"
              >
                Item
              </th>
              <th
                scope="col"
                :class="this.sort_by === 'sku' ? 'ordered ' + this.sort_direction : 'orderable'"
                @click="sort('sku')"
              >
                SKU
              </th>
              <th
                scope="col"
                :class="this.sort_by === 'type' ? 'ordered ' + this.sort_direction : 'orderable'"
                @click="sort('type')"
              >
                Product Type
              </th>
              <th
                scope="col"
                :class="this.sort_by === 'totalSold' ? 'ordered ' + this.sort_direction : 'orderable'"
                @click="sort('totalSold')"
              >
                Total Sold
              </th>
              <th
                scope="col"
                :class="this.sort_by === 'sales' ? 'ordered ' + this.sort_direction : 'orderable'"
                @click="sort('sales')"
              >
                Total Sales
              </th>
            </tr>

            <tr v-if="elementType === 'Products'">
              <th scope="col">Title</th>
              <th scope="col">Product Type</th>
              <th scope="col">Total Sold</th>
              <th scope="col">Total Sales</th>
            </tr>

            <tr v-if="elementType === 'Customers'">
              <th
                scope="col"
                :class="this.sort_by === 'email' ? 'ordered ' + this.sort_direction : 'orderable'"
                @click="sort('email')"
              >
                Email
              </th>
              <th
                scope="col"
                :class="this.sort_by === 'ordersCount' ? 'ordered ' + this.sort_direction : 'orderable'"
                @click="sort('ordersCount')"
              >
                # Orders
              </th>
              <th
                scope="col"
                :class="this.sort_by === 'itemsQty' ? 'ordered ' + this.sort_direction : 'orderable'"
                @click="sort('itemsQty')"
              >
                # Items
              </th>
              <th
                scope="col"
                :class="this.sort_by === 'amountPaid' ? 'ordered ' + this.sort_direction : 'orderable'"
                @click="sort('amountPaid')"
              >
                Total Spent
              </th>
              <th
                scope="col"
                :class="this.sort_by === 'aov' ? 'ordered ' + this.sort_direction : 'orderable'"
                @click="sort('aov')"
              >
                AOV
              </th>
              <th scope="col"
                  :class="this.sort_by === 'firstPurchase' ? 'ordered ' + this.sort_direction : 'orderable'"
                  @click="sort('firstPurchase')"
              >
                First Purchase
              </th>
              <th scope="col"
                  :class="this.sort_by === 'lastPurchase' ? 'ordered ' + this.sort_direction : 'orderable'"
                  @click="sort('lastPurchase')"
              >
                Last Purchase
              </th>
            </tr>
            </thead>

            <tbody v-if="elementType === 'Orders'">
            <tr class="commerce-insights-ajax-loader centeralign">
              <td colspan="9" class="centeralign">
                <div class="spinner loadingmore"></div>
              </td>
            </tr>
            <tr v-for="element in filteredElements" :key="element.id">
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

            <tbody v-if="elementType === 'Sales'">
            <tr class="commerce-insights-ajax-loader centeralign">
              <td colspan="9" class="centeralign">
                <div class="spinner loadingmore"></div>
              </td>
            </tr>
            <tr v-for="element in filteredElements" :key="element.id">
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
                <a :href="'/admin/commerceinsights/orders/product/' + element.id">
                  {{ element.numOrders }} orders
                </a>
              </td>
              <td>{{ element.sales }}</td>
            </tr>
            </tbody>

            <tbody v-if="elementType === 'Products'">
            <tr class="commerce-insights-ajax-loader centeralign">
              <td colspan="9" class="centeralign">
                <div class="spinner loadingmore"></div>
              </td>
            </tr>
            <tr v-for="element in filteredElements" :key="element.id"></tr>
            </tbody>

            <tbody v-if="elementType === 'Customers'">
            <tr class="commerce-insights-ajax-loader centeralign">
              <td colspan="9" class="centeralign">
                <div class="spinner loadingmore"></div>
              </td>
            </tr>
            <tr v-for="element in filteredElements" :key="element.email">
              <td v-if="element.customer">
                <a
                  :href="'/admin/users/' + element.customer.id"
                >
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

          <div style="margin-top: 30px">
            <button @click="prevPage" class="btn">Previous</button>
            <button @click="nextPage" class="btn">Next</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style>
.commerce-insights-combined-search-bar {
  background-color: #f1f5f8;
  border-radius: 5px;
  padding: 0.75rem 0.75rem 0.4rem 0.75rem;
}

.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  border: 0;
}
</style>
