import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'
import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import ListingStatusChip from '@/components/ListingStatusChip.vue'

describe('ListingStatusChip', () => {
  it('renders status label with status marker', () => {
    const wrapper = mount(ListingStatusChip, {
      props: { status: 'pending', label: 'На модерации' },
    })

    expect(wrapper.classes()).toContain('listing-status-chip')
    expect(wrapper.attributes('data-status')).toBe('pending')
    expect(wrapper.text()).toBe('На модерации')
    expect(wrapper.find('.listing-status-chip__mark').exists()).toBe(true)
  })

  it('shows check icon for verified status', () => {
    const wrapper = mount(ListingStatusChip, {
      props: { status: 'verified', label: 'Проверено' },
    })

    expect(wrapper.find('.listing-status-chip__icon').exists()).toBe(true)
    expect(wrapper.text()).toBe('Проверено')
  })

  it('keeps status label on one line without mid-word breaks', () => {
    const source = readFileSync(resolve(__dirname, './ListingStatusChip.vue'), 'utf8')

    expect(source).toContain('white-space: nowrap')
    expect(source).toContain('width: max-content')
    expect(source).toContain('border-radius: 8px')
    expect(source).not.toContain('border-radius: 999px')
    expect(source).not.toContain('overflow-wrap: anywhere')
  })
})
