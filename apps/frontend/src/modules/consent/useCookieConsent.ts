import { computed, ref } from 'vue'
import { acceptCookieConsent, readCookieConsent } from '@/modules/consent/cookieConsent'

const accepted = ref(readCookieConsent())

export function useCookieConsent() {
  const visible = computed(() => !accepted.value)

  function accept() {
    acceptCookieConsent()
    accepted.value = true
  }

  function resetForTests() {
    accepted.value = readCookieConsent()
  }

  return {
    visible,
    accept,
    resetForTests,
  }
}
