<script>
import Vue from 'vue';
import styles from '../css/common.css';
import moment from 'moment';
import CombinedSearchBar from '../components/CombinedSearchBar.vue';
import OrdersTable from '../components/OrdersTable.vue';
import ItemsSoldTable from '../components/ItemsSoldTable.vue';
import CustomersTable from '../components/CustomersTable.vue';
import Pagination from '../components/Pagination.vue';

export default {
  name: 'combined-search',
  components: {
    CombinedSearchBar,
    OrdersTable,
    ItemsSoldTable,
    CustomersTable,
    Pagination,
  },
  props: {
    elements: {
      type: Object | Array,
      default: () => [],
    },
    elementType: {
      type: String,
      required: true,
      default: '',
    },
    type: {
      type: String,
      default: '',
    },
    typeOptions: {
      type: Array,
      default: () => [],
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
      sortBy: '',
      sortDirection: 'desc',
      keyword: '',
      previousKeywordLen: 0,
      page_size: 0,
      current_page: 0,
      paged_elements: [],
      reset_page: false,
      selectedPaymentTypeOption: {},
      selectedTypeOption: {},
    };
  },
  mounted() {
    this.getSortBy();
  },
  methods: {
    getSortBy() {
      switch (this.elementType) {
        case 'Orders':
          this.sortBy = 'dateStamp';
          break;
        case 'ItemsSold':
          this.sortBy = 'totalSold';
          break;
        case 'Products':
          this.sortBy = 'title';
          break;
        case 'Customers':
          this.sortBy = 'lastPurchase';
          break;
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
    sort(col) {
      if (col === this.sortBy) {
        this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
      }

      this.sortBy = col;
    },
    showLoader() {
      const loaders = document.getElementsByClassName('commerce-insights-ajax-loader');

      if (loaders.length) {
        for (let loader of loaders) {
          loader.classList.remove('hidden');
        }
      }
    },
    hideLoader() {
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
    keywordChange(keyword) {
      this.keyword = keyword;
      this.emitKeywordChange();
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
    paginationLoaded(pageData) {
      this.page_size = pageData.pageSize;
      this.current_page = pageData.currentPage;
    },
    changePage(currentPage) {
      this.reset_page = false;
      this.current_page = currentPage;
    },
    resetPage() {
      if (this.keyword.length > 2) {
        this.reset_page = true;
      }
    },
  },
  computed: {
    filteredElements() {
      const self = this;
      let filteredElements = this.elements;

      this.showLoader();

      if (this.elementType === 'Products' || this.elementType === 'ItemsSold') {
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
        let a_sortBy = a[this.sortBy];
        let b_sortBy = b[this.sortBy];

        if (typeof a_sortBy === 'string') {
          a_sortBy = a_sortBy.toLowerCase();
          b_sortBy = b_sortBy.toLowerCase();

          switch (this.sortBy) {
            case 'amountPaid':
            case 'aov':
            case 'merchTotal':
            case 'tax':
            case 'discount':
            case 'shipping':
            case 'sales':
              a_sortBy = a_sortBy.replace(/[^\d.-]/g, '');
              b_sortBy = b_sortBy.replace(/[^\d.-]/g, '');
              break;
            default:
              if (this.sortDirection === 'desc') {
                if (a_sortBy < b_sortBy) {
                  return 1;
                }
                if (a_sortBy > b_sortBy) {
                  return -1;
                }
              } else {
                if (a_sortBy < b_sortBy) {
                  return -1;
                }
                if (a_sortBy > b_sortBy) {
                  return 1;
                }
              }

              return 0;
          }
        }

        return this.sortDirection === 'desc' ? b_sortBy - a_sortBy : a_sortBy - b_sortBy;
      });

      filteredElements = filteredElements.filter((row, idx) => {
        let start = (this.current_page - 1) * this.page_size;
        let end = this.current_page * this.page_size;

        if (idx >= start && idx < end) return true;
      });

      if (this.keyword.length > 2 || Object.keys(this.selectedTypeOption).length !== 0 || Object.keys(this.selectedPaymentTypeOption).length !== 0) {
        this.paged_elements = filteredElements;
      } else {
        this.paged_elements = this.elements;
      }

      this.hideLoader();
      return filteredElements;
    },
  },
};
</script>

<template>
  <div class="commerce-insights-combined-search w-full mt-6">
    <div>
      <CombinedSearchBar
        :element-type="elementType"
        :elements="elements"
        :all-types-label="allTypesLabel"
        :type-options="typeOptions"
        :selected-payment-type-option="selectedPaymentTypeOption"
        :selected-type-option="selectedTypeOption"
        @select-all-types="selectAllTypes"
        @select-type-option="selectTypeOption"
        @select-payment-option="selectPaymentTypeOption"
        @keyword-change="keywordChange"
      ></CombinedSearchBar>

      <div class="elements">
        <div class="tableview">
          <OrdersTable
            v-if="elementType === 'Orders'"
            :sort-by="sortBy"
            :sort-direction="sortDirection"
            :product-query="productQuery"
            :elements="filteredElements"
            @sort-table="sort"
          ></OrdersTable>

          <ItemsSoldTable
            v-if="elementType === 'ItemsSold'"
            :sort-by="sortBy"
            :sort-direction="sortDirection"
            :elements="filteredElements"
            @sort-table="sort"
          ></ItemsSoldTable>

          <CustomersTable
            v-if="elementType === 'Customers'"
            :sort-by="sortBy"
            :sort-direction="sortDirection"
            :elements="filteredElements"
            @sort-table="sort"
          ></CustomersTable>

          <Pagination
            v-if="paged_elements.length"
            :num-elements="paged_elements.length"
            :reset="reset_page"
            @pagination-loaded="paginationLoaded"
            @change-page="changePage"
          />
        </div>
      </div>
    </div>
  </div>
</template>
