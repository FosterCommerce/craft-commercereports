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
      type: [Number, String],
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
      if (this.data.percentChange > 0 || this.data.percentChange === 'INF') {
        return this.upDown ? 'up by' : 'increased by';
      } else if (this.data.percentChange === 0) {
        return 'unchanged';
      }

      return this.upDown ? 'down by' : 'decreased by';
    },
    getFormattedChangeValue() {
      let text = this.format === 'percent' ? `${this.getTrendPhrase()} ` : `${this.data.total} `;

      if (this.format === 'percent' && this.data.percentChange !== 0) {
        text += `${(this.data.percentChange === 'INF' ? '∞' : Math.abs(this.data.percentChange))}%`;
      }

      if (this.format === 'orders') {
        text += `${this.data.total !== 1 ? 'orders' : 'order'}`;
        text += ` (${this.getTrendPhrase()}${this.data.percentChange !== 0 ?
          ' ' + (this.data.percentChange === 'INF' ? '∞' : Math.abs(this.data.percentChange))
          + '%' : ''})`;
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
			'commerce-reports-up': data.percentChange > 0 || data.percentChange === 'INF',
			'commerce-reports-down': data.percentChange < 0,
			'commerce-reports-unchanged': data.percentChange === 0,
		}"
    class="commerce-reports-summary-datapoint"
  >
		{{ getFormattedChangeValue() }}
	</span>

  <span
    v-else-if="format === 'number' && number"
    :class="{
      'commerce-reports-up': number > 0 || number === 'INF',
			'commerce-reports-down': number < 0,
			'commerce-reports-unchanged': number === 0,
    }"
  >
    {{ number }}
  </span>

  <span
    v-else
    :class="{
      'commerce-reports-up': number > 0 || number === 'INF',
			'commerce-reports-down': number < 0,
			'commerce-reports-unchanged': number === 0,
    }"
  >
    <span v-if="number > 0 || number === 'INF'">up</span>
    <span v-if="number < 0">down</span>
    <span v-if="number === 0">unchanged</span>
    <span v-if="number !== 0">{{ (number === 'INF' ? '∞' : number) }}%</span>
  </span>
</template>

<style>
.commerce-reports-summary-datapoint {
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
