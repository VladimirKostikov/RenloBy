import { afterEach, describe, expect, it, vi } from 'vitest'
import {
  resolveScrollBehavior,
  scrollElementBelowStickyHeader,
} from '@/lib/scrollBelowStickyHeader'

describe('scrollBelowStickyHeader', () => {
  afterEach(() => {
    vi.restoreAllMocks()
    document.body.innerHTML = ''
  })

  it('uses auto behavior when reduced motion is preferred', () => {
    expect(resolveScrollBehavior(true)).toBe('auto')
    expect(resolveScrollBehavior(false)).toBe('smooth')
  })

  it('scrolls element to the bottom edge of sticky header', () => {
    const scrollTo = vi.fn()
    vi.stubGlobal('scrollTo', scrollTo)
    Object.defineProperty(window, 'scrollY', { configurable: true, value: 400 })
    vi.spyOn(window, 'matchMedia').mockReturnValue({
      matches: false,
      media: '',
      onchange: null,
      addListener: vi.fn(),
      removeListener: vi.fn(),
      addEventListener: vi.fn(),
      removeEventListener: vi.fn(),
      dispatchEvent: vi.fn(),
    } as MediaQueryList)

    const header = document.createElement('header')
    header.className = 'home-header'
    header.getBoundingClientRect = () => ({
      height: 120,
      width: 0,
      top: 0,
      left: 0,
      bottom: 120,
      right: 0,
      x: 0,
      y: 0,
      toJSON: () => ({}),
    })
    document.body.appendChild(header)

    const element = document.createElement('div')
    element.getBoundingClientRect = () => ({
      height: 800,
      width: 0,
      top: 260,
      left: 0,
      bottom: 1060,
      right: 0,
      x: 0,
      y: 260,
      toJSON: () => ({}),
    })
    document.body.appendChild(element)

    const top = scrollElementBelowStickyHeader(element)

    expect(top).toBe(540)
    expect(scrollTo).toHaveBeenCalledWith({ top: 540, behavior: 'smooth' })
  })
})
