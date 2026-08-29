export type PaletteId =
  | 'default'
  | 'ocean'
  | 'forest'
  | 'slate'
  | 'amber'
  | 'teal'
  | 'berry'
  | 'indigo'
  | 'clay'
  | 'orchid'
  | 'sky'
  | 'navy'
  | 'lime'
  | 'wine'
  | 'sand'
  | 'pine'

export type ThemeMode = 'light' | 'dark'

export interface ThemePaletteOption {
  id: PaletteId
  labelKey: string
  swatch: string
}

export const THEME_PALETTE_OPTIONS: ThemePaletteOption[] = [
  { id: 'default', labelKey: 'theme.palettes.default', swatch: '#e14554' },
  { id: 'ocean', labelKey: 'theme.palettes.ocean', swatch: '#0891b2' },
  { id: 'forest', labelKey: 'theme.palettes.forest', swatch: '#059669' },
  { id: 'slate', labelKey: 'theme.palettes.slate', swatch: '#475569' },
  { id: 'amber', labelKey: 'theme.palettes.amber', swatch: '#d97706' },
  { id: 'teal', labelKey: 'theme.palettes.teal', swatch: '#0d9488' },
  { id: 'berry', labelKey: 'theme.palettes.berry', swatch: '#be185d' },
  { id: 'indigo', labelKey: 'theme.palettes.indigo', swatch: '#4f46e5' },
  { id: 'clay', labelKey: 'theme.palettes.clay', swatch: '#c2410c' },
  { id: 'orchid', labelKey: 'theme.palettes.orchid', swatch: '#a21caf' },
  { id: 'sky', labelKey: 'theme.palettes.sky', swatch: '#0284c7' },
  { id: 'navy', labelKey: 'theme.palettes.navy', swatch: '#1d4ed8' },
  { id: 'lime', labelKey: 'theme.palettes.lime', swatch: '#65a30d' },
  { id: 'wine', labelKey: 'theme.palettes.wine', swatch: '#9f1239' },
  { id: 'sand', labelKey: 'theme.palettes.sand', swatch: '#ca8a04' },
  { id: 'pine', labelKey: 'theme.palettes.pine', swatch: '#166534' },
]

export const THEME_MODE_OPTIONS: Array<{ id: ThemeMode, labelKey: string }> = [
  { id: 'light', labelKey: 'theme.light' },
  { id: 'dark', labelKey: 'theme.dark' },
]

export function isPaletteId(value: string): value is PaletteId {
  return THEME_PALETTE_OPTIONS.some((option) => option.id === value)
}

export function isThemeMode(value: string): value is ThemeMode {
  return value === 'light' || value === 'dark'
}
