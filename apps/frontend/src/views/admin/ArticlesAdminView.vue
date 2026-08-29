<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { adminArticles } from '@/api/admin'
import AdminConfirmDialog from '@/modules/admin/components/AdminConfirmDialog.vue'
import AdminCrudForm from '@/modules/admin/components/AdminCrudForm.vue'
import AdminCrudTable from '@/modules/admin/components/AdminCrudTable.vue'
import AdminModal from '@/modules/admin/components/AdminModal.vue'
import AdminPageHeader from '@/modules/admin/components/AdminPageHeader.vue'
import ArticleMediaEditor from '@/modules/articles/components/ArticleMediaEditor.vue'
import { useAdminCrud } from '@/modules/admin/composables/useAdminCrud'
import { toTableRows, type AdminTableRow } from '@/modules/admin/types'
import type { ArticleDto, ArticleMediaItem } from '@/types/article'

const { t } = useI18n()
const { items, loading, create, update, remove } = useAdminCrud<ArticleDto>(adminArticles)

const showForm = ref(false)
const showConfirm = ref(false)
const editing = ref<ArticleDto | null>(null)
const pendingDelete = ref<AdminTableRow | null>(null)
const formModel = ref<Record<string, unknown>>({})
const coverImage = ref('')
const media = ref<ArticleMediaItem[]>([])

const columns = [
  { key: 'id', label: t('admin.fields.id') },
  { key: 'slug', label: t('admin.fields.slug') },
  { key: 'title', label: t('admin.fields.title') },
  { key: 'category', label: t('admin.fields.category') },
  { key: 'isPublished', label: t('admin.fields.isPublished') },
]

const fields = [
  { key: 'slug', label: t('admin.fields.slug') },
  { key: 'title', label: t('admin.fields.title') },
  { key: 'excerpt', label: t('admin.fields.excerpt'), type: 'textarea' as const },
  { key: 'body', label: t('admin.fields.body'), type: 'wysiwyg' as const },
  {
    key: 'category',
    label: t('admin.fields.category'),
    type: 'select' as const,
    options: [
      { value: 'guides', label: 'guides' },
      { value: 'market', label: 'market' },
      { value: 'tips', label: 'tips' },
      { value: 'law', label: 'law' },
    ],
  },
  { key: 'isPublished', label: t('admin.fields.isPublished'), type: 'checkbox' as const },
  { key: 'publishedAt', label: t('admin.fields.publishedAt') },
  { key: 'metaTitle', label: t('admin.fields.metaTitle') },
  { key: 'metaDescription', label: t('admin.fields.metaDescription'), type: 'textarea' as const },
  { key: 'isTest', label: t('admin.fields.isTest'), type: 'checkbox' as const },
]

function openCreate() {
  editing.value = null
  coverImage.value = ''
  media.value = []
  formModel.value = {
    slug: '',
    title: '',
    excerpt: '',
    body: '',
    category: 'guides',
    isPublished: false,
    publishedAt: new Date().toISOString().slice(0, 10),
    metaTitle: '',
    metaDescription: '',
    isTest: true,
  }
  showForm.value = true
}

function openEdit(item: AdminTableRow) {
  const article = item as unknown as ArticleDto
  editing.value = article
  coverImage.value = article.coverImage ?? ''
  media.value = [...(article.media ?? [])]
  formModel.value = {
    slug: article.slug,
    title: article.title,
    excerpt: article.excerpt,
    body: article.body,
    category: article.category,
    isPublished: article.isPublished,
    publishedAt: article.publishedAt,
    metaTitle: article.metaTitle ?? '',
    metaDescription: article.metaDescription ?? '',
    isTest: article.isTest ?? false,
  }
  showForm.value = true
}

async function save(payload: Record<string, unknown>) {
  const data: Partial<ArticleDto> = {
    slug: String(payload.slug ?? ''),
    title: String(payload.title ?? ''),
    excerpt: String(payload.excerpt ?? ''),
    body: String(payload.body ?? ''),
    category: payload.category as ArticleDto['category'],
    isPublished: Boolean(payload.isPublished),
    publishedAt: String(payload.publishedAt ?? ''),
    coverImage: coverImage.value.trim() === '' ? null : coverImage.value.trim(),
    media: media.value,
    metaTitle: payload.metaTitle === '' || payload.metaTitle == null ? null : String(payload.metaTitle),
    metaDescription:
      payload.metaDescription === '' || payload.metaDescription == null
        ? null
        : String(payload.metaDescription),
    isTest: Boolean(payload.isTest),
  }
  if (editing.value) {
    await update(editing.value.id, data)
  } else {
    await create(data)
  }
  showForm.value = false
}

function askRemove(item: AdminTableRow) {
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
</script>

<template>
  <div>
    <AdminPageHeader :title="t('admin.articles')">
      <template #actions>
        <button type="button" class="admin-btn-primary" @click="openCreate">{{ t('admin.create') }}</button>
      </template>
    </AdminPageHeader>

    <AdminCrudTable
      :items="toTableRows(items)"
      :columns="columns"
      :loading="loading"
      @edit="openEdit"
      @remove="askRemove"
    />

    <AdminModal
      :open="showForm"
      :title="editing ? t('admin.edit') : t('admin.create')"
      wide
      @close="showForm = false"
    >
      <ArticleMediaEditor
        :cover-image="coverImage"
        :media="media"
        @update:cover-image="coverImage = $event"
        @update:media="media = $event"
      />
      <AdminCrudForm
        :fields="fields"
        :model-value="formModel"
        @save="save"
        @cancel="showForm = false"
      />
    </AdminModal>

    <AdminConfirmDialog
      :open="showConfirm"
      :message="t('admin.confirmDelete')"
      @confirm="confirmRemove"
      @cancel="showConfirm = false"
    />
  </div>
</template>
