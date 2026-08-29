import { describe, expect, it } from 'vitest'
import {
  extractPhoneLocalDigits,
  formatPhoneMask,
  isPhoneComplete,
  phoneE164,
} from '@/lib/phoneMask'

describe('phoneMask', () => {
  it('formats Belarus numbers while typing', () => {
    expect(formatPhoneMask('by', '29')).toBe('+375 29')
    expect(formatPhoneMask('by', '291112233')).toBe('+375 29 111-22-33')
    expect(formatPhoneMask('by', '+375291112233')).toBe('+375 29 111-22-33')
    expect(formatPhoneMask('by', '80291112233')).toBe('+375 29 111-22-33')
  })

  it('formats Russia numbers while typing', () => {
    expect(formatPhoneMask('ru', '9')).toBe('+7 9')
    expect(formatPhoneMask('ru', '9001234567')).toBe('+7 900 123-45-67')
    expect(formatPhoneMask('ru', '+79001234567')).toBe('+7 900 123-45-67')
    expect(formatPhoneMask('ru', '89001234567')).toBe('+7 900 123-45-67')
  })

  it('builds E.164 and validates completeness', () => {
    expect(phoneE164('by', '+375 29 111-22-33')).toBe('+375291112233')
    expect(phoneE164('ru', '+7 900 123-45-67')).toBe('+79001234567')
    expect(isPhoneComplete('by', '+375 29 111-22-33')).toBe(true)
    expect(isPhoneComplete('by', '+375 29 111')).toBe(false)
    expect(isPhoneComplete('ru', '+7 900 123-45-67')).toBe(true)
    expect(extractPhoneLocalDigits('by', '0291112233')).toBe('291112233')
  })
})
