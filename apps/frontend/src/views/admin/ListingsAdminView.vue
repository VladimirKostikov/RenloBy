<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { adminListings } from '@/api/admin'
import AdminConfirmDialog from '@/modules/admin/components/AdminConfirmDialog.vue'
import AdminCrudForm from '@/modules/admin/components/AdminCrudForm.vue'
import AdminCrudTable from '@/modules/admin/components/AdminCrudTable.vue'
import AdminModal from '@/modules/admin/components/AdminModal.vue'
import AdminPageHeader from '@/modules/admin/components/AdminPageHeader.vue'
import ListingImagesEditor from '@/modules/admin/components/ListingImagesEditor.vue'
import ListingStatusChip from '@/components/ListingStatusChip.vue'
import { useAdminCrud } from '@/modules/admin/composables/useAdminCrud'
import { toTableRows, type AdminTableRow } from '@/modules/admin/types'
import { useAdminTestModeStore } from '@/stores/adminTestMode'
import type { ListingDto, ListingStatus } from '@/types'

const { t } = useI18n()
const testMode = useAdminTestModeStore()

const statusFilter = ref<ListingStatus | ''>('pending')
const { items, loading, create, update, remove, load } = useAdminCrud<ListingDto>(adminListings, () => (
  statusFilter.value ? { status: statusFilter.value } : {}
))

watch(statusFilter, () => {
  void load()
})

const showTestModeHint = computed(
  () => testMode.isTest && statusFilter.value === 'pending' && !loading.value && items.value.length === 0,
)

function disableTestMode() {
  testMode.requestToggle(false)
}

const showForm = ref(false)
const showConfirm = ref(false)
const editing = ref<ListingDto | null>(null)
const pendingDelete = ref<AdminTableRow | null>(null)
const formModel = ref<Record<string, unknown>>({})
const formImages = ref<string[]>([])

const statusOptions = [
  { value: 'draft', label: t('admin.listingStatuses.draft') },
  { value: 'pending', label: t('admin.listingStatuses.pending') },
  { value: 'published', label: t('admin.listingStatuses.published') },
  { value: 'rejected', label: t('admin.listingStatuses.rejected') },
  { value: 'archived', label: t('admin.listingStatuses.archived') },
]

const LISTING_STATUSES: ListingStatus[] = ['draft', 'pending', 'published', 'rejected', 'archived']

function toListingStatus(value: unknown, fallback: ListingStatus): ListingStatus {
  return LISTING_STATUSES.includes(value as ListingStatus) ? (value as ListingStatus) : fallback
}

const filterOptions: Array<{ value: ListingStatus | ''; label: string }> = [
  { value: 'pending', label: t('admin.listingStatuses.pending') },
  { value: '', label: t('admin.listingStatuses.all') },
  { value: 'draft', label: t('admin.listingStatuses.draft') },
  { value: 'published', label: t('admin.listingStatuses.published') },
  { value: 'rejected', label: t('admin.listingStatuses.rejected') },
  { value: 'archived', label: t('admin.listingStatuses.archived') },
]

const columns = [
  { key: 'id', label: t('admin.fields.id') },
  { key: 'status', label: t('admin.fields.status') },
  { key: 'dealType', label: t('admin.fields.dealType') },
  { key: 'price', label: t('admin.fields.price') },
  { key: 'rooms', label: t('admin.fields.rooms') },
  { key: 'cityName', label: t('admin.fields.city') },
  { key: 'districtName', label: t('admin.fields.district') },
  { key: 'address', label: t('admin.fields.address') },
  { key: 'verified', label: t('admin.fields.verified') },
]

const fields = [
  {
    key: 'status',
    label: t('admin.fields.status'),
    type: 'select' as const,
    options: statusOptions,
  },
  {
    key: 'dealType',
    label: t('admin.fields.dealType'),
    type: 'select' as const,
    options: [
      { value: 'sale', label: t('account.listings.dealTypes.sale') },
      { value: 'rent', label: t('account.listings.dealTypes.rent') },
      { value: 'commercial', label: t('account.listings.dealTypes.commercial') },
    ],
  },
  {
    key: 'listingType',
    label: t('admin.fields.listingType'),
    type: 'select' as const,
    options: [
      { value: 'apartment', label: 'Apartment' },
      { value: 'house', label: 'House' },
      { value: 'room', label: 'Room' },
    ],
  },
  { key: 'price', label: t('admin.fields.price'), type: 'number' as const },
  { key: 'rooms', label: t('admin.fields.rooms'), type: 'number' as const },
  { key: 'area', label: t('admin.fields.area'), type: 'number' as const },
  { key: 'floor', label: t('admin.fields.floor'), type: 'number' as const },
  { key: 'totalFloors', label: t('admin.fields.totalFloors'), type: 'number' as const },
  { key: 'city', label: t('admin.fields.city') },
  { key: 'district', label: t('admin.fields.district') },
  { key: 'metro', label: t('admin.fields.metro') },
  { key: 'address', label: t('admin.fields.address') },
  { key: 'latitude', label: t('admin.fields.latitude'), type: 'number' as const },
  { key: 'longitude', label: t('admin.fields.longitude'), type: 'number' as const },
  { key: 'userId', label: t('admin.fields.userId'), type: 'number' as const },
  { key: 'verified', label: t('admin.fields.verified'), type: 'checkbox' as const },
  { key: 'aiGoodPrice', label: t('admin.fields.aiGoodPrice'), type: 'checkbox' as const },
]

