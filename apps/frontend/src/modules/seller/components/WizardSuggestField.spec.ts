import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import WizardSuggestField from '@/modules/seller/components/WizardSuggestField.vue'

describe('WizardSuggestField', () => {
  it('shows filtered suggestions and emits selection', async () => {
    const wrapper = mount(WizardSuggestField, {
      props: {
        modelValue: 'Ми',
        label: 'Город',
        options: ['Минск', 'Могилёв', 'Брест'],
      },
    })

    await wrapper.get('input').trigger('focus')
    expect(wrapper.findAll('.wizard-suggest__option')).toHaveLength(1)
    expect(wrapper.get('.wizard-suggest__option').text()).toBe('Минск')

    await wrapper.get('.wizard-suggest__option').trigger('mousedown')
    expect(wrapper.emitted('update:modelValue')?.at(-1)).toEqual(['Минск'])
  })
})
