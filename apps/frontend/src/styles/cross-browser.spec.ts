import { readFileSync } from 'node:fs'
import { dirname, join } from 'node:path'
import { fileURLToPath } from 'node:url'
import { describe, expect, it } from 'vitest'

const root = join(dirname(fileURLToPath(import.meta.url)), '../..')

describe('cross-browser tooling', () => {
  it('defines browserslist targets for modern desktop and mobile browsers', () => {
    const pkg = JSON.parse(readFileSync(join(root, 'package.json'), 'utf8')) as {
      browserslist?: string[]
    }

    expect(pkg.browserslist).toEqual(
      expect.arrayContaining(['iOS >= 15', 'Safari >= 15', 'Firefox ESR', 'not dead']),
    )
  })

  it('enables PostCSS Autoprefixer', () => {
    const config = readFileSync(join(root, 'postcss.config.js'), 'utf8')
    expect(config).toContain('autoprefixer')
  })
})
