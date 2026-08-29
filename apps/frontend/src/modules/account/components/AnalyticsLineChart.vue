<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  points: Array<{ date: string; value: number; average?: number }>
  color?: string
  showAverage?: boolean
  labels?: boolean
}>()

const width = 560
const height = 200
const padX = 20
const padY = 36

const values = computed(() => props.points.map((point) => point.value))
const maxValue = computed(() => Math.max(1, ...values.value, ...(props.points.map((p) => p.average ?? 0))))

function xAt(index: number) {
  if (props.points.length <= 1) return padX
  return padX + (index / (props.points.length - 1)) * (width - padX * 2)
}

function yAt(value: number) {
  const usable = height - padY * 2
  return height - padY - (value / maxValue.value) * usable
}

function labelY(value: number) {
  return Math.max(14, yAt(value) - 12)
}

const linePath = computed(() => {
  if (!props.points.length) return ''
  return props.points
    .map((point, index) => `${index === 0 ? 'M' : 'L'} ${xAt(index)} ${yAt(point.value)}`)
    .join(' ')
})

const areaPath = computed(() => {
  if (!props.points.length) return ''
  const baseY = height - padY
  const start = `M ${xAt(0)} ${baseY}`
  const line = props.points
    .map((point, index) => `L ${xAt(index)} ${yAt(point.value)}`)
    .join(' ')
  const end = `L ${xAt(props.points.length - 1)} ${baseY} Z`
  return `${start} ${line} ${end}`
})

const averagePath = computed(() => {
  if (!props.showAverage || !props.points.length) return ''
  const avg = props.points[0]?.average ?? 0
  const y = yAt(avg)
  return `M ${padX} ${y} L ${width - padX} ${y}`
})
</script>

<template>
  <svg
    class="analytics-chart"
    :viewBox="`0 0 ${width} ${height}`"
    role="img"
    aria-hidden="true"
  >
    <path
      v-if="areaPath"
      :d="areaPath"
      class="analytics-chart__area"
      :fill="color || 'var(--figma-accent)'"
    />
    <path
      v-if="averagePath"
      :d="averagePath"
      class="analytics-chart__average"
      fill="none"
    />
    <path
      v-if="linePath"
      :d="linePath"
      class="analytics-chart__line"
      :stroke="color || 'var(--figma-accent)'"
      fill="none"
    />
    <g v-if="labels">
      <g v-for="(point, index) in points" :key="point.date">
        <circle
          :cx="xAt(index)"
          :cy="yAt(point.value)"
          r="3.5"
          :fill="color || 'var(--figma-accent)'"
        />
        <text
          :x="xAt(index)"
          :y="labelY(point.value)"
          class="analytics-chart__label"
        >
          {{ point.value }}
        </text>
      </g>
    </g>
  </svg>
</template>

<style scoped>
.analytics-chart {
  display: block;
  width: 100%;
  height: auto;
}

.analytics-chart__area {
  opacity: 0.12;
}

.analytics-chart__line {
  stroke-width: 2.5;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.analytics-chart__average {
  stroke: #9ca3af;
  stroke-width: 1.5;
  stroke-dasharray: 4 4;
}

.analytics-chart__label {
  fill: #6b7280;
  font-size: 10px;
  text-anchor: middle;
}
</style>
