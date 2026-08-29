export interface FooterLink {
  key: string
  labelKey: string
  to?: string
  external?: boolean
  action?: 'login' | 'register'
}

export interface FooterSection {
  key: string
  titleKey: string
  links: FooterLink[]
}

export const FOOTER_SECTIONS: FooterSection[] = [
  {
    key: 'catalog',
    titleKey: 'footer.sections.catalog',
    links: [
      { key: 'map', labelKey: 'footer.links.map', to: '/' },
      { key: 'search', labelKey: 'footer.links.search', to: '/search' },
      { key: 'sale', labelKey: 'footer.links.sale', to: '/sale' },
      { key: 'rent', labelKey: 'footer.links.rent', to: '/rent' },
      { key: 'commercial', labelKey: 'footer.links.commercial', to: '/commercial' },
    ],
  },
  {
    key: 'info',
    titleKey: 'footer.sections.info',
    links: [
      { key: 'buyers', labelKey: 'footer.links.buyers', to: '/info/buyers' },
      { key: 'sellers', labelKey: 'footer.links.sellers', to: '/info/sellers' },
      { key: 'renters', labelKey: 'footer.links.renters', to: '/info/renters' },
      { key: 'deal-safety', labelKey: 'footer.links.dealSafety', to: '/info/deal-safety' },
      { key: 'faq', labelKey: 'footer.links.faq', to: '/info/faq' },
      { key: 'support', labelKey: 'footer.links.support', to: '/info/support' },
      { key: 'articles', labelKey: 'footer.links.articles', to: '/articles' },
    ],
  },
  {
    key: 'service',
    titleKey: 'footer.sections.service',
    links: [
      { key: 'post-listing', labelKey: 'footer.links.postListing', action: 'register' },
      { key: 'promotion', labelKey: 'footer.links.promotion', to: '/promotion/payment' },
      { key: 'login', labelKey: 'footer.links.login', action: 'login' },
    ],
  },
  {
    key: 'legal',
    titleKey: 'footer.sections.legal',
    links: [
      { key: 'offer', labelKey: 'footer.links.offer', to: '/info/offer' },
      { key: 'privacy', labelKey: 'footer.links.privacy', to: '/info/privacy' },
      { key: 'personal-data', labelKey: 'footer.links.personalData', to: '/info/personal-data' },
    ],
  },
]

export const FOOTER_LEGAL_LINKS: FooterLink[] = [
  { key: 'offer', labelKey: 'footer.links.offer', to: '/info/offer' },
  { key: 'privacy', labelKey: 'footer.links.privacy', to: '/info/privacy' },
  { key: 'personal-data', labelKey: 'footer.links.personalData', to: '/info/personal-data' },
]
