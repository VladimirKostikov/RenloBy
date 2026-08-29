<script setup lang="ts">
import { storeToRefs } from 'pinia'
import { computed, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { archiveMyListing, deleteMyDraftListing, fetchMyListings, publishMyListing } from '@/api/account'
import ListingDetailModal from '@/components/ListingDetailModal.vue'
import ListingStatusChip from '@/components/ListingStatusChip.vue'
import { formatListingPrice } from '@/lib/formatPrice'
import AccountListingActionButton from '@/modules/account/components/AccountListingActionButton.vue'
import AccountListingSeoModal from '@/modules/account/components/AccountListingSeoModal.vue'
import { useCurrencyStore } from '@/stores/currency'
import type { ListingDto, ListingStatus, MetroStationDto } from '@/types'

const PAGE_SIZE = 20

type StatusFilter = 'all' | 'draft' | 'pending' | 'published' | 'archived'

const { t } = useI18n()
const { code: currency } = storeToRefs(useCurrencyStore())

const listings = ref<ListingDto[]>([])
const total = ref(0)
const page = ref(1)
const statusFilter = ref<StatusFilter>('all')
const loading = ref(true)
const error = ref(false)
const actionId = ref<number | null>(null)
const previewListing = ref<ListingDto | null>(null)
const seoListing = ref<ListingDto | null>(null)

const filterOptions: StatusFilter[] = ['all', 'draft', 'pending', 'published', 'archived']

const isEmpty = computed(() => !loading.value && total.value === 0)
const totalPages = computed(() => Math.max(1, Math.ceil(total.value / PAGE_SIZE)))
const showPagination = computed(() => total.value > PAGE_SIZE)
const emptyMessage = computed(() =>
  statusFilter.value === 'draft' ? t('account.listings.emptyDrafts') : t('account.listings.empty'),
)

onMounted(async () => {
  await loadListings()
})

watch(statusFilter, async () => {
  page.value = 1
  await loadListings()
})

async function loadListings() {
  loading.value = true
  error.value = false
  try {
    const params: { page: number, limit: number, status?: ListingStatus } = {
      page: page.value,
      limit: PAGE_SIZE,
    }
    if (statusFilter.value !== 'all') {
      params.status = statusFilter.value
    }
    let response = await fetchMyListings(params)
    const maxPage = Math.max(1, Math.ceil(response.total / PAGE_SIZE))
    if (page.value > maxPage) {
      page.value = maxPage
      response = await fetchMyListings({ ...params, page: page.value })
    }
    listings.value = response.items
    total.value = response.total
  } catch {
    error.value = true
  } finally {
    loading.value = false
  }
}

async function goToPage(nextPage: number) {
  if (nextPage < 1 || nextPage > totalPages.value || nextPage === page.value || loading.value) {
    return
  }
  page.value = nextPage
  await loadListings()
}

function dealTypeLabel(dealType: ListingDto['dealType']) {
  return t(`account.listings.dealTypes.${dealType}`)
}

function statusLabel(status: ListingStatus) {
  return t(`account.listings.statuses.${status}`)
}

function listingThumb(listing: ListingDto) {
  return listing.images[0] ?? null
}

function openPreview(listing: ListingDto) {
  previewListing.value = listing
}

function closePreview() {
  previewListing.value = null
}

function openSeo(listing: ListingDto) {
  seoListing.value = listing
}

function closeSeo() {
  seoListing.value = null
}

function onSeoSaved(updated: ListingDto) {
  listings.value = listings.value.map((item) => (item.id === updated.id ? updated : item))
}

const previewMetroStation = computed((): MetroStationDto | undefined => {
  const listing = previewListing.value
  if (!listing?.metroStationId || !listing.metroStationName) {
    return undefined
  }
  return {
    id: listing.metroStationId,
    name: listing.metroStationName,
    slug: '',
    lineColor: '#1d4ed8',
    cityId: listing.cityId,
  }
})

async function publishListing(listing: ListingDto) {
  actionId.value = listing.id
  try {
    const updated = await publishMyListing(listing.id)
    listings.value = listings.value.map((item) => (item.id === updated.id ? updated : item))
    if (statusFilter.value === 'draft') {
      await loadListings()
    }
  } catch {
    error.value = true
  } finally {
    actionId.value = null
  }
}

async function archiveListing(listing: ListingDto) {
  actionId.value = listing.id
  try {
    const updated = await archiveMyListing(listing.id)
    listings.value = listings.value.map((item) => (item.id === updated.id ? updated : item))
  } catch {
    error.value = true
  } finally {
    actionId.value = null
  }
}

async function deleteListing(listing: ListingDto) {
  const confirmKey = listing.status === 'pending'
    ? 'account.listings.deletePendingConfirm'
    : 'account.listings.deleteDraftConfirm'
  if (!window.confirm(t(confirmKey))) {
    return
  }
  actionId.value = listing.id
  try {
    await deleteMyDraftListing(listing.id)
    await loadListings()
  } catch {
    error.value = true
  } finally {
    actionId.value = null
  }
}

</script>

<template>
  <div class="account-listings">
    <header class="account-listings__header">
      <div>
        <h1 class="account-listings__title">{{ t('account.listings.title') }}</h1>
        <p class="account-listings__subtitle">{{ t('account.listings.subtitle', { count: total }) }}</p>
      </div>
      <RouterLink to="/account/seller/create" class="account-listings__cta">
        {{ t('account.listings.create') }}
      </RouterLink>
    </header>

    <div class="account-listings__filters" role="tablist" :aria-label="t('account.listings.title')">
      <button
        v-for="filter in filterOptions"
        :key="filter"
        type="button"
        role="tab"
        class="account-listings__filter"
        :class="{ 'account-listings__filter--active': statusFilter === filter }"
        :aria-selected="statusFilter === filter"
        @click="statusFilter = filter"
      >
        {{ t(`account.listings.filters.${filter}`) }}
      </button>
    </div>

    <div v-if="loading" class="account-listings__state">{{ t('listing.loading') }}</div>
    <div v-else-if="error" class="account-listings__state">{{ t('account.error') }}</div>
    <div v-else-if="isEmpty" class="account-listings__state">
      <p>{{ emptyMessage }}</p>
      <RouterLink to="/account/seller/create" class="account-listings__cta">
        {{ t('account.listings.create') }}
      </RouterLink>
    </div>

    <template v-else>
      <div class="account-listings__table-wrap">
        <table class="account-listings__table">
          <thead>
            <tr>
              <th class="account-listings__col-photo">{{ t('account.listings.columns.photo') }}</th>
              <th>{{ t('account.listings.columns.address') }}</th>
              <th>{{ t('account.listings.columns.dealType') }}</th>
              <th>{{ t('account.listings.columns.status') }}</th>
              <th>{{ t('account.listings.columns.price') }}</th>
              <th>{{ t('account.listings.columns.views') }}</th>
              <th />
            </tr>
          </thead>
          <tbody>
            <tr v-for="listing in listings" :key="listing.id">
              <td class="account-listings__col-photo">
                <div class="account-listings__thumb" aria-hidden="true">
                  <img
                    v-if="listingThumb(listing)"
                    :src="listingThumb(listing)!"
                    alt=""
                    class="account-listings__thumb-img"
                    loading="lazy"
                  />
                </div>
              </td>
              <td>{{ listing.address }}</td>
              <td>{{ dealTypeLabel(listing.dealType) }}</td>
              <td>
                <div class="account-listings__statuses">
                  <ListingStatusChip
                    :status="listing.status"
                    :label="statusLabel(listing.status)"
                  />
                  <ListingStatusChip
                    v-if="listing.verified"
                    status="verified"
                    :label="t('listing.verified')"
                  />
                </div>
              </td>
              <td>{{ formatListingPrice(listing.price, currency) }}</td>
              <td>{{ listing.views }}</td>
              <td class="account-listings__actions">
                <div class="account-listings__actions-row">
                  <AccountListingActionButton
                    variant="view"
                    :title="t('account.listings.preview')"
                    data-testid="listing-preview"
                    @click="openPreview(listing)"
                  />
                  <AccountListingActionButton
                    variant="seo"
                    :title="t('account.listings.seo.open')"
                    data-testid="listing-seo"
                    @click="openSeo(listing)"
                  />
                  <AccountListingActionButton
                    v-if="listing.status === 'draft' || listing.status === 'archived' || listing.status === 'rejected'"
                    variant="publish"
                    :title="t('account.wizard.publish')"
                    :disabled="actionId === listing.id"
                    @click="publishListing(listing)"
                  />
                  <AccountListingActionButton
                    v-else-if="listing.status === 'published'"
                    variant="archive"
                    :title="t('account.listings.statuses.archived')"
                    :disabled="actionId === listing.id"
                    @click="archiveListing(listing)"
                  />
                  <span
                    v-else
                    class="account-listings__action-slot"
                    aria-hidden="true"
                  />
                  <AccountListingActionButton
                    v-if="listing.status === 'draft' || listing.status === 'pending'"
                    variant="delete"
                    :title="t('account.listings.deleteDraft')"
                    :disabled="actionId === listing.id"
                    data-testid="listing-delete"
                    @click="deleteListing(listing)"
                  />
                  <span
                    v-else
                    class="account-listings__action-slot"
                    aria-hidden="true"
                  />
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <nav
        v-if="showPagination"
        class="account-listings__pagination"
        :aria-label="t('account.listings.pagination.label')"
      >
        <button
          type="button"
          class="account-listings__page-btn"
          :disabled="page <= 1 || loading"
          @click="goToPage(page - 1)"
        >
          {{ t('account.listings.pagination.prev') }}
        </button>
        <span class="account-listings__page-info">
          {{ t('account.listings.pagination.page', { page, pages: totalPages }) }}
        </span>
        <button
          type="button"
          class="account-listings__page-btn"
          :disabled="page >= totalPages || loading"
          @click="goToPage(page + 1)"
        >
          {{ t('account.listings.pagination.next') }}
        </button>
      </nav>
    </template>

    <ListingDetailModal
      v-if="previewListing"
      :listing="previewListing"
      :metro-station="previewMetroStation"
      :district-name="previewListing.districtName"
      @close="closePreview"
      @show-on-map="closePreview"
    />

    <AccountListingSeoModal
      v-if="seoListing"
      :listing="seoListing"
      @close="closeSeo"
      @saved="onSeoSaved"
    />
  </div>
</template>

<style scoped>
.account-listings__header {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px 16px;
  margin-bottom: 16px;
}

.account-listings__title {
  margin: 0 0 8px;
  font-size: 28px;
  font-weight: 700;
}

.account-listings__subtitle {
  margin: 0;
  font-size: 15px;
  color: var(--color-text-muted);
}

.account-listings__filters {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 20px;
}

.account-listings__filter {
  min-height: 36px;
  padding: 0 14px;
  border: 1px solid var(--figma-border);
  border-radius: 999px;
  background: var(--figma-surface);
  color: var(--color-text-muted);
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
}

.account-listings__filter--active {
  border-color: var(--figma-accent);
  background: color-mix(in srgb, var(--figma-accent) 10%, var(--figma-surface));
  color: var(--figma-accent);
}

.account-listings__state {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
  padding: 40px 0;
  text-align: center;
  color: var(--color-text-muted);
}

.account-listings__cta {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 42px;
  padding: 0 18px;
  border-radius: var(--radius-md, 8px);
  background: var(--figma-accent);
  color: var(--figma-on-accent);
  font-size: 14px;
  font-weight: 600;
  text-decoration: none;
  transition: transform 0.2s ease, opacity 0.2s ease;
}

.account-listings__cta:hover {
  opacity: 0.92;
}

.account-listings__cta:active {
  transform: scale(0.98);
}

.account-listings__table-wrap {
  overflow-x: auto;
}

.account-listings__table {
  width: 100%;
  border-collapse: collapse;
  font-size: 14px;
}

.account-listings__table th,
.account-listings__table td {
  padding: 12px 10px;
  border-bottom: 1px solid var(--figma-border);
  text-align: left;
  vertical-align: middle;
}

.account-listings__table th {
  font-size: 12px;
  font-weight: 600;
  color: var(--color-text-muted);
}

.account-listings__col-photo {
  width: 64px;
  padding-right: 4px;
}

.account-listings__thumb {
  width: 56px;
  height: 42px;
  border-radius: 8px;
  overflow: hidden;
  background: color-mix(in srgb, var(--figma-border) 40%, transparent);
  border: 1px solid var(--figma-border);
}

.account-listings__thumb-img {
  display: block;
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.account-listings__statuses {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 6px;
  white-space: nowrap;
}

.account-listings__actions {
  width: 1%;
  white-space: nowrap;
  text-align: right;
  vertical-align: middle;
}

.account-listings__actions-row {
  display: grid;
  grid-template-columns: repeat(4, 36px);
  gap: 8px;
  justify-content: end;
  align-items: center;
}

.account-listings__action-slot {
  display: block;
  width: 36px;
  height: 36px;
}

.account-listings__pagination {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: center;
  gap: 12px;
  margin-top: 20px;
}

.account-listings__page-btn {
  min-height: 40px;
  min-width: 44px;
  padding: 0 14px;
  border: 1px solid var(--figma-border);
  border-radius: 8px;
  background: var(--figma-surface);
  color: var(--color-text);
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
}

.account-listings__page-btn:disabled {
  opacity: 0.45;
  cursor: default;
}

.account-listings__page-info {
  font-size: 13px;
  font-weight: 600;
  color: var(--color-text-muted);
}

@media (max-width: 767px) {
  .account-listings__header {
    flex-direction: column;
    align-items: stretch;
    gap: 12px;
  }

  .account-listings__cta {
    width: 100%;
    justify-content: center;
    text-align: center;
  }

  .account-listings__filters {
    display: flex;
    flex-wrap: nowrap;
    gap: 8px;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
    padding-bottom: 2px;
  }

  .account-listings__filters::-webkit-scrollbar {
    display: none;
  }

  .account-listings__filter {
    flex: 0 0 auto;
    min-height: var(--touch-target-min);
  }

  .account-listings__table {
    min-width: 720px;
  }
}
</style>
