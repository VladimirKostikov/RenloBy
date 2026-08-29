import { flushPromises, mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import AccountListingSeoModal from '@/modules/account/components/AccountListingSeoModal.vue'
import { i18n } from '@/modules/locale'
import type { ListingDto } from '@/types'

const updateMyListing = vi.fn()

vi.mock('@/api/account', () => ({
  updateMyListing: (...args: unknown[]) => updateMyListing(...args),
}))

function makeListing(overrides: Partial<ListingDto> = {}): ListingDto {
  return {
    id: 7,
    dealType: 'sale',
    listingType: 'apartment',
    status: 'published',
    price: 100000,
    pricePerSqm: 2000,
    rooms: 2,
    area: 50,
    floor: 3,
    totalFloors: 9,
    address: 'ул. Тестовая, 1',
    latitude: 53.9,
    longitude: 27.5,
    metroMinutes: null,
    verified: false,
    aiGoodPrice: false,
    rentTerm: null,
    hasDeposit: false,
    utilitiesIncluded: false,
    noCommission: false,
    fromOwner: true,
    hasRenovation: false,
    priceNegotiable: false,
    views: 10,
    images: [],
    metaTitle: null,
    metaDescription: null,
    metaKeywords: null,
    publishedAt: '2026-01-01T00:00:00+00:00',
    userId: 1,
    cityId: 1,
    districtId: 1,
    metroStationId: null,
    ...overrides,
  }
}

describe('AccountListingSeoModal', () => {
  beforeEach(() => {
    updateMyListing.mockReset()
  })

  it('saves SEO fields and emits saved', async () => {
    const listing = makeListing()
    updateMyListing.mockResolvedValueOnce({
      ...listing,
      metaTitle: 'Свой title',
      metaDescription: 'Свой description',
      metaKeywords: 'минск, квартира',
    })

    const wrapper = mount(AccountListingSeoModal, {
      props: { listing },
      global: { plugins: [i18n] },
    })

    await wrapper.get('[data-testid="listing-seo-title"]').setValue('Свой title')
    await wrapper.get('[data-testid="listing-seo-description"]').setValue('Свой description')
    await wrapper.get('[data-testid="listing-seo-keywords"]').setValue('минск, квартира')
    await wrapper.get('form').trigger('submit.prevent')
    await flushPromises()

    expect(updateMyListing).toHaveBeenCalledWith(7, {
      metaTitle: 'Свой title',
      metaDescription: 'Свой description',
      metaKeywords: 'минск, квартира',
    })
    expect(wrapper.emitted('saved')?.[0]?.[0]).toMatchObject({
      metaTitle: 'Свой title',
      metaDescription: 'Свой description',
      metaKeywords: 'минск, квартира',
    })
    expect(wrapper.emitted('close')).toHaveLength(1)
  })

  it('sends null for empty fields to clear overrides', async () => {
    const listing = makeListing({
      metaTitle: 'Old',
      metaDescription: 'Old desc',
      metaKeywords: 'old',
    })
    updateMyListing.mockResolvedValueOnce({
      ...listing,
      metaTitle: null,
      metaDescription: null,
      metaKeywords: null,
    })

    const wrapper = mount(AccountListingSeoModal, {
      props: { listing },
      global: { plugins: [i18n] },
    })

    await wrapper.get('[data-testid="listing-seo-title"]').setValue('  ')
    await wrapper.get('[data-testid="listing-seo-description"]').setValue('')
    await wrapper.get('[data-testid="listing-seo-keywords"]').setValue('')
    await wrapper.get('form').trigger('submit.prevent')
    await flushPromises()

    expect(updateMyListing).toHaveBeenCalledWith(7, {
      metaTitle: null,
      metaDescription: null,
      metaKeywords: null,
    })
  })
})
