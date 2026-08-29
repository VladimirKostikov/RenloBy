<script setup lang="ts">
import { computed } from 'vue'
import { normalizeMetroLineColor } from '@/lib/metroLineColor'

const props = withDefaults(
  defineProps<{
    color?: string
    size?: number
  }>(),
  {
    color: '#0072BC',
    size: 8,
  },
)

const fill = computed(() => normalizeMetroLineColor(props.color))
</script>

<template>
  <span
    class="metro-icon"
    :style="{
      width: `${size}px`,
      height: `${size}px`,
      backgroundColor: fill,
    }"
    aria-hidden="true"
  />
</template>

<style scoped>
.metro-icon {
  display: block;
  flex-shrink: 0;
  border-radius: 50%;
}
</style>
