<template>
    <div>
        <div class="period-summary">
            <div class="layout-flex w-3/5">
                <div class="w-1/2">
                    <h2 class="text-tan text-2xl font-normal">
                        <small class="block text-xs text-black font-normal pb-1">{{ currentSummary }}</small>
                        {{ currentValue }}
                    </h2>
                </div>
                <div class="w-1/2">
                    <h2 class="text-gray-600 text-2xl font-normal">
                        <small class="block text-xs text-black font-normal pb-1">{{ previousSummary }}</small>
                        {{ previousValue }}
                    </h2>
                </div>
            </div>
        </div>
        <div class="relative w-full h-64 pt-4">
            <chart type="line" v-bind:chartData="chartData" v-bind:fallbackHeight="120" is-date-series-overlay />
        </div>
    </div>
</template>

<style module>
.period-summary {
    display: flex;
    padding-top: 0.5rem;
    padding-bottom: 0.5rem;
}
</style>

<script>
import Pane from './Pane.vue';
import Chart from './Chart.vue';
import styles from '../css/common.module.css';

export default {
    name: 'line-chart-panel',
    components: {
        Pane,
        Chart
    },
    props: {
        currentSummary: {
            type: String,
            default: `Current Period`,
        },
        previousSummary: {
            type: String,
            default: `Previous Period`,
        },
        currentValue: String,
        previousValue: String,
        currentData: {
            type: Array,
            default: function() {
                return [];
            }
        },
        previousData: {
            type: Array,
            default: function() {
                return [];
            }
        },
        flushTop: {
            type: Boolean,
            default: false
        }
    },
    data() {
        return {
            chartData: this.formatChartData(), // data formatted for Chart.js
        }
    },
    methods: {
        getLabels() {
            return [];
        },
        formatChartData() {
            return {
                labels: this.getLabels(),
                datasets: [
                    { data: this.currentData },
                    { data: this.previousData }
                ]
            }
        }
    },
    watch: {
        currentData: function(newValue, oldValue) {
            this.chartData = this.formatChartData();
        },
        previousData: function(newValue, oldValue) {
            this.chartData = this.formatChartData();
        },
    }
}
</script>
