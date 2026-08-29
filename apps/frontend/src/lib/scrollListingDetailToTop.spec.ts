import { describe, expect, it, vi, afterEach } from 'vitest'
import {
  findListingDetailScrollTarget,
  scrollListingDetailToTop,
} from '@/lib/scrollListingDetailToTop'

describe('scrollListingDetailToTop', () => {
  afterEach(() => {
    vi.useRealTimers()
    vi.unstubAllGlobals()
    document.body.innerHTML = ''
  })

  it('scrolls overlay when panel is inside modal', () => {
    vi.useFakeTimers()

    const overlay = document.createElement('div')
    overlay.className = 'listing-detail-overlay'
    const scrollTo = vi.fn()
    overlay.scrollTo = scrollTo as unknown as typeof overlay.scrollTo

    const panel = document.createElement('div')
    overlay.appendChild(panel)
    document.body.appendChild(overlay)

    const windowScrollTo = vi.fn()
    vi.stubGlobal('scrollTo', windowScrollTo)

    scrollListingDetailToTop(panel, { asPage: false, behavior: 'smooth', retries: 1 })

    expect(overlay.scrollTop).toBe(0)
    expect(scrollTo).toHaveBeenCalledWith({ top: 0, behavior: 'smooth' })
    expect(windowScrollTo).not.toHaveBeenCalled()
    expect(findListingDetailScrollTarget(panel, false)).toBe(overlay)

    vi.runAllTimers()
    expect(scrollTo.mock.calls.length).toBeGreaterThan(1)
  })

  it('scrolls window for page mode', () => {
    vi.useFakeTimers()

    const windowScrollTo = vi.fn()
    vi.stubGlobal('scrollTo', windowScrollTo)

    scrollListingDetailToTop(document.createElement('div'), {
      asPage: true,
      behavior: 'smooth',
      retries: 0,
    })

    expect(windowScrollTo).toHaveBeenCalledWith({ top: 0, behavior: 'smooth' })
  })
})
