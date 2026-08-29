import { onUnmounted, ref, type Ref } from 'vue'
import { useRouter, type RouteLocationNormalized } from 'vue-router'

export function useRoutePathPending(
  shouldTrack: (to: RouteLocationNormalized, from: RouteLocationNormalized) => boolean = () => true,
): Ref<boolean> {
  const pending = ref(false)
  const router = useRouter()

  const removeBefore = router.beforeEach((to, from) => {
    if (to.path === from.path) {
      return
    }
    if (!shouldTrack(to, from)) {
      return
    }
    pending.value = true
  })

  const removeAfter = router.afterEach(() => {
    window.setTimeout(() => {
      pending.value = false
    }, 40)
  })

  const removeError = router.onError(() => {
    pending.value = false
  })

  onUnmounted(() => {
    removeBefore()
    removeAfter()
    removeError()
  })

  return pending
}
