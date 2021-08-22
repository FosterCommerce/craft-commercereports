<script>
export default {
  name: 'PaymentStatusDropdown',
  props: {
    selectedOption: {
      type: Object,
      required: true,
      default: () => {},
    },
  },
  methods: {
    getSelectedPaymentLabel() {
      if (Object.keys(this.selectedOption).length === 0) {
        return 'All Payment Statuses';
      }

      return this.selectedOption.label;
    },
    selectPaymentTypeOption(typeOption) {
      this.$emit('selected-payment-type', typeOption);
    },
    selectAllTypes() {
      this.$emit('select-all-types');
    },
  },
}
</script>

<template>
  <div>
    <div class="btn menubtn">{{ getSelectedPaymentLabel() }}</div>
    <div class="menu">
      <ul class="padded">
        <li>
          <a
            :class='{ "sel": Object.keys(selectedOption).length === 0 }'
            @click="selectAllTypes()"
          >
            All Payment Statuses
          </a>
        </li>
        <li>
          <a
            :class="{ 'sel': selectedOption.value === 'Paid' }"
            @click="selectPaymentTypeOption({label: 'Paid', value: 'Paid'})"
          >
            Paid
          </a>
        </li>
        <li>
          <a
            :class="{ 'sel': selectedOption.value === 'Partial' }"
            @click="selectPaymentTypeOption({label: 'Partial', value: 'Partial'})"
          >
            Partial
          </a>
        </li>
        <li>
          <a
            :class="{ 'sel': selectedOption.value === 'Unpaid' }"
            @click="selectPaymentTypeOption({label: 'Unpaid', value: 'Unpaid'})"
          >
            Unpaid
          </a>
        </li>
      </ul>
    </div>
  </div>
</template>
