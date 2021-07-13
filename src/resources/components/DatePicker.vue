<script>
	import $ from 'jquery';
	import 'daterangepicker/daterangepicker.js';
	import styles from '../css/daterangepicker.css';
	import moment from 'moment';

	export default {
		name: 'date-picker',
		props: ['start', 'end', 'format'],
		data() {
			return {
				picker: $(this.$refs.daterange),
			};
		},
		mounted() {
			const self = this;
			const onDateSelected = (start, end) => {
				this.$emit('change', {
					start: start,
					end: end,
				});
			};

			this.picker = $(this.$refs.daterange).daterangepicker({
				autoUpdateInput: false,
				locale: {cancelLabel: 'Clear'},
				opens: 'left',
				drops: 'down',
				autoApply: true,
				startDate: this.start,
				endDate: this.end,
				maxDate: moment(),
			}, onDateSelected);

			$(this.$refs.daterange).on('cancel.daterangepicker', function(ev, picker) {
				self.$emit('cancel');
			});

			$(this.$refs.daterange).on('apply.daterangepicker', function(ev, picker) {
				self.$emit('apply');
			});

			$(this.$refs.daterange).on('show.daterangepicker', function(ev, picker) {
				self.$emit('show');
			});

			$(this.$refs.daterange).on('hide.daterangepicker', function(ev, picker) {
				self.$emit('hide');
			});
		},
		beforeDestroy() {
			$(this.$el).daterangepicker('hide').daterangepicker('destroy');
		},
		computed: {
			formattedResult() {
				if (!this.start && !this.end) {
					return '';
				}

				return this.start.format(this.format) + ' - ' + this.end.format(this.format);
			},
		},
	};
</script>

<template>
	<input
		type="text"
		ref="daterange"
		name="datefilter"
		:value="formattedResult"
		class="commerce-insights-date-range-field"
	/>
</template>
