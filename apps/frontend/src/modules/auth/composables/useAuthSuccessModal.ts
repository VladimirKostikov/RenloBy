import { ref } from 'vue'

export type AuthSuccessKind = 'login' | 'register'

const isOpen = ref(false)
const kind = ref<AuthSuccessKind>('login')

export function useAuthSuccessModal() {
  function open(nextKind: AuthSuccessKind = 'login') {
    kind.value = nextKind
    isOpen.value = true
  }

  function close() {
    isOpen.value = false
  }

  return {
    isOpen,
    kind,
    open,
    close,
  }
}
