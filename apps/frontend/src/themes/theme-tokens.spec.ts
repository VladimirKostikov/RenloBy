import { readFileSync } from 'fs'
import { dirname, resolve } from 'path'
import { fileURLToPath } from 'url'
import { describe, expect, it } from 'vitest'

const themesDir = resolve(dirname(fileURLToPath(import.meta.url)))

describe('theme CSS tokens', () => {
  it('defines dark surface and ink tokens for public UI', () => {
    const dark = readFileSync(resolve(themesDir, 'dark.css'), 'utf8')

    expect(dark).toContain("[data-theme='dark']")
    expect(dark).toContain('color-scheme: dark')
    expect(dark).toContain('--figma-surface:')
    expect(dark).toContain('--figma-ink:')
    expect(dark).toContain('--figma-page-bg:')
    expect(dark).toContain('--figma-mix-base:')
    expect(dark).toContain('--figma-surface-glass:')
    expect(dark).toMatch(/--figma-ink:\s*#[fF]/)
  })

  it('does not force light body colors in figma-home', () => {
    const figmaHome = readFileSync(resolve(themesDir, 'figma-home.css'), 'utf8')

    expect(figmaHome).not.toMatch(/body\s*\{[^}]*background:\s*#fff/i)
    expect(figmaHome).not.toMatch(/body\s*\{[^}]*color:\s*#000/i)
  })

  it('exposes light defaults on :root for first paint', () => {
    const light = readFileSync(resolve(themesDir, 'light.css'), 'utf8')

    expect(light).toContain(':root')
    expect(light).toContain('--figma-surface:')
    expect(light).toContain('--figma-ink:')
  })

  it('keeps verified badge backgrounds opaque', () => {
    const light = readFileSync(resolve(themesDir, 'light.css'), 'utf8')
    const dark = readFileSync(resolve(themesDir, 'dark.css'), 'utf8')

    expect(light).toMatch(/--figma-verified-bg:\s*#[0-9a-fA-F]{6}/)
    expect(dark).toMatch(/--figma-verified-bg:\s*#[0-9a-fA-F]{6}/)
    expect(dark).not.toMatch(/--figma-verified-bg:\s*rgba\(/)
  })
})
