<script setup lang="ts">
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { fetchCities } from '@/api/reference'
import ListingCard from '@/components/ListingCard.vue'
import { useAiAssistantModal } from '@/modules/consultant/composables/useAiAssistantModal'
import {
  AI_ASSISTANT_STEPS,
  AI_CURRENCY_OPTIONS,
  AI_PRIORITY_OPTIONS,
  applyCustomBudgetDisplay,
  budgetPresets,
  clearAiAssistantDraftLocal,
  convertBudgetFromUsd,
  createEmptyAiAssistantDraft,
  currencySymbol,
  customBudgetDisplayValue,
  formatBudgetAmount,
  isBudgetPresetSelected,
  loadAiAssistantDraftLocal,
  parseBudgetInput,
  progressPercent,
  saveAiAssistantDraftLocal,
  toAiPreferenceAnswers,
  validateAiAssistantStep,
  type AiAssistantCurrency,
  type AiAssistantDraft,
  type AiAssistantStepId,
  type AiPriorityId,
} from '@/modules/consultant/lib/aiAssistantWizard'
import { useAiAssistantStore } from '@/stores/aiAssistant'
import { useExchangeRateStore } from '@/stores/exchangeRate'
import { useFavoritesStore } from '@/stores/favorites'
import { useListingsStore } from '@/stores/listings'
import type { CityDto, DealType, ListingDto } from '@/types'

const { t } = useI18n()
const router = useRouter()
const { isOpen, close } = useAiAssistantModal()
const store = useAiAssistantStore()
const listingsStore = useListingsStore()
const favorites = useFavoritesStore()
const exchangeRates = useExchangeRateStore()

const draft = ref<AiAssistantDraft>(createEmptyAiAssistantDraft())
const stepIndex = ref(0)
const cities = ref<CityDto[]>([])
const localError = ref('')
const stepDirection = ref(1)
const customBudgetMinInput = ref('')
const customBudgetMaxInput = ref('')

const dealOptions: DealType[] = ['rent', 'sale']
const currencyOptions = AI_CURRENCY_OPTIONS

const questionnaireSteps = AI_ASSISTANT_STEPS.filter((step) => step !== 'result')
const step = computed(() => AI_ASSISTANT_STEPS[stepIndex.value] ?? 'deal')
const progress = computed(() => progressPercent(Math.min(stepIndex.value, questionnaireSteps.length)))
const currentStepNumber = computed(() => Math.min(stepIndex.value + 1, questionnaireSteps.length))
const isResult = computed(() => step.value === 'result')
const canGoNext = computed(() => validateAiAssistantStep(step.value, draft.value))
const presets = computed(() => budgetPresets(draft.value.dealType))
const resultListings = computed(() => store.recommendedListings)

const metroById = computed(() => {
  const map = new Map<number, (typeof listingsStore.metroStations)[number]>()
  for (const station of listingsStore.metroStations) {
    map.set(station.id, station)
  }
  return map
})

function getMetroStation(listing: ListingDto) {
  if (!listing.metroStationId) {
    return undefined
  }
  return metroById.value.get(listing.metroStationId)
}

function getDistrictLabel(listing: ListingDto) {
  if (listing.districtName && listing.cityName) {
    return `${listing.districtName}, ${listing.cityName}`
  }
  const district = listingsStore.districts.find((item) => item.id === listing.districtId)
  const city = listingsStore.cities.find((item) => item.id === listing.cityId)
  if (!district || !city) {
    return listing.districtName || listing.cityName || undefined
  }
  return `${district.name}, ${city.name}`
}

async function openRecommendedListing(id: number) {
  close()
  listingsStore.selectListing(id)
  void listingsStore.openDetailListing(id)
  if (router.currentRoute.value.name !== 'listing-detail' && router.currentRoute.value.path !== `/listings/${id}`) {
    await router.push(`/listings/${id}`)
  }
}

async function handleFavorite(id: number) {
  const listing = resultListings.value.find((item) => item.id === id)
  await favorites.toggle(id, listing)
}

watch(
  () => isOpen.value,
  (open) => {
    if (open) {
      const saved = loadAiAssistantDraftLocal()
      draft.value = saved ?? createEmptyAiAssistantDraft()
      syncCustomBudgetInputs()
      stepIndex.value = 0
      localError.value = ''
      void loadCities()
      void exchangeRates.load()
      void nextTick(() => {
        document.getElementById('ai-assistant-panel')?.focus()
      })
    }
  },
)

watch(
  draft,
  (value) => {
    if (!isResult.value) {
      saveAiAssistantDraftLocal(value)
    }
  },
  { deep: true },
)

watch(
  () => isOpen.value,
  (open) => {
    document.body.style.overflow = open ? 'hidden' : ''
  },
  { immediate: true },
)

