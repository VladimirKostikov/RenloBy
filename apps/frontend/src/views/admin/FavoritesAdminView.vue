<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { adminFavorites } from '@/api/admin'
import AdminConfirmDialog from '@/modules/admin/components/AdminConfirmDialog.vue'
import AdminCrudForm from '@/modules/admin/components/AdminCrudForm.vue'
import AdminCrudTable from '@/modules/admin/components/AdminCrudTable.vue'
import AdminModal from '@/modules/admin/components/AdminModal.vue'
import AdminPageHeader from '@/modules/admin/components/AdminPageHeader.vue'
import { useAdminCrud } from '@/modules/admin/composables/useAdminCrud'
import { toTableRows, type AdminTableRow } from '@/modules/admin/types'
import type { FavoriteDto } from '@/types'

const { t } = useI18n()
const { items, loading, create, remove } = useAdminCrud<FavoriteDto>(adminFavorites)

const showForm = ref(false)
const showConfirm = ref(false)
const pendingDelete = ref<AdminTableRow | null>(null)
const formModel = ref<Record<string, unknown>>({})

const columns = [
  { key: 'id', label: t('admin.fields.id') },
  { key: 'userId', label: t('admin.fields.userId') },
  { key: 'listingId', label: t('admin.fields.listingId') },
]

const fields = [
  { key: 'userId', label: t('admin.fields.userId'), type: 'number' as const },
  { key: 'listingId', label: t('admin.fields.listingId'), type: 'number' as const },
]

function openCreate() {
  formModel.value = { userId: 1, listingId: 1 }
  showForm.value = true
}

async function save(payload: Record<string, unknown>) {
  await create({
    userId: Number(payload.userId),
    listingId: Number(payload.listingId),
  })
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
    <AdminPageHeader :title="t('admin.favorites')">
      <template #actions>
        <button type="button" class="admin-btn-primary" @click="openCreate">{{ t('admin.create') }}</button>
      </template>
    </AdminPageHeader>

    <AdminCrudTable
      hide-edit
      :items="toTableRows(items)"
      :columns="columns"
      :loading="loading"
      @remove="askRemove"
    />

    <AdminModal
      :open="showForm"
      :title="t('admin.create')"
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

.page-header h1 {
  margin: 0;
}

.page-header__btn {
  padding: 8px 16px;
  border: none;
  border-radius: var(--radius-sm);
  background: var(--accent);
  color: #fff;
  font-weight: 600;
}
</style>
