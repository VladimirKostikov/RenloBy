<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { adminListingRequests, type ListingRequestAdminDto } from '@/api/admin'
import AdminConfirmDialog from '@/modules/admin/components/AdminConfirmDialog.vue'
import AdminCrudForm from '@/modules/admin/components/AdminCrudForm.vue'
import AdminCrudTable from '@/modules/admin/components/AdminCrudTable.vue'
import AdminModal from '@/modules/admin/components/AdminModal.vue'
import AdminPageHeader from '@/modules/admin/components/AdminPageHeader.vue'
import { useAdminCrud } from '@/modules/admin/composables/useAdminCrud'
import { toTableRows, type AdminTableRow } from '@/modules/admin/types'

const { t } = useI18n()
const { items, loading, update, remove } = useAdminCrud<ListingRequestAdminDto>(adminListingRequests)

const showForm = ref(false)
const showConfirm = ref(false)
const editing = ref<ListingRequestAdminDto | null>(null)
const pendingDelete = ref<AdminTableRow | null>(null)
const formModel = ref<Record<string, unknown>>({})

const columns = [
  { key: 'id', label: t('admin.fields.id') },
  { key: 'listingId', label: t('admin.fields.listingId') },
  { key: 'listingAddress', label: t('admin.fields.address') },
  { key: 'name', label: t('admin.fields.fullName') },
  { key: 'phone', label: t('admin.fields.phone') },
  { key: 'message', label: t('admin.fields.body') },
  { key: 'status', label: t('admin.fields.requestStatus') },
  { key: 'createdAt', label: t('admin.fields.createdAt') },
]

const fields = [
  {
    key: 'status',
    label: t('admin.fields.requestStatus'),
    type: 'select' as const,
    options: [
      { value: 'new', label: t('admin.requestStatus.new') },
      { value: 'contacted', label: t('admin.requestStatus.contacted') },
      { value: 'closed', label: t('admin.requestStatus.closed') },
    ],
  },
]

function openEdit(item: AdminTableRow) {
  const request = item as unknown as ListingRequestAdminDto
  editing.value = request
  formModel.value = { status: request.status }
  showForm.value = true
}

async function save(payload: Record<string, unknown>) {
  if (!editing.value) {
    return
  }
  await update(editing.value.id, { status: String(payload.status ?? 'new') })
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
    <AdminPageHeader :title="t('admin.listingRequests')" />

    <AdminCrudTable
      :items="toTableRows(items)"
      :columns="columns"
      :loading="loading"
      @edit="openEdit"
      @remove="askRemove"
    />

    <AdminModal
      :open="showForm"
      :title="t('admin.edit')"
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
