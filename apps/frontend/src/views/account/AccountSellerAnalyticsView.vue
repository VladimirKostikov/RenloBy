<script setup lang="ts">
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  fetchListingAnalyticsDetail,
  fetchListingAnalyticsOptions,
  type ListingAnalyticsDetailDto,
  type ListingAnalyticsOptionDto,
} from '@/api/account'
import AnalyticsLineChart from '@/modules/account/components/AnalyticsLineChart.vue'
import { useRoutePageSeo } from '@/modules/seo/useRoutePageSeo'

const PAGE_SIZE = 20
const SEARCH_DEBOUNCE_MS = 300

const { t, locale } = useI18n()
useRoutePageSeo({ noindex: true })

const options = ref<ListingAnalyticsOptionDto[]>([])
const total = ref(0)
const page = ref(1)
const searchInput = ref('')
const searchQuery = ref('')
const selectedId = ref<number | null>(null)
const detail = ref<ListingAnalyticsDetailDto | null>(null)
const range = ref<'day' | 'week' | 'month'>('week')
const loading = ref(true)
const detailLoading = ref(false)
const error = ref(false)
const pickerOpen = ref(false)
let searchTimer: ReturnType<typeof setTimeout> | null = null

const selectedOption = computed(() => {
  if (detail.value?.listing.id === selectedId.value) {
    return detail.value.listing
  }
  return options.value.find((item) => item.id === selectedId.value) ?? null
})

const totalPages = computed(() => Math.max(1, Math.ceil(total.value / PAGE_SIZE)))
const showPagination = computed(() => total.value > PAGE_SIZE)
const hasActiveSearch = computed(() => searchQuery.value.trim() !== '')
const showEmptyCatalog = computed(
  () => !loading.value && total.value === 0 && !hasActiveSearch.value,
)
const showEmptySearch = computed(
  () => !loading.value && total.value === 0 && hasActiveSearch.value && !selectedId.value,
)

const updatedLabel = computed(() => {
  if (!detail.value?.updatedAt) return ''
  const date = new Date(detail.value.updatedAt)
  if (Number.isNaN(date.getTime())) return ''
  const time = date.toLocaleTimeString(locale.value === 'en' ? 'en-GB' : 'ru-RU', {
    hour: '2-digit',
    minute: '2-digit',
  })
  return t('account.analytics.updatedToday', { time })
})

const viewsChartPoints = computed(() =>
  (detail.value?.viewsSeries ?? []).map((point) => ({
    date: point.date,
    value: point.views,
    average: point.average,
  })),
)

const engagementChartPoints = computed(() =>
  (detail.value?.engagement.series ?? []).map((point) => ({
    date: point.date,
    value: point.contacts,
  })),
)

const funnelMax = computed(() => {
  if (!detail.value) return 1
  return Math.max(
    1,
    detail.value.funnel.views,
    detail.value.funnel.contacts,
    detail.value.funnel.messages,
  )
})

const maxPromotion = computed(() => {
  if (!detail.value) return 1
  return Math.max(
    1,
    ...detail.value.promotion.rows.flatMap((row) => [row.before, row.after]),
  )
})

async function loadOptions() {
  loading.value = true
  error.value = false
  try {
    const response = await fetchListingAnalyticsOptions({
      page: page.value,
      limit: PAGE_SIZE,
      q: searchQuery.value.trim() || undefined,
    })
    const maxPage = Math.max(1, Math.ceil(response.total / PAGE_SIZE))
    if (page.value > maxPage) {
      page.value = maxPage
      const retry = await fetchListingAnalyticsOptions({
        page: page.value,
        limit: PAGE_SIZE,
        q: searchQuery.value.trim() || undefined,
      })
      options.value = retry.items
      total.value = retry.total
    } else {
      options.value = response.items
      total.value = response.total
    }
  } catch {
    error.value = true
    options.value = []
    total.value = 0
  } finally {
    loading.value = false
  }
}

async function loadDetail() {
  if (selectedId.value == null) {
    detail.value = null
    return
  }
  detailLoading.value = true
  error.value = false
  try {
    detail.value = await fetchListingAnalyticsDetail(selectedId.value, range.value)
  } catch {
    error.value = true
    detail.value = null
  } finally {
    detailLoading.value = false
  }
}

