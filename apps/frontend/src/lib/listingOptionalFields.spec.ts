import { describe, expect, it } from 'vitest'
import { displayOptionalValue, formatFloorShort } from '@/lib/listingOptionalFields'

describe('listingOptionalFields', () => {
  it('formats floor when both values exist', () => {
    expect(formatFloorShort(3, 9)).toBe('3/9')
  })

  it('returns dash when floor values are missing', () => {
    expect(formatFloorShort(null, null)).toBe('-')
    expect(formatFloorShort(undefined, undefined, 'н/д')).toBe('н/д')
  })

  it('shows partial floor values', () => {
    expect(formatFloorShort(2, null)).toBe('2')
    expect(formatFloorShort(null, 5)).toBe('5')
  })

  it('displays optional values with fallback', () => {
    expect(displayOptionalValue(null)).toBe('-')
    expect(displayOptionalValue('  ')).toBe('-')
    expect(displayOptionalValue('Немига')).toBe('Немига')
    expect(displayOptionalValue(12)).toBe('12')
  })
})
