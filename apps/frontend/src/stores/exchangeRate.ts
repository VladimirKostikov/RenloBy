import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { fetchExchangeRates } from '@/api/exchangeRates'
import { formatRate, getUsdToBynRate, setUsdToBynRate } from '@/lib/formatPrice'

export const useExchangeRateStore = defineStore('exchangeRate', () => {
  const usdToByn = ref(getUsdToBynRate())
  const usdToRub = ref(0)
  const source = ref('fallback')
  const updatedAt = ref<string | null>(null)
  const loaded = ref(false)
  const loading = ref(false)

  const rateLabel = computed(() => formatRate(usdToByn.value))

  async function load(force = false) {
    if (loading.value || (loaded.value && !force)) {
      return
    }
    loading.value = true
    try {
      const rates = await fetchExchangeRates()
      if (Number.isFinite(rates.usdToByn) && rates.usdToByn > 0) {
        usdToByn.value = rates.usdToByn
        setUsdToBynRate(rates.usdToByn)
      }
      if (Number.isFinite(rates.usdToRub) && rates.usdToRub > 0) {
        usdToRub.value = rates.usdToRub
      }
      source.value = rates.source || 'fallback'
      updatedAt.value = rates.updatedAt
      loaded.value = true
    } catch {
      usdToByn.value = getUsdToBynRate()
      loaded.value = true
    } finally {
      loading.value = false
    }
  }

  return {
    usdToByn,
    usdToRub,
    source,
    updatedAt,
    loaded,
    loading,
    rateLabel,
    load,
  }
})
