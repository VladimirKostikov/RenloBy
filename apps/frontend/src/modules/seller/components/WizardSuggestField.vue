<script setup lang="ts">
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue'
import { filterSuggestOptions } from '@/modules/seller/lib/listingWizard'

const props = withDefaults(
  defineProps<{
    modelValue: string
    label: string
    placeholder?: string
    options: string[]
    invalid?: boolean
    errorText?: string
    maxlength?: number
  }>(),
  {
    placeholder: '',
    invalid: false,
    errorText: '',
    maxlength: 120,
  },
)

const emit = defineEmits<{
  'update:modelValue': [value: string]
  blur: []
}>()

const root = ref<HTMLElement | null>(null)
const open = ref(false)
const activeIndex = ref(-1)

const suggestions = computed(() => filterSuggestOptions(props.options, props.modelValue))

watch(
  () => props.modelValue,
  () => {
    activeIndex.value = -1
  },
)

function onInput(event: Event) {
  const target = event.target as HTMLInputElement
  emit('update:modelValue', target.value)
  open.value = true
}

function selectOption(value: string) {
  emit('update:modelValue', value)
  open.value = false
  activeIndex.value = -1
}

function onFocus() {
  open.value = true
}

function onBlur() {
  window.setTimeout(() => {
    open.value = false
    emit('blur')
  }, 120)
}

function onKeydown(event: KeyboardEvent) {
  if (!open.value && (event.key === 'ArrowDown' || event.key === 'ArrowUp')) {
    open.value = true
    return
  }

  if (!suggestions.value.length) {
    return
  }

  if (event.key === 'ArrowDown') {
    event.preventDefault()
    activeIndex.value = (activeIndex.value + 1) % suggestions.value.length
  } else if (event.key === 'ArrowUp') {
    event.preventDefault()
    activeIndex.value = activeIndex.value <= 0
      ? suggestions.value.length - 1
      : activeIndex.value - 1
  } else if (event.key === 'Enter' && activeIndex.value >= 0) {
    event.preventDefault()
    const value = suggestions.value[activeIndex.value]
    if (value) {
      selectOption(value)
    }
  } else if (event.key === 'Escape') {
    open.value = false
  }
}

function onDocumentClick(event: MouseEvent) {
  if (!root.value?.contains(event.target as Node)) {
    open.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', onDocumentClick)
})

onUnmounted(() => {
  document.removeEventListener('click', onDocumentClick)
})

watch(open, async (isOpen) => {
  if (isOpen) {
    await nextTick()
  }
})
</script>

<template>
  <label ref="root" class="wizard-suggest">
    <span class="wizard-suggest__label">{{ label }}</span>
    <input
      class="wizard-suggest__input"
      :class="{ 'is-invalid': invalid }"
      type="text"
      :value="modelValue"
      :placeholder="placeholder"
      :maxlength="maxlength"
      autocomplete="off"
      @input="onInput"
      @focus="onFocus"
      @blur="onBlur"
      @keydown="onKeydown"
    />
    <span v-if="errorText" class="wizard-suggest__error">{{ errorText }}</span>
    <ul v-if="open && suggestions.length" class="wizard-suggest__menu" role="listbox">
      <li
        v-for="(option, index) in suggestions"
        :key="option"
        role="option"
        class="wizard-suggest__option"
        :class="{ 'wizard-suggest__option--active': index === activeIndex }"
        @mousedown.prevent="selectOption(option)"
      >
        {{ option }}
      </li>
    </ul>
  </label>
</template>

<style scoped>
.wizard-suggest {
  position: relative;
  display: flex;
  flex-direction: column;
  gap: 6px;
  min-width: 0;
}

.wizard-suggest__label {
  font-size: 13px;
  font-weight: 600;
  color: var(--color-text);
}

.wizard-suggest__input {
  box-sizing: border-box;
  width: 100%;
  height: 44px;
  padding: 0 12px;
  border: 1px solid var(--figma-border);
  border-radius: 10px;
  background: var(--color-bg-elevated);
  color: var(--color-text);
  font-family: inherit;
  font-size: 15px;
  transition:
    border-color 0.2s ease,
    box-shadow 0.2s ease;
}

.wizard-suggest__input::placeholder {
  color: var(--color-text-muted, #999);
}

.wizard-suggest__input:hover {
  border-color: color-mix(in srgb, var(--figma-accent) 40%, var(--figma-border));
}

.wizard-suggest__input:focus {
  outline: none;
  border-color: var(--figma-accent);
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--figma-accent) 14%, transparent);
}

.wizard-suggest__input.is-invalid {
  border-color: var(--figma-accent);
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--figma-accent) 12%, transparent);
}

.wizard-suggest__error {
  font-size: 12px;
  font-weight: 500;
  color: var(--figma-accent);
}

.wizard-suggest__menu {
  position: absolute;
  top: calc(100% + 6px);
  left: 0;
  right: 0;
  z-index: 20;
  margin: 0;
  padding: 6px;
  list-style: none;
  border: 1px solid var(--figma-border);
  border-radius: 12px;
  background: var(--color-bg-elevated);
  box-shadow: 0 12px 28px rgba(0, 0, 0, 0.08);
  max-height: 220px;
  overflow: auto;
}

.wizard-suggest__option {
  min-height: 44px;
  padding: 10px 12px;
  border-radius: 8px;
  font-size: 15px;
  cursor: pointer;
}

.wizard-suggest__option:hover,
.wizard-suggest__option--active {
  background: color-mix(in srgb, var(--figma-accent) 10%, transparent);
  color: var(--figma-accent);
}
</style>
