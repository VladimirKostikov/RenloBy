<script setup lang="ts">
withDefaults(
  defineProps<{
    lines?: number
    height?: string
    rounded?: boolean
  }>(),
  {
    lines: 1,
    height: '14px',
    rounded: true,
  },
)
</script>

<template>
  <div class="skeleton-wave" aria-hidden="true">
    <span
      v-for="index in lines"
      :key="index"
      class="skeleton-wave__line"
      :class="{ 'skeleton-wave__line--rounded': rounded }"
      :style="{
        height,
        width: index === lines && lines > 1 ? '68%' : '100%',
      }"
    />
  </div>
</template>

<style scoped>
.skeleton-wave {
  display: flex;
  flex-direction: column;
  gap: 10px;
  width: 100%;
}

.skeleton-wave__line {
  display: block;
  background: linear-gradient(
    90deg,
    #ececef 0%,
    #f6f6f8 40%,
    #e4e4e8 60%,
    #ececef 100%
  );
  background-size: 200% 100%;
  animation: skeleton-wave 1.35s ease-in-out infinite;
}

.skeleton-wave__line--rounded {
  border-radius: 8px;
}

@keyframes skeleton-wave {
  0% {
    background-position: 100% 0;
  }
  100% {
    background-position: -100% 0;
  }
}

@media (prefers-reduced-motion: reduce) {
  .skeleton-wave__line {
    animation: none;
    background: var(--color-bg-muted);
  }
}
</style>
