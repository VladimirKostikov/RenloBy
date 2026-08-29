<script setup lang="ts">
import { computed, nextTick, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { createMyListing } from '@/api/account'
import { fetchCities, fetchMetroStations } from '@/api/reference'
import MetroIcon from '@/components/MetroIcon.vue'
import { reverseGeocode } from '@/lib/reverseGeocode'
import { METRO_LINE_COLOR_OPTIONS, normalizeMetroLineColor } from '@/lib/metroLineColor'
import { resolveApiError } from '@/lib/resolveApiError'
import { scrollElementBelowStickyHeader } from '@/lib/scrollBelowStickyHeader'
import { useExchangeRateStore } from '@/stores/exchangeRate'
import WizardLocationField from '@/modules/seller/components/WizardLocationField.vue'
import WizardLocationMap from '@/modules/seller/components/WizardLocationMap.vue'
import WizardMediaDropzone from '@/modules/seller/components/WizardMediaDropzone.vue'
import WizardReviewCard from '@/modules/seller/components/WizardReviewCard.vue'
import {
  clearWizardDraftLocal,
  createEmptyWizardDraft,
  isWizardDraftEmpty,
  loadWizardDraftLocal,
  parseOptionalNumber,
  resolveRegionLabel,
  saveWizardDraftLocal,
  syncWizardPriceFromByn,
  syncWizardPriceFromUsd,
  toCreatePayload,
  validateWizardField,
  validateWizardStep,
  WIZARD_STEPS,
  type ListingWizardDraft,
  type WizardLocationFieldKey,
  type WizardStepId,
} from '@/modules/seller/lib/listingWizard'
import type { CityDto, ListingStatus, MetroStationDto } from '@/types'

const { t, locale } = useI18n()
const router = useRouter()
const rootRef = ref<HTMLElement | null>(null)
const exchangeRate = useExchangeRateStore()

const draft = ref<ListingWizardDraft>(createEmptyWizardDraft())
const stepIndex = ref(0)
const errors = ref<Record<string, string>>({})
const touched = ref<Record<string, boolean>>({})
const submitting = ref(false)
const submitError = ref('')
const draftSavedAt = ref<number | null>(null)
const cities = ref<CityDto[]>([])
const metroStations = ref<MetroStationDto[]>([])
const geocoding = ref(false)
const geocodeError = ref('')
let geocodeRequestId = 0

const step = computed(() => WIZARD_STEPS[stepIndex.value] ?? 'deal')
const stepDirection = ref(1)

const metroColorOptions = computed(() => {
  const known = new Set<string>(METRO_LINE_COLOR_OPTIONS.map((color) => normalizeMetroLineColor(color)))
  for (const station of metroStations.value) {
    known.add(normalizeMetroLineColor(station.lineColor))
  }
  known.add(normalizeMetroLineColor(draft.value.metroLineColor))
  return [...known]
})

function syncMetroLineColorFromStation(metroName: string) {
  const normalized = metroName.trim().toLocaleLowerCase('ru')
  if (!normalized) {
    return
  }
  const station = metroStations.value.find(
    (item) => item.name.trim().toLocaleLowerCase('ru') === normalized,
  )
  if (station) {
    draft.value.metroLineColor = normalizeMetroLineColor(station.lineColor)
  }
}

function selectMetroLineColor(color: string) {
  draft.value.metroLineColor = normalizeMetroLineColor(color)
}

function setFieldAbsent(field: WizardLocationFieldKey, absent: boolean) {
  draft.value.absent[field] = absent
  if (!absent) {
    return
  }
  if (field === 'floor' || field === 'totalFloors') {
    draft.value[field] = null
    touch(field)
    return
  }
  draft.value[field] = ''
  if (field === 'metro') {
    draft.value.metroMinutes = null
  }
}

type DealTypeTileOption = 'sale' | 'rent' | 'commercial'

const selectedDealTypeOption = computed<DealTypeTileOption>(() =>
  draft.value.listingType === 'commercial' ? 'commercial' : draft.value.dealType,
)

function selectDealTypeOption(option: DealTypeTileOption) {
  if (option === 'commercial') {
    draft.value.dealType = 'sale'
    draft.value.listingType = 'commercial'
    return
  }

  draft.value.dealType = option
  if (draft.value.listingType === 'commercial') {
    draft.value.listingType = 'apartment'
  }
}

watch(
  () => draft.value.metro,
  (metro) => {
    syncMetroLineColorFromStation(metro)
  },
)

function regionLabelForCity(cityName: string): string {
  const city = cities.value.find(
    (item) => item.name.toLocaleLowerCase('ru') === cityName.trim().toLocaleLowerCase('ru'),
  )
  if (!city) {
    return draft.value.region
  }
  return resolveRegionLabel(city.regionSlug, t, city.name)
}

async function applyCoords(latitude: number, longitude: number) {
  draft.value.latitude = latitude
  draft.value.longitude = longitude
  geocodeError.value = ''
  geocoding.value = true
  const requestId = ++geocodeRequestId

  try {
    const result = await reverseGeocode(
      latitude,
      longitude,
      locale.value === 'en' ? 'en' : 'ru',
    )
    if (requestId !== geocodeRequestId || !result) {
      if (!result) {
        geocodeError.value = t('account.wizard.geocodeError')
      }
      return
    }

    const cityName = (result.city || result.label || '').trim()
    if (cityName) {
      draft.value.city = cityName
      draft.value.region = regionLabelForCity(cityName) || result.region || draft.value.region
      touch('city')
      touch('region')
    } else if (result.region) {
      draft.value.region = result.region
      touch('region')
    }
    if (result.district && !draft.value.absent.district) {
      draft.value.district = result.district
    }
    if (result.street && !draft.value.absent.street) {
      draft.value.street = result.street
      touch('street')
    }
    if (result.house && !draft.value.absent.house) {
      draft.value.house = result.house
      touch('house')
    }
  } catch {
    geocodeError.value = t('account.wizard.geocodeError')
  } finally {
    if (requestId === geocodeRequestId) {
      geocoding.value = false
    }
  }
}

function onMapCoords(latitude: number, longitude: number) {
  void applyCoords(latitude, longitude)
}

onMounted(async () => {
  const saved = loadWizardDraftLocal()
  if (saved) {
    draft.value = saved.draft
    stepIndex.value = saved.stepIndex
    draftSavedAt.value = Date.now()
  }

  const [cityList, metroList] = await Promise.all([
    fetchCities(),
    fetchMetroStations(),
    exchangeRate.load(),
  ])
  cities.value = cityList
  metroStations.value = metroList
  if (draft.value.price !== null) {
    syncWizardPriceFromUsd(draft.value, draft.value.price)
  }
})

watch(
  draft,
  () => {
    if (isWizardDraftEmpty(draft.value) && stepIndex.value === 0) {
      clearWizardDraftLocal()
      draftSavedAt.value = null
      revalidateTouched()
      return
    }
    saveWizardDraftLocal(draft.value, stepIndex.value)
    draftSavedAt.value = Date.now()
    revalidateTouched()
  },
  { deep: true },
)

watch(stepIndex, (index) => {
  if (isWizardDraftEmpty(draft.value) && index === 0) {
    clearWizardDraftLocal()
    draftSavedAt.value = null
    return
  }
  saveWizardDraftLocal(draft.value, index)
  draftSavedAt.value = Date.now()
})

watch(
  () => draft.value.dealType,
  (dealType) => {
    if (dealType !== 'rent') {
      draft.value.rentTerm = null
    } else if (!draft.value.rentTerm) {
      draft.value.rentTerm = 'long'
    }
  },
)

function fieldErrorKey(code: string): string {
  return `account.wizard.errors.${code}`
}

function setFieldError(field: string) {
  const code = validateWizardField(field, draft.value)
  if (code) {
    errors.value = { ...errors.value, [field]: fieldErrorKey(code) }
  } else {
    const next = { ...errors.value }
    delete next[field]
    errors.value = next
  }
}

function revalidateTouched() {
  for (const field of Object.keys(touched.value)) {
    if (touched.value[field]) {
      setFieldError(field)
    }
  }
}

function touch(field: string) {
  touched.value = { ...touched.value, [field]: true }
  setFieldError(field)
}

function parsePriceInput(event: Event): number | null {
  const raw = (event.target as HTMLInputElement).value
  if (raw.trim() === '') {
    return null
  }
  const value = Number(raw)
  return Number.isFinite(value) ? value : null
}

function onPriceUsdInput(event: Event) {
  syncWizardPriceFromUsd(draft.value, parsePriceInput(event))
  touch('price')
  touch('priceByn')
}

function onPriceBynInput(event: Event) {
  syncWizardPriceFromByn(draft.value, parsePriceInput(event))
  touch('price')
  touch('priceByn')
}

async function scrollAfterStepChange() {
  await nextTick()
  if (!rootRef.value) {
    return
  }
  scrollElementBelowStickyHeader(rootRef.value)
}

function goNext() {
  const stepErrors = validateWizardStep(step.value, draft.value)
  if (stepErrors.length) {
    const nextErrors: Record<string, string> = { ...errors.value }
    for (const field of stepErrors) {
      touched.value[field] = true
      nextErrors[field] = fieldErrorKey(field)
    }
    errors.value = nextErrors
    return
  }

  if (stepIndex.value < WIZARD_STEPS.length - 1) {
    stepDirection.value = 1
    stepIndex.value += 1
    void scrollAfterStepChange()
  }
}

function goBack() {
  submitError.value = ''
  if (stepIndex.value > 0) {
    stepDirection.value = -1
    stepIndex.value -= 1
    void scrollAfterStepChange()
  }
}

function goToStep(index: number) {
  if (index > stepIndex.value) {
    for (let i = stepIndex.value; i < index; i += 1) {
      const id = WIZARD_STEPS[i]
      if (!id) continue
      const stepErrors = validateWizardStep(id, draft.value)
      if (stepErrors.length) {
        stepIndex.value = i
        const nextErrors: Record<string, string> = { ...errors.value }
        for (const field of stepErrors) {
          touched.value[field] = true
          nextErrors[field] = fieldErrorKey(field)
        }
        errors.value = nextErrors
        void scrollAfterStepChange()
        return
      }
    }
  }
  if (index === stepIndex.value) {
    return
  }
  stepDirection.value = index >= stepIndex.value ? 1 : -1
  stepIndex.value = index
  void scrollAfterStepChange()
}

async function submit(status: ListingStatus) {
  for (const stepId of WIZARD_STEPS) {
    if (stepId === 'review' || stepId === 'photos') {
      continue
    }
    const stepErrors = validateWizardStep(stepId, draft.value)
    if (stepErrors.length) {
      stepIndex.value = WIZARD_STEPS.indexOf(stepId)
      const nextErrors: Record<string, string> = {}
      for (const field of stepErrors) {
        touched.value[field] = true
        nextErrors[field] = fieldErrorKey(field)
      }
      errors.value = nextErrors
      return
    }
  }

  submitting.value = true
  submitError.value = ''
  try {
    await createMyListing(toCreatePayload(draft.value, status))
    clearWizardDraftLocal()
    draftSavedAt.value = null
    await router.push('/account/seller/listings')
  } catch (err) {
    submitError.value = resolveApiError(err, t, 'account.wizard.submitError').message
  } finally {
    submitting.value = false
  }
}

async function saveDraftNow() {
  await submit('draft')
}

async function saveAndExit() {
  if (isWizardDraftEmpty(draft.value) && stepIndex.value === 0) {
    clearWizardDraftLocal()
    draftSavedAt.value = null
    await router.push('/account/seller/listings')
    return
  }

  saveWizardDraftLocal(draft.value, stepIndex.value)
  draftSavedAt.value = Date.now()
  await router.push('/account/seller/listings')
}

function clearDraft() {
  if (!window.confirm(t('account.wizard.clearDraftConfirm'))) {
    return
  }
  clearWizardDraftLocal()
  draft.value = createEmptyWizardDraft()
  stepIndex.value = 0
  draftSavedAt.value = null
  errors.value = {}
  touched.value = {}
  submitError.value = ''
}

function hasError(field: string) {
  return Boolean(errors.value[field])
}

function errorText(field: string) {
  const key = errors.value[field]
  return key ? t(key) : ''
}

function stepLabel(id: WizardStepId) {
  return t(`account.wizard.steps.${id}`)
}
</script>

<template>
  <div ref="rootRef" class="listing-wizard">
    <header class="listing-wizard__header">
      <div>
        <h1 class="listing-wizard__title">{{ t('account.wizard.title') }}</h1>
        <p class="listing-wizard__subtitle">{{ t('account.wizard.subtitle') }}</p>
      </div>
      <div class="listing-wizard__header-actions">
        <p v-if="draftSavedAt" class="listing-wizard__autosave">{{ t('account.wizard.draftSaved') }}</p>
        <button
          type="button"
          class="listing-wizard__save-exit"
          :disabled="submitting"
          @click="saveAndExit"
        >
          <img
            src="/figma/save-exit.svg"
            alt=""
            width="18"
            height="18"
            class="listing-wizard__save-exit-icon"
            aria-hidden="true"
          />
          <span>{{ t('account.wizard.saveAndExit') }}</span>
        </button>
        <button
          v-if="draftSavedAt"
          type="button"
          class="listing-wizard__clear"
          @click="clearDraft"
        >
          {{ t('account.wizard.clearDraft') }}
        </button>
      </div>
    </header>

    <div class="listing-wizard__layout">
      <aside class="listing-wizard__rail" aria-label="steps">
        <ol class="listing-wizard__chain">
          <li
            v-for="(id, index) in WIZARD_STEPS"
            :key="id"
            class="listing-wizard__chain-item"
            :class="{
              'listing-wizard__chain-item--active': index === stepIndex,
              'listing-wizard__chain-item--done': index < stepIndex,
            }"
          >
            <button
              type="button"
              class="listing-wizard__chain-btn"
              :aria-current="index === stepIndex ? 'step' : undefined"
              @click="goToStep(index)"
            >
              <span class="listing-wizard__chain-num">{{ index + 1 }}</span>
              <span class="listing-wizard__chain-label">{{ stepLabel(id) }}</span>
            </button>
          </li>
        </ol>
      </aside>

      <div class="listing-wizard__main">
        <div
          class="listing-wizard__stage"
          :class="stepDirection >= 0 ? 'listing-wizard__stage--next' : 'listing-wizard__stage--prev'"
        >
          <Transition name="wizard-slide" mode="out-in">
            <section :key="step" class="listing-wizard__panel">
            <template v-if="step === 'deal'">
              <div class="listing-wizard__deal-step">
                <h2 class="listing-wizard__panel-title listing-wizard__panel-title--deal">
                  {{ t('account.wizard.dealType') }}
                </h2>
                <div
                  class="listing-wizard__deal-tiles listing-wizard__deal-tiles--deal"
                  role="group"
                  :aria-label="t('account.wizard.dealType')"
                >
                  <button
                    v-for="option in (['sale', 'rent', 'commercial'] as const)"
                    :key="option"
                    type="button"
                    class="listing-wizard__deal-tile"
                    :class="{ 'listing-wizard__deal-tile--active': selectedDealTypeOption === option }"
                    @click="selectDealTypeOption(option)"
                  >
                    <span class="listing-wizard__deal-icon" aria-hidden="true">
                      <span
                        class="listing-wizard__deal-glyph"
                        :style="{ maskImage: `url(/figma/nav-${option}.svg)`, WebkitMaskImage: `url(/figma/nav-${option}.svg)` }"
                      />
                    </span>
                    <span class="listing-wizard__deal-label">{{ t(`nav.${option}`) }}</span>
                  </button>
                </div>
              </div>
            </template>

            <template v-else-if="step === 'object'">
              <h2 class="listing-wizard__panel-title">{{ t('account.wizard.listingType') }}</h2>
              <div class="listing-wizard__deal-tiles" role="group" :aria-label="t('account.wizard.listingType')">
                <button
                  v-for="option in (['apartment', 'house', 'room'] as const)"
                  :key="option"
                  type="button"
                  class="listing-wizard__deal-tile"
                  :class="{ 'listing-wizard__deal-tile--active': draft.listingType === option }"
                  @click="draft.listingType = option"
                >
                  <span class="listing-wizard__deal-icon" aria-hidden="true">
                    <span
                      class="listing-wizard__deal-glyph"
                      :style="{ maskImage: `url(/figma/listing-${option}.svg)`, WebkitMaskImage: `url(/figma/listing-${option}.svg)` }"
                    />
                  </span>
                  <span class="listing-wizard__deal-label">{{ t(`account.wizard.listingTypes.${option}`) }}</span>
                </button>
              </div>
            </template>

            <template v-else-if="step === 'location'">
              <h2 class="listing-wizard__panel-title">{{ t('account.wizard.steps.location') }}</h2>
              <p class="listing-wizard__hint">{{ t('account.wizard.locationHint') }}</p>

              <WizardLocationMap
                :latitude="draft.latitude"
                :longitude="draft.longitude"
                @update:coords="onMapCoords"
              />

              <p v-if="geocoding" class="listing-wizard__hint">{{ t('account.wizard.geocoding') }}</p>
              <p v-if="geocodeError" class="listing-wizard__field-error" role="alert">{{ geocodeError }}</p>

              <div class="listing-wizard__grid">
                <WizardLocationField
                  :model-value="draft.region"
                  :allow-absent="false"
                  :label="t('account.wizard.region')"
                  :placeholder="t('account.wizard.regionPlaceholder')"
                  :maxlength="120"
                  :invalid="hasError('region')"
                  :error-text="errorText('region')"
                  @update:model-value="draft.region = $event; touch('region')"
                  @blur="touch('region')"
                />
                <WizardLocationField
                  :model-value="draft.city"
                  :allow-absent="false"
                  :label="t('account.wizard.city')"
                  :placeholder="t('account.wizard.cityPlaceholder')"
                  :maxlength="120"
                  :invalid="hasError('city')"
                  :error-text="errorText('city')"
                  @update:model-value="draft.city = $event; touch('city')"
                  @blur="touch('city')"
                />
                <WizardLocationField
                  :model-value="draft.district"
                  :absent="draft.absent.district"
                  :label="t('account.wizard.district')"
                  :placeholder="t('account.wizard.districtPlaceholder')"
                  :maxlength="120"
                  @update:model-value="draft.district = $event"
                  @update:absent="setFieldAbsent('district', $event)"
                />
                <WizardLocationField
                  :model-value="draft.metro"
                  :absent="draft.absent.metro"
                  :label="t('account.wizard.metro')"
                  :placeholder="t('account.wizard.metroPlaceholder')"
                  :maxlength="120"
                  @update:model-value="draft.metro = $event"
                  @update:absent="setFieldAbsent('metro', $event)"
                />
              </div>

              <div v-if="draft.metro.trim() && !draft.absent.metro" class="listing-wizard__metro-color">
                <span class="listing-wizard__field-label">{{ t('account.wizard.metroLineColor') }}</span>
                <div
                  class="listing-wizard__metro-swatches"
                  role="group"
                  :aria-label="t('account.wizard.metroLineColor')"
                >
                  <button
                    v-for="color in metroColorOptions"
                    :key="color"
                    type="button"
                    class="listing-wizard__metro-swatch"
                    :class="{ 'listing-wizard__metro-swatch--active': normalizeMetroLineColor(draft.metroLineColor) === color }"
                    :style="{ '--swatch-color': color }"
                    :aria-pressed="normalizeMetroLineColor(draft.metroLineColor) === color"
                    :title="color"
                    @click="selectMetroLineColor(color)"
                  >
                    <MetroIcon :color="color" :size="14" />
                  </button>
                </div>
                <p class="listing-wizard__hint">{{ t('account.wizard.metroLineColorHint') }}</p>
              </div>

              <div class="listing-wizard__grid">
                <WizardLocationField
                  :model-value="draft.street"
                  :absent="draft.absent.street"
                  :label="t('account.wizard.street')"
                  :placeholder="t('account.wizard.streetPlaceholder')"
                  :maxlength="200"
                  :invalid="hasError('street')"
                  :error-text="errorText('street')"
                  @update:model-value="draft.street = $event; touch('street')"
                  @update:absent="setFieldAbsent('street', $event)"
                  @blur="touch('street')"
                />
                <WizardLocationField
                  :model-value="draft.house"
                  :absent="draft.absent.house"
                  :label="t('account.wizard.house')"
                  :placeholder="t('account.wizard.housePlaceholder')"
                  :maxlength="32"
                  :invalid="hasError('house')"
                  :error-text="errorText('house')"
                  @update:model-value="draft.house = $event; touch('house')"
                  @update:absent="setFieldAbsent('house', $event)"
                  @blur="touch('house')"
                />
              </div>

              <div class="listing-wizard__grid">
                <WizardLocationField
                  :model-value="draft.entrance"
                  :absent="draft.absent.entrance"
                  :label="t('account.wizard.entrance')"
                  :placeholder="t('account.wizard.entrancePlaceholder')"
                  :maxlength="32"
                  @update:model-value="draft.entrance = $event"
                  @update:absent="setFieldAbsent('entrance', $event)"
                />
                <WizardLocationField
                  :model-value="draft.apartmentNumber"
                  :absent="draft.absent.apartmentNumber"
                  :label="t('account.wizard.apartmentNumber')"
                  :placeholder="t('account.wizard.apartmentNumberPlaceholder')"
                  :maxlength="32"
                  @update:model-value="draft.apartmentNumber = $event"
                  @update:absent="setFieldAbsent('apartmentNumber', $event)"
                />
              </div>
            </template>

            <template v-else-if="step === 'details'">
              <h2 class="listing-wizard__panel-title">{{ t('account.wizard.steps.details') }}</h2>
              <div class="listing-wizard__grid">
                <label class="listing-wizard__field">
                  <span class="listing-wizard__field-label">{{ t('account.wizard.rooms') }}</span>
                  <input
                    v-model.number="draft.rooms"
                    type="number"
                    min="1"
                    max="20"
                    class="listing-wizard__control"
                    :class="{ 'is-invalid': hasError('rooms') }"
                    @blur="touch('rooms')"
                    @input="touch('rooms')"
                  />
                  <span v-if="hasError('rooms')" class="listing-wizard__field-error">{{ errorText('rooms') }}</span>
                </label>
                <label class="listing-wizard__field">
                  <span class="listing-wizard__field-label">{{ t('account.wizard.area') }}</span>
                  <input
                    v-model.number="draft.area"
                    type="number"
                    min="1"
                    step="0.1"
                    class="listing-wizard__control"
                    :class="{ 'is-invalid': hasError('area') }"
                    @blur="touch('area')"
                    @input="touch('area')"
                  />
                  <span v-if="hasError('area')" class="listing-wizard__field-error">{{ errorText('area') }}</span>
                </label>
                <WizardLocationField
                  :model-value="draft.floor === null ? '' : String(draft.floor)"
                  :absent="draft.absent.floor"
                  :label="t('account.wizard.floor')"
                  input-type="number"
                  :invalid="hasError('floor')"
                  :error-text="errorText('floor')"
                  @update:model-value="draft.floor = parseOptionalNumber($event); touch('floor')"
                  @update:absent="setFieldAbsent('floor', $event)"
                  @blur="touch('floor')"
                />
                <WizardLocationField
                  :model-value="draft.totalFloors === null ? '' : String(draft.totalFloors)"
                  :absent="draft.absent.totalFloors"
                  :label="t('account.wizard.totalFloors')"
                  input-type="number"
                  :invalid="hasError('totalFloors')"
                  :error-text="errorText('totalFloors')"
                  @update:model-value="draft.totalFloors = parseOptionalNumber($event); touch('totalFloors')"
                  @update:absent="setFieldAbsent('totalFloors', $event)"
                  @blur="touch('totalFloors')"
                />
              </div>

              <template v-if="draft.dealType === 'rent'">
                <p class="listing-wizard__label">{{ t('account.wizard.rentTerm') }}</p>
                <div
                  class="listing-wizard__deal-tiles listing-wizard__deal-tiles--two"
                  role="group"
                  :aria-label="t('account.wizard.rentTerm')"
                >
                  <button
                    v-for="option in (['long', 'daily'] as const)"
                    :key="option"
                    type="button"
                    class="listing-wizard__deal-tile"
                    :class="{
                      'listing-wizard__deal-tile--active': draft.rentTerm === option,
                      'is-invalid': hasError('rentTerm'),
                    }"
                    @click="draft.rentTerm = option; touch('rentTerm')"
                  >
                    <span class="listing-wizard__deal-icon" aria-hidden="true">
                      <span
                        class="listing-wizard__deal-glyph"
                        :style="{ maskImage: `url(/figma/rent-${option}.svg)`, WebkitMaskImage: `url(/figma/rent-${option}.svg)` }"
                      />
                    </span>
                    <span class="listing-wizard__deal-label">{{ t(`account.wizard.rentTerms.${option}`) }}</span>
                  </button>
                </div>
                <span v-if="hasError('rentTerm')" class="listing-wizard__field-error">{{ errorText('rentTerm') }}</span>
              </template>

              <p class="listing-wizard__label">{{ t('account.wizard.sellerRole') }}</p>
              <div
                class="listing-wizard__deal-tiles listing-wizard__deal-tiles--two"
                role="group"
                :aria-label="t('account.wizard.sellerRole')"
                data-testid="seller-role-tiles"
              >
                <button
                  type="button"
                  class="listing-wizard__deal-tile"
                  :class="{
                    'listing-wizard__deal-tile--active': draft.fromOwner === true,
                    'is-invalid': hasError('fromOwner'),
                  }"
                  @click="draft.fromOwner = true; touch('fromOwner')"
                >
                  <span class="listing-wizard__deal-icon" aria-hidden="true">
                    <span
                      class="listing-wizard__deal-glyph"
                      :style="{ maskImage: 'url(/figma/seller-owner.svg)', WebkitMaskImage: 'url(/figma/seller-owner.svg)' }"
                    />
                  </span>
                  <span class="listing-wizard__deal-label">{{ t('account.wizard.sellerRoles.owner') }}</span>
                </button>
                <button
                  type="button"
                  class="listing-wizard__deal-tile"
                  :class="{
                    'listing-wizard__deal-tile--active': draft.fromOwner === false,
                    'is-invalid': hasError('fromOwner'),
                  }"
                  @click="draft.fromOwner = false; touch('fromOwner')"
                >
                  <span class="listing-wizard__deal-icon" aria-hidden="true">
                    <span
                      class="listing-wizard__deal-glyph"
                      :style="{ maskImage: 'url(/figma/seller-agent.svg)', WebkitMaskImage: 'url(/figma/seller-agent.svg)' }"
                    />
                  </span>
                  <span class="listing-wizard__deal-label">{{ t('account.wizard.sellerRoles.agent') }}</span>
                </button>
              </div>
              <span v-if="hasError('fromOwner')" class="listing-wizard__field-error">{{ errorText('fromOwner') }}</span>

              <div class="listing-wizard__checks">
                <label class="listing-wizard__check">
                  <input v-model="draft.noCommission" type="checkbox" class="listing-wizard__checkbox" />
                  <span>{{ t('account.wizard.flags.noCommission') }}</span>
                </label>
                <label class="listing-wizard__check">
                  <input v-model="draft.hasRenovation" type="checkbox" class="listing-wizard__checkbox" />
                  <span>{{ t('account.wizard.flags.hasRenovation') }}</span>
                </label>
                <label v-if="draft.dealType === 'rent'" class="listing-wizard__check">
                  <input v-model="draft.hasDeposit" type="checkbox" class="listing-wizard__checkbox" />
                  <span>{{ t('account.wizard.flags.hasDeposit') }}</span>
                </label>
                <label v-if="draft.dealType === 'rent'" class="listing-wizard__check">
                  <input v-model="draft.utilitiesIncluded" type="checkbox" class="listing-wizard__checkbox" />
                  <span>{{ t('account.wizard.flags.utilitiesIncluded') }}</span>
                </label>
              </div>
            </template>

            <template v-else-if="step === 'price'">
              <h2 class="listing-wizard__panel-title">{{ t('account.wizard.price') }}</h2>
              <div class="listing-wizard__grid">
                <label class="listing-wizard__field">
                  <span class="listing-wizard__field-label">{{ t('account.wizard.priceUsd') }}</span>
                  <input
                    :value="draft.price ?? ''"
                    type="number"
                    min="1"
                    class="listing-wizard__control listing-wizard__control--price"
                    :class="{ 'is-invalid': hasError('price') }"
                    :placeholder="t('account.wizard.priceUsdPlaceholder')"
                    @blur="touch('price'); touch('priceByn')"
                    @input="onPriceUsdInput"
                  />
                  <span v-if="hasError('price')" class="listing-wizard__field-error">{{ errorText('price') }}</span>
                </label>
                <label class="listing-wizard__field">
                  <span class="listing-wizard__field-label">{{ t('account.wizard.priceByn') }}</span>
                  <input
                    :value="draft.priceByn ?? ''"
                    type="number"
                    min="1"
                    class="listing-wizard__control listing-wizard__control--price"
                    :class="{ 'is-invalid': hasError('priceByn') }"
                    :placeholder="t('account.wizard.priceBynPlaceholder')"
                    @blur="touch('price'); touch('priceByn')"
                    @input="onPriceBynInput"
                  />
                  <span v-if="hasError('priceByn')" class="listing-wizard__field-error">{{ errorText('priceByn') }}</span>
                </label>
              </div>
              <p class="listing-wizard__hint">
                {{ t('account.wizard.priceCurrenciesHint', { rate: exchangeRate.rateLabel }) }}
              </p>
              <div class="listing-wizard__checks">
                <label class="listing-wizard__check">
                  <input v-model="draft.priceNegotiable" type="checkbox" class="listing-wizard__checkbox" />
                  <span>{{ t('account.wizard.flags.priceNegotiable') }}</span>
                </label>
              </div>
            </template>

            <template v-else-if="step === 'photos'">
              <h2 class="listing-wizard__panel-title">{{ t('account.wizard.photos') }}</h2>
              <p class="listing-wizard__hint">{{ t('account.wizard.photosHint') }}</p>
              <WizardMediaDropzone v-model:images="draft.images" />
            </template>

            <template v-else>
              <h2 class="listing-wizard__panel-title">{{ t('account.wizard.steps.review') }}</h2>
              <p class="listing-wizard__hint">{{ t('account.wizard.reviewHint') }}</p>
              <WizardReviewCard :draft="draft" />
              <p v-if="submitError" class="listing-wizard__error" role="alert">{{ submitError }}</p>
            </template>
            </section>
          </Transition>
        </div>

        <div class="listing-wizard__actions">
          <p
            v-if="step === 'review' && submitError"
            class="listing-wizard__error listing-wizard__error--actions"
            role="alert"
          >
            {{ submitError }}
          </p>
          <template v-if="step !== 'review'">
            <button
              type="button"
              class="listing-wizard__btn listing-wizard__btn--next"
              @click="goNext"
            >
              {{ t('account.wizard.next') }}
            </button>
            <button
              v-if="stepIndex > 0"
              type="button"
              class="listing-wizard__btn listing-wizard__btn--back"
              @click="goBack"
            >
              {{ t('account.wizard.back') }}
            </button>
          </template>
          <template v-else>
            <button
              type="button"
              class="listing-wizard__btn listing-wizard__btn--next"
              :disabled="submitting"
              @click="submit('published')"
            >
              {{ submitting ? t('listing.loading') : t('account.wizard.publish') }}
            </button>
            <button
              type="button"
              class="listing-wizard__btn listing-wizard__btn--back listing-wizard__btn--icon"
              :disabled="submitting"
              @click="saveDraftNow"
            >
              <img
                src="/figma/save-exit.svg"
                alt=""
                width="18"
                height="18"
                aria-hidden="true"
              />
              <span>{{ t('account.wizard.saveAndExit') }}</span>
            </button>
            <button
              type="button"
              class="listing-wizard__btn listing-wizard__btn--back"
              :disabled="submitting"
              @click="goBack"
            >
              {{ t('account.wizard.back') }}
            </button>
          </template>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.listing-wizard {
  display: flex;
  flex-direction: column;
  gap: 14px;
  width: 100%;
  min-width: 0;
  min-height: 100%;
  height: 100%;
  box-sizing: border-box;
}

