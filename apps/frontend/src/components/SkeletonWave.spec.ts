import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import SkeletonWave from '@/components/SkeletonWave.vue'

describe('SkeletonWave', () => {
  it('renders requested number of lines', () => {
    const wrapper = mount(SkeletonWave, {
      props: { lines: 4 },
    })

    expect(wrapper.findAll('.skeleton-wave__line')).toHaveLength(4)
  })
})
