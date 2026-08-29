<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { adminTariffs, type TariffDto } from '@/api/admin'
import AdminConfirmDialog from '@/modules/admin/components/AdminConfirmDialog.vue'
import AdminCrudForm from '@/modules/admin/components/AdminCrudForm.vue'
import AdminCrudTable from '@/modules/admin/components/AdminCrudTable.vue'
import AdminModal from '@/modules/admin/components/AdminModal.vue'
import AdminPageHeader from '@/modules/admin/components/AdminPageHeader.vue'
import { useAdminCrud } from '@/modules/admin/composables/useAdminCrud'
import { toTableRows, type AdminTableRow } from '@/modules/admin/types'

const { t } = useI18n()
const { items, loading, update, remove } = useAdminCrud<TariffDto>(adminTariffs)

const showForm = ref(false)
const showConfirm = ref(false)
const editing = ref<TariffDto | null>(null)
const pendingDelete = ref<AdminTableRow | null>(null)
const formModel = ref<Record<string, unknown>>({})

const columns = [
  { key: 'id', label: t('admin.fields.id') },
  { key: 'code', label: t('admin.fields.tariffCode') },
  { key: 'priceUsd', label: t('admin.fields.priceUsd') },
  { key: 'priceByn', label: t('admin.fields.priceByn') },
  { key: 'priceRub', label: t('admin.fields.priceRub') },
  { key: 'isPopular', label: t('admin.fields.isPopular') },
  { key: 'sortOrder', label: t('admin.fields.sortOrder') },
]

const fields = [
  { key: 'priceUsd', label: t('admin.fields.priceUsd') },
  { key: 'priceByn', label: t('admin.fields.priceByn') },
  { key: 'priceRub', label: t('admin.fields.priceRub') },
  { key: 'isPopular', label: t('admin.fields.isPopular'), type: 'checkbox' as const },
  { key: 'sortOrder', label: t('admin.fields.sortOrder'), type: 'number' as const },
  { key: 'isTest', label: t('admin.fields.isTest'), type: 'checkbox' as const },
]

function openEdit(item: AdminTableRow) {
  const tariff = item as unknown as TariffDto
  editing.value = tariff
  formModel.value = {
    code: tariff.code,
    priceUsd: tariff.priceUsd,
    priceByn: tariff.priceByn ?? '',
    priceRub: tariff.priceRub ?? '',
    isPopular: tariff.isPopular,
    sortOrder: tariff.sortOrder,
    isTest: tariff.isTest,
  }
  showForm.value = true
}

async function save(payload: Record<string, unknown>) {
  if (!editing.value) {
    return
  }
  await update(editing.value.id, {
    priceUsd: String(payload.priceUsd ?? ''),
    priceByn: String(payload.priceByn ?? ''),
    priceRub: String(payload.priceRub ?? ''),
    isPopular: Boolean(payload.isPopular),
    sortOrder: Number(payload.sortOrder ?? 0),
    isTest: Boolean(payload.isTest),
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
    <AdminPageHeader :title="t('admin.tariffs')" />

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