function handleKeydown(event: KeyboardEvent) {
  if (event.key === 'Escape' && isOpen.value && !store.submitting) {
    close()
  }
}

onMounted(() => {
  window.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeydown)
  document.body.style.overflow = ''
})

async function loadCities() {
  try {
    cities.value = await fetchCities()
  } catch {
    cities.value = []
  }
}

function setDealType(dealType: DealType) {
  draft.value.dealType = dealType
  draft.value.budgetCustom = false
  draft.value.budgetMin = null
  draft.value.budgetMax = null
  customBudgetMinInput.value = ''
  customBudgetMaxInput.value = ''
}

function toggleCommercialListingType() {
  draft.value.listingType = draft.value.listingType === 'commercial' ? null : 'commercial'
}

function setCurrency(currency: AiAssistantCurrency) {
  draft.value.currency = currency
  if (draft.value.budgetCustom) {
    syncCustomBudgetInputs()
  }
}

function selectBudget(min: number | null, max: number | null) {
  draft.value.budgetCustom = false
  draft.value.budgetMin = min
  draft.value.budgetMax = max
  customBudgetMinInput.value = ''
  customBudgetMaxInput.value = ''
}

function selectCustomBudget() {
  draft.value.budgetCustom = true
  if (!draft.value.budgetMin && !draft.value.budgetMax) {
    customBudgetMinInput.value = ''
    customBudgetMaxInput.value = ''
    return
  }
  syncCustomBudgetInputs()
}

function syncCustomBudgetInputs() {
  if (!draft.value.budgetCustom) {
    customBudgetMinInput.value = ''
    customBudgetMaxInput.value = ''
    return
  }
  customBudgetMinInput.value = customBudgetDisplayValue(
    draft.value.budgetMin,
    draft.value.currency,
  )
  customBudgetMaxInput.value = customBudgetDisplayValue(
    draft.value.budgetMax,
    draft.value.currency,
  )
}

function onCustomBudgetInput() {
  applyCustomBudgetDisplay(
    draft.value,
    parseBudgetInput(customBudgetMinInput.value),
    parseBudgetInput(customBudgetMaxInput.value),
  )
}

function displayBudget(amountUsd: number | null): string | null {
  if (amountUsd === null) return null
  const value = convertBudgetFromUsd(amountUsd, draft.value.currency)
  return formatBudgetAmount(value)
}

function selectRooms(rooms: number | null) {
  draft.value.rooms = rooms
}

function selectCity(cityId: number | null) {
  draft.value.cityId = cityId
}

function togglePriority(priority: AiPriorityId) {
  const list = draft.value.priorities
  if (list.includes(priority)) {
    draft.value.priorities = list.filter((item) => item !== priority)
  } else {
    draft.value.priorities = [...list, priority]
  }
}

function goBack() {
  if (stepIndex.value <= 0 || store.submitting) return
  stepDirection.value = -1
  stepIndex.value -= 1
  localError.value = ''
}

async function goNext() {
  if (!canGoNext.value || store.submitting) return
  localError.value = ''

  if (step.value === 'priorities') {
    try {
      await store.submitAnswers(toAiPreferenceAnswers(draft.value))
      clearAiAssistantDraftLocal()
      stepDirection.value = 1
      stepIndex.value = AI_ASSISTANT_STEPS.indexOf('result')
    } catch {
      localError.value = store.error || t('aiAssistant.submitError')
    }
    return
  }

  if (stepIndex.value < questionnaireSteps.length - 1) {
    stepDirection.value = 1
    stepIndex.value += 1
  }
}

function restart() {
  draft.value = createEmptyAiAssistantDraft()
  clearAiAssistantDraftLocal()
  customBudgetMinInput.value = ''
  customBudgetMaxInput.value = ''
  stepIndex.value = 0
  localError.value = ''
}

function budgetLabel(min: number | null, max: number | null): string {
  const symbol = currencySymbol(draft.value.currency)
  const displayMin = displayBudget(min)
  const displayMax = displayBudget(max)
  if (min === null && displayMax !== null) {
    return t('aiAssistant.budgetUpTo', { n: displayMax, symbol })
  }
  if (displayMin !== null && max === null) {
    return t('aiAssistant.budgetFrom', { n: displayMin, symbol })
  }
  if (displayMin !== null && displayMax !== null) {
    return t('aiAssistant.budgetRange', { min: displayMin, max: displayMax, symbol })
  }
  return t('aiAssistant.budgetAny')
}

function isBudgetSelected(min: number | null, max: number | null): boolean {
  return isBudgetPresetSelected(draft.value, min, max)
}

function stepTitle(id: AiAssistantStepId): string {
  return t(`aiAssistant.steps.${id}.title`)
}

function stepHint(id: AiAssistantStepId): string {
  return t(`aiAssistant.steps.${id}.hint`)
}

