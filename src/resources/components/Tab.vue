<script>
	import styles from '../css/common.module.css';

	export default {
		name: 'tab',
		props: {
			title: String,
			trend: String,
			positiveTrend: Boolean,
		},
		data() {
			return {
				active: false,
			};
		},
		methods: {
			handleClick() {
				this.$parent.$emit('select', this);
			},
			select() {
				this.active = true;
			},
			deselect() {
				this.active = false;
			},
		},
	};
</script>

<template>
	<div v-cloak class="panel w-full" :class="{ 'hidden': ! active }">
		<slot></slot>
	</div>
</template>
