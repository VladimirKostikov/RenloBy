import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { describe, expect, it } from 'vitest'
import { createI18n } from 'vue-i18n'
import CatalogSidebarFilters from '@/components/catalog/CatalogSidebarFilters.vue'
import ru from '@/locales/ru.json'

function mountSidebar(dealType: 'rent' | 'sale' = 'rent', commercialCatalog = false) {
  const i18n = createI18n({
    legacy: false,
    locale: 'ru',
    messages: { ru },
  })

  setActivePinia(createPinia())

  return mount(CatalogSidebarFilters, {
    props: { dealType, commercialCatalog },
    global: {
      plugins: [i18n],
    },
  })
}

describe('CatalogSidebarFilters', () => {
  it('renders compact sticky sidebar root', () => {
    const wrapper = mountSidebar('rent')

    expect(wrapper.find('.catalog-sidebar.catalog-sidebar--compact').exists()).toBe(true)
    expect(wrapper.find('aside.catalog-sidebar').exists()).toBe(true)
  })

  it('marks floor and rooms selects as placeholder when value is empty', () => {
    const wrapper = mountSidebar('rent')

    const placeholders = wrapper.findAll('.catalog-sidebar__select--placeholder')
    expect(placeholders).toHaveLength(2)
    expect(placeholders[0].text()).toContain('Любой')
    expect(placeholders[1].text()).toContain('Любая')
  })

  it('includes studio in rooms filter options', () => {
    const wrapper = mountSidebar('rent')
    const roomsSelect = wrapper.findAllComponents({ name: 'FilterSelect' }).find((item) =>
      String(item.props('overlayId')).includes('rooms'),
    )

    expect(roomsSelect).toBeTruthy()
    const options = roomsSelect!.props('options') as Array<{ value: string | number; label: string }>
    expect(options.some((option) => option.value === 0 && option.label === 'Студия')).toBe(true)
  })
})
