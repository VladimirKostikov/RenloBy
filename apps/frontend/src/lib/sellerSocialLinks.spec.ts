import { describe, expect, it } from 'vitest'
import {
  instagramProfileHref,
  telegramProfileHref,
  viberProfileHref,
  whatsappProfileHref,
} from '@/lib/sellerSocialLinks'

describe('sellerSocialLinks', () => {
  it('builds telegram links from handle and url', () => {
    expect(telegramProfileHref('@ivan')).toBe('https://t.me/ivan')
    expect(telegramProfileHref('ivan_seller')).toBe('https://t.me/ivan_seller')
    expect(telegramProfileHref('https://t.me/ivan')).toBe('https://t.me/ivan')
    expect(telegramProfileHref('javascript:alert(1)')).toBe(null)
  })

  it('builds instagram links from handle and url', () => {
    expect(instagramProfileHref('@anna.demo')).toBe('https://instagram.com/anna.demo')
    expect(instagramProfileHref('anna.demo')).toBe('https://instagram.com/anna.demo')
    expect(instagramProfileHref('https://instagram.com/anna.demo')).toBe('https://instagram.com/anna.demo')
    expect(instagramProfileHref('javascript:alert(1)')).toBe(null)
  })

  it('builds whatsapp and viber links from phone', () => {
    expect(whatsappProfileHref('+375 29 111-22-33')).toBe('https://wa.me/375291112233')
    expect(viberProfileHref('+375291112233')).toBe('viber://chat?number=%2B375291112233')
  })
})
