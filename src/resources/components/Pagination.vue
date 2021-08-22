<script>
export default {
  name: 'Pagination',
  props: {
    numElements: {
      type: Number,
      required: true,
      default: 0,
    },
    reset: {
      type: Boolean,
      required: true,
      default: false,
    },
  },
  data() {
    return {
      pageSize: 5,
      currentPage: 1,
      numPages: 1,
    }
  },
  watch: {
    numElements() {
      this.updateNumPages();
    },
    reset() {
      if (this.reset) this.resetPage();
    },
  },
  mounted() {
    this.updateNumPages();

    this.$emit('pagination-loaded', {
      pageSize: this.pageSize,
      currentPage: this.currentPage
    });
  },
  methods: {
    prevPage() {
      if (this.currentPage > 1) this.currentPage--;
      this.changePage();
    },
    nextPage() {
      if ((this.currentPage * this.pageSize) < this.numElements) this.currentPage++;
      this.changePage();
    },
    resetPage() {
      this.currentPage = 1;
      this.changePage();
    },
    changePage() {
      this.$emit('change-page', this.currentPage);
    },
    updateNumPages() {
      this.numPages = this.numElements ? Math.ceil(this.numElements / this.pageSize) : 0;
    },
  },
}
</script>

<template>
  <div class="pagination">
    <button
      @click="prevPage"
      class="btn"
      :class="currentPage < 2 ? 'disabled' : ''"
    >
      Previous
    </button>

    <button
      @click="nextPage"
      class="btn"
      :class="currentPage === numPages ? 'disabled' : ''"
    >
      Next
    </button>
  </div>
</template>

<style scoped>
.pagination {
  margin-top: 30px
}
</style>
