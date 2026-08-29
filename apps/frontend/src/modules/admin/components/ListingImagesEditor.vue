<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { uploadAdminMedia, MediaFileTooLargeError } from '@/api/adminMedia'
import { isMediaFileWithinLimit } from '@/lib/mediaUploadLimits'
import { isSafeMediaUrl } from '@/lib/isSafeMediaUrl'

const props = defineProps<{
  images: string[]
}>()

const emit = defineEmits<{
  'update:images': [value: string[]]
}>()

const { t } = useI18n()
const uploading = ref(false)
const error = ref('')

async function onFilesSelected(event: Event) {
  const input = event.target as HTMLInputElement
  const files = Array.from(input.files ?? [])
  input.value = ''
  if (files.length === 0) {
    return
  }

  uploading.value = true
  error.value = ''
  const next = [...props.images]
  try {
    for (const file of files) {
      if (next.length >= 10) {
        break
      }
      if (!isMediaFileWithinLimit(file)) {
        error.value = t('admin.media.tooLarge')
        continue
      }
      const uploaded = await uploadAdminMedia(file)
      if (uploaded.type === 'image' && isSafeMediaUrl(uploaded.url)) {
        next.push(uploaded.url)
      }
    }
    emit('update:images', next)
  } catch (err) {
    error.value = err instanceof MediaFileTooLargeError
      ? t('admin.media.tooLarge')
      : t('admin.media.uploadError')
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

function moveImage(index: number, delta: number) {
  const target = index + delta
  if (target < 0 || target >= props.images.length) {
    return
  }
  const next = [...props.images]
  const [item] = next.splice(index, 1)
  next.splice(target, 0, item)
  emit('update:images', next)
}
</script>

<template>
  <div class="listing-images-editor">
    <span class="listing-images-editor__label">{{ t('admin.fields.images') }}</span>
    <div v-if="images.length" class="listing-images-editor__grid">
      <div v-for="(url, index) in images" :key="`${url}-${index}`" class="listing-images-editor__item">
        <img v-if="isSafeMediaUrl(url)" :src="url" alt="" />
        <div class="listing-images-editor__actions">
          <button
            type="button"
            class="listing-images-editor__move"
            :disabled="index === 0"
            @click="moveImage(index, -1)"
          >
            ↑
          </button>
          <button
            type="button"
            class="listing-images-editor__move"
            :disabled="index === images.length - 1"
            @click="moveImage(index, 1)"
          >
            ↓
          </button>
          <button type="button" class="listing-images-editor__remove" @click="removeImage(index)">
            {{ t('admin.media.remove') }}
          </button>
        </div>
      </div>
    </div>
    <label class="listing-images-editor__file">
      <input
        type="file"
        accept="image/jpeg,image/png,image/webp,image/gif"
        multiple
        @change="onFilesSelected"
      />
      <span>{{ uploading ? t('admin.media.uploading') : t('admin.media.uploadPhotos') }}</span>
    </label>
    <p v-if="error" class="listing-images-editor__error">{{ error }}</p>
  </div>
</template>

<style scoped>
.listing-images-editor {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-bottom: 16px;
}

.listing-images-editor__label {
  font-size: 13px;
  font-weight: 600;
  color: var(--admin-text-muted, #6b7280);
}

.listing-images-editor__grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
  gap: 10px;
}

.listing-images-editor__item {
  overflow: hidden;
  border: 1px solid var(--admin-border, #e5e7eb);
  border-radius: 10px;
  background: #f9fafb;
}

.listing-images-editor__item img {
  display: block;
  width: 100%;
  height: 120px;
  object-fit: cover;
}

.listing-images-editor__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
  padding: 8px;
}

.listing-images-editor__move,
.listing-images-editor__remove {
  min-height: 28px;
  padding: 0 8px;
  border: 1px solid var(--admin-border, #e5e7eb);
  border-radius: 6px;
  background: #fff;
  color: var(--admin-text, #1a1d26);
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
}

.listing-images-editor__move:disabled {
  opacity: 0.4;
  cursor: default;
}

.listing-images-editor__remove:hover {
  border-color: var(--admin-accent, #e14554);
  color: var(--admin-accent, #e14554);
}

.listing-images-editor__file {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: fit-content;
  min-height: 36px;
  padding: 0 14px;
  border: 1px dashed var(--admin-border, #d4d8e0);
  border-radius: 8px;
  background: #fff;
  color: var(--admin-text, #1a1d26);
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
}

.listing-images-editor__file input {
  position: absolute;
  width: 1px;
  height: 1px;
  overflow: hidden;
  clip: rect(0 0 0 0);
}

.listing-images-editor__error {
  margin: 0;
  color: var(--admin-accent, #e14554);
  font-size: 13px;
  font-weight: 500;
}
</style>
