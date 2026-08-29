<script setup lang="ts">
import { computed } from 'vue'

const props = withDefaults(
  defineProps<{
    min: number
    max: number
    step?: number
    minValue: number
    maxValue: number
  }>(),
  {
    step: 1,
  },
)

const emit = defineEmits<{
  'update:minValue': [value: number]
  'update:maxValue': [value: number]
}>()

const range = computed(() => props.max - props.min)

const minPercent = computed(() => ((props.minValue - props.min) / range.value) * 100)
const maxPercent = computed(() => ((props.maxValue - props.min) / range.value) * 100)

function onMinInput(event: Event) {
  const value = Number((event.target as HTMLInputElement).value)
  emit('update:minValue', Math.min(value, props.maxValue))
}

function onMaxInput(event: Event) {
  const value = Number((event.target as HTMLInputElement).value)
  emit('update:maxValue', Math.max(value, props.minValue))
}
</script>

<template>
  <div class="range-slider">
    <div class="range-slider__track">
      <div
        class="range-slider__fill"
        :style="{ left: `${minPercent}%`, right: `${100 - maxPercent}%` }"
      />
      <input
        class="range-slider__input range-slider__input--min"
        type="range"
        :min="min"
        :max="max"
        :step="step"
        :value="minValue"
        @input="onMinInput"
      />
      <input
        class="range-slider__input range-slider__input--max"
        type="range"
        :min="min"
        :max="max"
        :step="step"
        :value="maxValue"
        @input="onMaxInput"
      />
    </div>
  </div>
</template>

<style scoped>
.range-slider__track {
  position: relative;
  height: 15px;
}

.range-slider__fill {
  position: absolute;
  top: 6px;
  height: 3px;
  border-radius: 999px;
  background: var(--figma-accent);
  pointer-events: none;
  transition: left 0.14s ease-out, right 0.14s ease-out;
}

.range-slider__track::before {
  content: '';
  position: absolute;
  top: 6px;
  left: 0;
  right: 0;
  height: 3px;
  border-radius: 999px;
  background: rgba(146, 146, 146, 0.25);
}

.range-slider__input {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 15px;
  margin: 0;
  background: none;
  pointer-events: none;
  -webkit-appearance: none;
  appearance: none;
}

.range-slider__input::-webkit-slider-thumb {
  -webkit-appearance: none;
  appearance: none;
  width: 15px;
  height: 15px;
  border: none;
  border-radius: 50%;
  background: var(--figma-accent);
  pointer-events: auto;
  cursor: pointer;
}

.range-slider__input::-moz-range-thumb {
  width: 15px;
  height: 15px;
  border: none;
  border-radius: 50%;
  background: var(--figma-accent);
  pointer-events: auto;
  cursor: pointer;
}

.range-slider__input--max {
  z-index: 2;
}

@media (prefers-reduced-motion: reduce) {
  .range-slider__fill {
    transition: none;
  }
}
</style>
