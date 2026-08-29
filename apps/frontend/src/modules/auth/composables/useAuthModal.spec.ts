import { describe, expect, it } from 'vitest'
import { useAuthModal } from '@/modules/auth/composables/useAuthModal'

describe('useAuthModal', () => {
  it('opens login modal with redirect and callback', () => {
    const modal = useAuthModal()
    const onSuccess = () => undefined

    modal.close()
    modal.openLogin({ redirect: '/admin', onSuccess })

    expect(modal.isOpen.value).toBe(true)
    expect(modal.mode.value).toBe('login')
    expect(modal.redirectPath.value).toBe('/admin')

    const pending = modal.consumeSuccessCallback()
    expect(pending.redirect).toBe('/admin')
    expect(pending.callback).toBe(onSuccess)
  })

  it('switches to register mode', () => {
    const modal = useAuthModal()

    modal.openRegister()
    expect(modal.mode.value).toBe('register')

    modal.setMode('login')
    expect(modal.mode.value).toBe('login')
  })

  it('closes modal and clears state', () => {
    const modal = useAuthModal()

    modal.openLogin({ redirect: '/sale' })
    modal.close()

    expect(modal.isOpen.value).toBe(false)
    expect(modal.redirectPath.value).toBeNull()
  })
})
