import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import ThemeIcon from '@/components/ThemeIcon.vue'

describe('ThemeIcon', () => {
  it('renders mask icon with size and src', () => {
    const wrapper = mount(ThemeIcon, {
      props: {
        src: '/figma/heart.svg',
        width: 17,
        height: 14,
      },
    })

    const icon = wrapper.get('.theme-icon')
    const el = icon.element as HTMLElement
    expect(icon.attributes('aria-hidden')).toBe('true')
    expect(el.style.width).toBe('17px')
    expect(el.style.height).toBe('14px')
    expect(el.style.maskImage || el.style.webkitMaskImage).toContain('/figma/heart.svg')
  })
})
