export function resolveListingDetailScrollBehavior(
  prefersReducedMotion = false,
): ScrollBehavior {
  return prefersReducedMotion ? 'auto' : 'smooth'
}

function scrollTargetToTop(
  target: HTMLElement | Window,
  behavior: ScrollBehavior,
): void {
  if (target instanceof HTMLElement) {
    target.scrollTop = 0
    target.scrollTo({ top: 0, behavior })
    return
  }

  target.scrollTo({ top: 0, behavior })
}

export function findListingDetailScrollTarget(
  root: HTMLElement | null | undefined,
  asPage = false,
): HTMLElement | Window | null {
  if (typeof window === 'undefined') {
    return null
  }

  if (asPage) {
    return window
  }

  const overlay = root?.closest('.listing-detail-overlay')
  if (overlay instanceof HTMLElement) {
    return overlay
  }

  return window
}

export function scrollListingDetailToTop(
  root: HTMLElement | null | undefined,
  options?: {
    asPage?: boolean
    behavior?: ScrollBehavior
    retries?: number
  },
): void {
  if (typeof window === 'undefined') {
    return
  }

  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches
  const behavior = options?.behavior
    ?? resolveListingDetailScrollBehavior(prefersReducedMotion)
  const target = findListingDetailScrollTarget(root, options?.asPage === true)
  if (!target) {
    return
  }

  scrollTargetToTop(target, behavior)

  const retries = options?.retries ?? 2
  for (let attempt = 1; attempt <= retries; attempt += 1) {
    window.setTimeout(() => {
      const nextTarget = findListingDetailScrollTarget(root, options?.asPage === true)
      if (nextTarget) {
        scrollTargetToTop(nextTarget, behavior === 'smooth' && attempt > 1 ? 'auto' : behavior)
      }
    }, attempt * 50)
  }
}
