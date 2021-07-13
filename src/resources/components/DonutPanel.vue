<script>
	import Pane from './Pane.vue';
	import Chart from './Chart.vue';
	import styles from '../css/common.css';

	export default {
		name: 'donut-panel',
		components: {
			Pane,
			Chart,
		},
		props: {
			title: String,
			data: Array | Object, // data in a simplified format for this component
			isVertical: {
				type: Boolean,
				default: true,
			},
		},
		data() {
			return {
				backgroundColors: Chart.props.flatBackgroundColors.default(),
				chartData: this.formatChartData(), // data formatted for Chart.js
			};
		},
		methods: {
			getSeriesLabels() {
				let labels = [];

				for (let item in this.data) {
					if (this.data.hasOwnProperty(item)) {
						labels.push(this.data[item].title);
					}
				}

				return labels;
			},
			getSeriesData() {
				let series = [];

				for (let item in this.data) {
					if (this.data.hasOwnProperty(item)) {
						const value = this.data[item].values[1].toString();
						const numeric = Number(value.replace(/[^0-9.-]+/g, ''));
						series.push(numeric);
					}
				}

				return series;
			},
			formatChartData() {
				return {
					labels: this.getSeriesLabels(),
					datasets: [
						{
							data: this.getSeriesData(),
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
	<pane :title="title">
		<div
			class="commerce-insights-donut-pane"
			:style="{ 'min-width: 240px;': isVertical, 'min-width: 320px;': ! isVertical }"
		>
			<div v-if="isVertical">
				<div
					ref="chartWrapper"
					class="commerce-insights-donut-chart-wrapper w-64 max-h-64 max-w-full"
				>
					<chart type="donut"
						:chartData="chartData"
						:padding="25"
						aspectRatio="square"
					/>
				</div>

				<table class="data fullwidth mini-table">
					<tbody>
					<tr v-for="(item, index) in data" :key="item.title">
						<td>
							<div class="color-key" :style="{ 'background': backgroundColors[index] }"></div>
						</td>
						<td>{{ item.title }}</td>
						<td v-for="value in item.values" :key="value">{{ value }}</td>
					</tr>
					</tbody>
				</table>
			</div>
			<div v-if="! isVertical" class="layout-flex w-full">
				<div class="w-1/2">
					<div class="py-3 pr-3">
						<table class="data fullwidth mini-table">
							<tbody>
								<tr v-for="(item, index) in data" :key="item.title">
									<td>
										<div
											class="color-key"
											:style="{ 'background': backgroundColors[index] }"
										></div>
									</td>
									<td>{{ item.title }}</td>
									<td v-for="value in item.values" :key="value">{{ value }}</td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>
				<div class="w-1/2">
					<div ref="chartWrapper" class="mx-auto w-full h-full relative">
						<chart
							type="donut"
							:chartData="chartData"
							:padding="10"
							aspectRatio="square"
						/>
					</div>
				</div>
			</div>
		</div>
	</pane>
</template>

<style>
	.commerce-insights-donut-pane {
		width: 100%;
	}

	.commerce-insights-donut-chart-wrapper {
		margin-left: auto;
		margin-right: auto;
		position: relative;
		margin-bottom: 0.5rem;
	}

	.color-key {
		width: 0.75rem;
		height: 0.75rem;
		border-radius: 9999px;
	}
</style>
