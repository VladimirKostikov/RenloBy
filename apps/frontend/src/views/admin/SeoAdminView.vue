<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { adminSeoMeta } from '@/api/admin'
import AdminConfirmDialog from '@/modules/admin/components/AdminConfirmDialog.vue'
import AdminCrudForm from '@/modules/admin/components/AdminCrudForm.vue'
import AdminCrudTable from '@/modules/admin/components/AdminCrudTable.vue'
import AdminModal from '@/modules/admin/components/AdminModal.vue'
import AdminPageHeader from '@/modules/admin/components/AdminPageHeader.vue'
import { useAdminCrud } from '@/modules/admin/composables/useAdminCrud'
import { toTableRows, type AdminTableRow } from '@/modules/admin/types'

type SeoMetaDto = {
  id: number
  pageKey: string
  locale: string
  title: string
  description: string
  h1: string | null
  keywords: string | null
}

const { t } = useI18n()
const { items, loading, create, update, remove } = useAdminCrud<SeoMetaDto>(adminSeoMeta)

const showForm = ref(false)
const showConfirm = ref(false)
const editing = ref<SeoMetaDto | null>(null)
const pendingDelete = ref<AdminTableRow | null>(null)
const formModel = ref<Record<string, unknown>>({})

const columns = [
  { key: 'id', label: t('admin.fields.id') },
  { key: 'pageKey', label: t('admin.fields.pageKey') },
  { key: 'locale', label: t('admin.fields.locale') },
  { key: 'title', label: t('admin.fields.title') },
  { key: 'keywords', label: t('admin.fields.keywords') },
  { key: 'h1', label: t('admin.fields.h1') },
]

const fields = [
  { key: 'pageKey', label: t('admin.fields.pageKey') },
  {
    key: 'locale',
    label: t('admin.fields.locale'),
    type: 'select' as const,
    options: [
      { value: 'ru', label: 'ru' },
      { value: 'en', label: 'en' },
    ],
  },
  { key: 'title', label: t('admin.fields.title') },
  { key: 'description', label: t('admin.fields.description'), type: 'textarea' as const },
  { key: 'keywords', label: t('admin.fields.keywords'), type: 'textarea' as const },
  { key: 'h1', label: t('admin.fields.h1') },
]

function openCreate() {
  editing.value = null
  formModel.value = { pageKey: '', locale: 'ru', title: '', description: '', keywords: '', h1: '' }
  showForm.value = true
}

function openEdit(item: AdminTableRow) {
  const seo = item as unknown as SeoMetaDto
  editing.value = seo
  formModel.value = {
    pageKey: seo.pageKey,
    locale: seo.locale,
    title: seo.title,
    description: seo.description,
    keywords: seo.keywords ?? '',
    h1: seo.h1 ?? '',
  }
  showForm.value = true
}

async function save(payload: Record<string, unknown>) {
  const data: Partial<SeoMetaDto> = {
    pageKey: String(payload.pageKey),
    locale: String(payload.locale),
    title: String(payload.title),
    description: String(payload.description),
    keywords: payload.keywords === '' || payload.keywords == null ? null : String(payload.keywords),
    h1: payload.h1 === '' || payload.h1 == null ? null : String(payload.h1),
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
    <AdminPageHeader :title="t('admin.seo')">
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
      @close="showForm = false"
    >
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
