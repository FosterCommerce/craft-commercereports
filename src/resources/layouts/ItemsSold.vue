<script>
import CombinedSearch from '../components/CombinedSearch.vue';

export default {
  name: 'ItemsSold',
  components: {
    CombinedSearch,
  },
  props: {
    elements: {
      type: Array,
      required: true,
      default: () => [],
    },
  },
}
</script>

<template>
  <div class="px-3">
    <CombinedSearch
      element-type="ItemsSold"
      :elements="elements"
    />
  </div>
</template>
