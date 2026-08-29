export function resolveScrollBehavior(
  prefersReducedMotion = false,
): ScrollBehavior {
  return prefersReducedMotion ? 'auto' : 'smooth'
}

export function scrollElementBelowStickyHeader(
  element: HTMLElement,
  options?: {
    stickySelector?: string
    behavior?: ScrollBehavior
  },
): number {
  const stickySelector = options?.stickySelector ?? '.home-header'
  const header = document.querySelector(stickySelector)
  const offset = header instanceof HTMLElement ? header.getBoundingClientRect().height : 0
  const top = Math.max(0, Math.round(window.scrollY + element.getBoundingClientRect().top - offset))
  const behavior = options?.behavior
    ?? resolveScrollBehavior(window.matchMedia('(prefers-reduced-motion: reduce)').matches)

  window.scrollTo({ top, behavior })
  return top
}
