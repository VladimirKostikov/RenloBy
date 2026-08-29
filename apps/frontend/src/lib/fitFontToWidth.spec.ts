import { describe, expect, it, vi } from 'vitest'
import { fitFontToWidth } from '@/lib/fitFontToWidth'

describe('fitFontToWidth', () => {
  it('reduces font size until text fits available width', () => {
    const element = document.createElement('span')
    element.textContent = '189 660 BYN'
    document.body.appendChild(element)

    vi.spyOn(window, 'getComputedStyle').mockReturnValue({
      fontSize: '20px',
    } as CSSStyleDeclaration)

    Object.defineProperty(element, 'scrollWidth', {
      configurable: true,
      get() {
        const size = Number.parseFloat(element.style.fontSize || '20')
        return size * 10
      },
    })

    const result = fitFontToWidth(element, 80)

    expect(result).toBeLessThan(20)
    expect(Number.parseFloat(element.style.fontSize)).toBe(result)
    element.remove()
  })

  it('keeps original size when text already fits', () => {
    const element = document.createElement('span')
    document.body.appendChild(element)

    vi.spyOn(window, 'getComputedStyle').mockReturnValue({
      fontSize: '20px',
    } as CSSStyleDeclaration)

    Object.defineProperty(element, 'scrollWidth', {
      configurable: true,
      value: 40,
    })

    const result = fitFontToWidth(element, 120)

    expect(result).toBe(20)
    expect(element.style.fontSize).toBe('')
    element.remove()
  })
})
