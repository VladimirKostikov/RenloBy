import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import InfoPageBody from '@/modules/info/components/InfoPageBody.vue'

describe('InfoPageBody', () => {
  it('renders headings and bullet lists', () => {
    const wrapper = mount(InfoPageBody, {
      props: {
        body: '## Раздел\n\n- Пункт один\n- Пункт два',
      },
    })

    expect(wrapper.find('.info-page-body__heading').text()).toBe('Раздел')
    expect(wrapper.findAll('.info-page-body__list li')).toHaveLength(2)
  })
})
