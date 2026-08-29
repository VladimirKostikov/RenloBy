import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it } from 'vitest'
import { defineComponent, nextTick } from 'vue'
import FilterSelect from '@/components/FilterSelect.vue'
import { getActiveFilterOverlayId, provideFilterOverlayGroup, resetFilterOverlayGroup } from '@/lib/filterOverlayGroup'

const Wrapper = defineComponent({
  components: { FilterSelect },
  setup() {
    provideFilterOverlayGroup()
    return {
      cityOptions: [{ value: 1, label: 'Минск' }],
      roomOptions: [{ value: 2, label: '2' }],
    }
  },
  template: `
    <div>
      <FilterSelect
        overlay-id="city"
        label="Город"
        :model-value="1"
        :options="cityOptions"
        modifier="city"
      />
      <FilterSelect
        overlay-id="rooms"
        label="Комнаты"
        :model-value="2"
        :options="roomOptions"
        modifier="rooms"
      />
    </div>
  `,
})

describe('filterOverlayGroup', () => {
  beforeEach(() => {
    resetFilterOverlayGroup()
    document.body.innerHTML = ''
  })

  it('keeps only one filter open in a shared group', async () => {
    const wrapper = mount(Wrapper)
    const triggers = wrapper.findAll('button.filter-select__trigger')

    await triggers[0]?.trigger('click')
    await nextTick()
    expect(getActiveFilterOverlayId()).toBe('city')
    expect(wrapper.findAll('.filter-select__trigger--open').length).toBe(1)

    await triggers[1]?.trigger('click')
    await nextTick()
    expect(getActiveFilterOverlayId()).toBe('rooms')
    expect(wrapper.findAll('.filter-select__trigger--open').length).toBe(1)
  })
})
