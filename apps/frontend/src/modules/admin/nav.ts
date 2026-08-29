export type AdminNavIcon =
  | 'dashboard'
  | 'users'
  | 'listings'
  | 'articles'
  | 'seo'
  | 'telegram'
  | 'complaints'
  | 'notifications'
  | 'tariffs'
  | 'payments'
  | 'site'
  | 'info'
  | 'code'
  | 'media'

export interface AdminNavItem {
  to: string
  labelKey: string
  icon: AdminNavIcon
  exact?: boolean
}

export const adminNavItems: AdminNavItem[] = [
  { to: '/admin', labelKey: 'admin.dashboard', icon: 'dashboard', exact: true },
  { to: '/admin/listings', labelKey: 'admin.listings', icon: 'listings' },
  { to: '/admin/users', labelKey: 'admin.users', icon: 'users' },
  { to: '/admin/site-settings', labelKey: 'admin.siteSettings', icon: 'site' },
  { to: '/admin/media-files', labelKey: 'admin.mediaFiles', icon: 'media' },
  { to: '/admin/info-pages', labelKey: 'admin.infoPages', icon: 'info' },
  { to: '/admin/head-snippets', labelKey: 'admin.headSnippets', icon: 'code' },
  { to: '/admin/tariffs', labelKey: 'admin.tariffs', icon: 'tariffs' },
  { to: '/admin/payment-transactions', labelKey: 'admin.paymentTransactions', icon: 'payments' },
  { to: '/admin/telegram', labelKey: 'admin.telegram', icon: 'telegram' },
  { to: '/admin/seo', labelKey: 'admin.seo', icon: 'seo' },
  { to: '/admin/articles', labelKey: 'admin.articles', icon: 'articles' },
  { to: '/admin/listing-reports', labelKey: 'admin.complaints', icon: 'complaints' },
  { to: '/admin/listing-requests', labelKey: 'admin.listingRequests', icon: 'notifications' },
  { to: '/admin/user-notifications', labelKey: 'admin.userNotifications', icon: 'notifications' },
]
