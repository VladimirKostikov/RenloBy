import { describe, expect, it } from 'vitest'
import { adminNavItems } from '@/modules/admin/nav'

describe('adminNavItems', () => {
  it('exposes only intended admin sections', () => {
    const paths = adminNavItems.map((item) => item.to)
    expect(paths).toEqual([
      '/admin',
      '/admin/listings',
      '/admin/users',
      '/admin/site-settings',
      '/admin/media-files',
      '/admin/info-pages',
      '/admin/head-snippets',
      '/admin/tariffs',
      '/admin/payment-transactions',
      '/admin/telegram',
      '/admin/seo',
      '/admin/articles',
      '/admin/listing-reports',
      '/admin/listing-requests',
      '/admin/user-notifications',
    ])
    expect(paths).not.toContain('/admin/ai-preferences')
  })
})