function openCreate() {
  editing.value = null
  formImages.value = []
  formModel.value = {
    status: 'published',
    dealType: 'sale',
    listingType: 'apartment',
    price: 0,
    rooms: 1,
    area: 40,
    floor: 1,
    totalFloors: 9,
    city: '',
    district: '',
    metro: '',
    address: '',
    latitude: 53.9,
    longitude: 27.5,
    userId: 1,
    verified: false,
    aiGoodPrice: false,
  }
  showForm.value = true
}

function openEdit(listing: ListingDto) {
  editing.value = listing
  formImages.value = [...(listing.images ?? [])]
  formModel.value = {
    status: listing.status,
    dealType: listing.dealType,
    listingType: listing.listingType,
    price: listing.price,
    rooms: listing.rooms,
    area: listing.area,
    floor: listing.floor,
    totalFloors: listing.totalFloors,
    city: listing.cityName ?? '',
    district: listing.districtName ?? '',
    metro: listing.metroStationName ?? '',
    address: listing.address,
    latitude: listing.latitude,
    longitude: listing.longitude,
    userId: listing.userId,
    verified: listing.verified,
    aiGoodPrice: listing.aiGoodPrice,
    isTest: listing.isTest ?? false,
  }
  showForm.value = true
}

async function save(payload: Record<string, unknown>) {
  const metro = String(payload.metro ?? '').trim()
  const data = {
    ...payload,
    status: toListingStatus(payload.status, 'pending'),
    price: Number(payload.price),
    rooms: Number(payload.rooms),
    area: Number(payload.area),
    floor: Number(payload.floor),
    totalFloors: Number(payload.totalFloors),
    latitude: Number(payload.latitude),
    longitude: Number(payload.longitude),
    city: String(payload.city ?? '').trim(),
    district: String(payload.district ?? '').trim(),
    metro: metro !== '' ? metro : null,
    userId: Number(payload.userId),
    verified: Boolean(payload.verified),
    aiGoodPrice: Boolean(payload.aiGoodPrice),
    images: formImages.value,
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

function statusLabel(status: unknown) {
  const key = String(status)
  if (
    key === 'draft'
    || key === 'pending'
    || key === 'published'
    || key === 'rejected'
    || key === 'archived'
  ) {
    return t(`admin.listingStatuses.${key}`)
  }
  return key
}

function openEditFromRow(item: AdminTableRow) {
  const listing = items.value.find((row) => row.id === item.id)
  if (listing) {
    openEdit(listing)
  }
}
</script>

<template>
  <div>
    <AdminPageHeader :title="t('admin.listings')">
      <template #actions>
        <button type="button" class="admin-btn-primary" @click="openCreate">{{ t('admin.create') }}</button>
      </template>
    </AdminPageHeader>

    <div class="listings-admin__filters" role="group" :aria-label="t('admin.fields.status')">
      <button
        v-for="option in filterOptions"
        :key="option.value || 'all'"
        type="button"
        class="listings-admin__filter"
        :class="{ 'listings-admin__filter--active': statusFilter === option.value }"
        @click="statusFilter = option.value"
      >
        {{ option.label }}
      </button>
    </div>

    <p v-if="showTestModeHint" class="listings-admin__hint">
      {{ t('admin.listingsTestModePendingHint') }}
      <button type="button" class="listings-admin__hint-btn" @click="disableTestMode">
        {{ t('admin.listingsShowRealData') }}
      </button>
    </p>

    <AdminCrudTable
      :items="toTableRows(items)"
      :columns="columns"
      :loading="loading"
      @edit="openEditFromRow"
      @remove="askRemove"
    >
      <template #cell-status="{ item }">
        <div class="listings-admin__status-cell">
          <span class="listings-admin__badges">
            <ListingStatusChip
              :status="String(item.status)"
              :label="statusLabel(item.status)"
            />
            <ListingStatusChip
              v-if="item.isTest"
              status="test"
              :label="t('admin.listingStatuses.test')"
            />
          </span>
        </div>
      </template>
      <template #cell-isTest="{ value }">
        <ListingStatusChip
          v-if="value"
          status="test"
          :label="t('admin.listingStatuses.test')"
        />
        <span v-else>{{ t('admin.no') }}</span>
      </template>
    </AdminCrudTable>

    <AdminModal
      wide
      :open="showForm"
      :title="editing ? t('admin.edit') : t('admin.create')"
      @close="showForm = false"
    >
      <ListingImagesEditor v-model:images="formImages" />
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
.listings-admin__filters {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 16px;
}

.listings-admin__hint {
  margin: 0 0 16px;
  padding: 12px 14px;
  border: 1px solid color-mix(in srgb, var(--admin-accent, #e14554) 28%, #fff);
  border-radius: 10px;
  background: color-mix(in srgb, var(--admin-accent, #e14554) 6%, #fff);
  color: var(--admin-text, #1a1d26);
  font-size: 14px;
  line-height: 1.45;
}

.listings-admin__hint-btn {
  display: inline;
  margin-left: 6px;
  padding: 0;
  border: none;
  background: none;
  color: var(--admin-accent, #e14554);
  font: inherit;
  font-weight: 700;
  text-decoration: underline;
  cursor: pointer;
}

.listings-admin__filter {
  min-height: 36px;
  padding: 0 12px;
  border: 1px solid var(--admin-border, #ddd);
  border-radius: 8px;
  background: #fff;
  color: inherit;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
}

.listings-admin__filter--active {
  border-color: transparent;
  background: var(--figma-accent, #e14554);
  color: #fff;
}

.listings-admin__status-cell {
  white-space: nowrap;
}

.listings-admin__badges {
  display: inline-flex;
  flex-wrap: nowrap;
  gap: 6px;
  align-items: center;
  white-space: nowrap;
}
</style>
