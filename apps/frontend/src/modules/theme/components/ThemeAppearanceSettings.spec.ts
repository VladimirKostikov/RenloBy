import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it } from 'vitest'
import ThemeAppearanceSettings from '@/modules/theme/components/ThemeAppearanceSettings.vue'
import { i18n } from '@/modules/locale'
import { useThemeStore } from '@/stores/theme'

describe('ThemeAppearanceSettings', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    localStorage.clear()
    document.documentElement.removeAttribute('data-theme')
    document.documentElement.removeAttribute('data-palette')
  })

  it('switches palette and updates document attributes', async () => {
    const wrapper = mount(ThemeAppearanceSettings, {
      global: {
        plugins: [i18n],
      },
    })

    const theme = useThemeStore()
    const ocean = wrapper.findAll('.theme-appearance__palette')[1]
    expect(ocean).toBeDefined()
    await ocean!.trigger('click')

    expect(theme.palette).toBe('ocean')
    expect(document.documentElement.getAttribute('data-palette')).toBe('ocean')
  })

  it('renders theme mode as tiles', () => {
    const wrapper = mount(ThemeAppearanceSettings, {
      global: {
        plugins: [i18n],
      },
    })

    expect(wrapper.find('.theme-appearance__mode-tiles').exists()).toBe(true)
    expect(wrapper.findAll('.theme-appearance__mode-btn')).toHaveLength(2)
    expect(wrapper.findAll('.theme-appearance__mode-preview')).toHaveLength(2)
  })

  it('renders compact round palette swatches without names', () => {
    const wrapper = mount(ThemeAppearanceSettings, {
      global: {
        plugins: [i18n],
      },
    })

    const buttons = wrapper.findAll('.theme-appearance__palette')
    expect(buttons.length).toBeGreaterThan(1)
    expect(wrapper.find('.theme-appearance__palette-name').exists()).toBe(false)
    expect(wrapper.findAll('.theme-appearance__swatch')).toHaveLength(buttons.length)
    expect(buttons[0]!.attributes('aria-label')).toBeTruthy()
  })

  it('switches theme mode', async () => {
    const wrapper = mount(ThemeAppearanceSettings, {
      global: {
        plugins: [i18n],
      },
    })

    const theme = useThemeStore()
    theme.setMode('light')

    const darkBtn = wrapper.findAll('.theme-appearance__mode-btn')[1]
    await darkBtn!.trigger('click')

    expect(theme.mode).toBe('dark')
    expect(document.documentElement.getAttribute('data-theme')).toBe('dark')
  })
})
