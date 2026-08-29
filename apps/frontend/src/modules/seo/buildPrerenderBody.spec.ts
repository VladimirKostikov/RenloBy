import { describe, expect, it } from 'vitest'
import { buildPrerenderBodyHtml, buildPrerenderPayload } from '@/modules/seo/buildPrerenderBody'

describe('buildPrerenderBody', () => {
  it('builds info page payload and html', () => {
    const context = {
      infoPage: {
        slug: 'sellers',
        title: 'Продавцам',
        body: '## Заголовок\n\nТекст страницы',
      },
    }
    const payload = buildPrerenderPayload('/info/sellers', context)

    expect(payload?.kind).toBe('info-page')
    expect(buildPrerenderBodyHtml(payload)).toContain('Продавцам')
    expect(buildPrerenderBodyHtml(payload)).toContain('Заголовок')
  })
})
