export const COOKIE_CONSENT_STORAGE_KEY = 'renlo-cookie-consent'
export const COOKIE_CONSENT_ACCEPTED = 'accepted'

export function readCookieConsent(): boolean {
  try {
    return localStorage.getItem(COOKIE_CONSENT_STORAGE_KEY) === COOKIE_CONSENT_ACCEPTED
  } catch {
    return false
  }
}

export function acceptCookieConsent(): void {
  localStorage.setItem(COOKIE_CONSENT_STORAGE_KEY, COOKIE_CONSENT_ACCEPTED)
}
