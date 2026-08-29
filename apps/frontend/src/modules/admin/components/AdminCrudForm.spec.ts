import { mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'
import { createI18n } from 'vue-i18n'
import AdminCrudForm from '@/modules/admin/components/AdminCrudForm.vue'
import ru from '@/locales/ru.json'

vi.mock('@/stores/adminTestMode', () => ({
  useAdminTestModeStore: () => ({ isTest: false }),
}))

describe('AdminCrudForm selects', () => {
  it('renders styled select control', () => {
    const i18n = createI18n({ legacy: false, locale: 'ru', messages: { ru } })
    const wrapper = mount(AdminCrudForm, {
      props: {
        omitTestField: true,
        fields: [
          {
            key: 'status',
            label: 'Статус',
            type: 'select',
            options: [
              { value: 'pending', label: 'На модерации' },
              { value: 'published', label: 'Одобрено' },
            ],
          },
        ],
        modelValue: { status: 'pending' },
      },
      global: { plugins: [i18n] },
    })

    const select = wrapper.get('select.admin-form__control')
    expect(select.exists()).toBe(true)
    expect((select.element as HTMLSelectElement).value).toBe('pending')
    expect(select.classes()).toContain('admin-form__control')
  })

  it('allows decimal values in number fields', () => {
    const i18n = createI18n({ legacy: false, locale: 'ru', messages: { ru } })
    const wrapper = mount(AdminCrudForm, {
      props: {
        omitTestField: true,
        fields: [
          { key: 'latitude', label: 'Широта', type: 'number' },
          { key: 'longitude', label: 'Долгота', type: 'number' },
        ],
        modelValue: { latitude: 53.9045, longitude: 27.5615 },
      },
      global: { plugins: [i18n] },
    })

    const inputs = wrapper.findAll('input[type="number"]')
    expect(inputs).toHaveLength(2)
    inputs.forEach((input) => {
      expect(input.attributes('step')).toBe('any')
    })
    expect((inputs[0].element as HTMLInputElement).value).toBe('53.9045')
    expect((inputs[1].element as HTMLInputElement).value).toBe('27.5615')
  })
})
