import { ref, shallowRef } from 'vue'

export type AuthModalMode = 'login' | 'register'

export interface AuthModalOptions {
  mode?: AuthModalMode
  redirect?: string | null
  onSuccess?: () => void | Promise<void>
}

const isOpen = ref(false)
const mode = ref<AuthModalMode>('login')
const redirectPath = ref<string | null>(null)
const successCallback = shallowRef<(() => void | Promise<void>) | null>(null)

export function useAuthModal() {
  function open(options: AuthModalOptions = {}) {
    mode.value = options.mode ?? 'login'
    redirectPath.value = options.redirect ?? null
    successCallback.value = options.onSuccess ?? null
    isOpen.value = true
  }

  function openLogin(options: Omit<AuthModalOptions, 'mode'> = {}) {
    open({ ...options, mode: 'login' })
  }

  function openRegister(options: Omit<AuthModalOptions, 'mode'> = {}) {
    open({ ...options, mode: 'register' })
  }

  function setMode(nextMode: AuthModalMode) {
    mode.value = nextMode
  }

  function close() {
    isOpen.value = false
    successCallback.value = null
    redirectPath.value = null
  }

  function consumeSuccessCallback() {
    const callback = successCallback.value
    const redirect = redirectPath.value
    successCallback.value = null
    redirectPath.value = null

    return { callback, redirect }
  }

  return {
    isOpen,
    mode,
    redirectPath,
    open,
    openLogin,
    openRegister,
    setMode,
    close,
    consumeSuccessCallback,
  }
}
