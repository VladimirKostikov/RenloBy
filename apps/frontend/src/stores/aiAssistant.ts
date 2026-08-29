import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import {
  createAiPreference,
  fetchLatestAiPreference,
  type AiPreferenceDto,
} from '@/api/aiPreferences'
import { resolveApiError } from '@/lib/resolveApiError'
import { i18n } from '@/modules/locale'

const MAX_VISIBLE_RECOMMENDATIONS = 4

export const useAiAssistantStore = defineStore('aiAssistant', () => {
  const preference = ref<AiPreferenceDto | null>(null)
  const loading = ref(false)
  const submitting = ref(false)
  const error = ref('')
  const initialized = ref(false)

  const recommendedIds = computed(() => preference.value?.recommendedListingIds ?? [])
  const recommendedIdSet = computed(() => new Set(recommendedIds.value.slice(0, MAX_VISIBLE_RECOMMENDATIONS)))
  const hasPreference = computed(() => preference.value !== null)
  const summary = computed(() => preference.value?.summary ?? '')
  const highlights = computed(() => preference.value?.highlights ?? [])
  const recommendedListings = computed(() =>
    (preference.value?.listings ?? []).slice(0, MAX_VISIBLE_RECOMMENDATIONS),
  )

  function isRecommended(listingId: number): boolean {
    return recommendedIdSet.value.has(listingId)
  }

  async function initialize() {
    if (initialized.value || loading.value) return
    loading.value = true
    error.value = ''
    try {
      preference.value = await fetchLatestAiPreference()
      initialized.value = true
    } catch (err) {
      error.value = resolveApiError(err, i18n.global.t, 'aiAssistant.loadError').message
    } finally {
      loading.value = false
    }
  }

  async function submitAnswers(answers: Record<string, unknown>): Promise<AiPreferenceDto> {
    submitting.value = true
    error.value = ''
    try {
      const created = await createAiPreference(answers)
      preference.value = created
      initialized.value = true
      return created
    } catch (err) {
      error.value = resolveApiError(err, i18n.global.t, 'aiAssistant.submitError').message
      throw err
    } finally {
      submitting.value = false
    }
  }

  function clearLocal() {
    preference.value = null
  }

  return {
    preference,
    loading,
    submitting,
    error,
    initialized,
    recommendedIds,
    hasPreference,
    summary,
    highlights,
    recommendedListings,
    isRecommended,
    initialize,
    submitAnswers,
    clearLocal,
  }
})