function primaryLabel(): string {
  if (store.submitting) return t('aiAssistant.analyzing')
  if (step.value === 'priorities') return t('aiAssistant.analyze')
  return t('aiAssistant.next')
}
</script>

<template>
  <Teleport to="body">
    <Transition name="ai-assistant-fade">
      <div
        v-if="isOpen"
        class="ai-assistant"
        role="dialog"
        aria-modal="true"
        :aria-label="t('aiAssistant.title')"
        @click.self="close"
      >
        <div
          id="ai-assistant-panel"
          class="ai-assistant__panel"
          tabindex="-1"
          @click.stop
        >
          <div class="ai-assistant__accent" aria-hidden="true" />

          <button
            type="button"
            class="ai-assistant__close"
            :aria-label="t('aiAssistant.close')"
            :disabled="store.submitting"
            @click="close"
          >
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
            </svg>
          </button>

          <header class="ai-assistant__header">
            <div class="ai-assistant__brand">
              <span class="ai-assistant__mark" aria-hidden="true">
                <img src="/figma/ai-sparkle.svg" alt="" width="22" height="22" />
              </span>
              <div class="ai-assistant__brand-text">
                <p class="ai-assistant__title">{{ t('aiAssistant.title') }}</p>
                <p class="ai-assistant__subtitle">{{ t('aiAssistant.subtitle') }}</p>
              </div>
            </div>

            <div v-if="!isResult" class="ai-assistant__steps" aria-hidden="true">
              <span
                v-for="(item, index) in questionnaireSteps"
                :key="item"
                class="ai-assistant__step-dot"
                :class="{
                  'ai-assistant__step-dot--done': index < stepIndex,
                  'ai-assistant__step-dot--active': index === stepIndex,
                }"
              />
            </div>
            <p v-if="!isResult" class="ai-assistant__step-meta">
              {{ t('aiAssistant.stepOf', { current: currentStepNumber, total: questionnaireSteps.length }) }}
            </p>
            <div v-if="!isResult" class="ai-assistant__progress" aria-hidden="true">
              <div class="ai-assistant__progress-bar" :style="{ width: `${progress}%` }" />
            </div>
          </header>

          <div class="ai-assistant__body">
            <Transition :name="stepDirection >= 0 ? 'ai-step-forward' : 'ai-step-back'" mode="out-in">
              <div :key="step" class="ai-assistant__step">
                <template v-if="!isResult">
                  <h2 class="ai-assistant__step-title">{{ stepTitle(step) }}</h2>
                  <p class="ai-assistant__step-hint">{{ stepHint(step) }}</p>

                  <div v-if="step === 'deal'" class="ai-assistant__cards">
                    <button
                      v-for="deal in dealOptions"
                      :key="deal"
                      type="button"
                      class="ai-assistant__card ai-assistant__card--compact"
                      :class="{ 'ai-assistant__card--active': draft.dealType === deal }"
                      @click="setDealType(deal)"
                    >
                      <span class="ai-assistant__card-copy">
                        <span class="ai-assistant__card-title">{{ t(`aiAssistant.deal.${deal}`) }}</span>
                        <span class="ai-assistant__card-hint">{{ t(`aiAssistant.deal.${deal}Hint`) }}</span>
                      </span>
                      <span class="ai-assistant__card-check" aria-hidden="true">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                          <path d="M6 12.5l4 4 8-9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                      </span>
                    </button>
                    <button
                      type="button"
                      class="ai-assistant__card ai-assistant__card--compact"
                      :class="{ 'ai-assistant__card--active': draft.listingType === 'commercial' }"
                      @click="toggleCommercialListingType"
                    >
                      <span class="ai-assistant__card-copy">
                        <span class="ai-assistant__card-title">{{ t('aiAssistant.deal.commercial') }}</span>
                        <span class="ai-assistant__card-hint">{{ t('aiAssistant.deal.commercialHint') }}</span>
                      </span>
                      <span class="ai-assistant__card-check" aria-hidden="true">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                          <path d="M6 12.5l4 4 8-9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                      </span>
                    </button>
                  </div>

                  <div v-else-if="step === 'currency'" class="ai-assistant__cards">
                    <button
                      v-for="code in currencyOptions"
                      :key="code"
                      type="button"
                      class="ai-assistant__card ai-assistant__card--compact"
                      :class="{ 'ai-assistant__card--active': draft.currency === code }"
                      @click="setCurrency(code)"
                    >
                      <span class="ai-assistant__card-copy">
                        <span class="ai-assistant__card-title">{{ t(`aiAssistant.currency.${code}`) }}</span>
                        <span class="ai-assistant__card-hint">{{ t(`aiAssistant.currency.${code}Hint`) }}</span>
                      </span>
                      <span class="ai-assistant__card-check" aria-hidden="true">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                          <path d="M6 12.5l4 4 8-9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                      </span>
                    </button>
                  </div>

                  <div v-else-if="step === 'budget'" class="ai-assistant__budget">
                    <div class="ai-assistant__cards">
                      <button
                        v-for="(preset, index) in presets"
                        :key="index"
                        type="button"
                        class="ai-assistant__card ai-assistant__card--compact"
                        :class="{ 'ai-assistant__card--active': isBudgetSelected(preset.min, preset.max) }"
                        @click="selectBudget(preset.min, preset.max)"
                      >
                        <span class="ai-assistant__card-title">{{ budgetLabel(preset.min, preset.max) }}</span>
                        <span class="ai-assistant__card-check" aria-hidden="true">
                          <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                            <path d="M6 12.5l4 4 8-9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                          </svg>
                        </span>
                      </button>
                      <button
                        type="button"
                        class="ai-assistant__card ai-assistant__card--compact"
                        :class="{ 'ai-assistant__card--active': draft.budgetCustom }"
                        @click="selectCustomBudget"
                      >
                        <span class="ai-assistant__card-copy">
                          <span class="ai-assistant__card-title">{{ t('aiAssistant.budgetCustom') }}</span>
                          <span class="ai-assistant__card-hint">{{ t('aiAssistant.budgetCustomHint') }}</span>
                        </span>
                        <span class="ai-assistant__card-check" aria-hidden="true">
                          <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                            <path d="M6 12.5l4 4 8-9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                          </svg>
                        </span>
                      </button>
                    </div>

                    <div v-if="draft.budgetCustom" class="ai-assistant__custom-budget">
                      <label class="ai-assistant__custom-field">
                        <span class="ai-assistant__custom-label">{{ t('aiAssistant.budgetFromLabel') }}</span>
                        <span class="ai-assistant__custom-control">
                          <input
                            v-model="customBudgetMinInput"
                            class="ai-assistant__custom-input"
                            type="number"
                            inputmode="numeric"
                            min="0"
                            step="1"
                            :placeholder="t('aiAssistant.budgetPlaceholder')"
                            :aria-label="t('aiAssistant.budgetFromLabel')"
                            @input="onCustomBudgetInput"
                          >
                          <span class="ai-assistant__custom-suffix">{{ currencySymbol(draft.currency) }}</span>
                        </span>
                      </label>
                      <label class="ai-assistant__custom-field">
                        <span class="ai-assistant__custom-label">{{ t('aiAssistant.budgetToLabel') }}</span>
                        <span class="ai-assistant__custom-control">
                          <input
                            v-model="customBudgetMaxInput"
                            class="ai-assistant__custom-input"
                            type="number"
                            inputmode="numeric"
                            min="0"
                            step="1"
                            :placeholder="t('aiAssistant.budgetPlaceholder')"
                            :aria-label="t('aiAssistant.budgetToLabel')"
                            @input="onCustomBudgetInput"
                          >
                          <span class="ai-assistant__custom-suffix">{{ currencySymbol(draft.currency) }}</span>
                        </span>
                      </label>
                    </div>
                  </div>

                  <div v-else-if="step === 'rooms'" class="ai-assistant__rooms">
                    <button
                      type="button"
                      class="ai-assistant__room"
                      :class="{ 'ai-assistant__room--active': draft.rooms === null }"
                      @click="selectRooms(null)"
                    >
                      {{ t('aiAssistant.roomsAny') }}
                    </button>
                    <button
                      type="button"
                      class="ai-assistant__room"
                      :class="{ 'ai-assistant__room--active': draft.rooms === 0 }"
                      @click="selectRooms(0)"
                    >
                      {{ t('aiAssistant.roomsStudio') }}
                    </button>
                    <button
                      v-for="n in 4"
                      :key="n"
                      type="button"
                      class="ai-assistant__room"
                      :class="{ 'ai-assistant__room--active': draft.rooms === n }"
                      @click="selectRooms(n)"
                    >
                      {{ t('aiAssistant.roomsN', { n }) }}
                    </button>
                    <button
                      type="button"
                      class="ai-assistant__room"
                      :class="{ 'ai-assistant__room--active': draft.rooms === 5 }"
                      @click="selectRooms(5)"
                    >
                      {{ t('aiAssistant.rooms5plus') }}
                    </button>
                  </div>

                  <div v-else-if="step === 'city'" class="ai-assistant__chips">
                    <button
                      type="button"
                      class="ai-assistant__chip"
                      :class="{ 'ai-assistant__chip--active': draft.cityId === null }"
                      @click="selectCity(null)"
                    >
                      {{ t('aiAssistant.cityAny') }}
                    </button>
                    <button
                      v-for="city in cities"
                      :key="city.id"
                      type="button"
                      class="ai-assistant__chip"
                      :class="{ 'ai-assistant__chip--active': draft.cityId === city.id }"
                      @click="selectCity(city.id)"
                    >
                      {{ city.name }}
                    </button>
                  </div>

                  <div v-else-if="step === 'priorities'" class="ai-assistant__cards">
                    <button
                      v-for="priority in AI_PRIORITY_OPTIONS"
                      :key="priority"
                      type="button"
                      class="ai-assistant__card ai-assistant__card--compact"
                      :class="{ 'ai-assistant__card--active': draft.priorities.includes(priority) }"
                      @click="togglePriority(priority)"
                    >
                      <span class="ai-assistant__card-title">{{ t(`aiAssistant.priorities.${priority}`) }}</span>
                      <span class="ai-assistant__card-check" aria-hidden="true">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                          <path d="M6 12.5l4 4 8-9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                      </span>
                    </button>
                    <p class="ai-assistant__note">{{ t('aiAssistant.prioritiesOptional') }}</p>
                  </div>
                </template>

                <template v-else>
                  <div class="ai-assistant__result-hero">
                    <span class="ai-assistant__result-mark" aria-hidden="true">
                      <img src="/figma/ai-star.svg" alt="" width="18" height="18" />
                    </span>
                    <h2 class="ai-assistant__step-title">{{ t('aiAssistant.resultTitle') }}</h2>
                  </div>
                  <p class="ai-assistant__summary">{{ store.summary }}</p>
                  <div v-if="store.highlights.length" class="ai-assistant__tags">
                    <span v-for="(item, index) in store.highlights" :key="index" class="ai-assistant__tag">
                      {{ item }}
                    </span>
                  </div>

                  <div v-if="resultListings.length" class="ai-assistant__results">
                    <div
                      v-for="listing in resultListings"
                      :key="listing.id"
                      class="ai-assistant__result-item"
                      role="button"
                      tabindex="0"
                      @click="openRecommendedListing(listing.id)"
                      @keydown.enter.prevent="openRecommendedListing(listing.id)"
                      @keydown.space.prevent="openRecommendedListing(listing.id)"
                    >
                      <ListingCard
                        :listing="listing"
                        :metro-station="getMetroStation(listing)"
                        :district-name="getDistrictLabel(listing)"
                        :favorited="favorites.isFavorite(listing.id)"
                        ai-recommended
                        @favorite="handleFavorite"
                      />
                    </div>
                  </div>
                  <p v-else class="ai-assistant__empty">{{ t('aiAssistant.noMatches') }}</p>
                </template>
              </div>
            </Transition>

            <p v-if="localError" class="ai-assistant__error" role="alert">{{ localError }}</p>
          </div>

          <footer class="ai-assistant__footer">
            <button
              v-if="!isResult && stepIndex > 0"
              type="button"
              class="ai-assistant__btn ai-assistant__btn--ghost"
              :disabled="store.submitting"
              @click="goBack"
            >
              {{ t('aiAssistant.back') }}
            </button>
            <button
              v-if="!isResult"
              type="button"
              class="ai-assistant__btn ai-assistant__btn--primary"
              :disabled="!canGoNext || store.submitting"
              @click="goNext"
            >
              <span v-if="store.submitting" class="ai-assistant__spinner" aria-hidden="true" />
              {{ primaryLabel() }}
            </button>
            <template v-else>
              <button type="button" class="ai-assistant__btn ai-assistant__btn--ghost" @click="restart">
                {{ t('aiAssistant.restart') }}
              </button>
              <button type="button" class="ai-assistant__btn ai-assistant__btn--primary" @click="close">
                {{ t('aiAssistant.done') }}
              </button>
            </template>
          </footer>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.ai-assistant {
  position: fixed;
  inset: 0;
  z-index: 4200;
  display: grid;
  place-items: center;
  padding: 16px;
  padding-bottom: calc(16px + env(safe-area-inset-bottom, 0px));
  background: rgba(0, 0, 0, 0.58);
  backdrop-filter: blur(2px);
}

