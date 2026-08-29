<script setup lang="ts">
import { computed, nextTick, onUnmounted, ref, watch } from 'vue'
import { useFilterOverlay } from '@/lib/filterOverlayGroup'

export interface FilterSelectOption {
  value: string | number
  label: string
}

const props = withDefaults(
  defineProps<{
    overlayId: string
    label?: string
    modelValue?: string | number
    options: FilterSelectOption[]
    modifier?: 'object-type' | 'region' | 'city' | 'district' | 'rooms' | 'area' | 'price'
    placeholder?: string
    selectClass?: string
    showChevron?: boolean
  }>(),
  {
    showChevron: true,
    selectClass: '',
  },
)

const emit = defineEmits<{
  'update:modelValue': [value: string | number | undefined]
  change: [value: string | number | undefined]
}>()

const overlay = useFilterOverlay(props.overlayId)
const root = ref<HTMLElement | null>(null)
const menuStyle = ref<{ top: string; left: string; minWidth: string } | null>(null)

const displayValue = computed(() => {
  if (props.modelValue === undefined || props.modelValue === null || props.modelValue === '') {
    return props.placeholder ?? ''
  }

  const match = props.options.find((option) => String(option.value) === String(props.modelValue))
  return match?.label ?? props.placeholder ?? ''
})

const chipClass = computed(() => {
  const classes = ['filter-chip', 'filter-chip--selector', 'filter-select__trigger']
  if (props.modifier) {
    classes.push(`filter-chip--${props.modifier}`)
  }
  if (overlay.isOpen.value) {
    classes.push('filter-select__trigger--open')
  }
  return classes
})

function updateMenuPosition() {
  if (!root.value) {
    return
  }

  const rect = root.value.getBoundingClientRect()
  menuStyle.value = {
    top: `${rect.bottom + 6}px`,
    left: `${rect.left}px`,
    minWidth: `${rect.width}px`,
  }
}

async function openMenu() {
  overlay.open()
  await nextTick()
  updateMenuPosition()
}

function closeMenu() {
  overlay.close()
  menuStyle.value = null
}

function toggleMenu() {
  if (overlay.isOpen.value) {
    closeMenu()
    return
  }
  void openMenu()
}

function selectOption(option: FilterSelectOption) {
  const value = option.value === '' ? undefined : option.value
  emit('update:modelValue', value)
  emit('change', value)
  closeMenu()
}

function onDocumentClick(event: MouseEvent) {
  if (!overlay.isOpen.value) {
    return
  }
  const target = event.target as Node
  if (root.value?.contains(target)) {
    return
  }
  if (target instanceof Element && target.closest('.filter-select__menu, .filter-select__trigger')) {
    return
  }
  closeMenu()
}

function onDocumentKeydown(event: KeyboardEvent) {
  if (event.key === 'Escape') {
    closeMenu()
  }
}

function onViewportChange() {
  if (overlay.isOpen.value) {
    updateMenuPosition()
  }
}

watch(overlay.isOpen, (isOpen) => {
  if (isOpen) {
    document.addEventListener('click', onDocumentClick)
    document.addEventListener('keydown', onDocumentKeydown)
    window.addEventListener('resize', onViewportChange)
    window.addEventListener('scroll', onViewportChange, true)
    return
  }

  document.removeEventListener('click', onDocumentClick)
  document.removeEventListener('keydown', onDocumentKeydown)
  window.removeEventListener('resize', onViewportChange)
  window.removeEventListener('scroll', onViewportChange, true)
})

onUnmounted(() => {
  document.removeEventListener('click', onDocumentClick)
  document.removeEventListener('keydown', onDocumentKeydown)
  window.removeEventListener('resize', onViewportChange)
  window.removeEventListener('scroll', onViewportChange, true)
})
</script>

<template>
  <div ref="root" class="filter-select" :class="selectClass">
    <button type="button" :class="chipClass" @click.stop="toggleMenu">
      <span v-if="label" class="filter-chip__label">{{ label }}</span>
      <span class="filter-chip__value">{{ displayValue }}</span>
      <img data-theme-ink
        v-if="showChevron"
        src="/figma/chevron.svg"
        alt=""
        class="filter-chip__chevron"
        width="8"
        height="16"
      />
    </button>

    <Teleport to="body">
      <div
        v-if="overlay.isOpen && menuStyle"
        class="filter-select__menu"
        :style="menuStyle"
        role="listbox"
        @click.stop
      >
        <button
          v-for="option in options"
          :key="String(option.value)"
          type="button"
          class="filter-select__option"
          :class="{ 'filter-select__option--active': String(option.value) === String(modelValue ?? '') }"
          role="option"
          :aria-selected="String(option.value) === String(modelValue ?? '')"
          @click="selectOption(option)"
        >
          {{ option.label }}
        </button>
      </div>
    </Teleport>
  </div>
</template>

<style scoped>
.filter-select {
  position: relative;
  display: inline-flex;
  flex-shrink: 0;
}

.filter-select__trigger {
  border: none;
  text-align: left;
}

.filter-select__trigger--open {
  border-color: rgba(225, 69, 84, 0.35);
  background: rgba(255, 208, 213, 0.12);
}
</style>

<style>
.filter-select__menu {
  position: fixed;
  z-index: 2200;
  max-height: min(280px, 50vh);
  overflow-y: auto;
  padding: 6px;
  border: 1px solid rgba(146, 146, 146, 0.18);
  border-radius: var(--figma-radius-chip);
  background: var(--figma-surface);
  box-shadow: 0 10px 28px rgba(0, 0, 0, 0.12);
  -webkit-overflow-scrolling: touch;
}

.filter-select__option {
  display: block;
  width: 100%;
  min-height: var(--touch-target-min, 44px);
  padding: 10px 12px;
  border: none;
  border-radius: 8px;
  background: transparent;
  text-align: left;
  font-family: var(--font-family);
  font-size: var(--figma-filter-value-size);
  font-weight: 600;
  line-height: 1.2;
  color: var(--figma-ink);
  cursor: pointer;
  transition: background-color 0.15s ease, color 0.15s ease;
}

.filter-select__option:hover {
  background: rgba(255, 208, 213, 0.14);
}

.filter-select__option--active {
  color: var(--figma-accent);
  background: rgba(225, 69, 84, 0.08);
}
</style>
