import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import InfoNavIcon from '@/modules/info/components/InfoNavIcon.vue'
import type { InfoPageCategory } from '@/types/info'

const categories: Array<InfoPageCategory | 'info'> = [
  'info',
  'buyers',
  'sellers',
  'renters',
  'deal_safety',
  'faq',
  'support',
  'offer',
  'privacy',
  'personal_data',
]

describe('InfoNavIcon', () => {
  it.each(categories)('renders icon for %s', (name) => {
    const wrapper = mount(InfoNavIcon, {
      props: { name },
    })

    expect(wrapper.find('svg.info-nav-icon').exists()).toBe(true)
    expect(wrapper.find('svg').element.children.length).toBeGreaterThan(0)
  })
})
