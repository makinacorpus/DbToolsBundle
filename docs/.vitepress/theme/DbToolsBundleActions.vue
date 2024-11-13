<script setup lang="ts">
import { reactive, onMounted } from 'vue'
import VPButton from 'vitepress/dist/client/theme-default/components/VPButton.vue'

const stats = reactive({
  github: '--',
  packagist: '--',
});

onMounted(() => {
  fetch("https://packagist.org/packages/makinacorpus/db-tools-bundle.json")
    .then(raw => raw.json())
    .then(data => {
      stats.packagist = data.package.downloads.monthly
      stats.github = data.package.github_stars
    });
});

</script>

<template>
  <div class="actions">
    <div class="action">
      <VPButton
        tag="a"
        size="medium"
        theme="brand"
        text="Get Started"
        href="./getting-started/introduction"
      />
    </div>
    <div class="action">
      <VPButton
        tag="a"
        size="medium"
        theme="alt"
        text="View on GitHub"
        href="https://github.com/makinacorpus/DbToolsBundle"
      />
      <small>{{ stats.github }} ⭐</small>

    </div>
    <div class="action">
      <VPButton
        tag="a"
        size="medium"
        theme="alt"
        text="View on packagist"
        href="https://packagist.org/packages/makinacorpus/db-tools-bundle"
      />
      <small>{{ stats.packagist }} installs/month</small>
    </div>
  </div>
</template>

<style scoped>
.actions {
  display: flex;
  justify-content: center;
  flex-wrap: wrap;
  margin: -6px;
  padding-top: 24px;

  .action:first-child {
    flex-basis: 100%;
  }
}

.actions small {
  margin-top: -5px;
}

@media (min-width: 640px) {
  .actions {
    padding-top: 32px;
    .action:first-child {
      flex-basis: auto;
    }
  }
}

@media (min-width: 960px) {
   .actions {
    justify-content: start;
  }
}

.action {
  flex-shrink: 0;
  padding: 6px;
  display: flex;
  flex-direction: column;
  justify-content: start;
  align-items: center;
  gap: 0.4rem;
}
</style>
