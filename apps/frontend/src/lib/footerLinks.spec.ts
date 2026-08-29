import { describe, expect, it } from 'vitest'
import { FOOTER_SECTIONS } from '@/lib/footerLinks'

describe('footerLinks', () => {
  it('contains catalog, info, service and legal sections', () => {
    const keys = FOOTER_SECTIONS.map((section) => section.key)
    expect(keys).toEqual(['catalog', 'info', 'service', 'legal'])
  })

  it('includes main public routes and auth actions', () => {
    const routes = FOOTER_SECTIONS.flatMap((section) => section.links.map((link) => link.to).filter(Boolean))
    const actions = FOOTER_SECTIONS.flatMap((section) => section.links.map((link) => link.action).filter(Boolean))

    expect(routes).toContain('/')
    expect(routes).toContain('/sale')
    expect(routes).toContain('/rent')
    expect(routes).toContain('/search')
    expect(routes).toContain('/info/deal-safety')
    expect(routes).toContain('/info/offer')
    expect(routes).toContain('/info/privacy')
    expect(routes).toContain('/info/personal-data')
    expect(routes).toContain('/articles')
    expect(routes).toContain('/promotion/payment')
    expect(actions).toContain('login')
    expect(actions).toContain('register')
  })

  it('uses short footer-only label keys', () => {
    const labelKeys = FOOTER_SECTIONS.flatMap((section) => section.links.map((link) => link.labelKey))

    expect(labelKeys.every((key) => key.startsWith('footer.links.'))).toBe(true)
  })
})
