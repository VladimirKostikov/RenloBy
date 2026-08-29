<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { ListingMediaTooLargeError, uploadListingMedia } from '@/api/account'
import { isMediaFileWithinLimit } from '@/lib/mediaUploadLimits'
import { isSafeMediaUrl } from '@/lib/isSafeMediaUrl'

const props = defineProps<{
  images: string[]
  maxFiles?: number
}>()

const emit = defineEmits<{
  'update:images': [value: string[]]
}>()

const { t } = useI18n()
const dragging = ref(false)
const uploading = ref(false)
const error = ref('')
const inputRef = ref<HTMLInputElement | null>(null)

const maxFiles = props.maxFiles ?? 10
const accept = 'image/jpeg,image/png,image/webp,image/gif'

function openPicker() {
  inputRef.value?.click()
}

function onDragEnter(event: DragEvent) {
  event.preventDefault()
  dragging.value = true
}

function onDragOver(event: DragEvent) {
  event.preventDefault()
  dragging.value = true
}

function onDragLeave(event: DragEvent) {
  event.preventDefault()
  const target = event.currentTarget as HTMLElement
  const related = event.relatedTarget as Node | null
  if (related && target.contains(related)) {
    return
  }
  dragging.value = false
}

function onDrop(event: DragEvent) {
  event.preventDefault()
  dragging.value = false
  const files = Array.from(event.dataTransfer?.files ?? [])
  void uploadFiles(files)
}

function onFileInput(event: Event) {
  const input = event.target as HTMLInputElement
  const files = Array.from(input.files ?? [])
  input.value = ''
  void uploadFiles(files)
}

async function uploadFiles(files: File[]) {
  if (files.length === 0 || uploading.value) {
    return
  }

  uploading.value = true
  error.value = ''
  const next = [...props.images]

  try {
    for (const file of files) {
      if (next.length >= maxFiles) {
        break
      }
      if (!isMediaFileWithinLimit(file)) {
        error.value = t('account.wizard.mediaTooLarge')
        continue
      }
      const uploaded = await uploadListingMedia(file)
      if (uploaded.type === 'image' && isSafeMediaUrl(uploaded.url)) {
        if (!next.includes(uploaded.url)) {
          next.push(uploaded.url)
        }
      } else {
        error.value = t('account.wizard.mediaUploadError')
      }
    }
    emit('update:images', next)
  } catch (err) {
    error.value = err instanceof ListingMediaTooLargeError
      ? t('account.wizard.mediaTooLarge')
      : t('account.wizard.mediaUploadError')
  } finally {
    uploading.value = false
  }
}

function removeImage(index: number) {
  emit(
    'update:images',
    props.images.filter((_, itemIndex) => itemIndex !== index),
  )
}
</script>

<template>
  <div class="wizard-media-dropzone">
    <div
      class="wizard-media-dropzone__zone"
      :class="{
        'wizard-media-dropzone__zone--dragging': dragging,
        'wizard-media-dropzone__zone--busy': uploading,
      }"
      role="button"
      tabindex="0"
      :aria-label="t('account.wizard.mediaDropAria')"
      @dragenter="onDragEnter"
      @dragover="onDragOver"
      @dragleave="onDragLeave"
      @drop="onDrop"
      @click="openPicker"
      @keydown.enter.prevent="openPicker"
      @keydown.space.prevent="openPicker"
    >
      <input
        ref="inputRef"
        type="file"
        class="wizard-media-dropzone__input"
        :accept="accept"
        multiple
        @change="onFileInput"
        @click.stop
      />
      <span class="wizard-media-dropzone__icon" aria-hidden="true" />
      <p class="wizard-media-dropzone__title">
        {{ uploading ? t('account.wizard.mediaUploading') : t('account.wizard.mediaDropTitle') }}
      </p>
      <p class="wizard-media-dropzone__hint">{{ t('account.wizard.mediaDropHint') }}</p>
    </div>

    <p v-if="error" class="wizard-media-dropzone__error" role="alert">{{ error }}</p>

    <div v-if="images.length" class="wizard-media-dropzone__grid">
      <div
        v-for="(url, index) in images"
        :key="`${url}-${index}`"
        class="wizard-media-dropzone__item"
      >
        <img v-if="isSafeMediaUrl(url)" :src="url" alt="" />
        <button
          type="button"
          class="wizard-media-dropzone__remove"
          @click.stop="removeImage(index)"
        >
          {{ t('account.wizard.removePhoto') }}
        </button>
      </div>
    </div>
    <p v-else class="wizard-media-dropzone__empty">{{ t('account.wizard.noPhotos') }}</p>
  </div>
</template>

<style scoped>
.wizard-media-dropzone {
  display: flex;
  flex-direction: column;
  gap: 12px;
  width: 100%;
}

.wizard-media-dropzone__zone {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  min-height: 180px;
  padding: 24px 16px;
  border: 2px dashed var(--figma-border);
  border-radius: 14px;
  background: color-mix(in srgb, var(--figma-accent) 4%, var(--figma-mix-base));
  color: var(--color-text);
  cursor: pointer;
  transition:
    border-color 0.2s ease,
    background-color 0.2s ease,
    box-shadow 0.2s ease;
}

.wizard-media-dropzone__zone:hover,
.wizard-media-dropzone__zone:focus-visible {
  outline: none;
  border-color: var(--figma-accent);
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--figma-accent) 12%, transparent);
}

.wizard-media-dropzone__zone--dragging {
  border-color: var(--figma-accent);
  background: color-mix(in srgb, var(--figma-accent) 10%, var(--figma-mix-base));
}

.wizard-media-dropzone__zone--busy {
  opacity: 0.75;
  cursor: wait;
  pointer-events: none;
}

.wizard-media-dropzone__input {
  position: absolute;
  width: 1px;
  height: 1px;
  overflow: hidden;
  clip: rect(0 0 0 0);
}

.wizard-media-dropzone__icon {
  display: block;
  width: 40px;
  height: 40px;
  border-radius: 12px;
  background: color-mix(in srgb, var(--figma-accent) 12%, var(--figma-mix-base));
  mask: url(/figma/account-create-listing.svg) center / 22px no-repeat;
  -webkit-mask: url(/figma/account-create-listing.svg) center / 22px no-repeat;
  background-color: var(--figma-accent);
}

.wizard-media-dropzone__title {
  margin: 0;
  font-size: 15px;
  font-weight: 700;
  text-align: center;
}

.wizard-media-dropzone__hint {
  margin: 0;
  max-width: 320px;
  color: var(--figma-text-muted, #929292);
  font-size: 13px;
  font-weight: 500;
  text-align: center;
}

.wizard-media-dropzone__error {
  margin: 0;
  color: var(--figma-accent);
  font-size: 12px;
  font-weight: 500;
}

.wizard-media-dropzone__empty {
  margin: 0;
  color: var(--figma-text-muted, #929292);
  font-size: 13px;
}

.wizard-media-dropzone__grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
  gap: 10px;
  width: 100%;
}

.wizard-media-dropzone__item {
  display: flex;
  flex-direction: column;
  gap: 4px;
  overflow: hidden;
  border: 1px solid var(--figma-border);
  border-radius: 10px;
  background: var(--color-bg-elevated);
}

.wizard-media-dropzone__item img,
.wizard-media-dropzone__item video {
  display: block;
  width: 100%;
  height: 96px;
  object-fit: cover;
  background: var(--color-bg-muted, #eee);
}

.wizard-media-dropzone__remove {
  border: none;
  background: transparent;
  color: var(--figma-accent);
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  min-height: 32px;
}
</style>
