<script setup lang="ts">
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import CatalogViewOnMapLink from '@/components/catalog/CatalogViewOnMapLink.vue'
import { useListingsStore } from '@/stores/listings'
import type { CatalogCategory, RentTerm } from '@/lib/catalogListing'
import type { DealType } from '@/types'

const props = defineProps<{
  dealType: 'sale' | 'rent' | 'commercial'
  compact?: boolean
  commercialCatalog?: boolean
}>()

const { t } = useI18n()
const listings = useListingsStore()

const categoriesEl = ref<HTMLElement | null>(null)
const indicatorStyle = ref({ transform: 'translateX(0px)', width: '0px' })

const localePrefix = computed(() => {
  if (props.dealType === 'sale') {
    return 'saleCatalog'
  }
  if (props.dealType === 'commercial') {
    return 'commercialCatalog'
  }
  return 'rentCatalog'
})

const activeDealType = computed<DealType>(() =>
  props.commercialCatalog ? listings.dealType : (props.dealType === 'commercial' ? 'sale' : props.dealType),
)

const dealTypeTabs = computed(() => [
  { key: 'sale' as DealType, label: t('dealType.sale') },
  { key: 'rent' as DealType, label: t('dealType.rent') },
])

const categoryTabs = computed(() => {
  const tabs = [
    { key: 'all' as CatalogCategory, label: t(`${localePrefix.value}.categories.all`) },
    { key: 'apartment' as CatalogCategory, label: t(`${localePrefix.value}.categories.apartments`) },
    { key: 'house' as CatalogCategory, label: t(`${localePrefix.value}.categories.houses`) },
    { key: 'room' as CatalogCategory, label: t(`${localePrefix.value}.categories.rooms`) },
    { key: 'commercial' as CatalogCategory, label: t(`${localePrefix.value}.categories.commercial`) },
  ]

  if (props.dealType === 'commercial') {
    return tabs.filter((tab) => tab.key !== 'commercial')
  }

  return tabs
})

const activeCategoryKey = computed<string>(() =>
  props.commercialCatalog ? activeDealType.value : listings.catalogCategory,
)

const rentTerms = computed(() => [
  {
    key: 'daily' as RentTerm,
    label: t('rentCatalog.rentTerm.daily'),
    icon: 'daily' as const,
  },
  {
    key: 'long' as RentTerm,
    label: t('rentCatalog.rentTerm.long'),
    icon: 'long' as const,
  },
])

async function selectCategory(category: CatalogCategory) {
  await listings.applyCatalogCategory(category)
}

async function selectDealType(dealType: DealType) {
  listings.setDealType(dealType)
  await listings.search()
}

async function selectRentTerm(term: RentTerm) {
  listings.rentTerm = term
  await listings.search()
}

async function updateIndicator() {
  await nextTick()
  const container = categoriesEl.value
  if (!container) {
    return
  }
  const active = container.querySelector<HTMLElement>('.catalog-toolbar__tab--active')
  if (!active) {
    return
  }
  indicatorStyle.value = {
    transform: `translate3d(${active.offsetLeft}px, 0, 0)`,
    width: `${active.offsetWidth}px`,
  }
}

watch(activeCategoryKey, () => {
  void updateIndicator()
})
watch(
  () => [props.commercialCatalog, props.compact, categoryTabs.value.length] as const,
  () => {
    void updateIndicator()
  },
)

onMounted(() => {
  void updateIndicator()
  window.addEventListener('resize', updateIndicator)
  requestAnimationFrame(() => {
    void updateIndicator()
  })
})

onUnmounted(() => {
  window.removeEventListener('resize', updateIndicator)
})
</script>

<template>
  <div class="catalog-toolbar" :class="{ 'catalog-toolbar--compact': compact }">
    <div ref="categoriesEl" class="catalog-toolbar__categories">
      <span
        class="catalog-toolbar__indicator catalog-toolbar__indicator--categories"
        aria-hidden="true"
        :style="indicatorStyle"
      />
      <template v-if="commercialCatalog">
        <button
          v-for="tab in dealTypeTabs"
          :key="tab.key"
          type="button"
          class="catalog-toolbar__tab"
          :class="{ 'catalog-toolbar__tab--active': activeDealType === tab.key }"
          @click="selectDealType(tab.key)"
        >
          {{ tab.label }}
        </button>
      </template>
      <template v-else>
        <button
          v-for="tab in categoryTabs"
          :key="tab.key"
          type="button"
          class="catalog-toolbar__tab"
          :class="{ 'catalog-toolbar__tab--active': listings.catalogCategory === tab.key }"
          @click="selectCategory(tab.key)"
        >
          {{ tab.label }}
        </button>
      </template>
    </div>

    <div class="catalog-toolbar__side">
      <div v-if="activeDealType === 'rent'" class="catalog-toolbar__terms">
        <button
          v-for="term in rentTerms"
          :key="term.key"
          type="button"
          class="catalog-toolbar__term"
          :class="{ 'catalog-toolbar__term--active': listings.rentTerm === term.key }"
          @click="selectRentTerm(term.key)"
        >
          <svg
            v-if="term.icon === 'daily'"
            class="catalog-toolbar__term-icon"
            width="16"
            height="16"
            viewBox="0 0 16 16"
            fill="none"
            aria-hidden="true"
          >
            <rect x="2.5" y="3.5" width="11" height="10" rx="1.5" stroke="currentColor" stroke-width="1.25" />
            <path d="M5.5 2V5" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" />
            <path d="M10.5 2V5" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" />
            <path d="M2.5 7H13.5" stroke="currentColor" stroke-width="1.25" />
            <circle cx="8" cy="10.5" r="1.1" fill="currentColor" />
          </svg>
          <svg
            v-else
            class="catalog-toolbar__term-icon"
            width="16"
            height="16"
            viewBox="0 0 16 16"
            fill="none"
            aria-hidden="true"
          >
            <rect x="2.5" y="3.5" width="11" height="10" rx="1.5" stroke="currentColor" stroke-width="1.25" />
            <path d="M5.5 2V5" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" />
            <path d="M10.5 2V5" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" />
            <path d="M2.5 7H13.5" stroke="currentColor" stroke-width="1.25" />
            <path d="M5.5 10H10.5" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" />
            <path d="M5.5 12H8.5" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" />
          </svg>
          <span>{{ term.label }}</span>
        </button>
      </div>
      <CatalogViewOnMapLink />
    </div>
  </div>
