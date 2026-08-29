import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import CatalogViewOnMapLink from '@/components/catalog/CatalogViewOnMapLink.vue'
import { i18n } from '@/modules/locale'

describe('CatalogViewOnMapLink', () => {
  it('links to search map with extended panel', () => {
    const wrapper = mount(CatalogViewOnMapLink, {
      global: {
        plugins: [i18n],
      },
    })

    const link = wrapper.get('a.catalog-view-on-map')
    expect(link.attributes('href')).toBe('/search?panel=extended')
    expect(link.text()).toContain('Посмотреть на карте')
  })
})
