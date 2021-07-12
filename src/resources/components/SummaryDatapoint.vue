<script>
	import styles from '../css/common.module.css';

	export default {
		name: 'summary-datapoint',
		props: {
			changeValue: Number,
			format: {
				type: String,
				default: 'percent',
			},
			upDown: {
				type: Boolean,
				default: false,
			},
			placeholderWidth: {
				type: Number,
				default: 90,
			},
		},
		methods: {
			getTrendPhrase() {
				if (this.changeValue > 0) {
					return this.upDown ? 'up by' : 'increased by';
				} else if (this.changeValue === 0) {
				  return 'unchanged';
        }

				return this.upDown ? 'down by' : 'decreased by';
			},
			getFormattedChangeValue() {
			  let text = this.format === 'percent' ? `${this.getTrendPhrase()} ` : '';

        if (this.format === 'percent' && this.changeValue !== 0) {
          text += `${Math.abs(this.changeValue)}%`;
        }

			  if (this.format === 'orders') {
			    text += `${this.changeValue} ${this.changeValue !== 1 ? 'orders' : 'order'}`;
			    text += ` (${this.getTrendPhrase()}${this.changeValue !== 0 ? ' ' + Math.abs(this.changeValue) + '%' : ''})`;
        }

				return text;
			},
		},
	};
</script>

<template>
	<span
		:class="{
			'placeholder-summary-text': changeValue === undefined || changeValue === NaN,
			'commerce-insights-up': changeValue > 0,
			'commerce-insights-down': changeValue < 0,
			'commerce-insights-unchanged': changeValue === 0,
		}"
		class="commerce-insights-summary-datapoint"
	>
		{{ getFormattedChangeValue() }}
	</span>
</template>

<style module>
	.commerce-insights-summary-datapoint {
		transition: width 0.5s linear;
		display: inline-block;
	}

	.placeholder-summary-text {
		display: inline-block;
		background-color: #f1f5f8;
		color: #f1f5f8;
		border-radius: 3px;
	}
</style>