</template>

<style scoped>
.catalog-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
}

.catalog-toolbar__categories,
.catalog-toolbar__terms,
.catalog-toolbar__side {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

.catalog-toolbar__categories {
  position: relative;
  display: inline-flex;
  flex-wrap: nowrap;
  align-items: center;
  gap: 0;
  padding: 3px;
  border: 1px solid var(--figma-border);
  border-radius: 12px;
  background: var(--figma-surface);
  overflow: hidden;
}

.catalog-toolbar__indicator {
  position: absolute;
  top: 3px;
  left: 0;
  z-index: 0;
  height: calc(100% - 6px);
  border-radius: 9px;
  background: var(--figma-accent);
  pointer-events: none;
  transition:
    transform 0.28s cubic-bezier(0.22, 1, 0.36, 1),
    width 0.28s cubic-bezier(0.22, 1, 0.36, 1);
  will-change: transform, width;
}

.catalog-toolbar__tab {
  position: relative;
  z-index: 1;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  height: var(--figma-catalog-tab-height);
  padding: 0 16px;
  border: none;
  border-radius: 9px;
  background: transparent;
  font-size: 14px;
  font-weight: 600;
  color: var(--figma-ink);
  cursor: pointer;
  transition:
    color 0.2s ease,
    transform 0.2s ease;
}

.catalog-toolbar__side {
  margin-left: auto;
}

.catalog-toolbar__side :deep(.catalog-view-on-map) {
  align-self: center;
  height: var(--figma-catalog-tab-height);
}

.catalog-toolbar__term {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  height: var(--figma-catalog-tab-height);
  padding: 0 16px;
  border: 1px solid var(--figma-border);
  border-radius: 50px;
  background: var(--figma-surface);
  font-size: 14px;
  font-weight: 600;
  color: var(--figma-ink);
  cursor: pointer;
  transition:
    background-color 0.2s ease,
    color 0.2s ease,
    border-color 0.2s ease,
    box-shadow 0.2s ease,
    transform 0.2s ease;
}

.catalog-toolbar__term-icon {
  flex-shrink: 0;
  display: block;
}

.catalog-toolbar__tab--active {
  background: transparent;
  color: var(--figma-on-accent);
}

.catalog-toolbar__term--active {
  background: var(--figma-accent);
  border-color: var(--figma-accent);
  color: var(--figma-on-accent);
}

.catalog-toolbar__tab:hover:not(.catalog-toolbar__tab--active) {
  color: var(--figma-accent);
}

.catalog-toolbar__term:hover:not(.catalog-toolbar__term--active) {
  border-color: rgba(225, 69, 84, 0.45);
  color: var(--figma-accent);
  background: rgba(225, 69, 84, 0.06);
  box-shadow: 0 2px 8px rgba(225, 69, 84, 0.12);
}

.catalog-toolbar__tab--active:hover {
  color: var(--figma-on-accent);
}

.catalog-toolbar__term--active:hover {
  background: var(--figma-accent-hover);
  border-color: var(--figma-accent-hover);
}

.catalog-toolbar__tab:active,
.catalog-toolbar__term:active {
  transform: scale(0.98);
}

.catalog-toolbar--compact .catalog-toolbar__tab,
.catalog-toolbar--compact .catalog-toolbar__term {
  height: 28px;
  padding: 0 12px;
  font-size: 13px;
  gap: 6px;
}

.catalog-toolbar--compact .catalog-toolbar__side :deep(.catalog-view-on-map) {
  height: 28px;
  padding: 0 12px;
  font-size: 13px;
}

.catalog-toolbar--compact .catalog-toolbar__term-icon {
  width: 14px;
  height: 14px;
}

.catalog-toolbar--compact {
  gap: 12px;
}

.catalog-toolbar--compact .catalog-toolbar__categories {
  border-radius: 10px;
}

.catalog-toolbar--compact .catalog-toolbar__indicator {
  border-radius: 7px;
}

.catalog-toolbar--compact .catalog-toolbar__terms {
  gap: 8px;
}

@media (prefers-reduced-motion: reduce) {
  .catalog-toolbar__indicator {
    transition: none;
  }

  .catalog-toolbar__tab,
  .catalog-toolbar__term {
    transition: none;
  }
}

@media (max-width: 767px) {
  .catalog-toolbar {
    flex-direction: column;
    align-items: stretch;
  }

  .catalog-toolbar__categories,
  .catalog-toolbar__terms {
    overflow-x: auto;
    flex-wrap: nowrap;
    max-width: 100%;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
  }

  .catalog-toolbar__categories::-webkit-scrollbar,
  .catalog-toolbar__terms::-webkit-scrollbar {
    display: none;
  }

  .catalog-toolbar__tab,
  .catalog-toolbar__term {
    flex-shrink: 0;
    min-height: var(--touch-target-min);
  }

  .catalog-toolbar__indicator {
    height: calc(100% - 6px);
  }
}
</style>
