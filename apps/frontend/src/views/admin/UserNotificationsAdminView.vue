<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { adminUserNotifications, type UserNotificationAdminDto } from '@/api/admin'
import AdminConfirmDialog from '@/modules/admin/components/AdminConfirmDialog.vue'
import AdminCrudTable from '@/modules/admin/components/AdminCrudTable.vue'
import AdminPageHeader from '@/modules/admin/components/AdminPageHeader.vue'
import { useAdminCrud } from '@/modules/admin/composables/useAdminCrud'
import { toTableRows, type AdminTableRow } from '@/modules/admin/types'

const { t } = useI18n()
const { items, loading, remove } = useAdminCrud<UserNotificationAdminDto>(adminUserNotifications)

const showConfirm = ref(false)
const pendingDelete = ref<AdminTableRow | null>(null)

const columns = [
  { key: 'id', label: t('admin.fields.id') },
  { key: 'userId', label: t('admin.fields.userId') },
  { key: 'type', label: t('admin.fields.type') },
  { key: 'isRead', label: t('admin.fields.isRead') },
  { key: 'createdAt', label: t('admin.fields.createdAt') },
]

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
    <AdminPageHeader :title="t('admin.userNotifications')" />

    <AdminCrudTable
      :items="toTableRows(items)"
      :columns="columns"
      :loading="loading"
      hide-edit
      @remove="askRemove"
    />

    <AdminConfirmDialog
      :open="showConfirm"
      :message="t('admin.confirmDelete')"
      @confirm="confirmRemove"
      @cancel="showConfirm = false"
    />
  </div>
</template>
