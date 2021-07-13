<script>
	import Chart from 'chart.js/auto';
	import moment from 'moment';
	import styles from '../css/common.css';

	export default {
		name: 'chart',
		props: {
			type: String, // `bar`, `donut`, `line`, `sparkline` or `muted-sparkline`
			chartData: Object, // required, passed to Chart.js `data`
			padding: {
				type: Number,
				default: 0,
			},
			aspectRatio: {
				type: String,
				default: 'auto', // `auto` or `square`
			},
			fallbackHeight: {
				type: Number,
				default: 0,
			},
			flatBackgroundColors: {
				type: Array,
				default: function() {
					return [
						'#35404d',
						'#d3a87a',
						'#ebcaa5',
						'#337187',
						'#dbcec1',
						'#798799',
						'#7bbcd4',
						'#5a7699',
						'#7b99b0',
						'#64727d',
						'#31383d',
					];
				},
			},
			gradientBackgroundColors: {
				type: Array,
				default: function() {
					return [
						['#4e5a6a', '#1e2732'],
						['#f0d4b4', '#d3a87a'],
						['#cbe7fd', '#a6d8ff'],
						['#c1c4c8', '#8d9297'],
						['#f7d5ad', '#ebcaa5'],
						['#f6ebe0', '#dbcec1'],
						['#5ca4be', '#337187'],
						['#959fa8', '#64727d'],
						['#5b6972', '#31383d'],
						['#869ab4', '#5a7699'],
					];
				},
			},
			softFadeBackgroundColors: {
				type: Array,
				default: function() {
					return ['rgba(211, 167, 138, 0.55)', 'rgba(211, 167, 138, 0)'];
				},
			},
			/**
			 * When true, assumes two chartData series, each node with
			 * individual x and y values, where the series are meant to be overlaid
			 * in the same view rather than plotted on a linear time scale.
			 * a) combines labels for overlay
			 * b) formats tooltips to prepend date to each value
			 * c) formats x axis ticks with minimal month/day
			 */
			isDateSeriesOverlay: {
				type: Boolean,
				default: false,
			},
			yAxisIsCurrency: {
				type: Boolean,
				default: true,
			},
		},
		data() {
			return {
				chart: null, // chart instance
				fontFamily: `system-ui, BlinkMacSystemFont, -apple-system, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', 'Fira Sans', 'Droid Sans', 'Helvetica Neue', sans-serif`,
				ticks: {
					beginAtZero: true,
					fontSize: 10,
					fontColor: '#8c99a7',
					fontFamily: `system-ui, BlinkMacSystemFont, -apple-system, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', 'Fira Sans', 'Droid Sans', 'Helvetica Neue', sans-serif`,
				},
			};
		},
		mounted() {
			// draw the chart
			this.chart = new Chart(this.getChartContext(), {
				type: this.getChartType(),
				data: this.getChartData(),
				options: this.getChartOptions(),
			});
		},
		methods: {
			/**
			 * Preps an array of drawn gradients to be used for chart element backgrounds.
			 * Uses colors in `gradientBackgroundColors`.
			 */
			getGradients(percentHeight = 1) {
				const ctx = this.getChartContext();
				const drawHeight = this.getDrawHeight(percentHeight);

				let gradients = [];

				for (let index = 0; index < this.gradientBackgroundColors.length; index++) {
					const colors = this.gradientBackgroundColors[index];
					const gradient = ctx.createLinearGradient(0, 0, 0, drawHeight);
					gradient.addColorStop(0, colors[0]);
					gradient.addColorStop(1, colors[1]);
					gradients.push(gradient);
				}

				return gradients;
			},

			/**
			 * Gets the chart canvas 2D context for drawing.
			 */
			getChartContext() {
				return this.$refs.chart.getContext('2d');
			},

			/**
			 * Gets the effective height the chart should be drawn at, falling back to
			 * an optional value if the canvas doesn't have a height.
			 */
			getDrawHeight(percentHeight = 1) {
				let drawHeight = this.$refs.chart.clientHeight;

				if (drawHeight === 0 && this.fallbackHeight > 0) {
					drawHeight = this.fallbackHeight;
				}

				return drawHeight * percentHeight;
			},

			/**
			 * Draw a linear gradient to be used as a background for a chart element,
			 * using the two colors defined for a soft fade.
			 */
			getSoftFadeBackground(percentHeight = 1) {
				const drawHeight = this.getDrawHeight(percentHeight);
				const gradient = this.getChartContext().createLinearGradient(0, 0, 0, drawHeight);

				gradient.addColorStop(0, this.softFadeBackgroundColors[0]);
				gradient.addColorStop(1, this.softFadeBackgroundColors[1]);

				return gradient;
			},

			/**
			 * Returns the type of Chart.js for our custom-named chart type.
			 */
			getChartType() {
				switch (this.type) {
					case 'bar':
						return 'bar';
						break;
					case 'donut':
						return 'pie';
						break;
					case 'line':
					case 'sparkline':
					case 'muted-sparkline':
						return 'line';
						break;
				}
			},

			/**
			 * Conditionally appends styles to our expected custom chart type datasets
			 * when certain properties (per chart) are either missing or set to `auto`.
			 *
			 * This makes it possible to have consistent default styling for our chart
			 * types, though it's still possible to override in child components.
			 */
			getChartData() {
				if (this.chartData.datasets) {
					if (this.type === 'bar') {
						// if we have a bar chart, add dataset backgroundColor if missing or `auto`
						this.chartData.datasets.forEach(function(dataset) {
							this.styleDataset(dataset, {
								backgroundColor: this.getGradients(),
							});
						}, this);
					} else if (this.type === 'line') {
						// if we have a line chart, set styles that are missing or `auto`
						for (let index = 0; index < this.chartData.datasets.length; index++) {
							const dataset = this.chartData.datasets[index];
							const isFirst = index === 0;
							const isSecond = index === 1;

							if (isFirst) {
								// first (current) set should be filled with a soft gradient
								this.styleDataset(dataset, {
									backgroundColor: this.getSoftFadeBackground(1.5),
								});
							} else if (isSecond) {
								// second has no fill, but a dashed line
								this.styleDataset(dataset, {
									backgroundColor: 'transparent',
									borderColor: 'rgba(0,0,0,0.4)',
									borderWidth: 1,
									borderDash: [2, 2],
								});
							}
						}

						// reformat dates for data overlay
						if (this.isDateSeriesOverlay) {
							this.reformatLabelsForOverlay();
						}

					} else if (this.type === 'donut') {
						// if we have a donut chart, set dataset backgroundColors if missing or auto
						this.chartData.datasets.forEach(function(dataset) {
							this.styleDataset(dataset, {
								backgroundColor: this.flatBackgroundColors,
							});
						}, this);
					} else if (this.type === 'sparkline') {
						// if we have a sparkline chart, set dataset bakgroundColor if missing or auto
						for (let index = 0; index < this.chartData.datasets.length; index++) {
							const dataset = this.chartData.datasets[index];

							if (typeof dataset.backgroundColor === 'undefined' || dataset.backgroundColor ===
									'auto') {
								dataset.backgroundColor = this.getSoftFadeBackground(0.5);
							}
						}
					} else if (this.type === 'muted-sparkline') {
						// if we have a muted-sparkline chart, set dataset bakgroundColor if missing or auto
						this.chartData.datasets.forEach(function(dataset) {
							this.styleDataset(dataset, {
								backgroundColor: 'rgba(211, 167, 138, 0.05)',
							});
						}, this);
					}
				}

				return this.chartData;
			},

			/**
			 * Returns a configuration object for the desired chart type.
			 * https://www.chartjs.org/docs/latest/configuration/
			 */
			getChartOptions() {
				const self = this;

				if (this.type === 'bar') {
					return {
						//aspectRatio: 1 / 1.025,
						aspectRatio: this.getAspectRatio(),
						responsive: true,
						legend: {display: false},
						tooltips: {enabled: false},
						scales: {
							yAxes: [
								{
									display: true,
									gridLines: {
										drawBorder: false,
										borderDash: [2, 2],
										lineWidth: 1,
										color: 'rgba(233,238,242,1)',
										zeroLineColor: 'rgba(233,238,242,1)',
										zeroLineWidth: 1,
									},
									ticks: this.ticks,
								}],
							xAxes: [
								{
									display: false,
									barPercentage: 1,
									categoryPercentage: 0.9,
									gridLines: {drawBorder: false},
								}],
						},
					};
				} else if (this.type === 'donut') {
					return {
						cutoutPercentage: 50,
						responsive: true,
						//aspectRatio: 1 / 1,
						aspectRatio: this.getAspectRatio(),
						legend: {display: false},
						elements: {
							arc: [],
						},
						tooltips: {
							enabled: true,
							// use centered labels if there's room around the donut
							yAlign: this.padding >= 25 ? 'bottom' : null,
							xAlign: this.padding >= 25 ? 'center' : null,
							titleAlign: 'center',
							bodyAlign: 'center',
							xPadding: 14,
							yPadding: 10,
							cornerRadius: 5,
							titleFontSize: 11,
							bodyFontSize: 11,
							footerFontSize: 11,
							backgroundColor: '#1e2732',
							titleFontFamily: this.fontFamily,
							bodyFontFamily: this.fontFamily,
							displayColors: false,
							callbacks: {
								title: function(item) {
									const currentItem = item[0];
									const itemLabel = this._data.labels[currentItem.index];
									return itemLabel;
								},
								label: function(item) {
									const itemValue = this._data.datasets[item.datasetIndex].data[item.index];
									return self.formatCurrencyValue(itemValue);
								},
							},
						},
						scales: {
							yAxes: [{display: false}],
							xAxes: [{display: false}],
						},
						layout: {
							padding: {
								left: this.padding,
								right: this.padding,
								top: this.padding,
								bottom: this.padding,
							},
						},
					};
				} else if (this.type === 'line') {
					let options = {
						responsive: true,
						aspectRatio: 3,
						//aspectRatio: this.getAspectRatio(),
						legend: {display: false},
						elements: {
							line: {
								borderColor: '#d3a87a',
								borderWidth: 2,
							},
							point: {
								radius: 0,
								hoverRadius: 5,
								hitRadius: 10,
							},
						},
						tooltips: {
							enabled: true,
							mode: 'x',
							titleAlign: 'center',
							bodyAlign: 'center',
							xPadding: 14,
							yPadding: 10,
							cornerRadius: 5,
							titleFontSize: 11,
							bodyFontSize: 11,
							footerFontSize: 11,
							backgroundColor: '#1e2732',
							titleFontFamily: this.fontFamily,
							bodyFontFamily: this.fontFamily,
							displayColors: false,
							callbacks: {},
						},
						scales: {
							yAxes: [
								{
									display: true,
									gridLines: {
										drawBorder: false,
										borderDash: [2, 2],
										lineWidth: 1,
										color: 'rgba(233,238,242,1)',
										zeroLineColor: 'rgba(233,238,242,1)',
										zeroLineWidth: 1,
									},
									ticks: this.ticks,
								}],
							xAxes: [
								{
									display: true,
									gridLines: {
										lineWidth: 0,
										drawBorder: false,
									},
									ticks: this.ticks,
								}],
						},
					};

					if (this.isDateSeriesOverlay) {
						//options.scales.xAxes[0].type = 'time';

						if (this.yAxisIsCurrency) {
							// format y axis as currency
							options.scales.yAxes[0].ticks.callback = function(value, index, values) {
								if (typeof value == 'number') {
									return self.formatCurrencyValue(value);
								}

								return value;
							};

							/**
							 * Take a combined label like `2019-07-13#2018-07-13`, get the first date,
							 * and reduce it to a concise month and day: `7/13`. Used in tooltip titles
							 * and x axis ticks.
							 */
							options.scales.xAxes[0].ticks.callback = function(value, index, values) {
								return self.getFormattedDateFromCombinedLabel(value);
							};

							/**
							 * Remove tooltip's title.
							 */
							options.tooltips.callbacks.title = function(item) {
								return '';
							};

							/**
							 * Prepend date for each tooltip value.
							 */
							options.tooltips.callbacks.label = function(item, data) {
								if (item.datasetIndex === 0) {
									return `${item.label}: ${self.formatCurrencyValue(item.value)}`;
								}

								const label = data.labels[item.index];
								const dateLabel = self.getFormattedDateFromCombinedLabel(label, 1);

								return `${dateLabel}: ${self.formatCurrencyValue(item.value)}`;
							};
						}
					}

					return options;
				} else if (this.type === 'sparkline') {
					return {
						responsive: true,
						animation: false,
						aspectRatio: this.getAspectRatio(),
						legend: {display: false},
						elements: {
							line: {
								borderColor: '#d3a87a',
								borderWidth: 2,
							},
							point: {radius: 0},
						},
						tooltips: {enabled: false},
						scales: {
							yAxes: [
								{
									display: false,
									ticks: {beginAtZero: true},
								}],
							xAxes: [{display: false}],
						},
						layout: {
							padding: {top: 2},
						},
					};
				} else if (this.type === 'muted-sparkline') {
					return {
						responsive: true,
						//animation: false,
						//aspectRatio: this.getAspectRatio(),
						legend: {display: false},
						elements: {
							line: {
								borderColor: 'rgba(211, 167, 138, 0.2)',
								borderWidth: 2,
							},
							point: {
								radius: 0,
							},
						},
						tooltips: {enabled: false},
						scales: {
							y: {
                  beginAtZero: true,
									display: false,
              },
							x: {
							  display: false
							},
						},
					};
				}
			},

			/**
			 * Calculates an aspect ratio based on the provided parent element's dimensions.
			 */
			getAspectRatio() {
				if (this.aspectRatio === 'square') {
					return 1;
				}

				const containerHeight = this.$refs.container.clientHeight;
				const containerWidth = this.$refs.container.clientWidth;

				return containerWidth / containerHeight;
			},

			/**
			 * Returns a formatted month/day from the provided combined label.
			 */
			getFormattedDateFromCombinedLabel(value, index = 0) {
				if (String(value).includes('#')) {
					const firstDate = String(value).split('#')[index];
					const m = moment(firstDate);
					// return month and year
					return moment(firstDate).format('M/D');
				}

				return value;
			},

			/**
			 * Renders the chart.
			 */
			renderChart() {
				this.chart.render();
			},

			/**
			 * Formats the provided value as currency.
			 */
			formatCurrencyValue(rawValue) {
				// https://caniuse.com/#feat=internationalization
				const formatter = new Intl.NumberFormat('en-US', {
					style: 'currency',
					currency: 'USD',
					minimumFractionDigits: 0,
				});

				return formatter.format(rawValue);
			},

			/**
			 * Stitches together dataset labels so data series can be visually overlaid.
			 * https://stackoverflow.com/a/48285659/897279
			 */
			reformatLabelsForOverlay() {
				const datasets = this.chartData.datasets;

				if (datasets.length !== 2) {
					// we must have exactly two datasets
					return;
				}

				if (datasets[0].data.length !== datasets[1].data.length) {
					// dataset lengths must match
					return;
				}

				const labelSeparator = '#';
				let labels = [];

				/**
				 * Build joined label set.
				 * Ex: `2019-07-01` and `2019-06-16` series labels become `2019-07-01#2019-06-16`
				 */
				for (let index = 0; index < datasets[0].data.length; index++) {
					const current = datasets[0].data[index].x;
					const previous = datasets[1].data[index].x;

					labels.push(`${current}#${previous}`);
				}

				//console.log(labels);
				this.chartData.labels = labels;
			},

			/**
			 * Sets styleProps on the provided dataset if it either doesn't have each one
			 * or it's explicitly set to `auto`.
			 */
			styleDataset(dataset, styleProps) {
				Object.entries(styleProps).forEach(entry => {
					let key = entry[0];
					let value = entry[1];

					// property is not set, or set to `auto`
					if (typeof dataset[key] === 'undefined' || dataset[key] == 'auto') {
						dataset[key] = value;
					}
				}, this);
			},
		},
		watch: {
			chartData: function(newValue, oldValue) {
				if (this.chartData !== null && this.chartData !== undefined) {

					/**
					 * Only touch nodes that require updating to avoid redrawing the entire chart.
					 */

					const updatedData = this.getChartData();

					if (!Object.is(newValue.labels, oldValue.labels)) {
						//console.log('data labels changed');
						this.chart.data.labels = updatedData.labels;
					}

					if (!Object.is(newValue.datasets, oldValue.datasets)) {
						//console.log('dataset changed');

						// same number of datasets or new length?
						if (newValue.datasets.length === oldValue.datasets.length || oldValue.datasets.length ===
								0 && newValue.datasets.length > 0) {
							//console.log('dataset details changed');

							// loop through and only update chart data and/or labels that changed
							oldValue.datasets.forEach((oldDataset, index) => {
								const newDataset = newValue.datasets[index];

								if (oldDataset.label !== newDataset.label) {
									this.chart.data.datasets[index].label = newDataset.label;
								}

								if (JSON.stringify(oldDataset.data) !== JSON.stringify(newValue.data)) {
									this.chart.data.datasets[index].data = newDataset.data;
								}
							});
						} else {
							// update the node, which will re-render the chart
							//console.log('dataset count changed');
							this.chart.data.datasets = updatedData.datasets;
						}
					}

					this.chart.update();
				}
			},
		},
	};
</script>

<template>
	<div v-cloak ref="container" class="commerce-insights-chart-container w-full h-full relative">
		<canvas ref="chart"></canvas>
	</div>
</template>
