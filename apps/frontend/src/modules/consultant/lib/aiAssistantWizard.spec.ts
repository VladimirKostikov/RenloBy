import { describe, expect, it } from 'vitest'
import {
  applyCustomBudgetDisplay,
  budgetPresets,
  convertBudgetFromUsd,
  createEmptyAiAssistantDraft,
  currencySymbol,
  isBudgetPresetSelected,
  parseBudgetInput,
  progressPercent,
  toAiPreferenceAnswers,
  validateAiAssistantStep,
} from './aiAssistantWizard'

describe('aiAssistantWizard', () => {
  it('validates currency step for byn and usd only', () => {
    const draft = createEmptyAiAssistantDraft()
    expect(draft.currency).toBe('byn')
    expect(validateAiAssistantStep('currency', draft)).toBe(true)

    draft.currency = 'usd'
    expect(validateAiAssistantStep('currency', draft)).toBe(true)
  })

  it('validates budget step only after selection', () => {
    const draft = createEmptyAiAssistantDraft()
    expect(validateAiAssistantStep('budget', draft)).toBe(false)

    draft.budgetMin = 300
    draft.budgetMax = 500
    expect(validateAiAssistantStep('budget', draft)).toBe(true)
  })

  it('validates custom budget after parsing display values', () => {
    const draft = createEmptyAiAssistantDraft()
    draft.currency = 'usd'
    expect(validateAiAssistantStep('budget', draft)).toBe(false)

    applyCustomBudgetDisplay(draft, 450, 700)
    expect(draft.budgetCustom).toBe(true)
    expect(draft.budgetMin).toBe(450)
    expect(draft.budgetMax).toBe(700)
    expect(validateAiAssistantStep('budget', draft)).toBe(true)
    expect(isBudgetPresetSelected(draft, 300, 500)).toBe(false)
  })

  it('maps draft to API answers with currency', () => {
    const draft = createEmptyAiAssistantDraft()
    draft.dealType = 'sale'
    draft.currency = 'usd'
    draft.budgetMax = 100000
    draft.rooms = 2
    draft.cityId = 7
    draft.priorities = ['fromOwner', 'nearMetro']

    expect(toAiPreferenceAnswers(draft)).toEqual({
      dealType: 'sale',
      listingType: null,
      currency: 'usd',
      budgetMin: null,
      budgetMax: 100000,
      rooms: 2,
      cityId: 7,
      priorities: ['fromOwner', 'nearMetro'],
    })
  })

  it('returns deal-specific budget presets', () => {
    expect(budgetPresets('rent')[0].max).toBe(300)
    expect(budgetPresets('sale')[0].max).toBe(50000)
  })

  it('converts budget display amounts by currency', () => {
    expect(convertBudgetFromUsd(100, 'usd')).toBe(100)
    expect(convertBudgetFromUsd(100, 'byn')).toBeGreaterThan(100)
    expect(currencySymbol('byn')).toBe('BYN')
    expect(currencySymbol('usd')).toBe('$')
  })

  it('parses custom budget input', () => {
    expect(parseBudgetInput('')).toBeNull()
    expect(parseBudgetInput('0')).toBeNull()
    expect(parseBudgetInput('1 200')).toBe(1200)
    expect(parseBudgetInput('350,5')).toBe(351)
  })

  it('computes progress percent', () => {
    expect(progressPercent(0, 7)).toBe(0)
    expect(progressPercent(6, 7)).toBe(100)
  })
})
