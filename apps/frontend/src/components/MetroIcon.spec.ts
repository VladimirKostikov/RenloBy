import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import MetroIcon from '@/components/MetroIcon.vue'

describe('MetroIcon', () => {
  it('renders colored circle with provided line color', () => {
    const wrapper = mount(MetroIcon, {
      props: {
        color: '#009A49',
        size: 10,
      },
    })

    const icon = wrapper.find('.metro-icon')
    expect(icon.element.style.width).toBe('10px')
    expect(icon.element.style.height).toBe('10px')
    expect(icon.element.style.backgroundColor).toBe('#009A49')
  })

  it('falls back to default line color for invalid values', () => {
    const wrapper = mount(MetroIcon, {
      props: {
        color: 'invalid',
      },
    })

    expect(wrapper.find('.metro-icon').element.style.backgroundColor).toBe('#0072BC')
  })
})
