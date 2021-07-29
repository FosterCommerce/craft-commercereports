<script>
import styles from '../css/common.css';

export default {
  name: 'pane',
  props: {
    // title displayed at top left of pane
    title: String,
    // optional text representation of the trend being shown
    trend: String,
    // whether the trend is up (true) or down (false), which determines color and arrow direction
    positiveTrend: Boolean,
    // add extra padding beneath the title?
    padTitle: {
      type: Boolean,
      default: true,
    },
    // vertically center content if it's shorter than the pane?
    centerVertically: {
      type: Boolean,
      default: false,
    },
    // apply flex layout?
    flex: {
      type: Boolean,
      default: false,
    },
    // remove border radius and top margin for tabbed mode?
    flushTop: {
      type: Boolean,
      default: false,
    },
  },
};
</script>

<template>
  <div
    v-cloak
    class="commerce-insights-pane"
    :class="{ 'flush-top': flushTop, 'vertical-center': centerVertically, 'is-flex': flex }"
  >
    <div v-if="title" class="commerce-insights-pane-title">
      <div :class="{ 'flex-grow': trend, 'pb-4': padTitle }">
        <h3 :class="{ 'pr-3': trend }">{{ title }}</h3>
      </div>
      <div v-if="trend" class="relative" style="top: -2px;">
        <div
          class="commerce-insights-trend"
          :class="{ 'up': positiveTrend, 'down': ! positiveTrend }"
        >
          {{ trend }}
        </div>
      </div>
    </div>
    <slot></slot>
  </div>
</template>

<style>
.commerce-insights-pane {
  position: relative;
  background: white;
  box-shadow: 0 0 0 1px #e3e5e8;
  overflow: hidden;
  padding: 16px;
  border-radius: 5px;
  word-wrap: break-word;
  box-sizing: border-box;
}

.commerce-insights-pane.is-flex {
  display: flex;
  flex-direction: column;
}

.commerce-insights-pane.vertical-center {
  display: flex;
  align-items: center;
}

.commerce-insights-pane-title {
  display: flex;
}

/* switch from a box-shadow "border" to a real one and remove it only on top */
.commerce-insights-pane.flush-top {
  border-top-left-radius: 0;
  border-top-right-radius: 0;
  margin-top: 0;
  box-shadow: none;
  border: 1px solid #e3e5e8;
  border-top: 0;
}
</style>
