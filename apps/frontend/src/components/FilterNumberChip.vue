<script setup lang="ts">
import { computed, nextTick, onUnmounted, ref, watch } from 'vue'
import CurrencyText from '@/components/CurrencyText.vue'
import { useFilterOverlay } from '@/lib/filterOverlayGroup'

const props = withDefaults(
  defineProps<{
    overlayId: string
    label: string
    modelValue?: number
    displayValue: string
    modifier?: 'price' | 'area'
    inputPlaceholder?: string
    showChevron?: boolean
    min?: number
    step?: number
  }>(),
  {
    showChevron: true,
    step: 1,
  },
)

const emit = defineEmits<{
  'update:modelValue': [value: number | undefined]
  change: [value: number | undefined]
}>()

const overlay = useFilterOverlay(props.overlayId)
const root = ref<HTMLElement | null>(null)
const inputRef = ref<HTMLInputElement | null>(null)
const menuStyle = ref<{ top: string; left: string; minWidth: string } | null>(null)
const draft = ref('')

const chipClass = computed(() => {
  const classes = ['filter-chip', 'filter-chip--selector', 'filter-number-chip__trigger']
  if (props.modifier) {
    classes.push(`filter-chip--${props.modifier}`)
  }
  if (!props.showChevron) {
    classes.push('filter-chip--no-chevron')
  }
  if (overlay.isOpen.value) {
    classes.push('filter-number-chip__trigger--open')
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
    minWidth: `${Math.max(rect.width, 200)}px`,
  }
}

async function openMenu() {
  draft.value = props.modelValue !== undefined && !Number.isNaN(props.modelValue) ? String(props.modelValue) : ''
  overlay.open()
  await nextTick()
  updateMenuPosition()
  inputRef.value?.focus()
  inputRef.value?.select()
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

function parseDraft(): number | undefined {
  const trimmed = draft.value.trim()
  if (trimmed === '') {
    return undefined
  }

  const parsed = Number(trimmed)
  if (!Number.isFinite(parsed) || parsed < 0) {
    return undefined
  }

  if (props.min !== undefined && parsed < props.min) {
    return undefined
  }

  return parsed
}

function apply() {
  const value = parseDraft()
  emit('update:modelValue', value)
  emit('change', value)
  closeMenu()
}

function clearValue() {
  draft.value = ''
  emit('update:modelValue', undefined)
  emit('change', undefined)
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
  if (target instanceof Element && target.closest('.filter-number-chip__menu, .filter-number-chip__trigger, .filter-select__menu, .filter-select__trigger')) {
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
  <div ref="root" class="filter-number-chip">
    <button type="button" :class="chipClass" @click.stop="toggleMenu">
      <span class="filter-chip__label">{{ label }}</span>
      <CurrencyText class="filter-chip__value" :text="displayValue" />
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
        class="filter-number-chip__menu"
        :style="menuStyle"
        @click.stop
      >
        <label class="filter-number-chip__field">
          <input
            ref="inputRef"
            v-model="draft"
            type="number"
            :min="min"
            :step="step"
            :placeholder="inputPlaceholder"
            @keydown.enter.prevent="apply"
          />
        </label>
        <div class="filter-number-chip__actions">
          <button type="button" class="filter-number-chip__clear" @click="clearValue">
            {{ $t('filters.clear') }}
          </button>
          <button type="button" class="filter-number-chip__apply" @click="apply">
            {{ $t('filters.apply') }}
          </button>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<style scoped>
.filter-number-chip {
  position: relative;
  display: inline-flex;
  flex-shrink: 0;
}

.filter-number-chip__trigger {
  border: none;
  text-align: left;
}

.filter-number-chip__trigger--open {
  border-color: rgba(225, 69, 84, 0.35);
  background: rgba(255, 208, 213, 0.12);
}
</style>

<style>
.filter-number-chip__menu {
  position: fixed;
  z-index: 2200;
  padding: 12px;
  border: 1px solid rgba(146, 146, 146, 0.18);
  border-radius: var(--figma-radius-chip);
  background: var(--figma-surface);
  box-shadow: 0 10px 28px rgba(0, 0, 0, 0.12);
}

.filter-number-chip__field {
  display: block;
}

.filter-number-chip__field input {
  width: 100%;
  height: 40px;
  padding: 0 12px;
  border: 1px solid var(--figma-border);
  border-radius: 8px;
  font-family: var(--font-family);
  font-size: var(--figma-filter-value-size);
  font-weight: 600;
  color: var(--figma-ink);
}

.filter-number-chip__actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  margin-top: 10px;
}

.filter-number-chip__clear,
.filter-number-chip__apply {
  min-height: 36px;
  padding: 0 12px;
  border-radius: 8px;
  font-family: var(--font-family);
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
}

.filter-number-chip__clear {
  border: 1px solid var(--figma-border);
  background: var(--figma-surface);
  color: var(--figma-ink);
}

.filter-number-chip__apply {
  border: 1px solid var(--figma-border);
  background: var(--figma-accent);
  color: var(--figma-on-accent);
}
</style>