.listing-wizard__header {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-end;
  justify-content: space-between;
  gap: 6px 12px;
  flex: 0 0 auto;
}

.listing-wizard__title {
  margin: 0 0 4px;
  font-size: 24px;
  font-weight: 700;
  color: var(--color-text, #000);
}

.listing-wizard__subtitle,
.listing-wizard__hint,
.listing-wizard__empty,
.listing-wizard__autosave {
  margin: 0;
  font-size: 13px;
  color: var(--color-text-muted, rgba(0, 0, 0, 0.72));
}

.listing-wizard__header-actions {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 10px;
}

.listing-wizard__save-exit {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  min-height: 40px;
  padding: 0 14px;
  border: 1px solid var(--figma-border, #e5e7eb);
  border-radius: 10px;
  background: #fff;
  color: var(--color-text, #000);
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  transition:
    border-color 0.2s ease,
    background-color 0.2s ease,
    transform 0.2s ease;
}

.listing-wizard__save-exit:hover {
  border-color: color-mix(in srgb, var(--figma-accent) 45%, var(--figma-border));
  background: color-mix(in srgb, var(--figma-accent) 6%, #fff);
}

.listing-wizard__save-exit:active {
  transform: scale(0.98);
}

.listing-wizard__save-exit:disabled {
  opacity: 0.65;
  cursor: default;
}

.listing-wizard__save-exit-icon {
  display: block;
  flex-shrink: 0;
}

.listing-wizard__btn--icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.listing-wizard__clear {
  min-height: 36px;
  padding: 0 12px;
  border: 1px solid var(--figma-border, #e5e7eb);
  border-radius: 8px;
  background: #fff;
  color: #b91c1c;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
}

.listing-wizard__clear:hover {
  background: #fef2f2;
}

.listing-wizard__layout {
  display: grid;
  grid-template-columns: 168px minmax(0, 1fr);
  gap: 20px;
  align-items: stretch;
  justify-content: stretch;
  flex: 1 1 auto;
  width: 100%;
  min-width: 0;
  min-height: 0;
}

.listing-wizard__rail {
  position: sticky;
  top: 16px;
  align-self: start;
}

.listing-wizard__chain {
  margin: 0;
  padding: 0;
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 0;
}

.listing-wizard__chain-item {
  position: relative;
  padding-bottom: 10px;
}

.listing-wizard__chain-item:not(:last-child)::before {
  content: '';
  position: absolute;
  left: 15px;
  top: 32px;
  bottom: 0;
  width: 2px;
  background: var(--figma-border);
}

.listing-wizard__chain-item--done:not(:last-child)::before,
.listing-wizard__chain-item--active:not(:last-child)::before {
  background: var(--figma-accent);
}

.listing-wizard__chain-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  width: 100%;
  padding: 0;
  border: none;
  background: transparent;
  text-align: left;
  cursor: pointer;
}

.listing-wizard__chain-num {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  border: 2px solid var(--figma-border);
  background: var(--color-bg-elevated, #fff);
  color: var(--color-text-muted, rgba(0, 0, 0, 0.55));
  font-size: 13px;
  font-weight: 700;
  transition:
    background-color 0.25s ease,
    border-color 0.25s ease,
    color 0.25s ease,
    transform 0.25s ease;
}

.listing-wizard__chain-item--done .listing-wizard__chain-num {
  border-color: var(--figma-accent);
  color: var(--figma-accent);
  background: color-mix(in srgb, var(--figma-accent) 10%, #fff);
}

.listing-wizard__chain-item--active .listing-wizard__chain-num {
  border-color: var(--figma-accent);
  background: var(--figma-accent);
  color: #fff;
  transform: scale(1.05);
}

.listing-wizard__chain-label {
  font-size: 12px;
  font-weight: 600;
  color: var(--color-text-muted, rgba(0, 0, 0, 0.65));
}

.listing-wizard__chain-item--active .listing-wizard__chain-label,
.listing-wizard__chain-item--done .listing-wizard__chain-label {
  color: var(--color-text, #000);
}

.listing-wizard__main {
  display: flex;
  flex-direction: column;
  gap: 12px;
  min-width: 0;
  width: 100%;
  max-width: none;
  min-height: 0;
  height: 100%;
}

.listing-wizard__stage {
  position: relative;
  flex: 1 1 auto;
  width: 100%;
  min-width: 0;
  min-height: 0;
  overflow: hidden;
}

.listing-wizard__main > :deep(.listing-wizard__panel),
.listing-wizard__panel {
  display: flex;
  flex-direction: column;
  gap: 14px;
  flex: 1 1 auto;
  width: 100%;
  min-width: 0;
  min-height: 100%;
  height: 100%;
  padding: 18px 20px;
  border: 1px solid var(--figma-border);
  border-radius: 12px;
  background: var(--color-bg-elevated, #fff);
  box-sizing: border-box;
  overflow: auto;
}

.listing-wizard__panel-title,
.listing-wizard__label {
  margin: 0;
  font-size: 15px;
  font-weight: 700;
  color: var(--color-text, #000);
}

.listing-wizard__label {
  font-size: 13px;
  font-weight: 600;
}

.listing-wizard__chips {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.listing-wizard__deal-tiles {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 12px;
  width: 100%;
}

.listing-wizard__deal-step {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  flex: 1 1 auto;
  gap: 28px;
  width: 100%;
  min-height: 100%;
  padding: 28px 16px 36px;
  box-sizing: border-box;
  text-align: center;
}

.listing-wizard__panel-title--deal {
  font-size: 28px;
  font-weight: 700;
  line-height: 1.2;
  letter-spacing: -0.02em;
}

.listing-wizard__deal-tiles--deal {
  max-width: 760px;
  gap: 20px;
  margin: 0 auto;
}

.listing-wizard__deal-tiles--deal .listing-wizard__deal-tile {
  min-height: 168px;
  gap: 14px;
  padding: 28px 16px;
  border-radius: 18px;
}

.listing-wizard__deal-tiles--deal .listing-wizard__deal-icon {
  width: 64px;
  height: 64px;
  border-radius: 16px;
}

.listing-wizard__deal-tiles--deal .listing-wizard__deal-glyph {
  width: 36px;
  height: 36px;
}

.listing-wizard__deal-tiles--deal .listing-wizard__deal-label {
  font-size: 18px;
}

.listing-wizard__deal-tiles--two {
  grid-template-columns: repeat(2, minmax(0, 1fr));
}

.listing-wizard__deal-tile {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 10px;
  min-height: 112px;
  width: 100%;
  padding: 16px 12px;
  border: 1px solid var(--figma-border);
  border-radius: 14px;
  background: #fff;
  color: var(--color-text, #000);
  cursor: pointer;
  transition:
    background-color 0.2s ease,
    color 0.2s ease,
    border-color 0.2s ease,
    box-shadow 0.2s ease,
    transform 0.2s ease;
}

.listing-wizard__deal-tile:hover {
  border-color: color-mix(in srgb, var(--figma-accent) 45%, var(--figma-border));
}

.listing-wizard__deal-tile:active {
  transform: scale(0.98);
}

.listing-wizard__deal-tile--active {
  border-color: var(--figma-accent);
  background: color-mix(in srgb, var(--figma-accent) 8%, #fff);
  box-shadow: 0 0 0 1px var(--figma-accent);
  color: var(--figma-accent);
}

.listing-wizard__deal-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: color-mix(in srgb, var(--figma-accent) 10%, #fff);
  color: var(--figma-accent);
}

.listing-wizard__deal-tile--active .listing-wizard__deal-icon {
  background: var(--figma-accent);
  color: #fff;
}

.listing-wizard__deal-glyph {
  display: block;
  width: 28px;
  height: 28px;
  background: currentColor;
  mask-repeat: no-repeat;
  mask-position: center;
  mask-size: contain;
  -webkit-mask-repeat: no-repeat;
  -webkit-mask-position: center;
  -webkit-mask-size: contain;
}

.listing-wizard__deal-label {
  font-size: 15px;
  font-weight: 600;
  text-align: center;
  line-height: 1.25;
}

.listing-wizard__chip {
  min-height: 40px;
  padding: 0 14px;
  border: 1px solid var(--figma-border);
  border-radius: 10px;
  background: #fff;
  color: var(--color-text, #000);
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition:
    background-color 0.2s ease,
    color 0.2s ease,
    border-color 0.2s ease,
    transform 0.2s ease;
}

.listing-wizard__chip:hover {
  border-color: color-mix(in srgb, var(--figma-accent) 45%, var(--figma-border));
}

.listing-wizard__chip--active {
  border-color: transparent;
  background: var(--figma-accent);
  color: #fff;
}

.listing-wizard__field {
  display: flex;
  flex-direction: column;
  gap: 6px;
  min-width: 0;
}

.listing-wizard__field-label {
  font-size: 13px;
  font-weight: 600;
  color: var(--color-text, #000);
}

.listing-wizard__control {
  box-sizing: border-box;
  width: 100%;
  min-width: 0;
  height: 44px;
  padding: 0 12px;
  border: 1px solid var(--figma-border);
  border-radius: 10px;
  background: var(--color-bg-elevated, #fff);
  color: var(--color-text, #000);
  font-family: inherit;
  font-size: 15px;
  transition:
    border-color 0.2s ease,
    box-shadow 0.2s ease;
}

.listing-wizard__control--price {
  max-width: none;
  font-size: 18px;
  font-weight: 600;
}

.listing-wizard__control:focus {
  outline: none;
  border-color: var(--figma-accent);
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--figma-accent) 14%, transparent);
}

.listing-wizard__control.is-invalid,
.listing-wizard__chip.is-invalid,
.listing-wizard__deal-tile.is-invalid {
  border-color: var(--figma-accent);
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--figma-accent) 12%, transparent);
}

.listing-wizard__field-error,
.listing-wizard__error {
  margin: 0;
  font-size: 12px;
  font-weight: 500;
  color: var(--figma-accent);
  line-height: 1.4;
}

.listing-wizard__error--actions {
  font-size: 13px;
  order: -1;
}

.listing-wizard__grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 12px;
  width: 100%;
}

.listing-wizard__metro-color {
  display: flex;
  flex-direction: column;
  gap: 8px;
  width: 100%;
}

.listing-wizard__metro-swatches {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.listing-wizard__metro-swatch {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 44px;
  height: 44px;
  border: 1px solid var(--figma-border);
  border-radius: 12px;
  background: #fff;
  cursor: pointer;
  transition:
    border-color 0.2s ease,
    box-shadow 0.2s ease,
    transform 0.2s ease;
}

.listing-wizard__metro-swatch:hover {
  border-color: color-mix(in srgb, var(--swatch-color, var(--figma-accent)) 55%, var(--figma-border));
}

.listing-wizard__metro-swatch:active {
  transform: scale(0.98);
}

.listing-wizard__metro-swatch--active {
  border-color: var(--swatch-color, var(--figma-accent));
  box-shadow: 0 0 0 2px color-mix(in srgb, var(--swatch-color, var(--figma-accent)) 35%, transparent);
}

.listing-wizard__checks {
  display: flex;
  flex-direction: column;
  gap: 8px;
  width: 100%;
  max-width: 480px;
}

.listing-wizard__check {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  min-height: 40px;
  padding: 0 12px;
  border: 1px solid var(--figma-border);
  border-radius: 10px;
  background: var(--color-bg-elevated, #fff);
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
}

.listing-wizard__checkbox {
  width: 18px;
  height: 18px;
  margin: 0;
  accent-color: var(--figma-accent);
}

.listing-wizard__photo-add {
  display: flex;
  gap: 8px;
  align-items: stretch;
  width: 100%;
}

.listing-wizard__photo-add .listing-wizard__control {
  flex: 1;
}

.listing-wizard__photos {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
  gap: 10px;
  width: 100%;
}

.listing-wizard__photo {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.listing-wizard__photo img {
  width: 100%;
  height: 96px;
  object-fit: cover;
  border-radius: 10px;
  background: var(--color-bg-muted, #eee);
}

.listing-wizard__photo button {
  border: none;
  background: transparent;
  color: var(--figma-accent);
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
}

.listing-wizard__actions {
  display: flex;
  flex-direction: column;
  gap: 8px;
  align-items: stretch;
  margin-top: auto;
  flex: 0 0 auto;
  width: 100%;
}

.listing-wizard__btn {
  width: 100%;
  min-height: 44px;
  padding: 0 18px;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  transition:
    transform 0.2s ease,
    opacity 0.2s ease,
    background-color 0.2s ease,
    border-color 0.2s ease;
}

.listing-wizard__btn:active {
  transform: scale(0.98);
}

.listing-wizard__btn:disabled {
  opacity: 0.65;
  cursor: default;
}

.listing-wizard__btn--next {
  border: none;
  background: var(--figma-accent);
  color: #fff;
}

.listing-wizard__btn--back,
.listing-wizard__btn--ghost {
  border: 1px solid var(--figma-border);
  background: #fff;
  color: var(--color-text, #000);
}

.wizard-slide-enter-active {
  animation: wizard-slide-enter-next 0.32s cubic-bezier(0.22, 1, 0.36, 1);
}

.wizard-slide-leave-active {
  animation: wizard-slide-leave-next 0.24s ease forwards;
}

.listing-wizard__stage--prev .wizard-slide-enter-active {
  animation-name: wizard-slide-enter-prev;
}

.listing-wizard__stage--prev .wizard-slide-leave-active {
  animation-name: wizard-slide-leave-prev;
}

@keyframes wizard-slide-enter-next {
  from {
    opacity: 0;
    transform: translate3d(36px, 0, 0);
  }

  to {
    opacity: 1;
    transform: translate3d(0, 0, 0);
  }
}

@keyframes wizard-slide-leave-next {
  from {
    opacity: 1;
    transform: translate3d(0, 0, 0);
  }

  to {
    opacity: 0;
    transform: translate3d(-28px, 0, 0);
  }
}

@keyframes wizard-slide-enter-prev {
  from {
    opacity: 0;
    transform: translate3d(-36px, 0, 0);
  }

  to {
    opacity: 1;
    transform: translate3d(0, 0, 0);
  }
}

@keyframes wizard-slide-leave-prev {
  from {
    opacity: 1;
    transform: translate3d(0, 0, 0);
  }

  to {
    opacity: 0;
    transform: translate3d(28px, 0, 0);
  }
}

@media (prefers-reduced-motion: reduce) {
  .wizard-slide-enter-active,
  .wizard-slide-leave-active {
    animation: none;
  }

  .listing-wizard__chain-num,
  .listing-wizard__chip,
  .listing-wizard__deal-tile,
  .listing-wizard__btn {
    transition: none;
  }
}

@media (max-width: 1279px) {
  .listing-wizard__layout {
    grid-template-columns: 1fr;
  }

  .listing-wizard__rail {
    position: static;
  }

  .listing-wizard__chain {
    flex-direction: row;
    flex-wrap: wrap;
    gap: 6px;
  }

  .listing-wizard__chain-item {
    padding-bottom: 0;
  }

  .listing-wizard__chain-item:not(:last-child)::before {
    display: none;
  }

  .listing-wizard__chain-label {
    display: none;
  }

  .listing-wizard__main {
    max-width: none;
  }
}

@media (max-width: 767px) {
  .listing-wizard__deal-step {
    gap: 20px;
    padding: 20px 8px 28px;
  }

  .listing-wizard__panel-title--deal {
    font-size: 22px;
  }

  .listing-wizard__deal-tiles {
    gap: 8px;
  }

  .listing-wizard__deal-tile {
    min-height: 96px;
    padding: 12px 8px;
  }

  .listing-wizard__deal-tiles--deal {
    gap: 12px;
  }

  .listing-wizard__deal-tiles--deal .listing-wizard__deal-tile {
    min-height: 128px;
    padding: 18px 10px;
  }

  .listing-wizard__deal-icon {
    width: 40px;
    height: 40px;
  }

  .listing-wizard__deal-tiles--deal .listing-wizard__deal-icon {
    width: 52px;
    height: 52px;
  }

  .listing-wizard__deal-glyph {
    width: 22px;
    height: 22px;
  }

  .listing-wizard__deal-tiles--deal .listing-wizard__deal-glyph {
    width: 28px;
    height: 28px;
  }

  .listing-wizard__deal-label {
    font-size: 13px;
  }

  .listing-wizard__deal-tiles--deal .listing-wizard__deal-label {
    font-size: 15px;
  }

  .listing-wizard__grid,
  .listing-wizard__photo-add {
    grid-template-columns: 1fr;
    flex-direction: column;
  }
}
</style>
