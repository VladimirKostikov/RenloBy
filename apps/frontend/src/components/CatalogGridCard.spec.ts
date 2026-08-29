import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { describe, expect, it } from 'vitest'
import { createI18n } from 'vue-i18n'
import CatalogGridCard from '@/components/CatalogGridCard.vue'
import ru from '@/locales/ru.json'
import type { ListingDto } from '@/types'

const listing: ListingDto = {
  id: 1,
  dealType: 'sale',
  listingType: 'apartment',
  status: 'published',
  price: 145_000,
  pricePerSqm: 2500,
  rooms: 2,
  area: 58,
  floor: 7,
  totalFloors: 12,
  address: 'ул. Петра Мстиславца, 18',
  latitude: 53.9,
  longitude: 27.5,
  metroMinutes: 8,
  verified: true,
  aiGoodPrice: false,
  rentTerm: null,
  hasDeposit: false,
  utilitiesIncluded: false,
    noCommission: false,
    fromOwner: false,
    hasRenovation: false,
    views: 152,
  images: ['https://example.com/photo.jpg'],
  publishedAt: '2026-07-14T10:00:00.000Z',
  userId: 1,
  cityId: 1,
  districtId: 1,
  metroStationId: 1,
}

function mountCard(overrides: Partial<ListingDto> = {}, cardProps: Record<string, unknown> = {}) {
  const i18n = createI18n({
    legacy: false,
    locale: 'ru',
    messages: { ru },
  })

  setActivePinia(createPinia())

  return mount(CatalogGridCard, {
    props: {
      listing: { ...listing, ...overrides },
      featured: true,
      districtName: 'Фрунзенский район, Минск',
      metroStation: {
        id: 1,
        name: 'Михалово',
        slug: 'mihalovo',
        lineColor: '#009A49',
        cityId: 1,
      },
      ...cardProps,
    },
    global: {
      plugins: [i18n],
    },
  })
}

