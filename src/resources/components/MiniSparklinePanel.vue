<script>
	import Pane from './Pane.vue';
	import Chart from './Chart.vue';
	import styles from '../css/common.module.css';

	export default {
		name: 'mini-sparkline-panel',
		components: {
			Pane,
			Chart,
		},
		props: {
			title: String,
			trend: String,
			positiveTrend: Boolean,
			value: Number,
			caption: String,
			data: Array,
		},
		data() {
			return {
				chartData: this.formatChartData(), // data formatted for Chart.js
			};
		},
		methods: {
			formatChartData() {
				return {
					labels: this.data,
					datasets: [
						{
							data: this.data,
						},
					],
				};
			},
		},
		watch: {
			data: function(newValue, oldValue) {
				this.chartData = this.formatChartData();
			},
		},
	};
</script>

<template>
	<pane
			:title="title"
			:trend="trend"
			:positive-trend="positiveTrend"
			:pad-title="false"
			flex
			class="h-34"
	>
		<div class="commerce-insights-mini-sparkline-pane flex-grow">
			<div class="commerce-insights-mini-sparkline-pane-content flex-col justify-end flex-grow">
				<h4 class="commerce-insights-mini-sparkline-pane-value flex-grow">{{ value }}</h4>
				<p class="commerce-insights-mini-sparkline-pane-caption self-end">{{ caption }}</p>
			</div>
			<div class="chart-pane-fill-container">
				<chart type="muted-sparkline" :chartData="chartData" class="w-full h-full"/>
			</div>
		</div>
	</pane>
</template>

<style module>
	.commerce-insights-mini-sparkline-pane {
		display: flex;
		width: 100%;
		position: relative;
	}

	.commerce-insights-mini-sparkline-pane-content {
		display: flex;
		width: 100%;
	}

	.commerce-insights-mini-sparkline-pane-value {
		color: #d3a87a;
		font-weight: 300;
		font-size: 1.5rem;
		margin: 0;
		padding: 0;
		width: 100%;
	}

	.commerce-insights-mini-sparkline-pane-caption {
		width: 100%;
		margin: 0;
		padding: 0;
		line-height: 1.25;
		display: flex;
		font-size: 0.75rem;
		color: #a0aec0;
	}

	.chart-pane-fill-container {
		position: absolute;
		z-index: 0;
		bottom: 0;
		left: 0;
		right: 0;
		margin: -16px;
	}
</style>
