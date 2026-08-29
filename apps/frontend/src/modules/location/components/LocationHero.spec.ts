import { mount } from '@vue/test-utils'
import { createPinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import { describe, expect, it } from 'vitest'
import LocationHero from '@/modules/location/components/LocationHero.vue'
import { i18n } from '@/modules/locale'

const router = createRouter({
  history: createWebHistory(),
  routes: [{ path: '/', component: { template: '<div />' } }],
})

describe('LocationHero', () => {
  it('renders title and stats', () => {
    const wrapper = mount(LocationHero, {
      global: {
        plugins: [createPinia(), i18n, router],
      },
      props: {
        kind: 'city',
        title: 'Недвижимость в Минске',
        stats: { count: 42, avgPrice: 80000, avgPricePerSqm: 1200 },
      },
    })

    expect(wrapper.find('.location-hero__title').text()).toBe('Недвижимость в Минске')
    expect(wrapper.findAll('.location-hero__stat-value')[0].text()).toBe('42')
  })
})
