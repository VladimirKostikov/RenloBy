import { describe, expect, it, vi, afterEach } from 'vitest'
import { CREATE_LISTING_PATH, goToCreateListing } from '@/lib/goToCreateListing'

describe('goToCreateListing', () => {
  afterEach(() => {
    vi.unstubAllGlobals()
  })

  it('opens register with redirect for guests', async () => {
    const openRegister = vi.fn()
    const push = vi.fn()

    await goToCreateListing({
      isAuthenticated: false,
      router: {
        push,
        currentRoute: { value: { path: '/' } },
      } as never,
      openRegister,
    })

    expect(openRegister).toHaveBeenCalledWith({ redirect: CREATE_LISTING_PATH })
    expect(push).not.toHaveBeenCalled()
  })

  it('navigates to create page for authenticated users', async () => {
    const openRegister = vi.fn()
    const push = vi.fn().mockResolvedValue(undefined)
    const scrollTo = vi.fn()
    vi.stubGlobal('scrollTo', scrollTo)

    await goToCreateListing({
      isAuthenticated: true,
      router: {
        push,
        currentRoute: { value: { path: '/' } },
      } as never,
      openRegister,
    })

    expect(openRegister).not.toHaveBeenCalled()
    expect(push).toHaveBeenCalledWith(CREATE_LISTING_PATH)
    expect(scrollTo).toHaveBeenCalledWith({ top: 0, behavior: 'smooth' })
  })
})
