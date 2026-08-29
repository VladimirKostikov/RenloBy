import { flushPromises, mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createI18n } from 'vue-i18n'
import ListingRequestModal, { REQUEST_MESSAGE_MIN_LENGTH } from '@/components/ListingRequestModal.vue'
import {
  LISTING_DETAIL_OVERLAY_Z_INDEX,
  LISTING_NESTED_MODAL_Z_INDEX,
} from '@/lib/listingModalZIndex'

vi.mock('@/api/listingRequests', () => ({
  createListingRequest: vi.fn(),
}))

import { createListingRequest } from '@/api/listingRequests'

function mountModal() {
  const i18n = createI18n({
    legacy: false,
    locale: 'ru',
    messages: {
      ru: {
        listingDetail: {
          close: 'Закрыть',
          leaveRequestTitle: 'Оставить заявку',
          leaveRequestName: 'Имя',
          leaveRequestNamePlaceholder: 'Как к вам обращаться',
          leaveRequestPhone: 'Телефон',
          leaveRequestPhoneBy: 'РБ',
          leaveRequestPhoneRu: 'РФ',
          leaveRequestPhonePlaceholder: '+375 29 000-00-00',
          leaveRequestPhonePlaceholderRu: '+7 900 000-00-00',
          leaveRequestMessage: 'Сообщение',
          leaveRequestMessagePlaceholder: 'Напишите, чем интересуетесь',
          leaveRequestMessageCounter: '{n} / {min}',
          leaveRequestValidation: 'Проверьте телефон и сообщение',
          leaveRequestSubmit: 'Отправить',
          leaveRequestSending: 'Отправка...',
          leaveRequestError: 'Ошибка',
          leaveRequestThanks: 'Заявка отправлена. Продавец получит уведомление.',
        },
      },
    },
  })

  return mount(ListingRequestModal, {
    props: { open: true, listingId: 42 },
    global: {
      plugins: [i18n],
      stubs: {
        teleport: true,
      },
    },
  })
}

describe('ListingRequestModal', () => {
  beforeEach(() => {
    vi.mocked(createListingRequest).mockReset()
  })

  it('submits valid Belarus phone request', async () => {
    vi.mocked(createListingRequest).mockResolvedValue({
      id: 1,
      listingId: 42,
      name: 'Анна',
      phone: '+375291112233',
      message: 'Хочу посмотреть квартиру',
      status: 'new',
      createdAt: '2026-07-16T00:00:00+00:00',
      isTest: false,
    })

    const wrapper = mountModal()
    await wrapper.find('#listing-request-name').setValue('Анна')
    const phoneInput = wrapper.find('#listing-request-phone')
    await phoneInput.setValue('291112233')
    await phoneInput.trigger('input')
    await wrapper.find('#listing-request-message').setValue('Хочу посмотреть квартиру')
    await wrapper.find('.listing-request-modal__submit').trigger('click')
    await flushPromises()

    expect(createListingRequest).toHaveBeenCalledWith(42, {
      phone: '+375291112233',
      message: 'Хочу посмотреть квартиру',
      name: 'Анна',
    })
    expect(wrapper.text()).toContain('Заявка отправлена')
  })

  it('switches to Russia mask', async () => {
    const wrapper = mountModal()
    await wrapper.findAll('.listing-request-modal__country')[1].trigger('click')
    const phoneInput = wrapper.find('#listing-request-phone')
    await phoneInput.setValue('9001234567')
    await phoneInput.trigger('input')
    expect((phoneInput.element as HTMLInputElement).value).toBe('+7 900 123-45-67')
  })

  it('blocks short message', async () => {
    const wrapper = mountModal()
    const phoneInput = wrapper.find('#listing-request-phone')
    await phoneInput.setValue('291112233')
    await phoneInput.trigger('input')
    await wrapper.find('#listing-request-message').setValue('коротко')
    await wrapper.find('.listing-request-modal__submit').trigger('click')
    await flushPromises()

    expect(createListingRequest).not.toHaveBeenCalled()
    expect(REQUEST_MESSAGE_MIN_LENGTH).toBe(10)
  })

  it('stacks above listing detail overlay', () => {
    expect(LISTING_NESTED_MODAL_Z_INDEX).toBeGreaterThan(LISTING_DETAIL_OVERLAY_Z_INDEX)
  })
})
