import { mount, flushPromises } from '@vue/test-utils'
import { afterEach, describe, expect, it } from 'vitest'
import { createI18n } from 'vue-i18n'
import { nextTick } from 'vue'
import ListingChatModal from '@/components/ListingChatModal.vue'
import SocialBrandIcon from '@/components/SocialBrandIcon.vue'
import {
  LISTING_DETAIL_OVERLAY_Z_INDEX,
  LISTING_NESTED_MODAL_Z_INDEX,
} from '@/lib/listingModalZIndex'
import ru from '@/locales/ru.json'

function mountModal(props: Record<string, unknown> = {}) {
  const i18n = createI18n({
    legacy: false,
    locale: 'ru',
    messages: { ru },
  })

  return mount(ListingChatModal, {
    props: {
      text: 'https://donmap.by/listing/1',
      open: true,
      ...props,
    },
    global: {
      plugins: [i18n],
      stubs: {
        teleport: true,
      },
    },
  })
}

describe('ListingChatModal', () => {
  afterEach(() => {
    document.body.innerHTML = ''
  })

  it('shows centered circular brand logos without text labels', () => {
    const wrapper = mountModal()

    const items = wrapper.findAll('.listing-chat-modal__item')
    expect(items).toHaveLength(3)
    expect(wrapper.findAllComponents(SocialBrandIcon)).toHaveLength(3)
    expect(wrapper.find('.listing-chat-modal__list').classes()).not.toContain('column')

    for (const item of items) {
      expect(item.text().trim()).toBe('')
      expect(item.attributes('aria-label')).toBeTruthy()
    }

    expect(wrapper.text()).not.toContain('WhatsApp')
    expect(wrapper.text()).not.toContain('Telegram')
    expect(wrapper.text()).not.toContain('Viber')
    expect(wrapper.text()).toContain('Связаться с продавцом')

    wrapper.unmount()
  })

  it('toggles visibility with open prop and keeps transition classes', async () => {
    const wrapper = mountModal({ open: false })

    expect(wrapper.find('.listing-chat-modal').exists()).toBe(false)

    await wrapper.setProps({ open: true })
    await nextTick()
    await flushPromises()

    expect(wrapper.find('.listing-chat-modal').exists()).toBe(true)
    expect(wrapper.html()).toContain('listing-chat-modal')

    await wrapper.setProps({ open: false })
    await nextTick()
    await flushPromises()

    expect(wrapper.find('.listing-chat-modal').exists()).toBe(false)

    wrapper.unmount()
  })

  it('stacks above listing detail overlay', () => {
    const wrapper = mountModal()
    const style = wrapper.find('.listing-chat-modal').attributes('style') ?? ''
    expect(style).toContain(`z-index: ${LISTING_NESTED_MODAL_Z_INDEX}`)
    expect(LISTING_NESTED_MODAL_Z_INDEX).toBeGreaterThan(LISTING_DETAIL_OVERLAY_Z_INDEX)
    wrapper.unmount()
  })
})
