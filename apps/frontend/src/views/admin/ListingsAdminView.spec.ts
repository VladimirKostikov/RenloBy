import { flushPromises, mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createI18n } from 'vue-i18n'
import ListingsAdminView from '@/views/admin/ListingsAdminView.vue'
import ru from '@/locales/ru.json'

const listMock = vi.fn()
const updateMock = vi.fn()
const createMock = vi.fn()
const requestToggle = vi.fn()
const testModeState = {
  isTest: false,
  requestToggle,
}

vi.mock('@/api/admin', () => ({
  adminListings: {
    list: (...args: unknown[]) => listMock(...args),
    create: (...args: unknown[]) => createMock(...args),
    update: (...args: unknown[]) => updateMock(...args),
    remove: vi.fn(),
    get: vi.fn(),
  },
}))

vi.mock('@/api/adminMedia', () => ({
  uploadAdminMedia: vi.fn(),
  MediaFileTooLargeError: class MediaFileTooLargeError extends Error {},
}))

vi.mock('@/stores/adminTestMode', () => ({
  useAdminTestModeStore: () => testModeState,
}))

describe('ListingsAdminView moderation', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    document.body.innerHTML = ''
    testModeState.isTest = false
    listMock.mockResolvedValue({
      items: [
        {
          id: 11,
          status: 'pending',
          dealType: 'sale',
          listingType: 'apartment',
          price: 1000,
          rooms: 2,
          area: 50,
          floor: 1,
          totalFloors: 5,
          cityName: 'Минск',
          districtName: 'Центр',
          metroStationName: null,
          address: 'ул. Тест, 1',
          latitude: 53.9,
          longitude: 27.5,
          userId: 2,
          verified: false,
          aiGoodPrice: false,
          isTest: true,
          images: ['/uploads/listings/a.jpg'],
        },
      ],
      total: 1,
      page: 1,
      limit: 100,
    })
    updateMock.mockResolvedValue({})
    createMock.mockResolvedValue({})
  })

  it('loads pending queue by default and exposes status in edit form', async () => {
    const i18n = createI18n({ legacy: false, locale: 'ru', messages: { ru } })
    const wrapper = mount(ListingsAdminView, {
      attachTo: document.body,
      global: { plugins: [i18n] },
    })
    await flushPromises()

    expect(listMock).toHaveBeenCalledWith(expect.objectContaining({ status: 'pending' }))
    expect(wrapper.text()).toContain('ул. Тест, 1')
    expect(wrapper.find('.listings-admin__filter--active').text()).toContain('На модерации')
    expect(wrapper.find('.listing-status-chip[data-status="pending"]').exists()).toBe(true)
    expect(wrapper.find('.listing-status-chip[data-status="test"]').exists()).toBe(true)

    await wrapper.get('.admin-table__btn').trigger('click')
    await flushPromises()

    const statusSelect = document.querySelector('select.admin-form__control') as HTMLSelectElement | null
    expect(statusSelect).not.toBeNull()
    expect(statusSelect?.value).toBe('pending')
    expect(document.querySelector('.listing-images-editor')).not.toBeNull()
    expect(document.querySelector('.listing-images-editor img')?.getAttribute('src')).toBe(
      '/uploads/listings/a.jpg',
    )

    statusSelect!.value = 'published'
    statusSelect!.dispatchEvent(new Event('input'))
    statusSelect!.dispatchEvent(new Event('change'))
    const form = document.querySelector('form.admin-form') as HTMLFormElement
    form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }))
    await flushPromises()

    expect(updateMock).toHaveBeenCalledWith(
      11,
      expect.objectContaining({
        status: 'published',
        images: ['/uploads/listings/a.jpg'],
      }),
    )

    wrapper.unmount()
  })

  it('can reject listing from edit form', async () => {
    const i18n = createI18n({ legacy: false, locale: 'ru', messages: { ru } })
    const wrapper = mount(ListingsAdminView, {
      attachTo: document.body,
      global: { plugins: [i18n] },
    })
    await flushPromises()

    await wrapper.get('.admin-table__btn').trigger('click')
    await flushPromises()

    const statusSelect = document.querySelector('select.admin-form__control') as HTMLSelectElement
    statusSelect.value = 'rejected'
    statusSelect.dispatchEvent(new Event('input'))
    statusSelect.dispatchEvent(new Event('change'))
    const form = document.querySelector('form.admin-form') as HTMLFormElement
    form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }))
    await flushPromises()

    expect(updateMock).toHaveBeenCalledWith(
      11,
      expect.objectContaining({ status: 'rejected' }),
    )

    wrapper.unmount()
  })

  it('hints to leave test mode when pending queue is empty', async () => {
    testModeState.isTest = true
    listMock.mockResolvedValue({ items: [], total: 0, page: 1, limit: 100 })

    const i18n = createI18n({ legacy: false, locale: 'ru', messages: { ru } })
    const wrapper = mount(ListingsAdminView, {
      global: { plugins: [i18n] },
    })
    await flushPromises()

    expect(wrapper.text()).toContain('Тестовый режим скрывает реальные объявления на модерации')
    await wrapper.get('.listings-admin__hint-btn').trigger('click')
    expect(requestToggle).toHaveBeenCalledWith(false)

    wrapper.unmount()
  })
})
