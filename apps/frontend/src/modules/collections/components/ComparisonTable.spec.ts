import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it } from 'vitest'
import ComparisonTable from '@/modules/collections/components/ComparisonTable.vue'
import { i18n } from '@/modules/locale'
import type { ListingDto } from '@/types'

const listing = {
  id: 1,
  dealType: 'sale',
  listingType: 'apartment',
  status: 'published',
  price: 100000,
  pricePerSqm: 2000,
  rooms: 2,
  area: 50,
  floor: 3,
  totalFloors: 9,
  address: 'Test',
  latitude: 0,
  longitude: 0,
  metroMinutes: null,
  verified: false,
  aiGoodPrice: false,
  rentTerm: null,
  hasDeposit: false,
  utilitiesIncluded: false,
  noCommission: false,
  fromOwner: false,
  hasRenovation: false,
  views: 0,
  images: [],
  publishedAt: '2026-01-01',
  userId: 1,
  cityId: 1,
  districtId: 1,
  metroStationId: null,
} as ListingDto

describe('ComparisonTable', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('applies compact layout class when compact prop is set', () => {
    const wrapper = mount(ComparisonTable, {
      props: {
        listings: [listing],
        compact: true,
      },
      global: {
        plugins: [i18n],
      },
    })

    expect(wrapper.classes()).toContain('comparison-table--compact')
    expect(wrapper.find('.comparison-table__image').attributes('class')).toContain('comparison-table__image')
    expect(wrapper.find('.comparison-table__address').text()).toBe('Test')
    expect(wrapper.find('.comparison-table__remove').exists()).toBe(true)
  })

  it('keeps listing columns aligned in a shared grid', () => {
    const second = { ...listing, id: 2, address: 'Second' }
    const wrapper = mount(ComparisonTable, {
      props: {
        listings: [listing, second],
      },
      global: {
        plugins: [i18n],
      },
    })

    const root = wrapper.get('.comparison-table')
    expect(root.attributes('style')).toContain('--comparison-cols: 2')
    expect(wrapper.findAll('.comparison-table__card')).toHaveLength(2)
    expect(wrapper.find('.comparison-table__grid').exists()).toBe(true)
    expect(wrapper.findAll('.comparison-table__value').length).toBeGreaterThan(0)
  })

  it('centers values and highlights better metrics', () => {
    const cheap = { ...listing, id: 1, price: 500, pricePerSqm: 10, area: 40, rooms: 1 }
    const spacious = { ...listing, id: 2, price: 900, pricePerSqm: 18, area: 90, rooms: 3 }
    const wrapper = mount(ComparisonTable, {
      props: {
        listings: [cheap, spacious],
      },
      global: {
        plugins: [i18n],
      },
    })

    expect(wrapper.findAll('.comparison-table__value--best').length).toBeGreaterThanOrEqual(3)
    expect(wrapper.find('.comparison-table__card--best').exists()).toBe(true)
    expect(wrapper.find('.comparison-table__value').classes()).toContain('comparison-table__value')
  })
})
