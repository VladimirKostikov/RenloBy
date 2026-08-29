import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it } from 'vitest'
import { useThemeStore } from '@/stores/theme'

describe('theme store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    localStorage.clear()
    document.documentElement.removeAttribute('data-theme')
    document.documentElement.removeAttribute('data-palette')

    let meta = document.querySelector('meta[name="theme-color"]')
    if (!meta) {
      meta = document.createElement('meta')
      meta.setAttribute('name', 'theme-color')
      document.head.appendChild(meta)
    }
    meta.setAttribute('content', '#ffffff')
  })

  it('applies dark theme tokens to document and theme-color', () => {
    const theme = useThemeStore()
    theme.setMode('light')
    theme.setMode('dark')

    expect(theme.mode).toBe('dark')
    expect(document.documentElement.getAttribute('data-theme')).toBe('dark')
    expect(localStorage.getItem('renlo-theme')).toBe('dark')
    expect(document.querySelector('meta[name="theme-color"]')?.getAttribute('content')).toBe('#0f1117')
  })

  it('restores light theme-color when switching back', () => {
    const theme = useThemeStore()
    theme.setMode('dark')
    theme.setMode('light')

    expect(document.documentElement.getAttribute('data-theme')).toBe('light')
    expect(document.querySelector('meta[name="theme-color"]')?.getAttribute('content')).toBe('#ffffff')
  })

  it('toggles between light and dark', () => {
    const theme = useThemeStore()
    theme.setMode('light')
    theme.toggleMode()
    expect(theme.mode).toBe('dark')
    theme.toggleMode()
    expect(theme.mode).toBe('light')
  })
})
