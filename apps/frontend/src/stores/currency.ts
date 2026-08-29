import { defineStore } from 'pinia'
import { ref, watch } from 'vue'
import type { CurrencyCode } from '@/types'

export const DEFAULT_CURRENCY: CurrencyCode = 'byn'
const CURRENCY_KEY = 'renlo-currency'

function readStoredCurrency(): CurrencyCode {
  try {
    const stored = localStorage.getItem(CURRENCY_KEY)
    if (stored === 'usd' || stored === 'byn') {
      return stored
    }
  } catch {
    return DEFAULT_CURRENCY
  }
  return DEFAULT_CURRENCY
}

export const useCurrencyStore = defineStore('currency', () => {
  const code = ref<CurrencyCode>(readStoredCurrency())

  function persist(value: CurrencyCode) {
    try {
      localStorage.setItem(CURRENCY_KEY, value)
    } catch {
      return
    }
  }

  watch(code, (value) => {
    persist(value)
  })

  function setCurrency(value: CurrencyCode) {
    code.value = value
    persist(value)
  }

  function toggleCurrency() {
    setCurrency(code.value === 'usd' ? 'byn' : 'usd')
  }

  return { code, setCurrency, toggleCurrency }
})
