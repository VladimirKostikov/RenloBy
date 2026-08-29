import { useAuthStore } from '@/stores/auth'
import { useAuthModal } from '@/modules/auth/composables/useAuthModal'

export function useRequireAuth() {
  const auth = useAuthStore()
  const authModal = useAuthModal()

  function requireAuth(action: () => void | Promise<void>): boolean {
    if (auth.isAuthenticated) {
      void action()
      return true
    }

    authModal.openLogin({ onSuccess: action })
    return false
  }

  return { requireAuth }
}
