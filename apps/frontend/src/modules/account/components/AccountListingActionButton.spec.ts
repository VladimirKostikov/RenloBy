import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import AccountListingActionButton from '@/modules/account/components/AccountListingActionButton.vue'

describe('AccountListingActionButton', () => {
  it('renders icon button with accessible label', () => {
    const wrapper = mount(AccountListingActionButton, {
      props: {
        title: 'Удалить',
        variant: 'delete',
      },
    })

    const button = wrapper.get('button')
    expect(button.attributes('aria-label')).toBe('Удалить')
    expect(button.attributes('title')).toBe('Удалить')
    expect(button.classes()).toContain('account-action-btn--delete')
    expect(wrapper.find('svg').exists()).toBe(true)
    expect(button.text()).toBe('')
  })

  it('renders seo variant', () => {
    const wrapper = mount(AccountListingActionButton, {
      props: {
        title: 'SEO',
        variant: 'seo',
      },
    })

    expect(wrapper.get('button').classes()).toContain('account-action-btn--seo')
  })
})