describe('CatalogGridCard', () => {
  it('renders price, address and learn more button', () => {
    const wrapper = mountCard()

    expect(wrapper.text()).toMatch(/145\s*000|474\s*150/)
    expect(wrapper.text()).toContain('ул. Петра Мстиславца, 18')
    expect(wrapper.text()).toContain('Узнать подробнее')
    expect(wrapper.text()).toContain('Продажа')
    expect(wrapper.text()).toContain('Квартира')
    const offerTypes = wrapper.findAll('.catalog-card__offer-type')
    expect(offerTypes).toHaveLength(2)
    expect(offerTypes[0]?.text()).toBe('Продажа')
    expect(offerTypes[1]?.text()).toBe('Квартира')
    expect(wrapper.find('.catalog-card__top').exists()).toBe(true)
    expect(wrapper.find('.catalog-card__top').text()).toBe('ТОП')
    expect(wrapper.text()).toContain('Проверено')
    expect(wrapper.find('.listing-verified-badge').exists()).toBe(true)
    expect(wrapper.text()).toContain('Михалово')
    expect(wrapper.find('.catalog-card__location').exists()).toBe(true)
    expect(wrapper.find('.metro-icon').element.style.backgroundColor).toBe('#009A49')
  })

  it('shows verified badge for verified listings without forcing top', () => {
    const wrapper = mountCard({ verified: true, aiGoodPrice: false }, { featured: false })

    expect(wrapper.find('.listing-verified-badge').exists()).toBe(true)
    expect(wrapper.text()).toContain('Проверено')
    expect(wrapper.find('.catalog-card__top').exists()).toBe(false)
  })

  it('hides verified badge when listing is not verified', () => {
    const wrapper = mountCard({ verified: false }, { featured: false })

    expect(wrapper.find('.listing-verified-badge').exists()).toBe(false)
  })

  it('emits open event when CTA is clicked', async () => {
    const wrapper = mountCard()

    await wrapper.find('.catalog-card__cta').trigger('click')

    expect(wrapper.emitted('open')?.[0]).toEqual([1])
  })

  it('emits open event when card body is clicked', async () => {
    const wrapper = mountCard()

    await wrapper.find('.catalog-card__address').trigger('click')

    expect(wrapper.emitted('open')?.[0]).toEqual([1])
  })

  it('emits favorite event without opening listing when heart is clicked', async () => {
    const wrapper = mountCard()

    await wrapper.find('.catalog-card__favorite').trigger('click')

    expect(wrapper.emitted('favorite')?.[0]).toEqual([1])
    expect(wrapper.emitted('open')).toBeUndefined()
  })

  it('uses active favorite icon when listing is favorited', () => {
    const wrapper = mountCard({}, { favorited: true })

    expect(wrapper.find('.catalog-card__favorite--active').exists()).toBe(true)
    expect(wrapper.find('.catalog-card__favorite path').attributes('fill')).toBe('var(--figma-accent)')
  })

  it('uses default favorite icon when listing is not favorited', () => {
    const wrapper = mountCard({}, { favorited: false })

    expect(wrapper.find('.catalog-card__favorite--active').exists()).toBe(false)
    expect(wrapper.find('.catalog-card__favorite path').attributes('fill')).toBe('none')
  })

  it('emits compare event without opening listing when compare controls are clicked', async () => {
    const wrapper = mountCard()

    await wrapper.find('.catalog-card__compare-btn').trigger('click')
    expect(wrapper.emitted('compare')?.[0]).toEqual([1])
    expect(wrapper.emitted('open')).toBeUndefined()

    await wrapper.find('.catalog-card__compare-icon').trigger('click')
    expect(wrapper.emitted('compare')?.[1]).toEqual([1])
  })

  it('links to the standalone listing page without opening the modal', async () => {
    const wrapper = mountCard()
    const link = wrapper.find('.catalog-card__page-link')

    expect(link.exists()).toBe(true)
    expect(link.attributes('href')).toBe('/listings/1')
    expect(link.attributes('aria-label')).toBe('Открыть страницу объявления')

    await link.trigger('click')
    expect(wrapper.emitted('open')).toBeUndefined()
  })

  it('shows active compare state when listing is compared', () => {
    const wrapper = mountCard({}, { compared: true })

    expect(wrapper.find('.catalog-card__compare-btn--active').exists()).toBe(true)
    expect(wrapper.find('.catalog-card__compare-icon--active').exists()).toBe(true)
    expect(wrapper.text()).toContain('В сравнении')
  })

  it('renders image slider with navigation for multiple photos', () => {
    const wrapper = mountCard({
      images: [
        'https://example.com/photo-1.jpg',
        'https://example.com/photo-2.jpg',
      ],
    })

    expect(wrapper.find('.listing-image-slider__nav--next').exists()).toBe(true)
  })

  it('applies compact card class when compact prop is set', () => {
    const wrapper = mountCard({}, { compact: true })

    expect(wrapper.find('.catalog-card--compact').exists()).toBe(true)
  })

  it('keeps footer pinned for equal-height grid rows', () => {
    const source = readFileSync(resolve(__dirname, './CatalogGridCard.vue'), 'utf8')
    expect(source).toContain('align-self: stretch')
    expect(source).toContain('min-height: 100%')
    expect(source).toContain('.catalog-card__footer')
    expect(source).toContain('margin-top: auto')
    expect(source).toContain('min-height: 64px')
    expect(source).toContain('grid-template-columns: minmax(0, 1fr) auto')
    expect(source).toContain('flex-wrap: nowrap')
  })

  it('hides price per sqm on compact cards to avoid overflow', () => {
    const source = readFileSync(resolve(__dirname, './CatalogGridCard.vue'), 'utf8')
    expect(source).toContain('.catalog-card--compact .catalog-card__sqm')
    expect(source).toContain('display: none')
  })

  it('keeps learn-more and compare buttons the same size', () => {
    const source = readFileSync(resolve(__dirname, './CatalogGridCard.vue'), 'utf8')
    expect(source).toContain('.catalog-card__cta,\n  .catalog-card__compare-btn')
    expect(source).toContain('min-height: 40px')
    expect(source).toContain('font-size: 14px')
    expect(source).not.toContain('min-height: var(--touch-target-min, 44px)')
  })
})
