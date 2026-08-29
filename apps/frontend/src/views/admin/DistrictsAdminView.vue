<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { adminDistricts } from '@/api/admin'
import AdminConfirmDialog from '@/modules/admin/components/AdminConfirmDialog.vue'
import AdminCrudForm from '@/modules/admin/components/AdminCrudForm.vue'
import AdminCrudTable from '@/modules/admin/components/AdminCrudTable.vue'
import AdminModal from '@/modules/admin/components/AdminModal.vue'
import AdminPageHeader from '@/modules/admin/components/AdminPageHeader.vue'
import { useAdminCrud } from '@/modules/admin/composables/useAdminCrud'
import { toTableRows, type AdminTableRow } from '@/modules/admin/types'
import type { DistrictDto } from '@/types'

const { t } = useI18n()
const { items, loading, create, update, remove } = useAdminCrud<DistrictDto>(adminDistricts)

const showForm = ref(false)
const showConfirm = ref(false)
const editing = ref<DistrictDto | null>(null)
const pendingDelete = ref<AdminTableRow | null>(null)
const formModel = ref<Record<string, unknown>>({})

const columns = [
  { key: 'id', label: t('admin.fields.id') },
  { key: 'name', label: t('admin.fields.name') },
  { key: 'slug', label: t('admin.fields.slug') },
  { key: 'cityId', label: t('admin.fields.cityId') },
]

const fields = [
  { key: 'name', label: t('admin.fields.name') },
  { key: 'slug', label: t('admin.fields.slug') },
  { key: 'cityId', label: t('admin.fields.cityId'), type: 'number' as const },
]

function openCreate() {
  editing.value = null
  formModel.value = { name: '', slug: '', cityId: 1 }
  showForm.value = true
}

function openEdit(item: AdminTableRow) {
  const district = item as unknown as DistrictDto
  editing.value = district
  formModel.value = { name: district.name, slug: district.slug, cityId: district.cityId }
  showForm.value = true
}

async function save(payload: Record<string, unknown>) {
  const data = { ...payload, cityId: Number(payload.cityId) }
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
    <AdminPageHeader :title="t('admin.districts')">
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
