import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'
import { describe, expect, it } from 'vitest'

describe('ListingWizardPanel layout styles', () => {
  it('stretches main column across the account content width', () => {
    const source = readFileSync(
      resolve(__dirname, './ListingWizardPanel.vue'),
      'utf8',
    )

    expect(source).toContain('grid-template-columns: 168px minmax(0, 1fr)')
    expect(source).toContain('max-width: none')
    expect(source).toContain('name="wizard-slide"')
    expect(source).toContain('@keyframes wizard-slide-enter-next')
    expect(source).not.toContain('minmax(0, 560px)')
    expect(source).not.toMatch(/\.listing-wizard__main\s*\{[^}]*max-width:\s*560px/)
  })
})
