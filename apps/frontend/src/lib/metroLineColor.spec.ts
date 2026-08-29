import { describe, expect, it } from 'vitest'
import {
  isKnownMetroLineColor,
  METRO_LINE_COLOR_OPTIONS,
  normalizeMetroLineColor,
} from '@/lib/metroLineColor'

describe('normalizeMetroLineColor', () => {
  it('returns default color for invalid values', () => {
    expect(normalizeMetroLineColor(undefined)).toBe('#0072BC')
    expect(normalizeMetroLineColor('red')).toBe('#0072BC')
  })

  it('returns valid hex colors unchanged', () => {
    expect(normalizeMetroLineColor('#D62027')).toBe('#D62027')
    expect(normalizeMetroLineColor('#009a49')).toBe('#009A49')
    expect(normalizeMetroLineColor('0072BC')).toBe('#0072BC')
    expect(normalizeMetroLineColor('#07C')).toBe('#0077CC')
  })

  it('exposes minsk metro line palette', () => {
    expect(METRO_LINE_COLOR_OPTIONS).toEqual(['#0072BC', '#D62027', '#009A49'])
    expect(isKnownMetroLineColor('#009A49')).toBe(true)
    expect(isKnownMetroLineColor('#FFFFFF')).toBe(false)
  })
})
