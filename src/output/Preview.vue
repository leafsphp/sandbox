<script setup lang="ts">
import Message from '../Message.vue'
import {
  inject,
} from 'vue'
import { Store } from '../store'

const store = inject('store') as Store

defineProps(['output'])
</script>

<template>
  <div class="compiled-data-container" v-html="output || ''" ref="container"></div>

  <Message v-if="store.state.errors.length" :err="store.state.errors" />
  <!-- <Message v-if="!runtimeError" :warn="runtimeWarning" /> -->
</template>

<style scoped>
.compiled-data-container,
.compiled-data-container :deep(iframe) {
  width: 100%;
  height: 100%;
  border: none;
}
</style>
