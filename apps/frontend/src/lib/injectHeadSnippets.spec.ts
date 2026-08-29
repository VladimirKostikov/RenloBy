import { describe, expect, it } from 'vitest'
import { clearInjectedHeadSnippets, injectHeadSnippetCodes } from '@/lib/injectHeadSnippets'

describe('injectHeadSnippets', () => {
  it('injects meta and executable script tags into head', () => {
    const doc = document.implementation.createHTMLDocument('test')
    injectHeadSnippetCodes(
      [
        '<meta name="verification" content="abc">',
        '<script>window.__renloHead = 1</script>',
      ],
      doc,
    )

    expect(doc.head.querySelector('meta[name="verification"]')?.getAttribute('content')).toBe('abc')
    const script = doc.head.querySelector('script[data-renlo-head-snippet]')
    expect(script).not.toBeNull()
    expect(script?.textContent).toContain('__renloHead')
  })

  it('wraps bare script source without tags', () => {
    const doc = document.implementation.createHTMLDocument('test')
    injectHeadSnippetCodes(['window.__renloBare = true'], doc)

    const script = doc.head.querySelector('script[data-renlo-head-snippet]')
    expect(script).not.toBeNull()
    expect(script?.textContent).toContain('__renloBare')
  })

  it('clears previously injected snippets', () => {
    const doc = document.implementation.createHTMLDocument('test')
    injectHeadSnippetCodes(['<meta name="a" content="1">'], doc)
    clearInjectedHeadSnippets(doc)
    expect(doc.head.querySelectorAll('[data-renlo-head-snippet]')).toHaveLength(0)
  })
})
