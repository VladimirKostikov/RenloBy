import { describe, expect, it } from 'vitest'
import {
  ACCOUNT_CABINETS,
  ACCOUNT_NAV_ITEMS,
  ACCOUNT_NAV_SECTIONS,
  ACCOUNT_PINNED_NAV_ITEMS,
  ACCOUNT_SELLER_NAV_ITEMS,
  ACCOUNT_USER_NAV_ITEMS,
  isAccountNavActive,
  resolveAccountCabinet,
} from '@/modules/account/lib/accountNav'

describe('accountNav', () => {
  it('pins profile and notifications as first two slots in both cabinets', () => {
    expect(ACCOUNT_PINNED_NAV_ITEMS.map((item) => item.key)).toEqual(['profile', 'notifications'])
    expect(ACCOUNT_USER_NAV_ITEMS.map((item) => item.key).slice(0, 2)).toEqual([
      'profile',
      'notifications',
    ])
    expect(ACCOUNT_SELLER_NAV_ITEMS.map((item) => item.key).slice(0, 2)).toEqual([
      'profile',
      'notifications',
    ])
    expect(ACCOUNT_CABINETS[0]?.items[0]?.key).toBe('profile')
    expect(ACCOUNT_CABINETS[0]?.items[1]?.key).toBe('notifications')
    expect(ACCOUNT_CABINETS[1]?.items[0]?.key).toBe('profile')
    expect(ACCOUNT_CABINETS[1]?.items[1]?.key).toBe('notifications')
  })

  it('defines user and seller sections with separate items', () => {
    expect(ACCOUNT_NAV_SECTIONS).toHaveLength(2)
    expect(ACCOUNT_USER_NAV_ITEMS.map((item) => item.key)).toEqual([
      'profile',
      'notifications',
      'favorites',
      'compare',
      'settings',
    ])
    expect(ACCOUNT_SELLER_NAV_ITEMS.map((item) => item.key)).toEqual([
      'profile',
      'notifications',
      'create',
      'listings',
      'requests',
      'complaints',
      'analytics',
      'promotion',
      'payments',
      'telegram',
    ])
  })

  it('marks nested account routes as active', () => {
    const profile = ACCOUNT_USER_NAV_ITEMS.find((item) => item.key === 'profile')
    const listings = ACCOUNT_SELLER_NAV_ITEMS.find((item) => item.key === 'listings')

    expect(isAccountNavActive(profile!, '/account/user/profile')).toBe(true)
    expect(isAccountNavActive(listings!, '/account/seller/listings')).toBe(true)
  })

  it('marks favorites and compare account routes as active', () => {
    const favorites = ACCOUNT_USER_NAV_ITEMS.find((item) => item.key === 'favorites')
    const compare = ACCOUNT_USER_NAV_ITEMS.find((item) => item.key === 'compare')

    expect(isAccountNavActive(favorites!, '/account/user/favorites')).toBe(true)
    expect(isAccountNavActive(favorites!, '/favorites')).toBe(false)
    expect(isAccountNavActive(compare!, '/account/user/compare')).toBe(true)
  })

  it('resolves active cabinet from route', () => {
    expect(resolveAccountCabinet('/account/user/profile')).toBe('user')
    expect(resolveAccountCabinet('/account/seller/listings')).toBe('seller')
  })

  it('uses default cabinet paths without overview', () => {
    expect(ACCOUNT_NAV_SECTIONS[0]?.items[0]?.to).toBe('/account/user/profile')
    expect(ACCOUNT_NAV_SECTIONS[1]?.items[2]?.to).toBe('/account/seller/create')
  })

  it('defines icon for every nav item', () => {
    for (const item of ACCOUNT_NAV_ITEMS) {
      expect(item.iconSrc, item.key).toMatch(/^\/figma\/.*\.svg$/)
    }
  })

  it('defines distinct icons for cabinet switcher', () => {
    expect(ACCOUNT_CABINETS[0]?.iconSrc).toBe('/figma/account-cabinet-user.svg')
    expect(ACCOUNT_CABINETS[1]?.iconSrc).toBe('/figma/account-cabinet-seller.svg')
    expect(ACCOUNT_CABINETS[0]?.iconSrc).not.toBe(ACCOUNT_CABINETS[1]?.iconSrc)
  })

  it('uses distinct icons for promotion and payments', () => {
    const promotion = ACCOUNT_SELLER_NAV_ITEMS.find((item) => item.key === 'promotion')
    const payments = ACCOUNT_SELLER_NAV_ITEMS.find((item) => item.key === 'payments')
    expect(promotion?.iconSrc).toBe('/figma/account-promotion.svg')
    expect(payments?.iconSrc).toBe('/figma/account-payments.svg')
    expect(promotion?.iconSrc).not.toBe(payments?.iconSrc)
  })

  it('maps section icons to matching assets', () => {
    const byKey = Object.fromEntries(ACCOUNT_NAV_ITEMS.map((item) => [item.key, item.iconSrc]))
    expect(byKey.compare).toBe('/figma/account-compare.svg')
    expect(byKey.notifications).toBe('/figma/account-notifications.svg')
    expect(byKey.favorites).toBe('/figma/heart.svg')
    expect(byKey.listings).toBe('/figma/account-listings.svg')
    expect(byKey.complaints).toBe('/figma/account-complaints.svg')
    expect(byKey.requests).toBe('/figma/account-notifications.svg')
    expect(byKey.telegram).toBe('/figma/account-telegram.svg')
  })
})
