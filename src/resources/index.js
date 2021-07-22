import BarChartPanel from './components/BarChartPanel.vue';
import Chart from './components/Chart.vue';
import CombinedSearch from './components/CombinedSearch.vue';
import DateRangePicker from './components/DateRangePicker.vue';
import DonutPanel from './components/DonutPanel.vue';
import LineChartPanel from './components/LineChartPanel.vue';
import MiniSparklinePanel from './components/MiniSparklinePanel.vue';
import Pane from './components/Pane.vue';
import SparklinePanel from './components/SparklinePanel.vue';
import SummaryDatapoint from './components/SummaryDatapoint.vue';
import Tab from './components/Tab.vue';
import TabbedPanes from './components/TabbedPanes.vue';
import Vue from 'vue';
import axios from 'axios';
import moment from 'moment';
import qs from 'qs';

const app = new Vue({
    el: '#main',
    delimiters: ['${', '}'],
    components: {
        'bar-chart-panel': BarChartPanel,
        'combined-search': CombinedSearch,
        'chart': Chart,
        'date-range-picker': DateRangePicker,
        'donut-panel': DonutPanel,
        'line-chart-panel': LineChartPanel,
        'mini-sparkline-panel': MiniSparklinePanel,
        'pane': Pane,
        'sparkline-panel': SparklinePanel,
        'summary-datapoint': SummaryDatapoint,
        'tab': Tab,
        'tabbed-panes': TabbedPanes
    },
    data() {
        return {
            dateRange: {
                start: moment().subtract(7, 'days').format('YYYY-MM-DD 00:00:00'),
                end: moment().format('YYYY-MM-DD 23:59:59'),
            },
            stats: {
                "orders": {
                    "summary": {},
                    "topLocations": [],
                    "totalOrders": {},
                    "averageValue": {},
                    "averageQuantity": {},
                    "totalCustomers": {},
                    "newCustomers": {},
                    "returningCustomers": {}
                },
                "products": {
                    "summary": {},
                    "mostPurchased": {},
                    "mostProfitable": []
                },
                "customers": {
                    "summary": {}
                }
            },
            orders: [],
            products: [],
            sales: [],
            customers: []
        }
    },
    methods: {
        getUrlParam(param) {
            const url = window.location.href;
            const name = param.replace(/[\[\]]/g, '\\$&');
            const regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)');
            const res = regex.exec(url);

            if (!res) return null;
            if (!res[2]) return '';
            return !res ? null : !res[2] ? '' : res[2];
        },
        handleDateChange(dates) {
            const start = moment(dates.start).format('YYYY-MM-DD 00:00:00');
            const end = moment(dates.end).format('YYYY-MM-DD 23:59:59');
            const loaders = document.getElementsByClassName('commerce-insights-ajax-loader');

            if(loaders.length) {
                for(let loader of loaders) {
                    loader.classList.remove('hidden');
                }
            }

            this.dateRange.start = start;
            this.dateRange.end = end;

            this.refreshAllData();
        },
        refreshAllData() {
            const page_url = window.location.href;
            let page = page_url.substr(page_url.lastIndexOf('/') + 1);
            const idx = page.indexOf('?');

            page = page.substring(0, idx !== -1 ? idx : page.length);

            this.fetchStats();

            switch(page) {
                case 'orders':
                    this.fetchOrders();
                break;
                case 'sales':
                    this.fetchSales();
                break;
                case 'products':
                    this.fetchProducts();
                break;
                case 'customers':
                    this.fetchCustomers();
                break;
                default:
                    if(isNaN(page)) {
                        this.fetchOrders();
                        this.fetchSales();
                        this.fetchProducts();
                        this.fetchCustomers();
                    } else {
                        this.fetchItemOrders(page);
                    }
            }
        },
        fetchStats() {
            const self = this;
            const data = {
                range_start: self.dateRange.start,
                range_end: self.dateRange.end
            };

            data[Craft.csrfTokenName] = Craft.csrfTokenValue;

            axios.post('/actions/commerceinsights/vue/get-stats', qs.stringify(data))
            .then(response => {
                self.stats = response.data;
            })
            .catch(error => {
                console.log(error);
            });
        },
        fetchOrders() {
            const self = this;
            const data = {
                range_start: self.dateRange.start,
                range_end: self.dateRange.end
            };
            let url = '/actions/commerceinsights/vue/get-orders';

            data[Craft.csrfTokenName] = Craft.csrfTokenValue;

            axios.post(url, qs.stringify(data))
            .then(response => {
                self.orders = response.data;
            })
            .catch(error => {
                console.log(error);
            });
        },
        fetchItemOrders(item) {
            const self = this;
            const variant = self.getUrlParam('variant');
            const variant_type = self.getUrlParam('variant_type');
            const color = self.getUrlParam('color');
            const start = self.getUrlParam('startDate');
            const end = self.getUrlParam('endDate');
            const data = {
                range_start: self.dateRange.start,
                range_end: self.dateRange.end
            };
            let url = '/admin/commerceinsights/orders/product/' + item;

            if(variant || variant_type || color || start || end) url += '?';

            if(start) {
                url += `startDate=${start}`;
            }

            if(end) {
                url += start ? `&endDate=${end}` : `endDate=${end}`;
            }

            if(variant) {
                url += start || end ? `&variant=${variant}` : `variant=${variant}`;
            }

            if(variant_type) {
                url += start || end || variant ? `&variant_type=${variant_type}` : `variant_type=${variant_type}`;
            }

            if(color) {
                url += start || end || variant || variant_type ? `&color=${color}` : `color=${color}`;
            }

            data[Craft.csrfTokenName] = Craft.csrfTokenValue;
            data['render'] = false;
            data['id'] = item;

            axios.post(url, qs.stringify(data))
            .then(response => {
                console.log(response);
                self.orders = response.data;
            })
            .catch(error => {
                console.log(error);
            });
        },
        fetchProducts() {
            const self = this;
            const data = {
                range_start: self.dateRange.start,
                range_end: self.dateRange.end
            };

            data[Craft.csrfTokenName] = Craft.csrfTokenValue;

            axios.post('/actions/commerceinsights/vue/get-products', qs.stringify(data))
            .then(response => {
                self.products = response.data;
            })
            .catch(error => {
                console.log(error);
            });
        },
        fetchCustomers() {
            const self = this;
            const data = {
                range_start: self.dateRange.start,
                range_end: self.dateRange.end
            };

            data[Craft.csrfTokenName] = Craft.csrfTokenValue;

            axios.post('/actions/commerceinsights/vue/get-customers', qs.stringify(data))
            .then(response => {
                self.customers = response.data;
            })
            .catch(error => {
                console.log(error);
            });
        }
    }
});
