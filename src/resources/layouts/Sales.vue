<script>
import CombinedSearch from '../components/CombinedSearch.vue';

export default {
  name: 'Sales',
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
