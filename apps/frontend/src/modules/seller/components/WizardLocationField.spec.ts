import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import { createI18n } from 'vue-i18n'
import WizardLocationField from '@/modules/seller/components/WizardLocationField.vue'
import ru from '@/locales/ru.json'

describe('WizardLocationField', () => {
  it('disables input and clears value when absent is checked', async () => {
    const i18n = createI18n({ legacy: false, locale: 'ru', messages: { ru } })
    const wrapper = mount(WizardLocationField, {
      props: {
        label: 'Район',
        modelValue: 'Центр',
        absent: false,
        placeholder: 'Название района',
      },
      global: { plugins: [i18n] },
    })

    expect(wrapper.text()).toContain('Отсутствует')
    const input = wrapper.get('input[type="text"]')
    expect(input.attributes('disabled')).toBeUndefined()

    await wrapper.get('input[type="checkbox"]').setValue(true)
    expect(wrapper.emitted('update:absent')?.[0]).toEqual([true])
    expect(wrapper.emitted('update:modelValue')?.[0]).toEqual([''])
  })

  it('hides absent checkbox when allowAbsent is false', () => {
    const i18n = createI18n({ legacy: false, locale: 'ru', messages: { ru } })
    const wrapper = mount(WizardLocationField, {
      props: {
        label: 'Город',
        modelValue: 'Минск',
        allowAbsent: false,
      },
      global: { plugins: [i18n] },
    })

    expect(wrapper.find('input[type="checkbox"]').exists()).toBe(false)
    expect(wrapper.text()).not.toContain('Отсутствует')
  })
})
