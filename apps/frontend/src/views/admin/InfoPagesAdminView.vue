<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { adminInfoPages } from '@/api/admin'
import AdminConfirmDialog from '@/modules/admin/components/AdminConfirmDialog.vue'
import AdminCrudForm from '@/modules/admin/components/AdminCrudForm.vue'
import AdminCrudTable from '@/modules/admin/components/AdminCrudTable.vue'
import AdminModal from '@/modules/admin/components/AdminModal.vue'
import AdminPageHeader from '@/modules/admin/components/AdminPageHeader.vue'
import { useAdminCrud } from '@/modules/admin/composables/useAdminCrud'
import { toTableRows, type AdminTableRow } from '@/modules/admin/types'
import type { InfoPageDto } from '@/types/info'

const { t } = useI18n()
const { items, loading, create, update, remove } = useAdminCrud<InfoPageDto>(adminInfoPages)

const showForm = ref(false)
const showConfirm = ref(false)
const editing = ref<InfoPageDto | null>(null)
const pendingDelete = ref<AdminTableRow | null>(null)
const formModel = ref<Record<string, unknown>>({})

const columns = [
  { key: 'id', label: t('admin.fields.id') },
  { key: 'slug', label: t('admin.fields.slug') },
  { key: 'title', label: t('admin.fields.title') },
  { key: 'category', label: t('admin.fields.category') },
  { key: 'sortOrder', label: t('admin.fields.sortOrder') },
]

const fields = [
  { key: 'slug', label: t('admin.fields.slug') },
  { key: 'title', label: t('admin.fields.title') },
  { key: 'body', label: t('admin.fields.body'), type: 'wysiwyg' as const },
  {
    key: 'category',
    label: t('admin.fields.category'),
    type: 'select' as const,
    options: [
      { value: 'buyers', label: t('footer.links.buyers') },
      { value: 'sellers', label: t('footer.links.sellers') },
      { value: 'renters', label: t('footer.links.renters') },
      { value: 'deal_safety', label: t('footer.links.dealSafety') },
      { value: 'faq', label: t('footer.links.faq') },
      { value: 'support', label: t('footer.links.support') },
      { value: 'offer', label: t('footer.links.offer') },
      { value: 'privacy', label: t('footer.links.privacy') },
      { value: 'personal_data', label: t('footer.links.personalData') },
    ],
  },
  { key: 'importantNote', label: t('admin.fields.importantNote'), type: 'textarea' as const },
  { key: 'faqItems', label: t('admin.fields.faqItems'), type: 'textarea' as const },
  { key: 'sortOrder', label: t('admin.fields.sortOrder'), type: 'number' as const },
  { key: 'metaTitle', label: t('admin.fields.metaTitle') },
  { key: 'metaDescription', label: t('admin.fields.metaDescription'), type: 'textarea' as const },
]

function openCreate() {
  editing.value = null
  formModel.value = {
    slug: '',
    title: '',
    body: '',
    category: 'deal_safety',
    importantNote: '',
    faqItems: '[]',
    sortOrder: 0,
    metaTitle: '',
    metaDescription: '',
  }
  showForm.value = true
}

function openEdit(item: AdminTableRow) {
  const page = item as unknown as InfoPageDto
  editing.value = page
  formModel.value = {
    slug: page.slug,
    title: page.title,
    body: page.body,
    category: page.category,
    importantNote: page.importantNote ?? '',
    faqItems: JSON.stringify(page.faqItems, null, 2),
    sortOrder: page.sortOrder,
    metaTitle: page.metaTitle ?? '',
    metaDescription: page.metaDescription ?? '',
  }
  showForm.value = true
}

function parsePayload(payload: Record<string, unknown>): Record<string, unknown> {
  const faqRaw = payload.faqItems
  let faqItems: unknown[] = []

  if (typeof faqRaw === 'string' && faqRaw.trim() !== '') {
    const parsed = JSON.parse(faqRaw)
    if (Array.isArray(parsed)) {
      faqItems = parsed
    }
  }

  return {
    ...payload,
    faqItems,
    sortOrder: Number(payload.sortOrder ?? 0),
    importantNote: payload.importantNote === '' ? null : payload.importantNote,
    metaTitle: payload.metaTitle === '' ? null : payload.metaTitle,
    metaDescription: payload.metaDescription === '' ? null : payload.metaDescription,
  }
}

async function save(payload: Record<string, unknown>) {
  const normalized = parsePayload(payload)

  if (editing.value) {
    await update(editing.value.id, normalized)
  } else {
    await create(normalized)
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
    <AdminPageHeader :title="t('admin.infoPages')">
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
      wide
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

<style scoped>
.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 20px;
}

.page-header__btn {
  padding: 10px 16px;
  border: none;
  border-radius: var(--radius-sm);
  background: var(--accent);
  color: #fff;
  font-weight: 600;
}
</style>