.ai-assistant__panel {
  position: relative;
  width: min(100%, 480px);
  max-height: min(92vh, 760px);
  display: flex;
  flex-direction: column;
  border: 1px solid var(--figma-border);
  border-radius: 20px;
  background: var(--figma-surface);
  color: var(--figma-ink);
  box-shadow: 0 24px 48px rgba(0, 0, 0, 0.16);
  overflow: hidden;
  outline: none;
}

.ai-assistant__accent {
  height: 4px;
  flex-shrink: 0;
  background: linear-gradient(90deg, var(--figma-accent) 0%, #ff8a96 100%);
}

.ai-assistant__close {
  position: absolute;
  top: 16px;
  right: 16px;
  z-index: 2;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border: 1px solid var(--figma-border);
  border-radius: 50px;
  background: var(--figma-surface);
  color: var(--color-text-muted);
  cursor: pointer;
  transition:
    border-color 0.2s ease,
    color 0.2s ease,
    transform 0.2s ease;
}

.ai-assistant__close:hover:not(:disabled) {
  border-color: #ccc;
  color: var(--figma-ink);
}

.ai-assistant__close:active:not(:disabled) {
  transform: scale(0.96);
}

.ai-assistant__close:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.ai-assistant__header {
  display: flex;
  flex-direction: column;
  gap: 14px;
  padding: 22px 24px 0;
  padding-right: 56px;
}

.ai-assistant__brand {
  display: flex;
  align-items: center;
  gap: 12px;
}

.ai-assistant__mark {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 48px;
  height: 48px;
  flex-shrink: 0;
  border-radius: 14px;
  background: color-mix(in srgb, var(--figma-accent) 12%, var(--figma-mix-base));
  background: var(--figma-filter-bg);
}

.ai-assistant__title {
  margin: 0;
  font-size: 20px;
  font-weight: 600;
  line-height: 1.15;
  color: var(--figma-ink);
}

.ai-assistant__subtitle {
  margin: 4px 0 0;
  font-size: 13px;
  line-height: 1.35;
  color: var(--color-text-muted);
}

.ai-assistant__steps {
  display: flex;
  gap: 6px;
}

.ai-assistant__step-dot {
  width: 8px;
  height: 8px;
  border-radius: 999px;
  background: #e8e8ec;
  transition:
    width 0.2s ease,
    background-color 0.2s ease;
}

.ai-assistant__step-dot--done {
  background: color-mix(in srgb, var(--figma-accent) 45%, var(--figma-mix-base));
}

.ai-assistant__step-dot--active {
  width: 22px;
  background: var(--figma-accent);
}

.ai-assistant__step-meta {
  margin: 0;
  font-size: 12px;
  color: var(--figma-gray-mid);
}

.ai-assistant__progress {
  height: 3px;
  border-radius: 999px;
  background: #f0f0f3;
  overflow: hidden;
}

.ai-assistant__progress-bar {
  height: 100%;
  border-radius: inherit;
  background: linear-gradient(90deg, var(--figma-accent) 0%, #ff8a96 100%);
  transition: width 0.25s ease-out;
}

.ai-assistant__body {
  flex: 1 1 auto;
  min-height: 0;
  overflow: auto;
  padding: 18px 24px 8px;
}

.ai-assistant__step-title {
  margin: 0 0 6px;
  font-size: 22px;
  font-weight: 700;
  line-height: 1.2;
  color: var(--figma-ink);
}

.ai-assistant__step-hint,
.ai-assistant__summary,
.ai-assistant__note,
.ai-assistant__empty {
  margin: 0 0 16px;
  font-size: 14px;
  line-height: 1.45;
  color: var(--color-text-muted);
}

.ai-assistant__cards {
  display: grid;
  gap: 10px;
}

.ai-assistant__card {
  display: grid;
  grid-template-columns: auto 1fr auto;
  align-items: center;
  gap: 12px;
  min-height: 72px;
  padding: 14px 16px;
  border: 1px solid #ebebef;
  border-radius: 14px;
  background: var(--figma-surface);
  text-align: left;
  cursor: pointer;
  transition:
    border-color 0.18s ease,
    background-color 0.18s ease,
    box-shadow 0.18s ease,
    transform 0.18s ease;
}

.ai-assistant__card--compact {
  grid-template-columns: 1fr auto;
  min-height: 54px;
}

.ai-assistant__card:hover {
  border-color: #d5d5dc;
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.04);
}

.ai-assistant__card:active {
  transform: scale(0.985);
}

.ai-assistant__card--active {
  border-color: var(--figma-accent);
  background: color-mix(in srgb, var(--figma-accent) 8%, var(--figma-mix-base));
  box-shadow: 0 0 0 1px color-mix(in srgb, var(--figma-accent) 25%, transparent);
}

.ai-assistant__card-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 42px;
  height: 42px;
  border-radius: 12px;
  background: var(--figma-page-bg);
  color: #333;
}

