import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it } from 'vitest'
import ThemeAppearanceMenu from '@/modules/theme/components/ThemeAppearanceMenu.vue'
import { THEME_PALETTE_OPTIONS } from '@/modules/theme/lib/palettes'
import { i18n } from '@/modules/locale'
import { useThemeStore } from '@/stores/theme'

describe('ThemeAppearanceMenu', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    localStorage.clear()
    document.documentElement.removeAttribute('data-theme')
    document.documentElement.removeAttribute('data-palette')
  })

  it('opens rainbow menu and switches palette and mode', async () => {
    const wrapper = mount(ThemeAppearanceMenu, {
      global: {
        plugins: [i18n],
      },
      attachTo: document.body,
    })

    expect(wrapper.find('.theme-menu__panel').exists()).toBe(false)
    expect(wrapper.find('.theme-menu__rainbow').exists()).toBe(true)

    await wrapper.get('.theme-menu__trigger').trigger('click')
    expect(wrapper.find('.theme-menu__panel').exists()).toBe(true)
    expect(wrapper.findAll('.theme-menu__swatch')).toHaveLength(THEME_PALETTE_OPTIONS.length)

    const theme = useThemeStore()
    await wrapper.findAll('.theme-menu__swatch')[1]!.trigger('click')
    expect(theme.palette).toBe('ocean')
    expect(document.documentElement.getAttribute('data-palette')).toBe('ocean')

    theme.setMode('light')
    await wrapper.findAll('.theme-menu__mode')[1]!.trigger('click')
    expect(theme.mode).toBe('dark')
    expect(document.documentElement.getAttribute('data-theme')).toBe('dark')

    wrapper.unmount()
  })

  it('closes on outside pointerdown', async () => {
    const wrapper = mount(ThemeAppearanceMenu, {
      global: {
        plugins: [i18n],
      },
      attachTo: document.body,
    })

    await wrapper.get('.theme-menu__trigger').trigger('click')
    expect(wrapper.find('.theme-menu__panel').exists()).toBe(true)

    document.dispatchEvent(new PointerEvent('pointerdown', { bubbles: true }))
    await wrapper.vm.$nextTick()

    expect(wrapper.find('.theme-menu__panel').exists()).toBe(false)
    wrapper.unmount()
  })
})
