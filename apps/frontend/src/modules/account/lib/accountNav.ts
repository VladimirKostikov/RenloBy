export interface AccountNavItem {
  key: string
  labelKey: string
  iconSrc: string
  to: string
  exact?: boolean
  external?: boolean
}

export interface AccountNavSection {
  key: 'user' | 'seller'
  titleKey: string
  items: AccountNavItem[]
}

export const ACCOUNT_PINNED_NAV_ITEMS: AccountNavItem[] = [
  {
    key: 'profile',
    labelKey: 'account.nav.profile',
    iconSrc: '/figma/account-profile.svg',
    to: '/account/user/profile',
  },
  {
    key: 'notifications',
    labelKey: 'account.nav.notifications',
    iconSrc: '/figma/account-notifications.svg',
    to: '/account/user/notifications',
  },
]

export const ACCOUNT_USER_ONLY_NAV_ITEMS: AccountNavItem[] = [
  {
    key: 'favorites',
    labelKey: 'account.nav.favorites',
    iconSrc: '/figma/heart.svg',
    to: '/account/user/favorites',
  },
  {
    key: 'compare',
    labelKey: 'account.nav.compare',
    iconSrc: '/figma/account-compare.svg',
    to: '/account/user/compare',
  },
  {
    key: 'settings',
    labelKey: 'account.nav.settings',
    iconSrc: '/figma/account-settings.svg',
    to: '/account/user/settings',
  },
]

export const ACCOUNT_SELLER_ONLY_NAV_ITEMS: AccountNavItem[] = [
  {
    key: 'create',
    labelKey: 'account.nav.createListing',
    iconSrc: '/figma/account-create-listing.svg',
    to: '/account/seller/create',
  },
  {
    key: 'listings',
    labelKey: 'account.nav.listings',
    iconSrc: '/figma/account-listings.svg',
    to: '/account/seller/listings',
  },
  {
    key: 'requests',
    labelKey: 'account.nav.requests',
    iconSrc: '/figma/account-notifications.svg',
    to: '/account/seller/requests',
  },
  {
    key: 'complaints',
    labelKey: 'account.nav.complaints',
    iconSrc: '/figma/account-complaints.svg',
    to: '/account/seller/complaints',
  },
  {
    key: 'analytics',
    labelKey: 'account.nav.analytics',
    iconSrc: '/figma/account-analytics.svg',
    to: '/account/seller/analytics',
  },
  {
    key: 'promotion',
    labelKey: 'account.nav.promotion',
    iconSrc: '/figma/account-promotion.svg',
    to: '/account/seller/promotion',
  },
  {
    key: 'payments',
    labelKey: 'account.nav.payments',
    iconSrc: '/figma/account-payments.svg',
    to: '/account/seller/payments',
  },
  {
    key: 'telegram',
    labelKey: 'account.nav.telegramNotifications',
    iconSrc: '/figma/account-telegram.svg',
    to: '/account/seller/telegram',
  },
]

export const ACCOUNT_USER_NAV_ITEMS: AccountNavItem[] = [
  ...ACCOUNT_PINNED_NAV_ITEMS,
  ...ACCOUNT_USER_ONLY_NAV_ITEMS,
]

export const ACCOUNT_SELLER_NAV_ITEMS: AccountNavItem[] = [
  ...ACCOUNT_PINNED_NAV_ITEMS,
  ...ACCOUNT_SELLER_ONLY_NAV_ITEMS,
]

export const ACCOUNT_NAV_SECTIONS: AccountNavSection[] = [
  {
    key: 'user',
    titleKey: 'account.sections.user',
    items: ACCOUNT_USER_NAV_ITEMS,
  },
  {
    key: 'seller',
    titleKey: 'account.sections.seller',
    items: ACCOUNT_SELLER_NAV_ITEMS,
  },
]

export const ACCOUNT_NAV_ITEMS = [...ACCOUNT_USER_NAV_ITEMS, ...ACCOUNT_SELLER_ONLY_NAV_ITEMS]

export type AccountCabinetKey = 'user' | 'seller'

export const ACCOUNT_CABINETS: Array<{
  key: AccountCabinetKey
  titleKey: string
  iconSrc: string
  defaultPath: string
  items: AccountNavItem[]
}> = [
  {
    key: 'user',
    titleKey: 'account.sections.userToggle',
    iconSrc: '/figma/account-cabinet-user.svg',
    defaultPath: '/account/user/profile',
    items: ACCOUNT_USER_NAV_ITEMS,
  },
  {
    key: 'seller',
    titleKey: 'account.sections.sellerToggle',
    iconSrc: '/figma/account-cabinet-seller.svg',
    defaultPath: '/account/seller/listings',
    items: ACCOUNT_SELLER_NAV_ITEMS,
  },
]

export function resolveAccountCabinet(path: string): AccountCabinetKey {
  if (path.startsWith('/account/seller')) {
    return 'seller'
  }

  return 'user'
}

export function isAccountNavActive(item: AccountNavItem, path: string): boolean {
  if (item.external) {
    return false
  }

  if (item.exact) {
    return path === item.to
  }

  return path === item.to || path.startsWith(`${item.to}/`)
}
