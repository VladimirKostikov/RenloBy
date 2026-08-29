import { mount } from '@vue/test-utils'
import { afterEach, describe, expect, it } from 'vitest'
import { createI18n } from 'vue-i18n'
import ListingNearbyPanel from '@/components/ListingNearbyPanel.vue'
import ru from '@/locales/ru.json'
import type { ListingDto } from '@/types'
import type { InfrastructurePoi } from '@/types/infrastructure'

const listing = {
  id: 1,
  latitude: 53.9,
  longitude: 27.56,
} as ListingDto

const places: InfrastructurePoi[] = [
  {
    id: 'shop-1',
    type: 'shop',
    name: 'Магазин',
    address: 'ул. Тестовая, 1',
    latitude: 53.901,
    longitude: 27.561,
  },
]

function mountPanel(props: Record<string, unknown> = {}) {
  const i18n = createI18n({
    legacy: false,
    locale: 'ru',
    messages: { ru },
  })

  return mount(ListingNearbyPanel, {
    props: {
      listing,
      places,
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

describe('ListingNearbyPanel', () => {
  afterEach(() => {
    document.body.innerHTML = ''
  })

  it('renders nearby places list with yandex maps link', () => {
    const wrapper = mountPanel()

    expect(wrapper.text()).toContain('Магазин')
    expect(wrapper.findAll('.nearby-panel__item')).toHaveLength(1)
    expect(wrapper.find('.nearby-panel__item-meta a').attributes('href')).toContain('yandex.ru/maps')
    expect(wrapper.text()).toContain(ru.listingDetail.openInYandex)

    wrapper.unmount()
  })

  it('marks monochrome infra icons for dark theme invert', () => {
    const wrapper = mountPanel({
      places: [
        ...places,
        {
          id: 'park-1',
          type: 'park',
          name: 'Парк',
          address: 'ул. Зелёная, 2',
          latitude: 53.902,
          longitude: 27.562,
        } satisfies InfrastructurePoi,
      ],
    })

    const icons = wrapper.findAll('.nearby-panel__item img')
    expect(icons).toHaveLength(2)
    expect(icons[0].attributes('data-theme-ink')).toBeDefined()
    expect(icons[1].attributes('data-theme-ink')).toBeUndefined()

    wrapper.unmount()
  })

  it('emits close from backdrop and button', async () => {
    const wrapper = mountPanel()

    await wrapper.find('.nearby-panel__close').trigger('click')
    expect(wrapper.emitted('close')?.length).toBe(1)

    await wrapper.find('.nearby-overlay').trigger('click')
    expect(wrapper.emitted('close')?.length).toBe(2)

    wrapper.unmount()
  })
})
