import { describe, expect, it } from 'vitest'
import { parseInfoBody } from '@/modules/info/lib/parseInfoBody'

describe('parseInfoBody', () => {
  it('parses headings, paragraphs and lists', () => {
    const blocks = parseInfoBody(
      'Intro paragraph.\n\n## Section title\n\n- First item\n- Second item',
    )

    expect(blocks).toEqual([
      { type: 'paragraph', text: 'Intro paragraph.' },
      { type: 'heading', text: 'Section title' },
      { type: 'list', items: ['First item', 'Second item'] },
    ])
  })
})
