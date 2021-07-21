<script>
import styles from '../css/common.css';

export default {
  name: 'summary-datapoint',
  props: {
    data: {
      type: Object,
      default: () => {
      },
    },
    number: {
      type: Number,
    },
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
      if (this.data.percentChange > 0) {
        return this.upDown ? 'up by' : 'increased by';
      } else if (this.data.percentChange === 0) {
        return 'unchanged';
      }

      return this.upDown ? 'down by' : 'decreased by';
    },
    getFormattedChangeValue() {
      let text = this.format === 'percent' ? `${this.getTrendPhrase()} ` : '';

      if (this.format === 'percent' && this.data.percentChange !== 0) {
        text += `${Math.abs(this.data.percentChange)}%`;
      }

      if (this.format === 'orders') {
        text += `${this.data.total} ${this.total !== 1 ? 'orders' : 'order'}`;
        text += ` (${this.getTrendPhrase()}${this.data.percentChange !== 0 ?
          ' ' + Math.abs(this.data.percentChange) + '%' :
          ''})`;
      }

      return text;
    },
  },
};
</script>

<template>
	<span
    v-if="data"
    :class="{
			'placeholder-summary-text': data.percentChange === undefined || data.percentChange === NaN,
			'commerce-insights-up': data.percentChange > 0,
			'commerce-insights-down': data.percentChange < 0,
			'commerce-insights-unchanged': data.percentChange === 0,
		}"
    class="commerce-insights-summary-datapoint"
  >
		{{ getFormattedChangeValue() }}
	</span>

  <span
    v-else
    :class="{
      'commerce-insights-up': number > 0,
			'commerce-insights-down': number < 0,
			'commerce-insights-unchanged': number === 0,
    }"
  >
    <span v-if="number > 0">up</span>
    <span v-if="number < 0">down</span>
    <span v-if="number === 0">unchanged</span>
    <span v-if="number !== 0">{{ number }}%</span>
  </span>
</template>

<style>
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
