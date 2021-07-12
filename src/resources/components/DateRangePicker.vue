<script>
	import DatePicker from './DatePicker.vue';
	import ClickOutside from 'vue-click-outside';
	import styles from '../css/common.module.css';

	import moment from 'moment';
	import axios from 'axios';
	import {stringify} from 'qs';

	export default {
		name: 'date-range-picker',
		components: {
			DatePicker,
		},
		data() {
			return {
				customLabel: 'Custom Date Range',
				saveLabel: 'Save',
				cancelLabel: 'Cancel',
				presetFormLabel: 'Preset Label',
				defaultDateFormat: 'MM/DD/YYYY',
				defaultPresets: {
					'today': {
						'label': 'Today',
						'start': moment().utc().subtract(1, 'day'),
						'end': moment().utc(),
					},
					'yesterday': {
						'label': 'Yesterday',
						'start': moment().utc().subtract(2, 'days'),
						'end': moment().utc().subtract(1, 'day'),
					},
					'last-7': {
						'label': 'Last 7 Days',
						'start': moment().utc().subtract(7, 'days'),
						'end': moment().utc(),
					},
					'last-30': {
						'label': 'Last 30 Days',
						'start': moment().utc().subtract(30, 'days'),
						'end': moment().utc(),
					},
					'quarter': {
						'label': 'This Quarter',
						'start': moment().utc().startOf('quarter'),
						'end': moment().utc(),
					},
					'last-quarter': {
						'label': 'Last Quarter',
						'start': moment().utc().subtract(1, 'quarter'),
						'end': moment().utc().subtract(1, 'quarter').endOf('quarter'),
					},
					'year': {
						'label': 'This Year',
						'start': moment().utc().startOf('year'),
						'end': moment().utc(),
					},
				},
				start: null,
				end: null,
				selectedPreset: {},
				savedPresets: [],
				menuOpen: false,
				saveDialogOpen: false,
				dateRangePicker: null,
				ready: false,
				customPresetLabel: '',
				client: null,
			};
		},
		mounted() {
			this.initComponent();
		},
		methods: {
			getUrlParam(param) {
				const url = window.location.href;
				const name = param.replace(/[\[\]]/g, '\\$&');
				const regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)');
				const res = regex.exec(url);

				if (!res) return null;
				if (!res[2]) return '';
				return !res ? null : !res[2] ? '' : decodeURIComponent(res[2].replace(/\+/g, ' '));
			},
			initComponent() {
				const urlStart = this.getUrlParam('startDate');
				const urlEnd = this.getUrlParam('endDate');

				if (urlStart && urlEnd) {
					this.handleRangeUpdate({'start': urlStart, 'end': urlEnd});
				} else {
					if (document.cookie.indexOf('DateRange=') >= 0) {
						const range = JSON.parse(this.getCookie('DateRange'));

						if (range.preset !== 'false') {
							this.selectPreset(range.preset);
						} else {
							this.handleRangeUpdate(range);
						}
					} else {
						this.selectPreset('last-7');
					}
				}

				this.fetchSavedPresets();
			},
			getCookie(name) {
				const v = document.cookie.match('(^|;) ?' + name + '=([^;]*)(;|$)');
				return v ? v[2] : null;
			},
			getClient() {
				if (!this.client) {
					let headers = {};

					headers[window.Craft.csrfTokenName] = window.Craft.csrfTokenValue;

					const axiosInstance = axios.create({
						headers: headers,
						transformRequest: [
							function(data) {
								return stringify(data);
							},
						],
					});
				}

				return this.client;
			},
			updateRange(preset) {
				const expires = new Date(moment().add(1, 'Y').toDate());
				const start = this.start.format('MM/DD/YYYY 00:00:00');
				const end = this.end.format('MM/DD/YYYY 23:59:59');
				document.cookie = `DateRange={"start":"${start}","end":"${end}","preset":"${preset}"};expires=${expires};path=/`;
			},
			handleRangeUpdate(range, update = true) {
				if (typeof range.start === 'object') {
					this.start = range.start;
				} else {
					this.start = moment(new Date(range.start), this.defaultDateFormat);
				}

				if (typeof range.end === 'object') {
					this.end = range.end;
				} else {
					this.end = moment(new Date(range.end), this.defaultDateFormat);
				}

				this.selectedPreset = this.getPresetFromRange(this.start, this.end);
				this.updateRange(false);
				this.ready = true;

				if (update) {
					this.$emit('change', {
						start: this.start,
						end: this.end,
						preset: this.selectedPreset,
					});
				}
			},
			handleRangeShown(range) {
				this.closeAll();
			},
			handleRangeHidden(range) {

			},
			selectPreset(key) {
				key = (key && key !== 'false') ? key : 'last-30';

				if (this.defaultPresets[key]) {
					this.start = this.defaultPresets[key].start;
					this.end = this.defaultPresets[key].end;
					this.selectedPreset = this.defaultPresets[key];
					this.ready = true;

					this.handleRangeUpdate({'start': this.start, 'end': this.end}, false);
					this.updateRange(key);

					this.$emit('change', {
						start: this.start,
						end: this.end,
						preset: key,
					});
				}
			},
			selectSavedPreset(preset) {
				this.start = moment(preset.start, this.defaultDateFormat);
				this.end = moment(preset.end, this.defaultDateFormat);
				this.selectedPreset = preset;
				this.ready = true;

				this.updateRange();

				this.$emit('change', {
					start: this.start,
					end: this.end,
					preset: preset,
				});
			},
			savePreset() {
				const preset = {
					start: this.start,
					end: this.end,
					label: this.customPresetLabel,
				};

				this.savedPresets.push(preset);

				let data = {};

				// TODO: really save

				axios.post('/actions/commerceinsights/vue/save-preset').then(function(response) {

				}).catch(function(error) {
					console.log(error);
				});

				// select the new preset
				this.selectedPreset = preset;

				// reset the save dialog
				this.saveDialogOpen = false;
				this.customPresetLabel = '';
			},
			deletePreset() {
				// TODO: delete preset
			},
			fetchSavedPresets() {
				const self = this;

				axios.get('/actions/commerceinsights/vue/get-presets').then(function(response) {
					self.savedPresets = response.data;
				}).catch(function(error) {
					console.log(error);
				});
			},
			toggleMenu() {
				this.menuOpen = !this.menuOpen;
			},
			getPresetFromRange(start, end) {
				const startStr = this.normalizeDate(start);
				const endStr = this.normalizeDate(end);

				for (const i in this.presets) {
					const preset = this.presets[i];

					if (
							this.normalizeDate(preset.start) === startStr &&
							this.normalizeDate(preset.end) === endStr
					) {
						return preset;
					}
				}

				return false;
			},
			normalizeDate(date) {
				if (typeof date === 'object') {
					date = date.format(this.defaultDateFormat);
				}

				return date;
			},
			closeAll() {
				this.menuOpen = false;
				this.saveDialogOpen = false;
			},
		},
		computed: {
			selectedLabel() {
				if (this.selectedPreset.label) {
					return this.selectedPreset.label;
				}

				return this.customLabel;
			},
			savedPresetCount() {
				return Object.keys(this.savedPresets).length;
			},
			presets() {
				return {...this.defaultPresets, ...this.savedPresets};
			},
			rangeIsCustom() {
				if (this.start === null || this.end === null) {
					return false;
				}

				const start = this.normalizeDate(this.start);
				const end = this.normalizeDate(this.end);

				for (const preset in this.defaultPresets) {
					const settings = this.defaultPresets[preset];

					if (
							this.normalizeDate(settings.start) === start &&
							this.normalizeDate(settings.end) === end
					) {
						return false;
					}
				}

				for (const preset in this.savedPresets) {
					const settings = this.savedPresets[preset];

					if (
							this.normalizeDate(settings.start) === start &&
							this.normalizeDate(settings.end) === end
					) {
						return false;
					}
				}

				return true;
			},
		},
		directives: {
			ClickOutside,
		},
	};
