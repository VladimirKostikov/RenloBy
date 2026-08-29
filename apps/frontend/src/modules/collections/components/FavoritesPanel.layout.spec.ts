import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'
import { describe, expect, it } from 'vitest'

describe('FavoritesPanel desktop grid', () => {
  it('uses four columns on desktop', () => {
    const source = readFileSync(resolve(__dirname, './FavoritesPanel.vue'), 'utf8')
    expect(source).toContain('grid-template-columns: repeat(4, minmax(0, 1fr))')
  })

  it('stretches cards to equal row height', () => {
    const source = readFileSync(resolve(__dirname, './FavoritesPanel.vue'), 'utf8')
    expect(source).toContain('align-items: stretch')
    expect(source).toContain('.favorites-panel__grid > :deep(.catalog-card)')
    expect(source).toContain('height: 100%')
    expect(source).toContain('min-height: 100%')
  })
})
