import { describe, expect, it } from 'vitest'
import { formatBelarusPhone } from '@/lib/formatBelarusPhone'

describe('formatBelarusPhone', () => {
  it('formats full +375 numbers', () => {
    expect(formatBelarusPhone('+375291112233')).toBe('+375 29 111-22-33')
    expect(formatBelarusPhone('375297778899')).toBe('+375 29 777-88-99')
  })

  it('normalizes local 80 and 0 prefixes', () => {
    expect(formatBelarusPhone('80291112233')).toBe('+375 29 111-22-33')
    expect(formatBelarusPhone('0291112233')).toBe('+375 29 111-22-33')
  })

  it('returns empty for blank input', () => {
    expect(formatBelarusPhone(null)).toBe('')
    expect(formatBelarusPhone('')).toBe('')
    expect(formatBelarusPhone('   ')).toBe('')
  })
})