.ai-assistant__card--active .ai-assistant__card-icon {
  background: color-mix(in srgb, var(--figma-accent) 14%, var(--figma-mix-base));
  color: var(--figma-accent);
}

.ai-assistant__card-copy {
  display: flex;
  flex-direction: column;
  gap: 3px;
  min-width: 0;
}

.ai-assistant__card-title {
  font-size: 15px;
  font-weight: 600;
  color: var(--figma-ink-secondary);
}

.ai-assistant__card-hint {
  font-size: 12px;
  line-height: 1.35;
  color: #777;
}

.ai-assistant__card-check {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  border-radius: 999px;
  border: 1px solid #e2e2e8;
  color: transparent;
  transition:
    background-color 0.18s ease,
    border-color 0.18s ease,
    color 0.18s ease;
}

.ai-assistant__card--active .ai-assistant__card-check {
  border-color: var(--figma-accent);
  background: var(--figma-accent);
  color: var(--figma-on-accent);
}

.ai-assistant__budget {
  display: grid;
  gap: 10px;
}

.ai-assistant__custom-budget {
  display: grid;
  gap: 10px;
  margin-top: 2px;
}

.ai-assistant__custom-field {
  display: grid;
  grid-template-columns: 36px minmax(0, 1fr);
  align-items: center;
  gap: 10px;
}

