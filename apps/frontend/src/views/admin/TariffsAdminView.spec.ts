import { flushPromises, mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createI18n } from 'vue-i18n'
import AdminCrudForm from '@/modules/admin/components/AdminCrudForm.vue'
import TariffsAdminView from '@/views/admin/TariffsAdminView.vue'

const update = vi.fn().mockResolvedValue({})
const remove = vi.fn().mockResolvedValue(undefined)
const list = vi.fn()

vi.mock('@/api/admin', () => ({
  adminTariffs: {
    list: (...args: unknown[]) => list(...args),
    update: (...args: unknown[]) => update(...args),
    create: vi.fn(),
    remove: (...args: unknown[]) => remove(...args),
  },
}))

vi.mock('@/stores/adminTestMode', () => ({
  useAdminTestModeStore: () => ({
    isTest: true,
  }),
}))

describe('TariffsAdminView', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    list.mockResolvedValue([
      {
        id: 3,
        code: 'standard',
        priceUsd: '19.90',
        priceByn: '65.00',
        priceRub: '1850.00',
        currency: 'USD',
        isPopular: true,
        sortOrder: 20,
        isTest: true,
      },
    ])
  })

  it('edits tariff prices in usd byn and rub', async () => {
    const i18n = createI18n({
      legacy: false,
      locale: 'ru',
      messages: {
        ru: {
          admin: {
            tariffs: 'Тарифы',
            edit: 'Редактировать',
            delete: 'Удалить',
            view: 'Просмотреть',
            save: 'Сохранить',
            cancel: 'Отмена',
            confirmDelete: 'Удалить?',
            loading: 'Загрузка...',
            empty: 'Нет данных',
            actions: 'Действия',
            yes: 'Да',
            no: 'Нет',
            fields: {
              id: 'ID',
              tariffCode: 'Код',
              priceUsd: 'USD',
              priceByn: 'BYN',
              priceRub: 'RUB',
              isPopular: 'Популярный',
              sortOrder: 'Порядок',
              isTest: 'Тест',
            },
          },
        },
      },
    })

    const wrapper = mount(TariffsAdminView, {
      global: {
        plugins: [i18n],
        stubs: {
          AdminConfirmDialog: true,
        },
      },
    })
    await flushPromises()

    expect(wrapper.text()).toContain('65.00')
    expect(wrapper.text()).toContain('1850.00')

    await wrapper.findAll('.admin-action-btn--edit')[0]?.trigger('click')
    await flushPromises()

    const form = wrapper.findComponent(AdminCrudForm)
    expect(form.exists()).toBe(true)
    expect(form.props('modelValue')).toMatchObject({
      priceUsd: '19.90',
      priceByn: '65.00',
      priceRub: '1850.00',
    })

    await form.vm.$emit('save', {
      priceUsd: '21.00',
      priceByn: '70.00',
      priceRub: '1950.00',
      isPopular: true,
      sortOrder: 20,
      isTest: true,
    })
    await flushPromises()

    expect(update).toHaveBeenCalledWith(3, {
      priceUsd: '21.00',
      priceByn: '70.00',
      priceRub: '1950.00',
      isPopular: true,
      sortOrder: 20,
      isTest: true,
    })
  })
})
