import { describe, expect, it, beforeEach, afterEach } from 'vitest'
import {
  clearWizardDraftLocal,
  composeListingAddress,
  createEmptyWizardDraft,
  filterSuggestOptions,
  isWizardDraftEmpty,
  loadWizardDraftLocal,
  matchClosestOption,
  resolveRegionLabel,
  saveWizardDraftLocal,
  syncWizardPriceFromByn,
  syncWizardPriceFromUsd,
  toCreatePayload,
  validateWizardField,
  validateWizardStep,
  WIZARD_DRAFT_STORAGE_KEY,
  WIZARD_STEPS,
} from '@/modules/seller/lib/listingWizard'

describe('listingWizard', () => {
  beforeEach(() => {
    localStorage.clear()
  })

  afterEach(() => {
    localStorage.clear()
  })

  it('defines seven wizard steps with merged location', () => {
    expect(WIZARD_STEPS).toEqual([
      'deal',
      'object',
      'location',
      'details',
      'price',
      'photos',
      'review',
    ])
  })

  it('requires region city street and house on location step, district optional', () => {
    const draft = createEmptyWizardDraft()
    expect(validateWizardStep('location', draft)).toEqual(['region', 'city', 'street', 'house'])

    draft.region = 'Минская область'
    draft.city = 'Минск'
    draft.street = 'ул. Ленина'
    draft.house = '10'
    expect(validateWizardStep('location', draft)).toEqual([])
  })

  it('skips street and house checks when marked absent', () => {
    const draft = createEmptyWizardDraft()
    draft.region = 'Минская область'
    draft.city = 'Минск'
    draft.absent.street = true
    draft.absent.house = true
    expect(validateWizardStep('location', draft)).toEqual([])
  })

  it('clears floor fields in payload when marked absent', () => {
    const draft = createEmptyWizardDraft()
    draft.region = 'Минская область'
    draft.city = 'Минск'
    draft.street = 'ул. Ленина'
    draft.house = '10'
    draft.rooms = 2
    draft.area = 50
    draft.floor = 3
    draft.totalFloors = 9
    draft.price = 100000
    draft.priceByn = 327000
    draft.fromOwner = true
    draft.absent.floor = true
    draft.absent.totalFloors = true

    const payload = toCreatePayload(draft, 'draft')
    expect(payload.floor).toBeNull()
    expect(payload.totalFloors).toBeNull()
    expect(validateWizardField('floor', draft)).toBeNull()
    expect(validateWizardField('totalFloors', draft)).toBeNull()
  })

  it('appends entrance and apartment to address payload', () => {
    const draft = createEmptyWizardDraft()
    draft.street = 'ул. Ленина'
    draft.house = '10'
    draft.entrance = '2'
    draft.apartmentNumber = '45'
    expect(composeListingAddress(draft)).toBe('ул. Ленина, 10, подъезд 2, кв. 45')

    draft.city = 'Минск'
    draft.district = 'Центр'
    draft.price = 100000
    draft.priceByn = 327000
    draft.rooms = 2
    draft.area = 50
    draft.floor = 3
    draft.totalFloors = 9

    expect(toCreatePayload(draft, 'draft').address).toBe('ул. Ленина, 10, подъезд 2, кв. 45')
  })

  it('validates fields in realtime helpers', () => {
    const draft = createEmptyWizardDraft()
    expect(validateWizardField('price', draft)).toBe('price')
    expect(validateWizardField('priceByn', draft)).toBe('priceByn')
    syncWizardPriceFromUsd(draft, 120000)
    expect(validateWizardField('price', draft)).toBeNull()
    expect(validateWizardField('priceByn', draft)).toBeNull()
    expect(draft.priceByn).toBeGreaterThan(0)
  })

  it('syncs usd and byn prices both ways', () => {
    const draft = createEmptyWizardDraft()
    syncWizardPriceFromUsd(draft, 100)
    expect(draft.price).toBe(100)
    expect(draft.priceByn).toBe(327)

    syncWizardPriceFromByn(draft, 327)
    expect(draft.price).toBe(100)
    expect(draft.priceByn).toBe(327)

    expect(validateWizardStep('price', createEmptyWizardDraft())).toEqual(['price', 'priceByn'])
  })

  it('allows studio rooms in wizard details', () => {
    const draft = createEmptyWizardDraft()
    draft.rooms = 0
    draft.area = 28
    draft.fromOwner = true
    expect(validateWizardField('rooms', draft)).toBeNull()
    expect(validateWizardStep('details', draft)).toEqual([])
    expect(toCreatePayload(draft, 'draft').rooms).toBe(0)
  })

  it('allows missing floor and metro on details step', () => {
    const draft = createEmptyWizardDraft()
    draft.rooms = 2
    draft.area = 50
    draft.fromOwner = true
    draft.floor = null
    draft.totalFloors = null
    expect(validateWizardStep('details', draft)).toEqual([])

    const payload = toCreatePayload(draft, 'draft')
    expect(payload.floor).toBeNull()
    expect(payload.totalFloors).toBeNull()
    expect(payload.metro).toBeNull()
    expect(payload.metroMinutes).toBeNull()
  })

  it('requires rent term for rent deals', () => {
    const draft = createEmptyWizardDraft()
    draft.dealType = 'rent'
    draft.rentTerm = null
    expect(validateWizardStep('details', draft)).toContain('rentTerm')
  })

  it('requires seller role owner or agent', () => {
    const draft = createEmptyWizardDraft()
    draft.rooms = 2
    draft.area = 50
    draft.floor = 3
    draft.totalFloors = 9
    draft.fromOwner = null
    expect(validateWizardStep('details', draft)).toContain('fromOwner')

    draft.fromOwner = false
    expect(validateWizardStep('details', draft)).not.toContain('fromOwner')
    expect(toCreatePayload(draft, 'draft').fromOwner).toBe(false)

    draft.fromOwner = true
    expect(toCreatePayload(draft, 'draft').fromOwner).toBe(true)
  })

  it('maps draft to create payload with free-text location', () => {
    const draft = createEmptyWizardDraft()
    draft.city = 'Минск'
    draft.district = 'Центр'
    draft.metro = 'Немига'
    draft.metroLineColor = '#0072BC'
    draft.street = 'Street 1'
    draft.house = '12'
    draft.price = 100000
    draft.priceNegotiable = true
    draft.rooms = 2
    draft.area = 50
    draft.floor = 3
    draft.totalFloors = 9
    draft.fromOwner = true
    draft.images = ['https://example.com/a.jpg']

    const payload = toCreatePayload(draft, 'draft')
    expect(payload.status).toBe('draft')
    expect(payload.city).toBe('Минск')
    expect(payload.district).toBe('Центр')
    expect(payload.metro).toBe('Немига')
    expect(payload.metroLineColor).toBe('#0072BC')
    expect(payload.priceNegotiable).toBe(true)
    expect(payload.fromOwner).toBe(true)
    expect(payload.images).toHaveLength(1)

    draft.absent.district = true
    draft.district = 'Центр'
    expect(toCreatePayload(draft, 'draft').district).toBeNull()
  })

  it('migrates missing byn from usd price', () => {
    localStorage.setItem(WIZARD_DRAFT_STORAGE_KEY, JSON.stringify({
      draft: {
        ...createEmptyWizardDraft(),
        price: 100,
        priceByn: null,
      },
      stepIndex: 4,
    }))

    const loaded = loadWizardDraftLocal()
    expect(loaded?.draft.price).toBe(100)
    expect(loaded?.draft.priceByn).toBe(327)
  })

  it('migrates legacy address field into street', () => {
    localStorage.setItem(WIZARD_DRAFT_STORAGE_KEY, JSON.stringify({
      draft: {
        ...createEmptyWizardDraft(),
        address: 'ул. Ленина, 10',
        city: 'Минск',
      },
      stepIndex: 2,
    }))

    const loaded = loadWizardDraftLocal()
    expect(loaded?.draft.street).toBe('ул. Ленина, 10')
    expect(loaded?.draft.city).toBe('Минск')
    expect((loaded?.draft as { address?: string }).address).toBeUndefined()
  })

  it('persists and restores local draft', () => {
    const draft = createEmptyWizardDraft()
    draft.city = 'Брест'
    saveWizardDraftLocal(draft, 2)

    const loaded = loadWizardDraftLocal()
    expect(loaded?.stepIndex).toBe(2)
    expect(loaded?.draft.city).toBe('Брест')
    expect(localStorage.getItem(WIZARD_DRAFT_STORAGE_KEY)).toBeTruthy()

    clearWizardDraftLocal()
    expect(loadWizardDraftLocal()).toBeNull()
  })

  it('detects empty wizard draft', () => {
    expect(isWizardDraftEmpty(createEmptyWizardDraft())).toBe(true)
    const draft = createEmptyWizardDraft()
    draft.city = 'Минск'
    expect(isWizardDraftEmpty(draft)).toBe(false)
  })

  it('resolves region label from map.regions locale key', () => {
    const translate = (key: string) => {
      if (key === 'map.regions.minsk-city') return 'г. Минск'
      return key
    }
    expect(resolveRegionLabel('minsk-city', translate, 'Минск')).toBe('г. Минск')
    expect(resolveRegionLabel('unknown-slug', translate, 'Минск')).toBe('Минск')
    expect(resolveRegionLabel(undefined, translate, '')).toBe('')
  })

  it('filters suggest options by query', () => {
    expect(filterSuggestOptions(['Минск', 'Могилёв', 'Брест'], 'ми')).toEqual(['Минск'])
  })

  it('matches closest option for geocoded values', () => {
    expect(matchClosestOption(['Центр', 'Заводской'], 'центральный район')).toBe('Центр')
    expect(matchClosestOption(['Минск'], 'Минск')).toBe('Минск')
  })
})
