<script setup lang="ts">
import { computed } from 'vue'
import { parseInfoBody } from '@/modules/info/lib/parseInfoBody'

const props = defineProps<{
  body: string
}>()

const blocks = computed(() => parseInfoBody(props.body))
</script>

<template>
  <div class="info-page-body">
    <template v-for="(block, index) in blocks" :key="index">
      <h2 v-if="block.type === 'heading'" class="info-page-body__heading">
        {{ block.text }}
      </h2>
      <ul v-else-if="block.type === 'list'" class="info-page-body__list">
        <li v-for="(item, itemIndex) in block.items" :key="itemIndex">
          {{ item }}
        </li>
      </ul>
      <p v-else class="info-page-body__paragraph">
        {{ block.text }}
      </p>
    </template>
  </div>
</template>

<style scoped>
.info-page-body {
  display: flex;
  flex-direction: column;
  gap: 16px;
  margin-bottom: 28px;
  font-size: 14px;
  line-height: 1.55;
  color: rgba(0, 0, 0, 0.78);
}

.info-page-body__heading {
  margin: 20px 0 4px;
  font-size: 18px;
  font-weight: 600;
  line-height: 1.3;
  color: var(--figma-ink);
}

.info-page-body__heading:first-child {
  margin-top: 0;
  margin-bottom: 8px;
}

.info-page-body__paragraph {
  margin: 0;
}

.info-page-body__list {
  margin: 0;
  padding-left: 20px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.info-page-body__list li {
  padding-left: 4px;
}
</style>
