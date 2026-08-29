import { beforeEach, describe, expect, it } from 'vitest'
import {
  COOKIE_CONSENT_ACCEPTED,
  COOKIE_CONSENT_STORAGE_KEY,
  acceptCookieConsent,
  readCookieConsent,
} from '@/modules/consent/cookieConsent'

describe('cookieConsent', () => {
  beforeEach(() => {
    localStorage.clear()
  })

  it('returns false when consent is missing', () => {
    expect(readCookieConsent()).toBe(false)
  })

  it('stores and reads accepted consent', () => {
    acceptCookieConsent()
    expect(localStorage.getItem(COOKIE_CONSENT_STORAGE_KEY)).toBe(COOKIE_CONSENT_ACCEPTED)
    expect(readCookieConsent()).toBe(true)
  })
})
