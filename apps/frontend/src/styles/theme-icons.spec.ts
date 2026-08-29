import { readFileSync } from 'fs'
import { dirname, resolve } from 'path'
import { fileURLToPath } from 'url'
import { describe, expect, it } from 'vitest'

const stylesDir = resolve(dirname(fileURLToPath(import.meta.url)))

describe('theme-icons CSS', () => {
  it('inverts ink icons in dark theme', () => {
    const css = readFileSync(resolve(stylesDir, 'theme-icons.css'), 'utf8')
    expect(css).toContain("[data-theme='dark'] img[data-theme-ink]")
    expect(css).toContain('filter:')
  })
})
