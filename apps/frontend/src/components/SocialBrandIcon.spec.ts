import { mount, flushPromises } from '@vue/test-utils'
import { afterEach, describe, expect, it } from 'vitest'
import { createI18n } from 'vue-i18n'
import { nextTick } from 'vue'
import ListingShareModal from '@/components/ListingShareModal.vue'
import SocialBrandIcon from '@/components/SocialBrandIcon.vue'
import ru from '@/locales/ru.json'

describe('SocialBrandIcon', () => {
  it('renders svg for each network', () => {
    for (const name of ['telegram', 'whatsapp', 'viber', 'vk', 'ok', 'instagram'] as const) {
      const wrapper = mount(SocialBrandIcon, { props: { name } })
      expect(wrapper.find('svg').exists()).toBe(true)
      expect(wrapper.find('path').exists()).toBe(true)
    }
  })

  it('uses correct instagram mark path', () => {
    const wrapper = mount(SocialBrandIcon, { props: { name: 'instagram' } })
    const path = wrapper.find('path')
    const d = path.attributes('d') ?? ''
    expect(path.attributes('fill-rule')).toBe('evenodd')
    expect(d).toContain('M12 0C8.74')
    expect(d).toContain('a6.162')
    expect(d).toContain('a1.44')
  })

  it('uses official viber mark path', () => {
    const wrapper = mount(SocialBrandIcon, { props: { name: 'viber' } })
    const d = wrapper.find('path').attributes('d') ?? ''
    expect(d.startsWith('M11.4 0C9.473')).toBe(true)
    expect(d).toContain('6.12 20.36')
  })

  it('uses official odnoklassniki mark path', () => {
    const wrapper = mount(SocialBrandIcon, { props: { name: 'ok' } })
    const d = wrapper.find('path').attributes('d') ?? ''
    expect(d.startsWith('M12 0a6.2')).toBe(true)
    expect(d).toContain('L12 20.066')
    expect(d).toContain('8.34 0')
  })
})

function mountShareModal(props: Record<string, unknown> = {}) {
  const i18n = createI18n({
    legacy: false,
    locale: 'ru',
    messages: { ru },
  })

  return mount(ListingShareModal, {
    props: {
      open: true,
      url: 'https://donmap.by/listing/1',
      title: 'Квартира',
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

describe('ListingShareModal', () => {
  afterEach(() => {
    document.body.innerHTML = ''
  })

  it('shows only circular brand logos without text labels', () => {
    const wrapper = mountShareModal()

    const items = wrapper.findAll('.listing-share-modal__item')
    expect(items).toHaveLength(5)
    expect(wrapper.findAllComponents(SocialBrandIcon)).toHaveLength(5)

    for (const item of items) {
      expect(item.text().trim()).toBe('')
      expect(item.attributes('aria-label')).toBeTruthy()
    }

    expect(wrapper.text()).not.toContain('Telegram')
    expect(wrapper.text()).not.toContain('WhatsApp')
    expect(wrapper.text()).not.toContain('Viber')
    expect(wrapper.text()).toContain('Скопировать ссылку')

    wrapper.unmount()
  })

  it('animates open and close via open prop', async () => {
    const wrapper = mountShareModal({ open: false })

    expect(wrapper.find('.listing-share-modal').exists()).toBe(false)

    await wrapper.setProps({ open: true })
    await nextTick()
    await flushPromises()

    expect(wrapper.find('.listing-share-modal').exists()).toBe(true)

    await wrapper.setProps({ open: false })
    await nextTick()
    await flushPromises()

    expect(wrapper.find('.listing-share-modal').exists()).toBe(false)

    wrapper.unmount()
  })
})
