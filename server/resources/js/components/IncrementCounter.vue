<script setup lang="ts">
import {ref} from "vue"

const counter = ref(0)
</script>

<template>
  <button
      type="button"
      @click="counter++"
      class="p-2 text-white bg-blue-950 rounded"
  >
    Counter is: {{ counter }}
  </button>
</template>

<style scoped>

</style>