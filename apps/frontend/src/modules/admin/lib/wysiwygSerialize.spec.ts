import { describe, expect, it } from 'vitest'
import { htmlToInfoBody, infoBodyToHtml, sanitizeHtml } from '@/modules/admin/lib/wysiwygSerialize'

describe('wysiwygSerialize', () => {
  it('converts markdown-like body to html blocks', () => {
    const html = infoBodyToHtml('## Heading\n\nParagraph text\n\n- One\n- Two')
    expect(html).toContain('<h2>Heading</h2>')
    expect(html).toContain('<p>Paragraph text</p>')
    expect(html).toContain('<li>One</li>')
    expect(html).toContain('<li>Two</li>')
  })

  it('converts html back to markdown-like body', () => {
    const body = htmlToInfoBody('<h2>Title</h2><p>Hello</p><ul><li>A</li><li>B</li></ul>')
    expect(body).toBe('## Title\n\nHello\n\n- A\n- B')
  })

  it('strips unsafe tags and attributes', () => {
    const cleaned = sanitizeHtml('<p onclick="alert(1)">Safe</p><script>bad()</script><img src=x onerror=alert(1)>')
    expect(cleaned).toContain('<p>Safe</p>')
    expect(cleaned).not.toContain('onclick')
    expect(cleaned).not.toContain('script')
    expect(cleaned).not.toContain('img')
  })
})
