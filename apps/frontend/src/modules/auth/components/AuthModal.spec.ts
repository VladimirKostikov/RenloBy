import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import AuthModal from '@/modules/auth/components/AuthModal.vue'
import { useAuthModal } from '@/modules/auth/composables/useAuthModal'
import { useAuthSuccessModal } from '@/modules/auth/composables/useAuthSuccessModal'
import { i18n } from '@/modules/locale'
import * as authApi from '@/api/auth'

vi.mock('@/api/auth', () => ({
  login: vi.fn(),
  register: vi.fn(),
  logout: vi.fn(),
  fetchMe: vi.fn(),
}))

const routerPush = vi.fn()

vi.mock('vue-router', async () => {
  const actual = await vi.importActual<typeof import('vue-router')>('vue-router')
  return {
    ...actual,
    useRouter: () => ({
      push: routerPush,
    }),
  }
})

function mountAuthModal() {
  const container = document.createElement('div')
  document.body.appendChild(container)

  return mount(AuthModal, {
    attachTo: container,
    global: {
      plugins: [i18n],
    },
  })
}

describe('AuthModal', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
    useAuthModal().close()
    useAuthSuccessModal().close()
    document.body.innerHTML = ''
  })

  it('renders login form when opened in login mode', async () => {
    useAuthModal().openLogin()

    mountAuthModal()

    expect(document.body.querySelector('.auth-modal')).not.toBeNull()
    expect(document.body.textContent).toContain('Вход')
    expect(document.body.querySelector('#auth-modal-email')).not.toBeNull()
    expect((document.body.querySelector('#auth-modal-email') as HTMLInputElement).placeholder).toBe('Введите email')
    expect(document.body.querySelectorAll('.auth-modal__tab-icon')).toHaveLength(2)
    expect(document.body.querySelectorAll('.auth-modal__control-icon').length).toBeGreaterThanOrEqual(2)
    expect(document.body.querySelector('.auth-modal__submit-icon')).not.toBeNull()
  })

  it('shows register tab icon and confirm password icon', async () => {
    useAuthModal().openRegister()
    mountAuthModal()

    const registerTab = Array.from(document.body.querySelectorAll('.auth-modal__tab')).find((el) =>
      el.textContent?.includes('Регистрация'),
    )
    expect(registerTab?.querySelector('.auth-modal__tab-icon')).not.toBeNull()
    expect(document.body.querySelectorAll('.auth-modal__control-icon')).toHaveLength(3)
  })

  it('submits login and closes modal on success', async () => {
    vi.mocked(authApi.login).mockResolvedValue({
      id: 1,
      email: 'user@renlo.local',
      name: 'user',
      roles: ['ROLE_USER'],
    })

    useAuthModal().openLogin({ redirect: '/sale' })

    mountAuthModal()

    const emailInput = document.body.querySelector('#auth-modal-email') as HTMLInputElement
    const passwordInput = document.body.querySelector('input[type="password"]') as HTMLInputElement
    const form = document.body.querySelector('form') as HTMLFormElement

    emailInput.value = 'user@renlo.local'
    emailInput.dispatchEvent(new Event('input'))
    passwordInput.value = 'SecurePass1'
    passwordInput.dispatchEvent(new Event('input'))
    form.dispatchEvent(new Event('submit'))

    await vi.waitFor(() => {
      expect(authApi.login).toHaveBeenCalledWith({
        email: 'user@renlo.local',
        password: 'SecurePass1',
      })
      expect(useAuthModal().isOpen.value).toBe(false)
      expect(routerPush).toHaveBeenCalledWith('/sale')
    })

    expect(useAuthSuccessModal().isOpen.value).toBe(true)
    expect(useAuthSuccessModal().kind.value).toBe('login')
  })
})

