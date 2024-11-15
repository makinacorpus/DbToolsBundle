<script setup lang="ts">
import DbToolsBundleFlavorSwitcher from './DbToolsBundleFlavorSwitcher.vue';
</script>

<template>
  <details open>
    <summary>
      Preferred flavor
    </summary>
    <div>
      <DbToolsBundleFlavorSwitcher />
    </div>
  </details>
</template>

<style scoped>
details {
  font-size: 12px;
  border-bottom: 1px solid var(--vt-c-divider-light);
  transition: border-color .5s,background-color .5s ease;
  /* margin-bottom: 20px; */
  position: sticky;
  top: -.5px;
  background-color: var(--vp-c-bg-alt);
  padding-top: 10px;
  padding-bottom: 1px;
  z-index: 10;
}
details > div {
  border-radius: 8px;
  background-color: var(--vp-c-bg);
  padding: 6px 12px;
  margin: 6px 0 12px;
}
</style>