function scrollAnalyticsTop() {
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

function selectListing(id: number) {
  selectedId.value = id
  pickerOpen.value = false
  void nextTick(() => {
    scrollAnalyticsTop()
  })
}

function clearSelection() {
  selectedId.value = null
  detail.value = null
  pickerOpen.value = false
  void nextTick(() => {
    scrollAnalyticsTop()
  })
}

async function goToPage(nextPage: number) {
  if (nextPage < 1 || nextPage > totalPages.value || nextPage === page.value || loading.value) {
    return
  }
  page.value = nextPage
  await loadOptions()
}

function onSearchInput(event: Event) {
  const value = (event.target as HTMLInputElement).value
  searchInput.value = value
  if (searchTimer) {
    clearTimeout(searchTimer)
  }
  searchTimer = setTimeout(() => {
    const next = value.trim()
    if (next === searchQuery.value) {
      return
    }
    searchQuery.value = next
    page.value = 1
    selectedId.value = null
    detail.value = null
    void loadOptions()
  }, SEARCH_DEBOUNCE_MS)
}

function listingStatusLabel(status: string) {
  const key = `account.listings.statuses.${status}`
  const translated = t(key)
  return translated === key ? status : translated
}

function formatPct(value: number) {
  const sign = value > 0 ? '+' : ''
  return `${sign}${value.toLocaleString(locale.value === 'en' ? 'en-US' : 'ru-RU')}%`
}

function promotionMetric(metric: string) {
  return t(`account.analytics.promotionMetrics.${metric}`)
}

onMounted(() => {
  void loadOptions()
})

onUnmounted(() => {
  if (searchTimer) {
    clearTimeout(searchTimer)
  }
})

watch([selectedId, range], () => {
  void loadDetail()
})
</script>

<template>
  <div class="listing-analytics">
    <header class="listing-analytics__header">
      <div>
        <h1 class="listing-analytics__title">{{ t('account.analytics.title') }}</h1>
        <p v-if="updatedLabel" class="listing-analytics__updated">{{ updatedLabel }}</p>
      </div>

      <div v-if="selectedId && selectedOption" class="listing-analytics__picker">
        <button
          type="button"
          class="listing-analytics__picker-btn"
          :aria-expanded="pickerOpen"
          @click="pickerOpen = !pickerOpen"
        >
          <img
            v-if="selectedOption.image"
            :src="selectedOption.image"
            alt=""
            class="listing-analytics__picker-image"
          />
          <span class="listing-analytics__picker-meta">
            <strong>{{ selectedOption.title }}</strong>
            <span>ID {{ selectedOption.id }}</span>
          </span>
          <span class="listing-analytics__picker-chevron" aria-hidden="true">▾</span>
        </button>
        <div v-if="pickerOpen" class="listing-analytics__picker-menu" role="listbox">
          <button
            type="button"
            class="listing-analytics__picker-item"
            @click="clearSelection"
          >
            <span class="listing-analytics__picker-meta">
              <strong>{{ t('account.analytics.backToList') }}</strong>
            </span>
          </button>
          <button
            v-for="item in options"
            :key="item.id"
            type="button"
            class="listing-analytics__picker-item"
            role="option"
            :aria-selected="item.id === selectedId"
            @click="selectListing(item.id)"
          >
            <img v-if="item.image" :src="item.image" alt="" class="listing-analytics__picker-image" />
            <span class="listing-analytics__picker-meta">
              <strong>{{ item.title }}</strong>
              <span>ID {{ item.id }}</span>
            </span>
          </button>
        </div>
      </div>
    </header>

    <Transition name="analytics-stage" mode="out-in">
    <div v-if="!selectedId" key="catalog" class="listing-analytics__stage">
    <div
      class="listing-analytics__toolbar"
    >
      <label class="listing-analytics__search">
        <span class="visually-hidden">{{ t('account.analytics.searchLabel') }}</span>
        <input
          type="search"
          class="listing-analytics__search-input"
          data-testid="analytics-search"
          :value="searchInput"
          :placeholder="t('account.analytics.searchPlaceholder')"
          @input="onSearchInput"
        />
      </label>
    </div>

    <div v-if="loading" class="listing-analytics__state">{{ t('listing.loading') }}</div>
    <div v-else-if="showEmptyCatalog" class="listing-analytics__state">
      <p>{{ t('account.analytics.noListings') }}</p>
      <RouterLink to="/account/seller/create" class="listing-analytics__cta">
        {{ t('account.analytics.createListing') }}
      </RouterLink>
    </div>
    <div v-else-if="showEmptySearch" class="listing-analytics__state">
      <p>{{ t('account.analytics.noSearchResults') }}</p>
    </div>

    <section
      v-else
      class="listing-analytics__select"
      data-testid="analytics-select"
    >
      <div class="listing-analytics__select-grid" role="list">
        <button
          v-for="item in options"
          :key="item.id"
          type="button"
          class="listing-analytics__select-card"
          role="listitem"
          @click="selectListing(item.id)"
        >
          <div class="listing-analytics__select-thumb" aria-hidden="true">
            <img v-if="item.image" :src="item.image" alt="" />
          </div>
          <div class="listing-analytics__select-body">
            <strong class="listing-analytics__select-name">{{ item.title }}</strong>
            <span class="listing-analytics__select-address">{{ item.address || '-' }}</span>
            <span class="listing-analytics__select-meta">
              {{ listingStatusLabel(item.status) }} · {{ item.views }} {{ t('account.analytics.metrics.views').toLowerCase() }}
            </span>
          </div>
        </button>
      </div>

      <nav
        v-if="showPagination"
        class="listing-analytics__pagination"
        :aria-label="t('account.analytics.pagination.label')"
      >
        <button
          type="button"
          class="listing-analytics__page-btn"
          :disabled="page <= 1 || loading"
          @click="goToPage(page - 1)"
        >
          {{ t('account.analytics.pagination.prev') }}
        </button>
        <span class="listing-analytics__page-info">
          {{ t('account.analytics.pagination.page', { page, pages: totalPages }) }}
        </span>
        <button
          type="button"
          class="listing-analytics__page-btn"
          :disabled="page >= totalPages || loading"
          @click="goToPage(page + 1)"
        >
          {{ t('account.analytics.pagination.next') }}
        </button>
      </nav>
    </section>
    </div>

    <div v-else key="detail" class="listing-analytics__detail">
      <div v-if="error && !detail" class="listing-analytics__state">{{ t('account.error') }}</div>
      <div
        v-else-if="!detail"
        class="listing-analytics__state"
      >
        {{ t('listing.loading') }}
      </div>
      <template v-else>
      <div v-if="detailLoading" class="listing-analytics__state listing-analytics__state--soft">
        {{ t('listing.loading') }}
      </div>

      <section class="listing-analytics__metrics" data-testid="analytics-metrics">
        <article class="metric-card metric-card--views">
          <div class="metric-card__top">
            <div class="metric-card__icon metric-card__icon--views" aria-hidden="true" />
            <h2>{{ t('account.analytics.metrics.views') }}</h2>
          </div>
          <div class="metric-card__periods">
            <div class="metric-card__period">
              <span class="metric-card__period-label">{{ t('account.analytics.period.day') }}</span>
              <strong class="metric-card__period-value">{{ detail.views.day }}</strong>
              <em class="metric-card__delta">{{ formatPct(detail.views.dayChangePct) }} {{ t('account.analytics.vsYesterday') }}</em>
            </div>
            <div class="metric-card__period">
              <span class="metric-card__period-label">{{ t('account.analytics.period.week') }}</span>
              <strong class="metric-card__period-value">{{ detail.views.week }}</strong>
              <em class="metric-card__delta">{{ formatPct(detail.views.weekChangePct) }} {{ t('account.analytics.vsLastWeek') }}</em>
            </div>
            <div class="metric-card__period">
              <span class="metric-card__period-label">{{ t('account.analytics.period.month') }}</span>
              <strong class="metric-card__period-value">{{ detail.views.month }}</strong>
              <em class="metric-card__delta">{{ formatPct(detail.views.monthChangePct) }} {{ t('account.analytics.vsLastMonth') }}</em>
            </div>
          </div>
        </article>

        <div class="listing-analytics__kpi-row">
          <article class="metric-card">
            <div class="metric-card__top">
              <div class="metric-card__icon metric-card__icon--contacts" aria-hidden="true" />
              <h2>{{ t('account.analytics.metrics.contacts') }}</h2>
            </div>
            <strong class="metric-card__main">{{ detail.contactOpensWeek }}</strong>
            <span class="metric-card__hint">{{ t('account.analytics.perWeek') }}</span>
            <em class="metric-card__delta">{{ formatPct(detail.contactOpensChangePct) }} {{ t('account.analytics.vsLastWeek') }}</em>
          </article>

          <article class="metric-card">
            <div class="metric-card__top">
              <div class="metric-card__icon metric-card__icon--messages" aria-hidden="true" />
              <h2>{{ t('account.analytics.metrics.messages') }}</h2>
            </div>
            <strong class="metric-card__main">{{ detail.messagesWeek }}</strong>
            <span class="metric-card__hint">{{ t('account.analytics.perWeek') }}</span>
            <em class="metric-card__delta">{{ formatPct(detail.messagesChangePct) }} {{ t('account.analytics.vsLastWeek') }}</em>
          </article>

          <article class="metric-card">
            <div class="metric-card__top">
              <div class="metric-card__icon metric-card__icon--conversion" aria-hidden="true" />
              <h2>{{ t('account.analytics.metrics.conversion') }}</h2>
            </div>
            <strong class="metric-card__main">{{ detail.conversionPct }}%</strong>
            <span class="metric-card__hint">{{ t('account.analytics.perWeek') }}</span>
            <em class="metric-card__delta">{{ formatPct(detail.conversionChangePct) }} {{ t('account.analytics.vsLastWeek') }}</em>
          </article>
        </div>
      </section>

      <section class="listing-analytics__mid">
        <article class="panel panel--chart">
          <div class="panel__head">
            <h2>{{ t('account.analytics.viewsChart') }}</h2>
            <div class="panel__tabs" role="tablist">
              <button
                v-for="item in (['day', 'week', 'month'] as const)"
                :key="item"
                type="button"
                role="tab"
                class="panel__tab"
                :class="{ 'panel__tab--active': range === item }"
                @click="range = item"
              >
                {{ t(`account.analytics.period.${item}`) }}
              </button>
            </div>
          </div>
          <div class="panel__chart">
            <AnalyticsLineChart :points="viewsChartPoints" show-average labels />
          </div>
          <div class="panel__legend">
            <span class="panel__legend-item"><i class="panel__dot" />{{ t('account.analytics.legend.views') }}</span>
            <span class="panel__legend-item"><i class="panel__dash" />{{ t('account.analytics.legend.average') }}</span>
          </div>
        </article>

        <article class="panel panel--funnel">
          <h2>{{ t('account.analytics.funnel.title') }}</h2>
          <p class="panel__caption">{{ t('account.analytics.funnel.caption') }}</p>
          <div class="funnel" data-testid="analytics-funnel">
            <div class="funnel__step">
              <div class="funnel__meta">
                <span>{{ t('account.analytics.funnel.views') }}</span>
                <strong>{{ detail.funnel.views }}</strong>
              </div>
              <div class="funnel__track" aria-hidden="true">
                <span
                  class="funnel__fill"
                  :style="{ width: `${(detail.funnel.views / funnelMax) * 100}%` }"
                />
              </div>
            </div>
            <div class="funnel__step">
              <div class="funnel__meta">
                <span>{{ t('account.analytics.funnel.contacts') }}</span>
                <strong>{{ detail.funnel.contacts }}</strong>
              </div>
              <div class="funnel__track" aria-hidden="true">
                <span
                  class="funnel__fill funnel__fill--mid"
                  :style="{ width: `${(detail.funnel.contacts / funnelMax) * 100}%` }"
                />
              </div>
              <p class="funnel__rate">
                {{ t('account.analytics.funnel.viewToContact', { pct: detail.funnel.viewToContactPct }) }}
              </p>
            </div>
            <div class="funnel__step">
              <div class="funnel__meta">
                <span>{{ t('account.analytics.funnel.messages') }}</span>
                <strong>{{ detail.funnel.messages }}</strong>
              </div>
              <div class="funnel__track" aria-hidden="true">
                <span
                  class="funnel__fill funnel__fill--low"
                  :style="{ width: `${(detail.funnel.messages / funnelMax) * 100}%` }"
                />
              </div>
              <p class="funnel__rate">
                {{ t('account.analytics.funnel.contactToMessage', { pct: detail.funnel.contactToMessagePct }) }}
              </p>
            </div>
          </div>
        </article>
      </section>

      <section class="listing-analytics__bottom">
        <article class="panel">
          <div class="panel__head">
            <h2>{{ t('account.analytics.promotion.title') }}</h2>
            <span v-if="detail.promotion.active" class="panel__badge">
              {{ t('account.analytics.promotion.tariffPremium') }}
            </span>
          </div>
          <div class="promo-table">
            <div class="promo-table__head">
              <span>{{ t('account.analytics.promotion.metric') }}</span>
              <span>{{ t('account.analytics.promotion.before') }} / {{ t('account.analytics.promotion.after') }}</span>
              <span>{{ t('account.analytics.promotion.growth') }}</span>
            </div>
            <div v-for="row in detail.promotion.rows" :key="row.metric" class="promo-table__row">
              <span class="promo-table__metric">{{ promotionMetric(row.metric) }}</span>
              <div class="promo-table__bars">
                <div class="promo-table__bar promo-table__bar--before" :style="{ width: `${(row.before / maxPromotion) * 100}%` }" />
                <div class="promo-table__bar promo-table__bar--after" :style="{ width: `${(row.after / maxPromotion) * 100}%` }" />
                <small>{{ row.before }} / {{ row.after }}</small>
              </div>
              <em class="metric-card__delta">{{ formatPct(row.growthPct) }}</em>
            </div>
          </div>
          <a href="/account/seller/promotion" class="listing-analytics__cta">
            {{ t('account.analytics.promotion.buy') }}
          </a>
        </article>

        <article class="panel">
          <div class="panel__head">
            <h2>{{ t('account.analytics.engagement.title') }}</h2>
          </div>
          <div class="engagement-grid" data-testid="analytics-engagement">
            <div>
              <span>{{ t('account.analytics.engagement.contactsTotal') }}</span>
              <strong>{{ detail.engagement.contactsTotal }}</strong>
            </div>
            <div>
              <span>{{ t('account.analytics.engagement.messagesTotal') }}</span>
              <strong>{{ detail.engagement.messagesTotal }}</strong>
            </div>
            <div>
              <span>{{ t('account.analytics.engagement.contactsAvg') }}</span>
              <strong>{{ detail.engagement.contactsAvg }}</strong>
            </div>
            <div>
              <span>{{ t('account.analytics.engagement.contactsPeak') }}</span>
              <strong>{{ detail.engagement.contactsPeak }}</strong>
            </div>
          </div>
          <div class="panel__chart">
            <AnalyticsLineChart :points="engagementChartPoints" />
          </div>
          <div class="panel__legend">
            <span class="panel__legend-item">
              <i class="panel__dot" />{{ t('account.analytics.engagement.legendContacts') }}
            </span>
          </div>
        </article>
      </section>
      </template>
    </div>
    </Transition>
  </div>
</template>

<style scoped>
.listing-analytics {
  display: flex;
  flex-direction: column;
  gap: 20px;
  min-width: 0;
  width: 100%;
}

.listing-analytics__detail {
  display: flex;
  flex-direction: column;
  gap: 20px;
  min-width: 0;
  width: 100%;
}

.listing-analytics__stage {
  display: flex;
  flex-direction: column;
  gap: 20px;
  min-width: 0;
  width: 100%;
}

.listing-analytics__header {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
}

.listing-analytics__title {
  margin: 0;
  font-size: 24px;
  font-weight: 700;
  line-height: 1.25;
}

.listing-analytics__updated {
  margin: 6px 0 0;
  color: var(--color-text-muted, #6b7280);
  font-size: 13px;
}

.listing-analytics__toolbar {
  margin: 0 0 16px;
}

.listing-analytics__search {
  display: block;
  width: 100%;
  max-width: 420px;
}

.listing-analytics__search-input {
  box-sizing: border-box;
  width: 100%;
  min-height: 44px;
  padding: 0 14px;
  border: 1px solid var(--figma-border, #e5e7eb);
  border-radius: 10px;
  background: #fff;
  color: var(--color-text, #111);
  font-size: 14px;
  font-weight: 500;
}

.listing-analytics__search-input:focus {
  outline: 2px solid color-mix(in srgb, var(--figma-accent) 40%, transparent);
  outline-offset: 1px;
  border-color: var(--figma-accent);
}

.listing-analytics__select {
  display: flex;
  flex-direction: column;
  gap: 12px;
  min-width: 0;
}

.listing-analytics__pagination {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: center;
  gap: 12px;
  margin-top: 8px;
}

.listing-analytics__page-btn {
  min-height: 40px;
  padding: 0 14px;
  border: 1px solid var(--figma-border, #e5e7eb);
  border-radius: 8px;
  background: #fff;
  cursor: pointer;
}

.listing-analytics__page-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.listing-analytics__page-info {
  font-size: 13px;
  color: rgba(0, 0, 0, 0.72);
}

.visually-hidden {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}

.listing-analytics__select-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 12px;
}

.listing-analytics__select-card {
  display: flex;
  align-items: stretch;
  gap: 14px;
  width: 100%;
  min-width: 0;
  padding: 12px;
  border: 1px solid var(--figma-border, #e5e7eb);
  border-radius: 14px;
  background: #fff;
  text-align: left;
  cursor: pointer;
  transition:
    border-color 0.2s ease,
    box-shadow 0.2s ease,
    transform 0.2s cubic-bezier(0.22, 1, 0.36, 1);
}

.listing-analytics__select-card:hover {
  border-color: color-mix(in srgb, var(--figma-accent) 40%, transparent);
  box-shadow: 0 4px 16px rgba(15, 23, 42, 0.06);
  transform: translateY(-1px);
}

.listing-analytics__select-card:active {
  transform: translateY(0);
}

.listing-analytics__select-thumb {
  width: 88px;
  height: 72px;
  flex-shrink: 0;
  border-radius: 10px;
  overflow: hidden;
  background: #f3f4f6;
  border: 1px solid var(--figma-border, #e5e7eb);
}

.listing-analytics__select-thumb img {
  display: block;
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.listing-analytics__select-body {
  display: flex;
  flex: 1;
  flex-direction: column;
  gap: 4px;
  min-width: 0;
  justify-content: center;
}

.listing-analytics__select-name {
  font-size: 15px;
  font-weight: 700;
  line-height: 1.3;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.listing-analytics__select-address,
.listing-analytics__select-meta {
  color: var(--color-text-muted, #6b7280);
  font-size: 13px;
  line-height: 1.35;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.listing-analytics__picker {
  position: relative;
  width: min(100%, 320px);
  flex: 0 1 320px;
}

.listing-analytics__picker-btn,
.listing-analytics__picker-item {
  display: flex;
  align-items: center;
  gap: 10px;
  width: 100%;
  padding: 8px 10px;
  border: 1px solid var(--figma-border, #e5e7eb);
  border-radius: 12px;
  background: #fff;
  text-align: left;
  cursor: pointer;
}

.listing-analytics__picker-menu {
  position: absolute;
  z-index: 20;
  top: calc(100% + 6px);
  right: 0;
  left: 0;
  max-height: 280px;
  overflow: auto;
  padding: 6px;
  border: 1px solid var(--figma-border, #e5e7eb);
  border-radius: 12px;
  background: #fff;
  box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
}

.listing-analytics__picker-item {
  border: none;
  border-radius: 8px;
}

.listing-analytics__picker-item:hover,
.listing-analytics__picker-item[aria-selected='true'] {
  background: color-mix(in srgb, var(--figma-accent) 8%, #fff);
}

.listing-analytics__picker-image {
  width: 40px;
  height: 40px;
  flex-shrink: 0;
  border-radius: 8px;
  object-fit: cover;
  background: #f3f4f6;
}

.listing-analytics__picker-meta {
  display: flex;
  flex: 1;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
}

.listing-analytics__picker-meta strong {
  font-size: 13px;
  font-weight: 600;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.listing-analytics__picker-meta span {
  color: var(--color-text-muted, #6b7280);
  font-size: 12px;
}

.listing-analytics__picker-chevron {
  flex-shrink: 0;
  color: #9ca3af;
}

.listing-analytics__state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
  min-height: 180px;
  color: var(--color-text-muted, #6b7280);
}

.listing-analytics__state--soft {
  min-height: 0;
  padding: 8px 0;
}

.listing-analytics__cta {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 44px;
  padding: 0 18px;
  border-radius: var(--figma-radius-btn, 10px);
  background: var(--figma-accent);
  color: #fff;
  font-weight: 600;
  text-decoration: none;
}

.listing-analytics__metrics {
  display: flex;
  flex-direction: column;
  gap: 12px;
  min-width: 0;
}

.listing-analytics__kpi-row {
  display: grid;
  grid-template-columns: 1fr;
  gap: 12px;
  min-width: 0;
}

.metric-card {
  display: flex;
  flex-direction: column;
  gap: 8px;
  min-width: 0;
  padding: 18px;
  border: 1px solid var(--figma-border, #e5e7eb);
  border-radius: 14px;
  background: #fff;
}

.metric-card__top {
  display: flex;
  align-items: center;
  gap: 10px;
  min-width: 0;
}

.metric-card h2 {
  margin: 0;
  font-size: 14px;
  font-weight: 600;
  line-height: 1.35;
  overflow-wrap: anywhere;
}

.metric-card__icon {
  display: inline-flex;
  flex-shrink: 0;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  border-radius: 999px;
  background: color-mix(in srgb, var(--figma-accent) 12%, #fff);
  color: var(--figma-accent);
}

.metric-card__icon::before {
  content: '';
  width: 12px;
  height: 12px;
  border: 2px solid currentColor;
  border-radius: 999px;
}

.metric-card__icon--views::before {
  border-radius: 999px 999px 999px 2px;
  transform: rotate(-45deg);
}

.metric-card__icon--contacts::before {
  width: 10px;
  height: 10px;
  border-radius: 999px;
  box-shadow: 0 8px 0 -2px currentColor;
}

.metric-card__icon--messages::before {
  border-radius: 3px;
}

.metric-card__icon--conversion::before {
  width: 10px;
  height: 10px;
  border-style: solid;
  border-width: 2px;
  background: radial-gradient(circle at center, currentColor 2px, transparent 2px);
}

.metric-card__periods {
  display: grid;
  grid-template-columns: 1fr;
  gap: 12px;
}

.metric-card__period {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 0;
  padding: 12px;
  border-radius: 10px;
  background: #f9fafb;
}

.metric-card__period-label {
  color: var(--color-text-muted, #6b7280);
  font-size: 12px;
  font-weight: 500;
  line-height: 1.3;
}

.metric-card__period-value,
.metric-card__main {
  display: block;
  font-size: 22px;
  font-weight: 700;
  line-height: 1.2;
}

.metric-card__hint {
  display: block;
  color: var(--color-text-muted, #6b7280);
  font-size: 13px;
  line-height: 1.3;
}

.metric-card__delta {
  display: block;
  margin: 0;
  color: #16a34a;
  font-size: 12px;
  font-style: normal;
  font-weight: 600;
  line-height: 1.35;
  overflow-wrap: anywhere;
}

.listing-analytics__mid,
.listing-analytics__bottom {
  display: grid;
  grid-template-columns: 1fr;
  gap: 12px;
  min-width: 0;
}

.panel {
  display: flex;
  flex-direction: column;
  gap: 12px;
  min-width: 0;
  padding: 18px;
  border: 1px solid var(--figma-border, #e5e7eb);
  border-radius: 14px;
  background: #fff;
}

.panel h2 {
  margin: 0;
  font-size: 16px;
  font-weight: 700;
  line-height: 1.3;
}

.panel__head {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
}

.panel__tabs {
  display: inline-flex;
  flex-wrap: wrap;
  gap: 4px;
  padding: 3px;
  border-radius: 999px;
  background: #f3f4f6;
}

.panel__tab {
  min-height: 32px;
  padding: 0 12px;
  border: none;
  border-radius: 999px;
  background: transparent;
  color: #6b7280;
  cursor: pointer;
  font-size: 12px;
  font-weight: 600;
}

.panel__tab--active {
  background: #fff;
  color: #111;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.06);
}

.panel__chart {
  width: 100%;
  min-width: 0;
  overflow: hidden;
}

.panel__legend {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 12px 18px;
  color: #6b7280;
  font-size: 12px;
  line-height: 1.4;
}

.panel__legend-item {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  white-space: nowrap;
}

.panel__dot,
.panel__dash {
  display: inline-block;
  flex-shrink: 0;
  width: 12px;
  vertical-align: middle;
}

.panel__dot {
  height: 12px;
  border-radius: 999px;
  background: var(--figma-accent);
}

.panel__dash {
  height: 0;
  border-top: 2px dashed #9ca3af;
}

.panel__caption {
  margin: 0;
  color: #6b7280;
  font-size: 13px;
  line-height: 1.4;
}

.panel__badge {
  padding: 4px 8px;
  border-radius: 999px;
  background: color-mix(in srgb, var(--figma-accent) 12%, #fff);
  color: var(--figma-accent);
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.02em;
  text-transform: uppercase;
}

.funnel {
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.funnel__step {
  display: flex;
  flex-direction: column;
  gap: 6px;
  min-width: 0;
}

.funnel__meta {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 12px;
  color: #374151;
  font-size: 13px;
  font-weight: 600;
}

.funnel__meta strong {
  font-size: 18px;
  font-weight: 700;
  color: #111;
}

.funnel__track {
  height: 10px;
  border-radius: 999px;
  background: #f3f4f6;
  overflow: hidden;
}

.funnel__fill {
  display: block;
  height: 100%;
  min-width: 0;
  border-radius: 999px;
  background: var(--figma-accent);
}

.funnel__fill--mid {
  background: color-mix(in srgb, var(--figma-accent) 72%, #6b7280);
}

.funnel__fill--low {
  background: color-mix(in srgb, var(--figma-accent) 48%, #9ca3af);
}

.funnel__rate {
  margin: 0;
  color: #6b7280;
  font-size: 12px;
  line-height: 1.4;
}

.promo-table {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.promo-table__head,
.promo-table__row {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(0, 1.6fr) auto;
  gap: 12px;
  align-items: center;
  font-size: 12px;
}

.promo-table__head {
  color: #6b7280;
  font-weight: 600;
}

.promo-table__metric {
  min-width: 0;
  line-height: 1.35;
  overflow-wrap: anywhere;
}

.promo-table__bars {
  position: relative;
  min-width: 0;
  min-height: 28px;
}

.promo-table__bar {
  height: 8px;
  margin-bottom: 4px;
  border-radius: 999px;
}

.promo-table__bar--before {
  background: #e5e7eb;
}

.promo-table__bar--after {
  background: var(--figma-accent);
}

.promo-table__bars small {
  color: #6b7280;
}

.engagement-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 10px;
}

.engagement-grid div {
  min-width: 0;
  padding: 10px;
  border-radius: 10px;
  background: #f9fafb;
}

.engagement-grid span {
  display: block;
  margin-bottom: 4px;
  color: #6b7280;
  font-size: 12px;
}

.engagement-grid strong {
  display: block;
  font-size: 15px;
  line-height: 1.3;
  overflow-wrap: anywhere;
}

@media (min-width: 640px) {
  .listing-analytics__select-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .metric-card__periods {
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 10px;
  }

  .listing-analytics__kpi-row {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}

@media (min-width: 960px) {
  .listing-analytics__mid,
  .listing-analytics__bottom {
    grid-template-columns: minmax(0, 1.35fr) minmax(0, 1fr);
    align-items: start;
  }

  .engagement-grid {
    grid-template-columns: repeat(4, minmax(0, 1fr));
  }
}

@media (max-width: 767px) {
  .promo-table__head,
  .promo-table__row {
    grid-template-columns: 1fr;
    gap: 6px;
  }

  .promo-table__head span:nth-child(2),
  .promo-table__head span:nth-child(3) {
    display: none;
  }
}
</style>

<style>
.analytics-stage-enter-active,
.analytics-stage-leave-active {
  transition:
    opacity 0.22s ease,
    transform 0.22s cubic-bezier(0.22, 1, 0.36, 1);
}

.analytics-stage-enter-from,
.analytics-stage-leave-to {
  opacity: 0;
  transform: translateY(10px);
}

@media (prefers-reduced-motion: reduce) {
  .analytics-stage-enter-active,
  .analytics-stage-leave-active {
    transition-duration: 0.01ms;
  }

  .analytics-stage-enter-from,
  .analytics-stage-leave-to {
    transform: none;
  }

  .listing-analytics__select-card {
    transition: none;
  }

  .listing-analytics__select-card:hover,
  .listing-analytics__select-card:active {
    transform: none;
  }
}
</style>
