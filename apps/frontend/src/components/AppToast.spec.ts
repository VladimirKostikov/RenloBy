import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it } from 'vitest'
import AppToast from '@/components/AppToast.vue'
import { useToastStore } from '@/stores/toast'

describe('AppToast', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('renders toast message when visible', async () => {
    const toast = useToastStore()
    toast.show('Добавлено к сравнению')

    const wrapper = mount(AppToast, {
      attachTo: document.body,
    })

    await wrapper.vm.$nextTick()

    expect(document.body.textContent).toContain('Добавлено к сравнению')
    expect(document.body.querySelector('.app-toast')).not.toBeNull()

    wrapper.unmount()
  })
})