</script>

<template>
	<div class="commerce-insights-date-range-picker" v-click-outside="closeAll">
		<div class="commerce-insights-menu">
			<div class="inline-block w-4 h-4">
				<svg
					class="w-full h-auto"
					height="14"
					width="13"
					viewBox="0 0 13 14"
					xmlns="http://www.w3.org/2000/svg"
				>
					<path
						d="m3.95652174 1.68h5.08695652v-1.68h1.13043474v1.68h2.2608696c.3125652 0 .5652174.25144.5652174.56v1.12h-13v-1.12c0-.30856.25321739-.56.56521739-.56h2.26086957v-1.68h1.13043478zm-2.82608696 11.2h2.26086957v-2.24h-2.26086957zm0-2.8h2.26086957v-2.24h-2.26086957zm0-2.8h2.26086957v-2.24h-2.26086957zm2.82608696 5.6h2.26086956v-2.24h-2.26086956zm0-2.8h2.26086956v-2.24h-2.26086956zm0-2.8h2.26086956v-2.24h-2.26086956zm2.82608696 5.6h2.26086956v-2.24h-2.26086956zm0-2.8h2.26086956v-2.24h-2.26086956zm0-2.8h2.26086956v-2.24h-2.26086956zm2.82608695 5.6h2.26086955v-2.24h-2.26086955zm0-2.8h2.26086955v-2.24h-2.26086955zm0-2.8h2.26086955v-2.24h-2.26086955zm-9.60869565 6.16v-9.52h13v9.52c0 .30968-.2526522.56-.5652174.56h-11.86956521c-.312 0-.56521739-.25032-.56521739-.56z"
						fill="#1e2732"
						fill-rule="evenodd"
					/>
				</svg>
			</div>
			<div
				class="inline-block leading-none ml-4 cursor-pointer"
				:class="{ 'text-gray-500': rangeIsCustom }"
			>
				{{ selectedLabel }}
			</div>
			<!--<a
				class="btn submit text-xs inline-block ml-4 cursor-pointer"
				v-if="rangeIsCustom"
				v-on:click="saveDialogOpen = ! saveDialogOpen"
			>
				{{ saveLabel }}
			</a>
			<div v-if="saveDialogOpen" id="save-menu" class="modal commerce-insights-save-modal">
				<div class="commerce-insights-save-modal-content">
					<label for="label" class="text-sm font-bold block mb-2">
						{{ presetFormLabel }}
					</label>

					<input
						class="text fullwidth"
						type="text"
						name="label"
						v-model="customPresetLabel"
						maxlength="90"
						autocomplete="off"
						autofocus
					/>
				</div>

				<div class="commerce-insights-save-modal-footer">
					<a
						class="btn"
						v-on:click="saveDialogOpen = false"
					>
						{{ cancelLabel }}
					</a>
					<a
						class="btn submit ml-1"
						v-on:click="savePreset()"
					>
						{{ saveLabel }}
					</a>
				</div>
			</div>-->
			<div v-on:click="toggleMenu" class="toggle-menu">
				<div class="toggle-arrow">
					<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
						<path d="M7.41 7.84L12 12.42l4.59-4.58L18 9.25l-6 6-6-6z"/>
					</svg>
				</div>
				<div v-if="menuOpen" class="menu commerce-insights-presets" style="display: block;">
					<ul role="listbox" aria-hidden="true">
						<li
							v-for="(settings, key) in defaultPresets"
							:key="key"
							class="commerce-insights-preset"
						>
							<a v-on:click="selectPreset(key)">{{ settings.label }}</a>
						</li>
						<li v-if="savedPresetCount" class="mb-1">
							<hr>
							<h6>saved</h6>
						</li>
						<li v-for="preset in savedPresets" :key="preset.label" class="commerce-insights-preset">
							<a v-on:click="selectSavedPreset(preset)">{{ preset.label }}</a>
						</li>
					</ul>
				</div>
			</div>
		</div>
		<div class="commerce-insights-dates">
			<date-picker
				@change="handleRangeUpdate"
				@show="handleRangeShown"
				@hide="handleRangeHidden"
				:start="start"
				:end="end"
				:format="defaultDateFormat"
				:key="ready"
			/>
		</div>
	</div>
