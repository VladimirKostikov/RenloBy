<script setup lang="ts">
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { adminMediaFiles, type MediaFileDto } from '@/api/admin'
import { isSafeMediaUrl } from '@/lib/isSafeMediaUrl'
import AdminConfirmDialog from '@/modules/admin/components/AdminConfirmDialog.vue'
import AdminPageHeader from '@/modules/admin/components/AdminPageHeader.vue'
import { useAdminCrud } from '@/modules/admin/composables/useAdminCrud'

const { t } = useI18n()
const { items, loading, remove } = useAdminCrud<MediaFileDto>(adminMediaFiles)

const showConfirm = ref(false)
const pendingDelete = ref<MediaFileDto | null>(null)

const rows = computed(() => (Array.isArray(items.value) ? items.value : []))

function formatSize(bytes: number): string {
  if (!Number.isFinite(bytes) || bytes < 0) {
    return '-'
  }
  if (bytes < 1024) {
    return `${bytes} B`
  }
  if (bytes < 1024 * 1024) {
    return `${(bytes / 1024).toFixed(1)} KB`
  }
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
}

function formatDate(value: string): string {
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) {
    return '-'
  }
  return date.toLocaleString()
}

function contextLabel(context: string): string {
  if (context === 'avatar') {
    return t('admin.mediaFilesContext.avatar')
  }
  if (context === 'article') {
    return t('admin.mediaFilesContext.article')
  }
  return context
}

function askRemove(item: MediaFileDto) {
  pendingDelete.value = item
  showConfirm.value = true
}

async function confirmRemove() {
  if (pendingDelete.value) {
    await remove(pendingDelete.value.id)
  }
  showConfirm.value = false
  pendingDelete.value = null
}

function previewUrl(url: string): string | null {
  return isSafeMediaUrl(url) ? url : null
}
</script>

<template>
  <div>
    <AdminPageHeader :title="t('admin.mediaFiles')" />

    <div class="media-files-admin__wrap">
      <div v-if="loading" class="media-files-admin__state">{{ t('admin.loading') }}</div>
      <table v-else class="media-files-admin__table">
        <thead>
          <tr>
            <th>{{ t('admin.fields.preview') }}</th>
            <th>{{ t('admin.fields.id') }}</th>
            <th>{{ t('admin.fields.context') }}</th>
            <th>{{ t('admin.fields.type') }}</th>
            <th>{{ t('admin.fields.size') }}</th>
            <th>{{ t('admin.fields.userId') }}</th>
            <th>{{ t('admin.fields.createdAt') }}</th>
            <th>{{ t('admin.fields.isTest') }}</th>
            <th>{{ t('admin.actions') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="rows.length === 0">
            <td colspan="9" class="media-files-admin__empty">{{ t('admin.empty') }}</td>
          </tr>
          <tr v-for="item in rows" :key="item.id">
            <td>
              <a
                v-if="previewUrl(item.url)"
                class="media-files-admin__preview-link"
                :href="previewUrl(item.url)!"
                target="_blank"
                rel="noopener noreferrer"
              >
                <img
                  v-if="item.type === 'image'"
                  class="media-files-admin__thumb"
                  :src="previewUrl(item.url)!"
                  :alt="item.originalName || item.url"
                >
                <span v-else class="media-files-admin__video">video</span>
              </a>
              <span v-else>-</span>
            </td>
            <td>{{ item.id }}</td>
            <td>{{ contextLabel(item.context) }}</td>
            <td>{{ item.type }}</td>
            <td>{{ formatSize(item.size) }}</td>
            <td>{{ item.uploadedByEmail || item.uploadedById || '-' }}</td>
            <td>{{ formatDate(item.createdAt) }}</td>
            <td>{{ item.isTest ? t('admin.yes') : t('admin.no') }}</td>
            <td>
              <button
                type="button"
                class="media-files-admin__btn media-files-admin__btn--danger"
                @click="askRemove(item)"
              >
                {{ t('admin.delete') }}
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <AdminConfirmDialog
      :open="showConfirm"
      :message="t('admin.confirmDelete')"
      @confirm="confirmRemove"
      @cancel="showConfirm = false"
    />
  </div>
</template>

<style scoped>
.media-files-admin__wrap {
  overflow-x: auto;
  border: 1px solid #e8eaef;
  border-radius: 10px;
  background: #fff;
}

.media-files-admin__table {
  width: 100%;
  border-collapse: collapse;
  font-size: 14px;
}

.media-files-admin__table th,
.media-files-admin__table td {
  padding: 12px 14px;
  text-align: left;
  border-bottom: 1px solid #e8eaef;
  vertical-align: middle;
}

.media-files-admin__table th {
  background: #f8f9fb;
  font-size: 12px;
  font-weight: 700;
  color: #6b7280;
  text-transform: uppercase;
}

.media-files-admin__preview-link {
  display: inline-flex;
  text-decoration: none;
}

.media-files-admin__thumb {
  display: block;
  width: 56px;
  height: 56px;
  object-fit: cover;
  border-radius: 8px;
  border: 1px solid #e8eaef;
  background: #f3f4f6;
}

.media-files-admin__video {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 56px;
  height: 56px;
  border-radius: 8px;
  border: 1px solid #e8eaef;
  background: #f3f4f6;
  color: #6b7280;
  font-size: 11px;
  font-weight: 700;
}

.media-files-admin__btn {
  min-height: 34px;
  padding: 0 12px;
  border: 1px solid #e8eaef;
  border-radius: 8px;
  background: #fff;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
}

.media-files-admin__btn--danger:hover {
  background: #e14554;
  border-color: #e14554;
  color: #fff;
}

.media-files-admin__empty,
.media-files-admin__state {
  padding: 40px 24px;
  text-align: center;
  color: #6b7280;
}
</style>
