import { convertFromUsd, convertToUsd } from '@/lib/formatPrice'
import type { DealType, ListingType } from '@/types'

export type AiAssistantStepId =
  | 'deal'
  | 'currency'
  | 'budget'
  | 'rooms'
  | 'city'
  | 'priorities'
  | 'result'

export type AiAssistantCurrency = 'byn' | 'usd'

export type AiPriorityId =
  | 'fromOwner'
  | 'noCommission'
  | 'hasRenovation'
  | 'nearMetro'
  | 'aiGoodPrice'

export interface AiAssistantDraft {
  dealType: DealType
  listingType: ListingType | null
  currency: AiAssistantCurrency
  budgetCustom: boolean
  budgetMin: number | null
  budgetMax: number | null
  rooms: number | null
  cityId: number | null
  priorities: AiPriorityId[]
}

export const AI_ASSISTANT_STEPS: AiAssistantStepId[] = [
  'deal',
  'currency',
  'budget',
  'rooms',
  'city',
  'priorities',
  'result',
]

export const AI_CURRENCY_OPTIONS: AiAssistantCurrency[] = ['byn', 'usd']

export const AI_PRIORITY_OPTIONS: AiPriorityId[] = [
  'fromOwner',
  'noCommission',
  'hasRenovation',
  'nearMetro',
  'aiGoodPrice',
]

export const AI_ASSISTANT_DRAFT_KEY = 'donmap-ai-assistant-draft'

export function createEmptyAiAssistantDraft(): AiAssistantDraft {
  return {
    dealType: 'rent',
    listingType: null,
    currency: 'byn',
    budgetCustom: false,
    budgetMin: null,
    budgetMax: null,
    rooms: null,
    cityId: null,
    priorities: [],
  }
}

export function budgetPresets(dealType: DealType): Array<{ min: number | null; max: number | null }> {
  if (dealType === 'sale') {
    return [
      { min: null, max: 50000 },
      { min: 50000, max: 100000 },
      { min: 100000, max: 150000 },
      { min: 150000, max: null },
    ]
  }
  return [
    { min: null, max: 300 },
    { min: 300, max: 500 },
    { min: 500, max: 800 },
    { min: 800, max: null },
  ]
}

export function convertBudgetFromUsd(amountUsd: number, currency: AiAssistantCurrency): number {
  if (currency === 'usd') {
    return Math.round(amountUsd)
  }
  return convertFromUsd(amountUsd, 'byn')
}

export function convertBudgetToUsd(amount: number, currency: AiAssistantCurrency): number {
  if (currency === 'usd') {
    return Math.round(amount)
  }
  return convertToUsd(amount, 'byn')
}

export function currencySymbol(currency: AiAssistantCurrency): string {
  return currency === 'usd' ? '$' : 'BYN'
}

export function formatBudgetAmount(value: number): string {
  return new Intl.NumberFormat('ru-RU', { maximumFractionDigits: 0 }).format(value).replace(/\u00a0/g, ' ')
}

export function parseBudgetInput(raw: string): number | null {
  const cleaned = raw.replace(/\s/g, '').replace(',', '.')
  if (cleaned === '') return null
  const value = Number(cleaned)
  if (!Number.isFinite(value) || value < 0) return null
  const rounded = Math.round(value)
  return rounded > 0 ? rounded : null
}

export function applyCustomBudgetDisplay(
  draft: AiAssistantDraft,
  minDisplay: number | null,
  maxDisplay: number | null,
): void {
  draft.budgetCustom = true
  draft.budgetMin =
    minDisplay === null ? null : convertBudgetToUsd(minDisplay, draft.currency)
  draft.budgetMax =
    maxDisplay === null ? null : convertBudgetToUsd(maxDisplay, draft.currency)
}

export function customBudgetDisplayValue(
  amountUsd: number | null,
  currency: AiAssistantCurrency,
): string {
  if (amountUsd === null) return ''
  return String(convertBudgetFromUsd(amountUsd, currency))
}

export function isBudgetPresetSelected(
  draft: AiAssistantDraft,
  min: number | null,
  max: number | null,
): boolean {
  if (draft.budgetCustom) return false
  return draft.budgetMin === min && draft.budgetMax === max
}

export function validateAiAssistantStep(step: AiAssistantStepId, draft: AiAssistantDraft): boolean {
  switch (step) {
    case 'deal':
      return ['rent', 'sale'].includes(draft.dealType)
    case 'currency':
      return AI_CURRENCY_OPTIONS.includes(draft.currency)
    case 'budget':
      if (draft.budgetMin === null && draft.budgetMax === null) return false
      if (
        draft.budgetMin !== null &&
        draft.budgetMax !== null &&
        draft.budgetMin > draft.budgetMax
      ) {
        return false
      }
      return true
    case 'rooms':
      return draft.rooms === null || (draft.rooms >= 0 && draft.rooms <= 5)
    case 'city':
      return true
    case 'priorities':
      return true
    case 'result':
      return true
    default:
      return false
  }
}

export function toAiPreferenceAnswers(draft: AiAssistantDraft): Record<string, unknown> {
  return {
    dealType: draft.dealType,
    listingType: draft.listingType,
    currency: draft.currency,
    budgetMin: draft.budgetMin,
    budgetMax: draft.budgetMax,
    rooms: draft.rooms,
    cityId: draft.cityId,
    priorities: [...draft.priorities],
  }
}

function normalizeCurrency(value: unknown): AiAssistantCurrency {
  if (value === 'usd' || value === 'byn') {
    return value
  }
  return 'byn'
}

function normalizeDealType(value: unknown): DealType {
  if (value === 'sale' || value === 'rent') {
    return value
  }
  return 'rent'
}

function normalizeListingType(value: unknown): ListingType | null {
  if (value === 'apartment' || value === 'house' || value === 'room' || value === 'commercial') {
    return value
  }
  return null
}

export function saveAiAssistantDraftLocal(draft: AiAssistantDraft): void {
  try {
    localStorage.setItem(AI_ASSISTANT_DRAFT_KEY, JSON.stringify(draft))
  } catch {
    /* ignore quota */
  }
}

export function loadAiAssistantDraftLocal(): AiAssistantDraft | null {
  try {
    const raw = localStorage.getItem(AI_ASSISTANT_DRAFT_KEY)
    if (!raw) return null
    const parsed = JSON.parse(raw) as Record<string, unknown>
    const legacyCommercial = parsed.dealType === 'commercial'
    return {
      ...createEmptyAiAssistantDraft(),
      ...(parsed as Partial<AiAssistantDraft>),
      dealType: legacyCommercial ? 'sale' : normalizeDealType(parsed.dealType),
      listingType: legacyCommercial
        ? 'commercial'
        : normalizeListingType(parsed.listingType),
      currency: normalizeCurrency(parsed.currency),
      budgetCustom: Boolean(parsed.budgetCustom),
      priorities: Array.isArray(parsed.priorities)
        ? parsed.priorities.filter((item): item is AiPriorityId =>
            AI_PRIORITY_OPTIONS.includes(item as AiPriorityId),
          )
        : [],
    }
  } catch {
    return null
  }
}

export function clearAiAssistantDraftLocal(): void {
  try {
    localStorage.removeItem(AI_ASSISTANT_DRAFT_KEY)
  } catch {
    /* ignore */
  }
}

export function progressPercent(stepIndex: number, total = AI_ASSISTANT_STEPS.length): number {
  if (total <= 1) return 100
  return Math.round((stepIndex / (total - 1)) * 100)
}
