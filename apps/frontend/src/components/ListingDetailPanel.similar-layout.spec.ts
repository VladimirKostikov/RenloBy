import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'
import { describe, expect, it } from 'vitest'

describe('ListingDetailPanel similar layout', () => {
  it('centers similar listings and keeps four desktop slots', () => {
    const source = readFileSync(resolve(__dirname, './ListingDetailPanel.vue'), 'utf8')

    expect(source).toContain('justify-content: center')
    expect(source).toContain('calc((100% - 30px) / 4)')
    expect(source).toContain('height: 132px')
    expect(source).toContain('width: calc((100% - 30px) / 4)')
    expect(source).toContain('SIMILAR_LISTINGS_LIMIT')
  })
})

describe('ListingDetailPanel security layout', () => {
  it('keeps security card compact beside the shield', () => {
    const source = readFileSync(resolve(__dirname, './ListingDetailPanel.vue'), 'utf8')

    expect(source).toContain('listing-detail-modal__security-body')
    expect(source).toContain('align-self: start')
    expect(source).toContain('grid-template-columns: minmax(0, 1fr) auto')
    expect(source).not.toContain('padding-right: 72px')
  })
})
