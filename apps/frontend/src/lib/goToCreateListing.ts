import type { Router } from 'vue-router'

export const CREATE_LISTING_PATH = '/account/seller/create'

export async function goToCreateListing(options: {
  isAuthenticated: boolean
  router: Router
  openRegister: (options: { redirect: string }) => void
}): Promise<void> {
  if (!options.isAuthenticated) {
    options.openRegister({ redirect: CREATE_LISTING_PATH })
    return
  }

  if (options.router.currentRoute.value.path !== CREATE_LISTING_PATH) {
    await options.router.push(CREATE_LISTING_PATH)
  }

  window.scrollTo({ top: 0, behavior: 'smooth' })
}
