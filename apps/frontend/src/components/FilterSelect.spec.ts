import { mount } from '@vue/test-utils'
import { describe, expect, it, beforeEach } from 'vitest'
import FilterSelect from '@/components/FilterSelect.vue'
import { resetFilterOverlayGroup } from '@/lib/filterOverlayGroup'

describe('FilterSelect', () => {
  beforeEach(() => {
    resetFilterOverlayGroup()
    document.body.innerHTML = ''
  })

  it('emits change with selected value', async () => {
    const wrapper = mount(FilterSelect, {
      props: {
        overlayId: 'city',
        label: 'Город',
        modelValue: 1,
        options: [
          { value: 1, label: 'Минск' },
          { value: 2, label: 'Гродно' },
        ],
        modifier: 'city',
      },
    })

    await wrapper.get('button.filter-select__trigger').trigger('click')
    const options = document.body.querySelectorAll('.filter-select__option')
    expect(options.length).toBe(2)

    await (options[1] as HTMLButtonElement).click()

    expect(wrapper.emitted('change')?.[0]).toEqual([2])
    expect(wrapper.emitted('update:modelValue')?.[0]).toEqual([2])
  })

  it('shows empty value when cleared instead of first option label', () => {
    const wrapper = mount(FilterSelect, {
      props: {
        overlayId: 'district',
        label: 'Район',
        modelValue: '',
        options: [
          { value: '', label: 'Все районы' },
          { value: 1, label: 'Центральный' },
        ],
        modifier: 'district',
      },
    })

    expect(wrapper.get('.filter-chip__value').text()).toBe('')
  })

  it('shows placeholder when provided and value is empty', () => {
    const wrapper = mount(FilterSelect, {
      props: {
        overlayId: 'floor',
        modelValue: '',
        placeholder: 'Любой',
        options: [
          { value: '', label: 'Любой' },
          { value: 1, label: '1' },
        ],
      },
    })

    expect(wrapper.get('.filter-chip__value').text()).toBe('Любой')
  })

  it('clears value when empty option is selected', async () => {
    const wrapper = mount(FilterSelect, {
      props: {
        overlayId: 'rooms',
        label: 'Комнаты',
        modelValue: 2,
        options: [
          { value: '', label: 'Любые' },
          { value: 2, label: '2' },
        ],
        modifier: 'rooms',
      },
    })

    await wrapper.get('button.filter-select__trigger').trigger('click')
    await (document.body.querySelector('.filter-select__option') as HTMLButtonElement).click()

    expect(wrapper.emitted('change')?.[0]).toEqual([undefined])
  })
})