.ai-assistant__custom-label {
  font-size: 14px;
  font-weight: 600;
  color: var(--figma-ink-secondary);
}

.ai-assistant__custom-control {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  align-items: center;
  gap: 8px;
  min-width: 0;
  padding: 0 12px 0 0;
  border: 1px solid #ebebef;
  border-radius: 12px;
  background: var(--figma-surface);
}

.ai-assistant__custom-input {
  width: 100%;
  min-width: 0;
  min-height: 44px;
  margin: 0;
  padding: 10px 12px;
  border: 0;
  border-radius: 12px;
  background: transparent;
  color: var(--figma-ink-secondary);
  font: inherit;
  font-size: 15px;
  font-weight: 600;
  appearance: textfield;
  -moz-appearance: textfield;
}

.ai-assistant__custom-input::-webkit-outer-spin-button,
.ai-assistant__custom-input::-webkit-inner-spin-button {
  margin: 0;
  -webkit-appearance: none;
}

.ai-assistant__custom-input:focus {
  outline: none;
}

.ai-assistant__custom-control:focus-within {
  border-color: var(--figma-accent);
  box-shadow: 0 0 0 1px color-mix(in srgb, var(--figma-accent) 25%, transparent);
}

.ai-assistant__custom-suffix {
  flex-shrink: 0;
  font-size: 13px;
  font-weight: 600;
  color: #777;
}

