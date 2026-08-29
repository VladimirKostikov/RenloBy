<script setup lang="ts">
withDefaults(
  defineProps<{
    src: string
    width?: number
    height?: number
    imageClass?: string
  }>(),
  {
    width: 16,
    height: 16,
  },
)
</script>

<template>
  <span
    class="theme-icon"
    :class="imageClass"
    :style="{
      width: `${width}px`,
      height: `${height}px`,
      maskImage: `url(${src})`,
      WebkitMaskImage: `url(${src})`,
    }"
    aria-hidden="true"
  />
</template>

<style scoped>
.theme-icon {
  display: inline-block;
  flex-shrink: 0;
  background-color: currentColor;
  mask-size: contain;
  mask-repeat: no-repeat;
  mask-position: center;
  -webkit-mask-size: contain;
  -webkit-mask-repeat: no-repeat;
  -webkit-mask-position: center;
}
</style>
