import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it } from 'vitest'
import CookieConsentBanner from '@/modules/consent/CookieConsentBanner.vue'
import { COOKIE_CONSENT_STORAGE_KEY, COOKIE_CONSENT_ACCEPTED } from '@/modules/consent/cookieConsent'
import { useCookieConsent } from '@/modules/consent/useCookieConsent'
import { i18n } from '@/modules/locale'

describe('CookieConsentBanner', () => {
  beforeEach(() => {
    localStorage.clear()
    useCookieConsent().resetForTests()
  })

  it('shows banner on first visit and hides after accept', async () => {
    const wrapper = mount(CookieConsentBanner, {
      global: { plugins: [i18n] },
    })

    expect(wrapper.find('.cookie-consent').exists()).toBe(true)
    expect(wrapper.text()).toContain(i18n.global.t('cookies.accept'))

    await wrapper.get('.cookie-consent__accept').trigger('click')

    expect(wrapper.find('.cookie-consent').exists()).toBe(false)
    expect(localStorage.getItem(COOKIE_CONSENT_STORAGE_KEY)).toBe(COOKIE_CONSENT_ACCEPTED)
  })

  it('stays hidden when consent was already accepted', () => {
    localStorage.setItem(COOKIE_CONSENT_STORAGE_KEY, COOKIE_CONSENT_ACCEPTED)
    useCookieConsent().resetForTests()

    const wrapper = mount(CookieConsentBanner, {
      global: { plugins: [i18n] },
    })

    expect(wrapper.find('.cookie-consent').exists()).toBe(false)
  })
})