.ai-assistant__rooms {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 10px;
}

.ai-assistant__room {
  min-height: 52px;
  border: 1px solid #ebebef;
  border-radius: 14px;
  background: var(--figma-surface);
  font-size: 15px;
  font-weight: 600;
  color: var(--figma-ink-secondary);
  cursor: pointer;
  transition:
    border-color 0.18s ease,
    background-color 0.18s ease,
    transform 0.18s ease;
}

.ai-assistant__room:hover {
  border-color: #d5d5dc;
}

.ai-assistant__room:active {
  transform: scale(0.97);
}

.ai-assistant__room--active {
  border-color: var(--figma-accent);
  background: color-mix(in srgb, var(--figma-accent) 10%, var(--figma-mix-base));
  color: var(--figma-accent);
}

.ai-assistant__chips {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  max-height: 280px;
  overflow: auto;
  padding-bottom: 4px;
}

.ai-assistant__chip {
  min-height: 40px;
  padding: 8px 14px;
  border: 1px solid #ebebef;
  border-radius: 999px;
  background: var(--figma-surface);
  color: #222;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  transition:
    border-color 0.18s ease,
    background-color 0.18s ease,
    color 0.18s ease;
}

.ai-assistant__chip:hover {
  border-color: #d5d5dc;
}

.ai-assistant__chip--active {
  border-color: var(--figma-accent);
  background: color-mix(in srgb, var(--figma-accent) 10%, var(--figma-mix-base));
  color: var(--figma-accent);
}

.ai-assistant__result-hero {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 8px;
}

.ai-assistant__result-mark {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border-radius: 10px;
  background: color-mix(in srgb, var(--figma-accent) 12%, var(--figma-mix-base));
}

.ai-assistant__result-hero .ai-assistant__step-title {
  margin: 0;
}

.ai-assistant__tags {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin: 0 0 16px;
}

.ai-assistant__tag {
  display: inline-flex;
  padding: 6px 10px;
  border-radius: 999px;
  background: var(--figma-page-bg);
  color: #444;
  font-size: 12px;
  font-weight: 600;
}

.ai-assistant__results {
  display: grid;
  gap: 10px;
}

.ai-assistant__result-card {
  display: grid;
  grid-template-columns: 88px 1fr;
  gap: 12px;
  padding: 10px;
  border: 1px solid #ebebef;
  border-radius: 14px;
  background: #fafafa;
}

.ai-assistant__result-photo {
  width: 88px;
  height: 88px;
  border-radius: 10px;
  overflow: hidden;
  background: #e8e8ec;
}

.ai-assistant__result-photo img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.ai-assistant__result-body {
  min-width: 0;
}

