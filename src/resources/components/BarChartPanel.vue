<script>
	import Pane from './Pane.vue';
	import Chart from './Chart.vue';
	import styles from '../css/common.module.css';

	export default {
		name: 'bar-chart-panel',
		components: {
			Pane,
			Chart,
		},
		props: {
			title: String,
			data: {
				type: Object, // data in a simplified format for this component
				default: function() {
					return {};
				},
			},
		},
		data() {
			return {
				chartData: this.formatChartData(), // data formatted for Chart.js
			};
		},
		ready() {
		},
		mounted() {
		},
		methods: {
			getCssGradient(index) {
				const gradients = Chart.props.gradientBackgroundColors.default();
				return `linear-gradient(to bottom, ${gradients[index][0]}, ${gradients[index][1]})`;
			},
			getLabels() {
				return Object.keys(this.data);
			},
			getDatasetValues() {
				return Object.values(this.data);
			},
			formatChartData() {
				return {
					labels: this.getLabels(),
					datasets: [
						{
							label: '',
							backgroundColor: 'auto',
							data: this.getDatasetValues(),
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
		<div class="bar-chart-wrapper">
			<div class="bar-chart-legend w-1/3 items-center">
				<ul class="text-xs">
					<li class="my-1" v-for="(label, index) in getLabels()" :key="label">
						<span
							:style="{ 'background': getCssGradient(index) }"
							class="bar-chart-key"
						></span>
						{{ label }}
					</li>
				</ul>
			</div>
			<div class="h-full w-2/3">
				<div class="h-full w-full relative">
					<chart v-if="Object.keys(data).length" type="bar" :chartData="chartData"/>
				</div>
			</div>
		</div>
	</pane>
</template>

<style module>
	.bar-chart-wrapper {
		display: flex;
		width: 350px;
		height: 250px;
	}

	.bar-chart-key {
		width: 10px;
		height: 10px;
		top: 1px;
		display: inline-block;
		position: relative;
		margin-right: 0.25rem;
		border-radius: 9999px;
	}

	.bar-chart-legend {
		display: flex;
	}
</style>
