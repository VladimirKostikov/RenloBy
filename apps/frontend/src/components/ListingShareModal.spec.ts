import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import { createI18n } from 'vue-i18n'
import ListingShareModal from '@/components/ListingShareModal.vue'
import {
  LISTING_DETAIL_OVERLAY_Z_INDEX,
  LISTING_NESTED_MODAL_Z_INDEX,
} from '@/lib/listingModalZIndex'
import ru from '@/locales/ru.json'

describe('ListingShareModal', () => {
  it('renders above listing detail overlay', () => {
    const i18n = createI18n({ legacy: false, locale: 'ru', messages: { ru } })
    const wrapper = mount(ListingShareModal, {
      props: {
        open: true,
        url: 'https://renlo.by/listing/1',
        title: 'Test',
      },
      global: {
        plugins: [i18n],
        stubs: { teleport: true },
      },
    })

    const overlay = wrapper.find('.listing-share-modal')
    expect(overlay.attributes('style')).toContain(`z-index: ${LISTING_NESTED_MODAL_Z_INDEX}`)
    expect(LISTING_NESTED_MODAL_Z_INDEX).toBeGreaterThan(LISTING_DETAIL_OVERLAY_Z_INDEX)
  })
})
