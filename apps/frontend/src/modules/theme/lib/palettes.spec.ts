import { describe, expect, it } from 'vitest'
import { isPaletteId, isThemeMode, THEME_PALETTE_OPTIONS } from '@/modules/theme/lib/palettes'

describe('theme palettes', () => {
  it('lists all supported palettes with swatches', () => {
    expect(THEME_PALETTE_OPTIONS.map((item) => item.id)).toEqual([
      'default',
      'ocean',
      'forest',
      'slate',
      'amber',
      'teal',
      'berry',
      'indigo',
      'clay',
      'orchid',
      'sky',
      'navy',
      'lime',
      'wine',
      'sand',
      'pine',
    ])
    for (const option of THEME_PALETTE_OPTIONS) {
      expect(option.swatch).toMatch(/^#[0-9a-fA-F]{6}$/)
    }
  })

  it('validates palette and theme mode ids', () => {
    expect(isPaletteId('ocean')).toBe(true)
    expect(isPaletteId('orchid')).toBe(true)
    expect(isPaletteId('sky')).toBe(true)
    expect(isPaletteId('pine')).toBe(true)
    expect(isPaletteId('violet')).toBe(false)
    expect(isThemeMode('dark')).toBe(true)
    expect(isThemeMode('auto')).toBe(false)
  })
})
