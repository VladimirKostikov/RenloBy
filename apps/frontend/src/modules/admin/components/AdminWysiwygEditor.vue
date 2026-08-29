<script setup lang="ts">
import { nextTick, onBeforeUnmount, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { htmlToInfoBody, infoBodyToHtml, sanitizeHtml } from '@/modules/admin/lib/wysiwygSerialize'

const props = defineProps<{
  modelValue: string
  label?: string
}>()

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

const { t } = useI18n()
const editorRef = ref<HTMLElement | null>(null)
const syncing = ref(false)

function emitFromDom() {
  if (!editorRef.value || syncing.value) {
    return
  }
  const html = sanitizeHtml(editorRef.value.innerHTML)
  emit('update:modelValue', htmlToInfoBody(html))
}

function setEditorHtml(value: string) {
  if (!editorRef.value) {
    return
  }
  syncing.value = true
  editorRef.value.innerHTML = infoBodyToHtml(value)
  syncing.value = false
}

watch(
  () => props.modelValue,
  async (value) => {
    await nextTick()
    if (!editorRef.value) {
      return
    }
    const current = htmlToInfoBody(editorRef.value.innerHTML)
    if (current !== value) {
      setEditorHtml(value)
    }
  },
  { immediate: true },
)

function runCommand(command: string, value?: string) {
  editorRef.value?.focus()
  document.execCommand(command, false, value)
  emitFromDom()
}

function onPaste(event: ClipboardEvent) {
  event.preventDefault()
  const text = event.clipboardData?.getData('text/plain') ?? ''
  document.execCommand('insertText', false, text)
  emitFromDom()
}

onBeforeUnmount(() => {
  emitFromDom()
})
</script>

<template>
  <div class="wysiwyg">
    <span v-if="label" class="wysiwyg__label">{{ label }}</span>
    <div class="wysiwyg__toolbar" role="toolbar">
      <button type="button" class="wysiwyg__btn" :title="t('admin.wysiwyg.bold')" @click="runCommand('bold')">
        <strong>B</strong>
      </button>
      <button type="button" class="wysiwyg__btn" :title="t('admin.wysiwyg.italic')" @click="runCommand('italic')">
        <em>I</em>
      </button>
      <button type="button" class="wysiwyg__btn" :title="t('admin.wysiwyg.heading')" @click="runCommand('formatBlock', 'H2')">
        H2
      </button>
      <button type="button" class="wysiwyg__btn" :title="t('admin.wysiwyg.paragraph')" @click="runCommand('formatBlock', 'P')">
        P
      </button>
      <button type="button" class="wysiwyg__btn" :title="t('admin.wysiwyg.list')" @click="runCommand('insertUnorderedList')">
        ••
      </button>
      <button type="button" class="wysiwyg__btn" :title="t('admin.wysiwyg.undo')" @click="runCommand('undo')">
        ↺
      </button>
    </div>
    <div
      ref="editorRef"
      class="wysiwyg__editor"
      contenteditable="true"
      role="textbox"
      aria-multiline="true"
      @input="emitFromDom"
      @blur="emitFromDom"
      @paste="onPaste"
    />
  </div>
</template>

<style scoped>
.wysiwyg {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.wysiwyg__label {
  font-size: 13px;
  font-weight: 600;
  color: var(--admin-text-muted, #6b7280);
}

.wysiwyg__toolbar {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  padding: 8px;
  border: 1px solid var(--admin-border, #e8eaef);
  border-bottom: none;
  border-radius: 8px 8px 0 0;
  background: #f8f9fb;
}

.wysiwyg__btn {
  min-width: 36px;
  min-height: 32px;
  padding: 0 10px;
  border: 1px solid transparent;
  border-radius: 6px;
  background: #fff;
  color: var(--admin-text, #1a1d26);
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  transition: background-color 160ms ease, border-color 160ms ease, transform 160ms ease;
}

.wysiwyg__btn:hover {
  border-color: var(--admin-border, #e8eaef);
  background: var(--admin-accent-muted, rgba(225, 69, 84, 0.08));
}

.wysiwyg__btn:active {
  transform: scale(0.96);
}

.wysiwyg__editor {
  min-height: 180px;
  max-height: 360px;
  overflow: auto;
  padding: 12px 14px;
  border: 1px solid var(--admin-border, #e8eaef);
  border-radius: 0 0 8px 8px;
  background: #fff;
  color: var(--admin-text, #1a1d26);
  font-size: 14px;
  line-height: 1.55;
  outline: none;
  transition: border-color 180ms ease, box-shadow 180ms ease;
}

.wysiwyg__editor:focus {
  border-color: var(--admin-accent, #e14554);
  box-shadow: 0 0 0 3px var(--admin-accent-muted, rgba(225, 69, 84, 0.08));
}

.wysiwyg__editor :deep(h2) {
  margin: 0 0 10px;
  font-size: 18px;
  font-weight: 700;
}

.wysiwyg__editor :deep(p) {
  margin: 0 0 10px;
}

.wysiwyg__editor :deep(ul) {
  margin: 0 0 10px;
  padding-left: 20px;
}
</style>
