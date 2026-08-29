import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import AppLogomark from '@/components/layout/AppLogomark.vue'

describe('AppLogomark', () => {
  it('renders inline svg logomark tinted by accent color', () => {
    const wrapper = mount(AppLogomark, {
      props: {
        width: 40,
        height: 40,
        imageClass: 'test-logomark',
      },
    })

    const svg = wrapper.find('svg.app-logomark')
    expect(svg.exists()).toBe(true)
    expect(svg.attributes('width')).toBe('40')
    expect(svg.attributes('height')).toBe('40')
    expect(svg.classes()).toContain('test-logomark')
    expect(svg.find('rect').attributes('fill')).toBe('currentColor')
    expect(svg.findAll('circle')[1]?.attributes('fill')).toBe('currentColor')
  })
})
