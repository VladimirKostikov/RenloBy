<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { adminCities } from '@/api/admin'
import AdminConfirmDialog from '@/modules/admin/components/AdminConfirmDialog.vue'
import AdminCrudForm from '@/modules/admin/components/AdminCrudForm.vue'
import AdminCrudTable from '@/modules/admin/components/AdminCrudTable.vue'
import AdminModal from '@/modules/admin/components/AdminModal.vue'
import AdminPageHeader from '@/modules/admin/components/AdminPageHeader.vue'
import { useAdminCrud } from '@/modules/admin/composables/useAdminCrud'
import { toTableRows, type AdminTableRow } from '@/modules/admin/types'
import type { CityDto } from '@/types'

const { t } = useI18n()
const { items, loading, create, update, remove } = useAdminCrud<CityDto>(adminCities)

const showForm = ref(false)
const showConfirm = ref(false)
const editing = ref<CityDto | null>(null)
const pendingDelete = ref<AdminTableRow | null>(null)
const formModel = ref<Record<string, unknown>>({})

const columns = [
  { key: 'id', label: t('admin.fields.id') },
  { key: 'name', label: t('admin.fields.name') },
  { key: 'slug', label: t('admin.fields.slug') },
  { key: 'regionSlug', label: t('admin.fields.regionSlug') },
]

const fields = [
  { key: 'name', label: t('admin.fields.name') },
  { key: 'slug', label: t('admin.fields.slug') },
  { key: 'regionSlug', label: t('admin.fields.regionSlug') },
]

function openCreate() {
  editing.value = null
  formModel.value = { name: '', slug: '', regionSlug: 'minsk-region' }
  showForm.value = true
}

function openEdit(item: AdminTableRow) {
  const city = item as unknown as CityDto
  editing.value = city
  formModel.value = { name: city.name, slug: city.slug, regionSlug: city.regionSlug }
  showForm.value = true
}

async function save(payload: Record<string, unknown>) {
  if (editing.value) {
    await update(editing.value.id, payload)
  } else {
    await create(payload)
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
    <AdminPageHeader :title="t('admin.cities')">
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
.admin-btn-primary {
  min-height: 42px;
  padding: 0 18px;
  border: none;
  border-radius: 8px;
  background: var(--admin-accent, #e14554);
  color: #fff;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 180ms ease, transform 180ms ease;
}

.admin-btn-primary:hover {
  background: var(--admin-accent-hover, #c93a48);
}

.admin-btn-primary:active {
  transform: scale(0.98);
}
</style>
