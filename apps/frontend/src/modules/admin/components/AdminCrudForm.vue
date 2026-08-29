<script setup lang="ts">
import { computed, reactive, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import AdminWysiwygEditor from './AdminWysiwygEditor.vue'
import { useAdminTestModeStore } from '@/stores/adminTestMode'

export interface FormField {
  key: string
  label: string
  type?: 'text' | 'number' | 'email' | 'password' | 'checkbox' | 'textarea' | 'wysiwyg' | 'select'
  options?: { value: string | number; label: string }[]
}

const props = defineProps<{
  fields: FormField[]
  modelValue: Record<string, unknown>
  omitTestField?: boolean
}>()

const emit = defineEmits<{
  save: [payload: Record<string, unknown>]
  cancel: []
}>()

const { t } = useI18n()
const testMode = useAdminTestModeStore()
const form = reactive<Record<string, unknown>>({})

const resolvedFields = computed<FormField[]>(() => {
  if (props.omitTestField || props.fields.some((field) => field.key === 'isTest')) {
    return props.fields
  }
  return [
    ...props.fields,
    { key: 'isTest', label: t('admin.fields.isTest'), type: 'checkbox' },
  ]
})

watch(
  () => props.modelValue,
  (value) => {
    for (const field of resolvedFields.value) {
      if (field.key === 'isTest') {
        form.isTest = value.isTest ?? testMode.isTest
        continue
      }
      form[field.key] = value[field.key] ?? (field.type === 'checkbox' ? false : '')
    }
  },
  { immediate: true, deep: true },
)

function submit() {
  if (props.omitTestField) {
    const payload = { ...form }
    delete payload.isTest
    emit('save', payload)
    return
  }
  emit('save', { ...form, isTest: Boolean(form.isTest) })
}
</script>

<template>
  <form class="admin-form" @submit.prevent="submit">
    <label
      v-for="field in resolvedFields"
      :key="field.key"
      class="admin-form__field"
      :class="{ 'admin-form__field--checkbox': field.type === 'checkbox' }"
    >
      <span v-if="field.type !== 'wysiwyg'">{{ field.label }}</span>
      <select v-if="field.type === 'select'" v-model="form[field.key]" class="admin-form__control">
        <option v-for="opt in field.options" :key="String(opt.value)" :value="opt.value">
          {{ opt.label }}
        </option>
      </select>
      <AdminWysiwygEditor
        v-else-if="field.type === 'wysiwyg'"
        :model-value="String(form[field.key] ?? '')"
        :label="field.label"
        @update:model-value="form[field.key] = $event"
      />
      <textarea
        v-else-if="field.type === 'textarea'"
        v-model="form[field.key] as string"
        class="admin-form__control admin-form__control--textarea"
        rows="4"
      />
      <input
        v-else-if="field.type === 'checkbox'"
        v-model="form[field.key]"
        type="checkbox"
        class="admin-form__checkbox"
      />
      <input
        v-else
        v-model="form[field.key]"
        class="admin-form__control"
        :type="field.type ?? 'text'"
        :step="field.type === 'number' ? 'any' : undefined"
      />
    </label>
    <div class="admin-form__actions">
      <button type="button" class="admin-form__cancel" @click="emit('cancel')">{{ t('admin.cancel') }}</button>
      <button type="submit" class="admin-form__save">{{ t('admin.save') }}</button>
    </div>
  </form>
</template>

<style scoped>
.admin-form {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.admin-form__field {
  display: flex;
  flex-direction: column;
  gap: 6px;
  font-size: 13px;
  font-weight: 600;
  color: var(--admin-text-muted, #6b7280);
}

.admin-form__field--checkbox {
  flex-direction: row;
  align-items: center;
  gap: 10px;
}

.admin-form__control {
  width: 100%;
  min-height: 42px;
  padding: 10px 12px;
  border: 1px solid var(--admin-border, #e8eaef);
  border-radius: 8px;
  background: #fff;
  color: var(--admin-text, #1a1d26);
  font: inherit;
  font-weight: 400;
  transition: border-color 180ms ease, box-shadow 180ms ease, background-color 180ms ease;
}

select.admin-form__control {
  -webkit-appearance: none;
  appearance: none;
  padding-right: 40px;
  background-color: #fff;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8' fill='none'%3E%3Cpath d='M1 1.5L6 6.5L11 1.5' stroke='%236b7280' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 14px center;
  background-size: 12px 8px;
  cursor: pointer;
}

.admin-form__control--textarea {
  min-height: 96px;
  resize: vertical;
}

.admin-form__control:hover {
  border-color: #d4d8e0;
}

.admin-form__control:focus {
  outline: none;
  border-color: var(--admin-accent, #e14554);
  box-shadow: 0 0 0 3px var(--admin-accent-muted, rgba(225, 69, 84, 0.08));
}

.admin-form__checkbox {
  width: 18px;
  height: 18px;
  accent-color: var(--admin-accent, #e14554);
}

.admin-form__actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  margin-top: 8px;
}

.admin-form__save,
.admin-form__cancel {
  min-height: 42px;
  padding: 0 18px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 180ms ease, border-color 180ms ease, transform 180ms ease;
}

.admin-form__save {
  border: none;
  background: var(--admin-accent, #e14554);
  color: #fff;
}

.admin-form__save:hover {
  background: var(--admin-accent-hover, #c93a48);
}

.admin-form__cancel {
  border: 1px solid var(--admin-border, #e8eaef);
  background: #fff;
  color: var(--admin-text, #1a1d26);
}

.admin-form__cancel:hover {
  background: var(--admin-row-hover, #f8f9fb);
}

.admin-form__save:active,
.admin-form__cancel:active {
  transform: scale(0.98);
}
</style>
