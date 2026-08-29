<script setup lang="ts">
const model = defineModel<boolean>({ default: false })

defineProps<{
  label: string
}>()
</script>

<template>
  <label class="toggle-switch">
    <span class="toggle-switch__label">{{ label }}</span>
    <button
      type="button"
      class="toggle-switch__control"
      :class="{ 'toggle-switch__control--active': model }"
      role="switch"
      :aria-checked="model"
      @click="model = !model"
    >
      <span class="toggle-switch__thumb" />
    </button>
  </label>
</template>

<style scoped>
.toggle-switch {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  min-height: 38px;
}

.toggle-switch__label {
  font-size: 15px;
  font-weight: 600;
  color: var(--figma-ink);
}

.toggle-switch__control {
  position: relative;
  width: 31px;
  height: 15px;
  padding: 0;
  border: none;
  border-radius: 999px;
  background: rgba(146, 146, 146, 0.35);
  cursor: pointer;
  transition: background-color 0.2s ease;
  flex-shrink: 0;
}

.toggle-switch__control--active {
  background: var(--figma-accent);
}

.toggle-switch__thumb {
  position: absolute;
  top: 1px;
  left: 1px;
  width: 13px;
  height: 13px;
  border-radius: 50%;
  background: var(--figma-surface);
  transition: transform 0.2s ease;
}

.toggle-switch__control--active .toggle-switch__thumb {
  transform: translateX(16px);
}
</style>
