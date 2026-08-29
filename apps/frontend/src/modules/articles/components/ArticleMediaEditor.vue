<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { uploadAdminMedia, MediaFileTooLargeError } from '@/api/adminMedia'
import { isMediaFileWithinLimit } from '@/lib/mediaUploadLimits'
import { isSafeMediaUrl } from '@/lib/isSafeMediaUrl'
import type { ArticleMediaItem } from '@/types/article'

const props = defineProps<{
  coverImage: string
  media: ArticleMediaItem[]
}>()

const emit = defineEmits<{
  'update:coverImage': [value: string]
  'update:media': [value: ArticleMediaItem[]]
}>()

const { t } = useI18n()
const uploadingCover = ref(false)
const uploadingMedia = ref(false)
const error = ref('')

async function onCoverSelected(event: Event) {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  input.value = ''
  if (!file) {
    return
  }

  if (!isMediaFileWithinLimit(file)) {
    error.value = t('admin.media.tooLarge')
    return
  }

  uploadingCover.value = true
  error.value = ''
  try {
    const uploaded = await uploadAdminMedia(file)
    emit('update:coverImage', uploaded.url)
  } catch (err) {
    error.value = err instanceof MediaFileTooLargeError
      ? t('admin.media.tooLarge')
      : t('admin.media.uploadError')
  } finally {
    uploadingCover.value = false
  }
}

async function onMediaSelected(event: Event) {
  const input = event.target as HTMLInputElement
  const files = Array.from(input.files ?? [])
  input.value = ''
  if (files.length === 0) {
    return
  }

  uploadingMedia.value = true
  error.value = ''
  const next = [...props.media]
  try {
    for (const file of files) {
      if (next.length >= 12) {
        break
      }
      if (!isMediaFileWithinLimit(file)) {
        error.value = t('admin.media.tooLarge')
        continue
      }
      const uploaded = await uploadAdminMedia(file)
      next.push({ url: uploaded.url, type: uploaded.type })
    }
    emit('update:media', next)
  } catch (err) {
    error.value = err instanceof MediaFileTooLargeError
      ? t('admin.media.tooLarge')
      : t('admin.media.uploadError')
  } finally {
    uploadingMedia.value = false
  }
}

function clearCover() {
  emit('update:coverImage', '')
}

function removeMedia(index: number) {
  emit(
    'update:media',
    props.media.filter((_, itemIndex) => itemIndex !== index),
  )
}
</script>

<template>
  <div class="article-media-editor">
    <div class="article-media-editor__block">
      <span class="article-media-editor__label">{{ t('admin.fields.coverImage') }}</span>
      <div v-if="coverImage && isSafeMediaUrl(coverImage)" class="article-media-editor__preview">
        <img :src="coverImage" alt="" />
        <button type="button" class="article-media-editor__remove" @click="clearCover">
          {{ t('admin.media.remove') }}
        </button>
      </div>
      <label class="article-media-editor__file">
        <input type="file" accept="image/jpeg,image/png,image/webp,image/gif" @change="onCoverSelected" />
        <span>{{ uploadingCover ? t('admin.media.uploading') : t('admin.media.uploadCover') }}</span>
      </label>
      <input
        class="article-media-editor__url"
        type="url"
        :value="coverImage"
        :placeholder="t('admin.media.urlPlaceholder')"
        @input="emit('update:coverImage', ($event.target as HTMLInputElement).value)"
      />
    </div>

    <div class="article-media-editor__block">
      <span class="article-media-editor__label">{{ t('admin.fields.media') }}</span>
      <div v-if="media.length" class="article-media-editor__grid">
        <div v-for="(item, index) in media" :key="`${item.url}-${index}`" class="article-media-editor__item">
          <img v-if="item.type === 'image' && isSafeMediaUrl(item.url)" :src="item.url" alt="" />
          <video v-else-if="item.type === 'video' && isSafeMediaUrl(item.url)" :src="item.url" controls muted />
          <button type="button" class="article-media-editor__remove" @click="removeMedia(index)">
            {{ t('admin.media.remove') }}
          </button>
        </div>
      </div>
      <label class="article-media-editor__file">
        <input
          type="file"
          accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm"
          multiple
          @change="onMediaSelected"
        />
        <span>{{ uploadingMedia ? t('admin.media.uploading') : t('admin.media.uploadFiles') }}</span>
      </label>
    </div>

    <p v-if="error" class="article-media-editor__error">{{ error }}</p>
  </div>
</template>

<style scoped>
.article-media-editor {
  display: flex;
  flex-direction: column;
  gap: 16px;
  margin-bottom: 8px;
}

.article-media-editor__block {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.article-media-editor__label {
  font-size: 13px;
  font-weight: 600;
  color: var(--admin-text-muted, #6b7280);
}

.article-media-editor__preview,
.article-media-editor__item {
  position: relative;
  overflow: hidden;
  border: 1px solid var(--admin-border, #e5e7eb);
  border-radius: 10px;
  background: var(--color-bg-muted);
}

.article-media-editor__preview {
  max-width: 320px;
}

.article-media-editor__preview img,
.article-media-editor__item img,
.article-media-editor__item video {
  display: block;
  width: 100%;
  height: 160px;
  object-fit: cover;
}

.article-media-editor__grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
  gap: 10px;
}

.article-media-editor__file {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: fit-content;
  min-height: 36px;
  padding: 0 14px;
  border-radius: 8px;
  border: 1px solid var(--admin-border, #d1d5db);
  background: var(--figma-surface);
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
}

.article-media-editor__file input {
  display: none;
}

.article-media-editor__url {
  width: 100%;
  min-height: 38px;
  padding: 8px 12px;
  border: 1px solid var(--admin-border, #d1d5db);
  border-radius: 8px;
  font: inherit;
}

.article-media-editor__remove {
  position: absolute;
  top: 8px;
  right: 8px;
  border: none;
  border-radius: 6px;
  padding: 4px 8px;
  background: rgba(0, 0, 0, 0.72);
  color: var(--figma-on-accent);
  font-size: 12px;
  cursor: pointer;
}

.article-media-editor__error {
  margin: 0;
  color: var(--color-danger);
  font-size: 13px;
}
</style>
