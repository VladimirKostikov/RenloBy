import { defineStore } from 'pinia'
import { ref } from 'vue'
import {
  isPaletteId,
  isThemeMode,
  type PaletteId,
  type ThemeMode,
} from '@/modules/theme/lib/palettes'

const THEME_KEY = 'renlo-theme'
const PALETTE_KEY = 'renlo-palette'

function readStoredTheme(): ThemeMode {
  const stored = localStorage.getItem(THEME_KEY)
  if (stored && isThemeMode(stored)) {
    return stored
  }
  return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
}

function readStoredPalette(): PaletteId {
  const stored = localStorage.getItem(PALETTE_KEY)
  if (stored && isPaletteId(stored)) {
    return stored
  }
  return 'default'
}

function applyTheme(mode: ThemeMode, palette: PaletteId) {
  document.documentElement.setAttribute('data-theme', mode)
  document.documentElement.setAttribute('data-palette', palette)
  const themeColor = mode === 'dark' ? '#0f1117' : '#ffffff'
  const meta = document.querySelector('meta[name="theme-color"]')
  if (meta) {
    meta.setAttribute('content', themeColor)
  }
}

export const useThemeStore = defineStore('theme', () => {
  const mode = ref<ThemeMode>(readStoredTheme())
  const palette = ref<PaletteId>(readStoredPalette())

  applyTheme(mode.value, palette.value)

  function setMode(value: ThemeMode) {
    mode.value = value
    localStorage.setItem(THEME_KEY, value)
    applyTheme(value, palette.value)
  }

  function toggleMode() {
    setMode(mode.value === 'light' ? 'dark' : 'light')
  }

  function setPalette(value: PaletteId) {
    palette.value = value
    localStorage.setItem(PALETTE_KEY, value)
    applyTheme(mode.value, value)
  }

  return { mode, palette, setMode, toggleMode, setPalette }
})
