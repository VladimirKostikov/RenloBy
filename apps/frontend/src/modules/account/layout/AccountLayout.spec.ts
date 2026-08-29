import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'
import { describe, expect, it } from 'vitest'

describe('AccountLayout sidebar height', () => {
  it('keeps sidebar height independent from content column', () => {
    const source = readFileSync(resolve(__dirname, './AccountLayout.vue'), 'utf8')

    expect(source).toContain('align-items: flex-start')
    expect(source).toContain('align-self: flex-start')
    expect(source).not.toContain('align-items: stretch')
    expect(source).not.toContain(':deep(.account-sidebar)')
  })

  it('keeps desktop sidebar sticky while content scrolls', () => {
    const source = readFileSync(resolve(__dirname, './AccountLayout.vue'), 'utf8')

    expect(source).toContain('position: sticky')
    expect(source).toContain('top: calc(var(--figma-header-height, 88px) + 12px)')
  })

  it('shows container preloader while switching account pages', () => {
    const source = readFileSync(resolve(__dirname, './AccountLayout.vue'), 'utf8')

    expect(source).toContain('ContainerPreloader')
    expect(source).toContain('useRoutePathPending')
    expect(source).toContain('lockedMinHeight')
  })

  it('scrolls window to top when account section path changes', () => {
    const source = readFileSync(resolve(__dirname, './AccountLayout.vue'), 'utf8')

    expect(source).toContain("() => route.path")
    expect(source).toContain("window.scrollTo({ top: 0, left: 0 })")
  })
})