</template>

<style module>
	.commerce-insights-date-range-picker {
		padding: 0.5rem 0.5rem 0.5rem 1rem;
		background: #fff;
		width: 500px;
		position: relative;
		border-radius: 0.125rem;
		align-items: center;
		display: flex;
	}

	.commerce-insights-menu {
		align-items: center;
		width: 100%;
		display: flex;
	}

	.commerce-insights-dates {
		align-items: center;
		display: flex;
	}

	.commerce-insights-save-modal {
		position: absolute;
		display: block;
		z-index: 30;
		overflow: visible;
		min-height: 0;
		height: auto;
		width: auto;
		min-width: 300px;
	}

	.commerce-insights-save-modal-content {
		padding: 0.75rem;
	}

	.commerce-insights-save-modal-footer {
		padding: 0.5rem;
		display: block;
		text-align: right;
		border-radius: 0 0 5px 5px;
		background-image: -webkit-gradient(linear, left top, left bottom, from(#ecedef), to(#e9eaec));
		background-image: linear-gradient(#ecedef, #e9eaec);
		-webkit-box-shadow: inset 0 2px 1px -2px rgba(0, 0, 0, 0.2);
		box-shadow: inset 0 2px 1px -2px rgba(0, 0, 0, 0.2);
	}

	.commerce-insights-date-range-field {
		width: 165px;
		border: 0;
		display: inline-block;
		text-align: center;
		z-index: 0;
		padding: 0.5rem;
		background: #edf2f7;
		color: #718096;
		font-size: 0.875rem;
		border-radius: 0.125rem;
	}

	.toggle-menu {
		margin-left: 0.5rem;
		display: inline-block;
		position: relative;
		cursor: pointer;
	}

	.toggle-arrow {
		line-height: 0;
	}

	#save-menu {
		left: 100px;
		top: 3rem;
	}
</style>
