import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import { createI18n } from 'vue-i18n'
import ListingSellerCard from '@/components/ListingSellerCard.vue'
import ru from '@/locales/ru.json'

describe('ListingSellerCard', () => {
  it('renders seller name, role and contacts', () => {
    const i18n = createI18n({
      legacy: false,
      locale: 'ru',
      messages: { ru },
    })

    const wrapper = mount(ListingSellerCard, {
      props: {
        fromOwner: true,
        seller: {
          id: 7,
          name: 'Иван Продавец',
          photo: null,
          phone: '+375291112233',
          instagram: '@ivan.demo',
          telegram: 'ivan_seller',
          whatsapp: '+375291112233',
          viber: null,
        },
      },
      global: { plugins: [i18n] },
    })

    expect(wrapper.text()).toContain('Иван Продавец')
    expect(wrapper.text()).toContain('Собственник')
    expect(wrapper.text()).toContain('Продавец')
    expect(wrapper.text()).toContain('+375 29 111-22-33')
    expect(wrapper.find('.listing-seller__role--owner').exists()).toBe(true)
    expect(wrapper.find('a[href="tel:+375291112233"]').exists()).toBe(true)
    expect(wrapper.find('a[href="https://instagram.com/ivan.demo"]').exists()).toBe(true)
    expect(wrapper.find('a[href="https://t.me/ivan_seller"]').exists()).toBe(true)
    expect(wrapper.find('a[href="https://wa.me/375291112233"]').exists()).toBe(true)
    expect(wrapper.find('.listing-seller__messenger--instagram').exists()).toBe(true)
    expect(wrapper.find('.listing-seller__messenger--telegram').exists()).toBe(true)
    expect(wrapper.find('.listing-seller__messenger--whatsapp').exists()).toBe(true)
  })

  it('emits open when card is clicked', async () => {
    const i18n = createI18n({
      legacy: false,
      locale: 'ru',
      messages: { ru },
    })

    const wrapper = mount(ListingSellerCard, {
      props: {
        fromOwner: false,
        seller: {
          id: 8,
          name: 'Петров Максим Олегович',
          photo: null,
          phone: null,
          telegram: null,
          whatsapp: null,
          viber: null,
        },
      },
      global: { plugins: [i18n] },
    })

    await wrapper.find('.listing-seller').trigger('click')
    expect(wrapper.emitted('open')).toHaveLength(1)
  })

  it('shows agent role badge for agencies', () => {
    const i18n = createI18n({
      legacy: false,
      locale: 'ru',
      messages: { ru },
    })

    const wrapper = mount(ListingSellerCard, {
      props: {
        fromOwner: false,
        seller: {
          id: 8,
          name: 'Петров Максим Олегович',
          photo: null,
          phone: null,
          telegram: null,
          whatsapp: null,
          viber: null,
        },
      },
      global: { plugins: [i18n] },
    })

    expect(wrapper.find('.listing-seller__role--agent').text()).toContain('Агент')
    expect(wrapper.find('.listing-seller__phone').exists()).toBe(false)
  })

  it('does not emit open when interactive is false', async () => {
    const i18n = createI18n({
      legacy: false,
      locale: 'ru',
      messages: { ru },
    })

    const wrapper = mount(ListingSellerCard, {
      props: {
        interactive: false,
        fromOwner: true,
        seller: {
          id: 7,
          name: 'Иван Продавец',
          photo: null,
          phone: null,
          telegram: null,
          whatsapp: null,
          viber: null,
        },
      },
      global: { plugins: [i18n] },
    })

    await wrapper.get('.listing-seller').trigger('click')
    expect(wrapper.emitted('open')).toBeUndefined()
    expect(wrapper.find('.listing-seller--static').exists()).toBe(true)
  })
})
