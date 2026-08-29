import { defineStore } from 'pinia'
import { computed, ref, watch } from 'vue'

const STORAGE_KEY = 'donmap_admin_test_mode'

function readStored(): boolean {
  try {
    const raw = sessionStorage.getItem(STORAGE_KEY)
    if (raw === null) {
      return false
    }
    return raw === '1'
  } catch {
    return false
  }
}

export const useAdminTestModeStore = defineStore('adminTestMode', () => {
  const enabled = ref(readStored())
  const pendingEnabled = ref<boolean | null>(null)
  const confirmOpen = ref(false)

  const isTest = computed(() => enabled.value)

  function persist(value: boolean) {
    try {
      sessionStorage.setItem(STORAGE_KEY, value ? '1' : '0')
    } catch {
      // ignore
    }
  }

  function requestToggle(next: boolean) {
    if (next === enabled.value) {
      return
    }
    pendingEnabled.value = next
    confirmOpen.value = true
  }

  function confirmToggle() {
    if (pendingEnabled.value === null) {
      confirmOpen.value = false
      return
    }
    enabled.value = pendingEnabled.value
    persist(enabled.value)
    pendingEnabled.value = null
    confirmOpen.value = false
  }

  function cancelToggle() {
    pendingEnabled.value = null
    confirmOpen.value = false
  }

  watch(enabled, (value) => {
    persist(value)
  })

  return {
    enabled,
    isTest,
    pendingEnabled,
    confirmOpen,
    requestToggle,
    confirmToggle,
    cancelToggle,
  }
})
