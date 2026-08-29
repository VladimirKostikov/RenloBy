<script setup lang="ts">
withDefaults(
  defineProps<{
    label: string
    modelValue: string
    absent?: boolean
    allowAbsent?: boolean
    placeholder?: string
    maxlength?: number
    invalid?: boolean
    errorText?: string
    inputType?: string
  }>(),
  {
    absent: false,
    allowAbsent: true,
  },
)

const emit = defineEmits<{
  'update:modelValue': [value: string]
  'update:absent': [value: boolean]
  blur: []
}>()

function onAbsentChange(event: Event) {
  const checked = (event.target as HTMLInputElement).checked
  emit('update:absent', checked)
  if (checked) {
    emit('update:modelValue', '')
  }
}
</script>

<template>
  <div class="wizard-location-field" :class="{ 'wizard-location-field--absent': absent }">
    <div class="wizard-location-field__head">
      <span class="wizard-location-field__label">{{ label }}</span>
      <label v-if="allowAbsent" class="wizard-location-field__absent">
        <input
          type="checkbox"
          class="wizard-location-field__absent-input"
          :checked="absent"
          @change="onAbsentChange"
        />
        <span>{{ $t('account.wizard.fieldAbsent') }}</span>
      </label>
    </div>
    <input
      :value="modelValue"
      :type="inputType ?? 'text'"
      class="wizard-location-field__control"
      :class="{ 'is-invalid': invalid }"
      :placeholder="placeholder"
      :maxlength="maxlength"
      :disabled="allowAbsent && absent"
      @input="emit('update:modelValue', ($event.target as HTMLInputElement).value)"
      @blur="emit('blur')"
    />
    <span v-if="invalid && errorText" class="wizard-location-field__error">{{ errorText }}</span>
  </div>
</template>

<style scoped>
.wizard-location-field {
  display: flex;
  flex-direction: column;
  gap: 8px;
  min-width: 0;
}

.wizard-location-field__head {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}

.wizard-location-field__label {
  font-size: 13px;
  font-weight: 600;
  color: rgba(0, 0, 0, 0.78);
}

.wizard-location-field__absent {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  min-height: 24px;
  font-size: 12px;
  font-weight: 500;
  color: var(--color-text-muted, rgba(0, 0, 0, 0.65));
  cursor: pointer;
  user-select: none;
}

.wizard-location-field__absent-input {
  width: 16px;
  height: 16px;
  margin: 0;
  accent-color: var(--figma-accent);
  cursor: pointer;
}

.wizard-location-field__control {
  box-sizing: border-box;
  width: 100%;
  height: 48px;
  padding: 0 14px;
  border: 1px solid var(--figma-border);
  border-radius: var(--figma-radius-chip, 8px);
  background: var(--color-bg-elevated, #fff);
  color: var(--color-text, #000);
  font-family: inherit;
  font-size: 14px;
  transition:
    border-color 0.2s ease,
    box-shadow 0.2s ease,
    opacity 0.2s ease;
}

.wizard-location-field__control:focus {
  outline: none;
  border-color: var(--figma-accent);
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--figma-accent) 16%, transparent);
}

.wizard-location-field__control:disabled {
  opacity: 0.55;
  cursor: default;
  background: color-mix(in srgb, var(--color-text, #000) 4%, transparent);
}

.wizard-location-field__control.is-invalid {
  border-color: var(--figma-accent);
}

.wizard-location-field__error {
  font-size: 12px;
  color: var(--figma-accent);
}
</style>
