import { describe, expect, it } from 'vitest'
import { appScrollBehavior } from '@/router/scrollBehavior'

function asRoute(path: string, hash = '') {
  return { path, hash, fullPath: `${path}${hash}` } as Parameters<typeof appScrollBehavior>[0]
}

describe('appScrollBehavior', () => {
  it('restores saved position on back navigation', () => {
    const result = appScrollBehavior(
      asRoute('/account/profile'),
      asRoute('/account/listings'),
      { left: 0, top: 420 },
    )

    expect(result).toEqual({ left: 0, top: 420 })
  })

  it('scrolls to hash when present', () => {
    const result = appScrollBehavior(
      asRoute('/info/faq', '#contacts'),
      asRoute('/info/deal-safety'),
      null,
    )

    expect(result).toEqual({ el: '#contacts' })
  })

  it('does not scroll when only query changes on the same path', () => {
    const result = appScrollBehavior(
      asRoute('/sale'),
      asRoute('/sale'),
      null,
    )

    expect(result).toBe(false)
  })

  it('scrolls to top when the section path changes', () => {
    const result = appScrollBehavior(
      asRoute('/account/seller/listings'),
      asRoute('/account/profile'),
      null,
    )

    expect(result).toEqual({ top: 0, left: 0 })
  })
})
