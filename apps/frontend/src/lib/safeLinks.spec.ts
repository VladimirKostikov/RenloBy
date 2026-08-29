import { describe, expect, it } from 'vitest'
import { isSafeHttpUrl, isSafeTelHref } from '@/lib/safeLinks'

describe('safeLinks', () => {
  it('accepts http and https urls', () => {
    expect(isSafeHttpUrl('https://t.me/renlo')).toBe(true)
    expect(isSafeHttpUrl('http://example.com')).toBe(true)
  })

  it('rejects unsafe schemes and invalid urls', () => {
    expect(isSafeHttpUrl('javascript:alert(1)')).toBe(false)
    expect(isSafeHttpUrl('mailto:a@b.c')).toBe(false)
    expect(isSafeHttpUrl('')).toBe(false)
    expect(isSafeHttpUrl(null)).toBe(false)
  })

  it('validates tel hrefs', () => {
    expect(isSafeTelHref('tel:+375290000000')).toBe(true)
    expect(isSafeTelHref('tel:abc')).toBe(false)
  })
})
