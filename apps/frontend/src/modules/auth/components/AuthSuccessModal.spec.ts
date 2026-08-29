import { mount } from '@vue/test-utils'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import AuthSuccessModal from '@/modules/auth/components/AuthSuccessModal.vue'
import { useAuthSuccessModal } from '@/modules/auth/composables/useAuthSuccessModal'
import { i18n } from '@/modules/locale'

describe('AuthSuccessModal', () => {
  beforeEach(() => {
    useAuthSuccessModal().close()
    document.body.innerHTML = ''
    vi.useFakeTimers()
  })

  afterEach(() => {
    vi.useRealTimers()
  })

  it('shows red checkmark and login success text', async () => {
    useAuthSuccessModal().open('login')

    mount(AuthSuccessModal, {
      attachTo: document.body,
      global: { plugins: [i18n] },
    })

    expect(document.body.querySelector('.auth-success')).not.toBeNull()
    expect(document.body.querySelector('.auth-success__icon')).not.toBeNull()
    expect(document.body.textContent).toContain(i18n.global.t('auth.successLoginTitle'))
    expect(document.body.textContent).toContain(i18n.global.t('auth.successLoginMessage'))
  })

  it('closes after accept click', async () => {
    useAuthSuccessModal().open('login')

    mount(AuthSuccessModal, {
      attachTo: document.body,
      global: { plugins: [i18n] },
    })

    const button = document.body.querySelector('.auth-success__ok') as HTMLButtonElement
    button.click()

    expect(useAuthSuccessModal().isOpen.value).toBe(false)
  })

  it('auto-closes after timeout', async () => {
    useAuthSuccessModal().open('register')

    mount(AuthSuccessModal, {
      attachTo: document.body,
      global: { plugins: [i18n] },
    })

    expect(useAuthSuccessModal().isOpen.value).toBe(true)
    vi.advanceTimersByTime(2800)
    expect(useAuthSuccessModal().isOpen.value).toBe(false)
  })
})
