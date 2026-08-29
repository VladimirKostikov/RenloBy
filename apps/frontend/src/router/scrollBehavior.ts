import type { RouterScrollBehavior } from 'vue-router'

export const appScrollBehavior: RouterScrollBehavior = (to, from, savedPosition) => {
  if (savedPosition) {
    return savedPosition
  }

  if (to.hash) {
    return { el: to.hash }
  }

  if (to.path === from.path) {
    return false
  }

  return { top: 0, left: 0 }
}