.ai-assistant__result-title {
  margin: 0 0 4px;
  font-size: 14px;
  font-weight: 600;
  color: var(--figma-ink-secondary);
}

.ai-assistant__result-address,
.ai-assistant__result-price {
  margin: 0;
  font-size: 13px;
  color: var(--color-text-muted);
}

.ai-assistant__result-price {
  margin-top: 4px;
  font-weight: 600;
  color: var(--figma-ink-secondary);
}

.ai-assistant__result-badge {
  display: inline-flex;
  margin-top: 8px;
  padding: 4px 8px;
  border-radius: 999px;
  background: color-mix(in srgb, var(--figma-accent) 12%, var(--figma-mix-base));
  color: var(--figma-accent);
  font-size: 11px;
  font-weight: 600;
}

.ai-assistant__error {
  margin: 12px 0 0;
  color: var(--color-danger);
  font-size: 13px;
}

.ai-assistant__footer {
  display: flex;
  gap: 10px;
  padding: 14px 24px 22px;
  border-top: 1px solid #f0f0f3;
}

.ai-assistant__btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  min-height: 48px;
  padding: 0 18px;
  border: 0;
  border-radius: 12px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  transition:
    opacity 0.18s ease,
    transform 0.18s ease,
    background-color 0.18s ease;
}

.ai-assistant__btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.ai-assistant__btn--primary {
  flex: 1 1 auto;
  background: var(--figma-accent);
  color: var(--figma-on-accent);
}

.ai-assistant__btn--primary:hover:not(:disabled) {
  background: var(--figma-accent-hover);
}

.ai-assistant__btn--ghost {
  flex: 0 0 auto;
  background: #f3f3f6;
  color: var(--figma-ink-secondary);
}

.ai-assistant__btn:active:not(:disabled) {
  transform: scale(0.98);
}

.ai-assistant__spinner {
  width: 16px;
  height: 16px;
  border: 2px solid rgba(255, 255, 255, 0.35);
  border-top-color: var(--figma-on-accent);
  border-radius: 50%;
  animation: ai-spin 0.7s linear infinite;
}

@keyframes ai-spin {
  to {
    transform: rotate(360deg);
  }
}

.ai-assistant-fade-enter-active,
.ai-assistant-fade-leave-active {
  transition: opacity 0.2s ease;
}

.ai-assistant-fade-enter-active .ai-assistant__panel,
.ai-assistant-fade-leave-active .ai-assistant__panel {
  transition:
    transform 0.22s ease,
    opacity 0.22s ease;
}

.ai-assistant-fade-enter-from,
.ai-assistant-fade-leave-to {
  opacity: 0;
}

.ai-assistant-fade-enter-from .ai-assistant__panel,
.ai-assistant-fade-leave-to .ai-assistant__panel {
  opacity: 0;
  transform: translateY(10px) scale(0.98);
}

.ai-step-forward-enter-active,
.ai-step-forward-leave-active,
.ai-step-back-enter-active,
.ai-step-back-leave-active {
  transition:
    opacity 0.18s ease,
    transform 0.18s ease;
}

.ai-step-forward-enter-from {
  opacity: 0;
  transform: translateX(14px);
}

.ai-step-forward-leave-to {
  opacity: 0;
  transform: translateX(-14px);
}

.ai-step-back-enter-from {
  opacity: 0;
  transform: translateX(-14px);
}

.ai-step-back-leave-to {
  opacity: 0;
  transform: translateX(14px);
}

@media (prefers-reduced-motion: reduce) {
  .ai-assistant__progress-bar,
  .ai-assistant__card,
  .ai-assistant__room,
  .ai-assistant__chip,
  .ai-assistant__btn,
  .ai-assistant__step-dot,
  .ai-assistant-fade-enter-active,
  .ai-assistant-fade-leave-active,
  .ai-assistant-fade-enter-active .ai-assistant__panel,
  .ai-assistant-fade-leave-active .ai-assistant__panel,
  .ai-step-forward-enter-active,
  .ai-step-forward-leave-active,
  .ai-step-back-enter-active,
  .ai-step-back-leave-active,
  .ai-assistant__spinner {
    transition-duration: 0.01ms !important;
    animation: none !important;
  }
}

@media (max-width: 767px) {
  .ai-assistant {
    align-items: end;
    place-items: end center;
    padding: 0;
  }

  .ai-assistant__panel {
    width: 100%;
    max-height: 94vh;
    border-radius: 20px 20px 0 0;
  }

  .ai-assistant__header,
  .ai-assistant__body {
    padding-left: 18px;
    padding-right: 18px;
  }

  .ai-assistant__header {
    padding-right: 52px;
  }

  .ai-assistant__footer {
    padding: 12px 18px calc(16px + env(safe-area-inset-bottom, 0px));
  }

  .ai-assistant__rooms {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}
</style>
