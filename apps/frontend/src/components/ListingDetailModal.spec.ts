import { mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'
import { nextTick } from 'vue'
import { createI18n } from 'vue-i18n'
import { createPinia, setActivePinia } from 'pinia'
import ListingDetailModal from '@/components/ListingDetailModal.vue'
import ru from '@/locales/ru.json'
import type { ListingDto } from '@/types'

vi.mock('@/components/ListingDetailPanel.vue', () => ({
  default: {
    name: 'ListingDetailPanel',
    props: ['listing', 'loading'],
    emits: ['close', 'showOnMap'],
    template:
      '<div class="panel-stub"><button class="close-stub" @click="$emit(\'close\')">x</button></div>',
  },
}))

const listing: ListingDto = {
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
  address: 'Test',
  latitude: 53.9,
  longitude: 27.5,
  metroMinutes: null,
  verified: false,
  aiGoodPrice: false,
  rentTerm: null,
  hasDeposit: false,
  utilitiesIncluded: false,
  noCommission: false,
  fromOwner: false,
  hasRenovation: false,
  views: 1,
  images: [],
  publishedAt: '2026-07-14T10:00:00.000Z',
  userId: 1,
  cityId: 1,
  districtId: 1,
  metroStationId: null,
}

describe('ListingDetailModal', () => {
  it('mounts overlay so appear transition can run', () => {
    const i18n = createI18n({ legacy: false, locale: 'ru', messages: { ru } })
    setActivePinia(createPinia())

    const wrapper = mount(ListingDetailModal, {
      props: { listing },
      global: {
        plugins: [i18n],
        stubs: { Teleport: true },
      },
    })

    expect(wrapper.find('.listing-detail-overlay').exists()).toBe(true)
  })

  it('hides overlay on close before parent unmount', async () => {
    const i18n = createI18n({ legacy: false, locale: 'ru', messages: { ru } })
    setActivePinia(createPinia())

    const wrapper = mount(ListingDetailModal, {
      props: { listing },
      global: {
        plugins: [i18n],
        stubs: {
          Teleport: true,
          Transition: {
            props: ['name', 'appear'],
            emits: ['afterLeave'],
            template: '<div class="transition-stub"><slot /></div>',
            methods: {
              triggerLeave() {
                this.$emit('afterLeave')
              },
            },
          },
        },
      },
    })

    await wrapper.find('.close-stub').trigger('click')
    await nextTick()

    expect(wrapper.find('.listing-detail-overlay').exists()).toBe(false)
    expect(wrapper.emitted('close')).toBeUndefined()
  })
})
