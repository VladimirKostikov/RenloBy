import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import ContainerPreloader from '@/components/ContainerPreloader.vue'

describe('ContainerPreloader', () => {
  it('renders spinner overlay when shown', () => {
    const wrapper = mount(ContainerPreloader, {
      props: { show: true, label: 'Загрузка...' },
    })

    expect(wrapper.find('.container-preloader').exists()).toBe(true)
    expect(wrapper.find('.container-preloader__spinner').exists()).toBe(true)
    expect(wrapper.text()).toContain('Загрузка...')
  })

  it('hides overlay when show is false', () => {
    const wrapper = mount(ContainerPreloader, {
      props: { show: false },
    })

    expect(wrapper.find('.container-preloader').exists()).toBe(false)
  })
})
