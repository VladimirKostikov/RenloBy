<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { adminHeadSnippets, type HeadSnippetDto } from '@/api/admin'
import AdminConfirmDialog from '@/modules/admin/components/AdminConfirmDialog.vue'
import AdminCrudForm from '@/modules/admin/components/AdminCrudForm.vue'
import AdminCrudTable from '@/modules/admin/components/AdminCrudTable.vue'
import AdminModal from '@/modules/admin/components/AdminModal.vue'
import AdminPageHeader from '@/modules/admin/components/AdminPageHeader.vue'
import { useAdminCrud } from '@/modules/admin/composables/useAdminCrud'
import { toTableRows, type AdminTableRow } from '@/modules/admin/types'

const { t } = useI18n()
const { items, loading, create, update, remove } = useAdminCrud<HeadSnippetDto>(adminHeadSnippets)

const showForm = ref(false)
const showConfirm = ref(false)
const editing = ref<HeadSnippetDto | null>(null)
const pendingDelete = ref<AdminTableRow | null>(null)
const formModel = ref<Record<string, unknown>>({})

const columns = [
  { key: 'id', label: t('admin.fields.id') },
  { key: 'name', label: t('admin.fields.name') },
  { key: 'isEnabled', label: t('admin.fields.isEnabled') },
  { key: 'sortOrder', label: t('admin.fields.sortOrder') },
]

const fields = [
  { key: 'name', label: t('admin.fields.headSnippetName') },
  { key: 'code', label: t('admin.fields.headSnippetCode'), type: 'textarea' as const },
  { key: 'isEnabled', label: t('admin.fields.isEnabled'), type: 'checkbox' as const },
  { key: 'sortOrder', label: t('admin.fields.sortOrder'), type: 'number' as const },
  { key: 'isTest', label: t('admin.fields.isTest'), type: 'checkbox' as const },
]

function openCreate() {
  editing.value = null
  formModel.value = {
    name: '',
    code: '',
    isEnabled: true,
    sortOrder: 0,
    isTest: false,
  }
  showForm.value = true
}

function openEdit(item: AdminTableRow) {
  const snippet = item as unknown as HeadSnippetDto
  editing.value = snippet
  formModel.value = {
    name: snippet.name,
    code: snippet.code,
    isEnabled: snippet.isEnabled,
    sortOrder: snippet.sortOrder,
    isTest: snippet.isTest,
  }
  showForm.value = true
}

async function save(payload: Record<string, unknown>) {
  const data = {
    name: String(payload.name ?? ''),
    code: String(payload.code ?? ''),
    isEnabled: Boolean(payload.isEnabled),
    sortOrder: Number(payload.sortOrder ?? 0),
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
    <AdminPageHeader :title="t('admin.headSnippets')">
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
