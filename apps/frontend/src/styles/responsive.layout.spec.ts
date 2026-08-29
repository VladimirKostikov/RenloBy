import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'
import { describe, expect, it } from 'vitest'

describe('responsive layout tokens', () => {
  const responsiveCss = readFileSync(
    resolve(__dirname, './responsive.css'),
    'utf8',
  )
  const homeHeader = readFileSync(
    resolve(__dirname, '../components/layout/HomeHeader.vue'),
    'utf8',
  )
  const catalogSidebar = readFileSync(
    resolve(__dirname, '../components/catalog/CatalogSidebarFilters.vue'),
    'utf8',
  )
  const accountSidebar = readFileSync(
    resolve(__dirname, '../modules/account/components/AccountSidebar.vue'),
    'utf8',
  )
  const cookieConsent = readFileSync(
    resolve(__dirname, '../modules/consent/CookieConsentBanner.vue'),
    'utf8',
  )
  const comparisonTable = readFileSync(
    resolve(__dirname, '../modules/collections/components/ComparisonTable.vue'),
    'utf8',
  )
  const favoritesPanel = readFileSync(
    resolve(__dirname, '../modules/collections/components/FavoritesPanel.vue'),
    'utf8',
  )
  const comparisonPanel = readFileSync(
    resolve(__dirname, '../modules/collections/components/ComparisonPanel.vue'),
    'utf8',
  )
  const infoPageView = readFileSync(
    resolve(__dirname, '../views/InfoPageView.vue'),
    'utf8',
  )
  const searchMapView = readFileSync(
    resolve(__dirname, '../views/SearchMapView.vue'),
    'utf8',
  )
  const saleCatalogView = readFileSync(
    resolve(__dirname, '../views/SaleCatalogView.vue'),
    'utf8',
  )
  const figmaCatalog = readFileSync(
    resolve(__dirname, '../themes/figma-catalog.css'),
    'utf8',
  )

  it('defines mobile tablet and desktop breakpoints', () => {
    expect(responsiveCss).toContain('--bp-mobile-max: 767px')
    expect(responsiveCss).toContain('--bp-tablet-min: 768px')
    expect(responsiveCss).toContain('--bp-desktop-min: 1280px')
    expect(responsiveCss).toContain('--figma-page-padding-x: 20px')
    expect(responsiveCss).toContain('overflow-x: hidden')
    expect(responsiveCss).toContain('safe-area-inset-left')
  })

  it('stacks home header into logo nav and search rows on mobile', () => {
    expect(homeHeader).toContain('@media (max-width: 767px)')
    expect(homeHeader).toContain('display: contents')
    expect(homeHeader).toContain("grid-area: logo")
    expect(homeHeader).toContain("grid-area: nav")
    expect(homeHeader).toContain("grid-area: center")
    expect(homeHeader).toContain("'logo nav actions'")
    expect(homeHeader).toContain('.home-header__cta span')
    expect(homeHeader).toContain('.home-header__brand')
    expect(homeHeader).toContain('.home-header__login > span:not(.home-header__login-icon-wrap)')
    expect(homeHeader).toContain('.home-header__user > span:not(.home-header__user-icon-wrap)')
  })

  it('compacts top bar on mobile by hiding rate and social contacts', () => {
    const topBar = readFileSync(
      resolve(__dirname, '../components/layout/HeaderTopBar.vue'),
      'utf8',
    )
    expect(topBar).toContain('.header-top-bar__rate')
    expect(topBar).toContain('.header-top-bar__contacts')
    expect(topBar).toContain('@media (max-width: 767px)')
  })

  it('keeps search filter bar as a single compact row on mobile', () => {
    const filterBar = readFileSync(
      resolve(__dirname, '../components/FilterBar.vue'),
      'utf8',
    )
    expect(filterBar).toContain('@media (max-width: 767px)')
    expect(filterBar).toContain('flex-wrap: nowrap')
    expect(filterBar).toContain('.filter-bar--compact :deep(.filter-chip--object-type)')
    expect(filterBar).toContain('display: none')
    expect(filterBar).toContain('@click="openMoreFilters"')
  })

  it('collapses catalog filters behind a checkbox toggle on mobile', () => {
    expect(catalogSidebar).toContain('catalog-sidebar__toggle-input')
    expect(catalogSidebar).toContain('catalog-sidebar__body')
    expect(catalogSidebar).toContain('.catalog-sidebar__toggle-input:checked ~ .catalog-sidebar__body')
  })

  it('makes account sidebar nav horizontally scrollable below desktop', () => {
    expect(accountSidebar).toContain('@media (max-width: 1279px)')
    expect(accountSidebar).toContain('overflow-x: auto')
    expect(accountSidebar).toContain('flex-direction: row')
  })

  it('aligns cookie consent banner breakpoint with the shared mobile token', () => {
    expect(cookieConsent).toContain('@media (max-width: 767px)')
    expect(cookieConsent).not.toContain('@media (max-width: 640px)')
    expect(cookieConsent).toContain('safe-area-inset-left')
  })

  it('keeps comparison table scroll wrapper separate from body overflow and grows mobile touch targets', () => {
    expect(comparisonTable).toContain('.comparison-table__scroll')
    expect(comparisonTable).toContain('overflow-x: auto')
    expect(comparisonTable).toContain('--touch-target-min')
  })

  it('reduces favorites and comparison panel padding on mobile without affecting embedded mode', () => {
    expect(favoritesPanel).toContain('.favorites-panel:not(.favorites-panel--embedded)')
    expect(comparisonPanel).toContain('.comparison-panel:not(.comparison-panel--embedded)')
  })

  it('tightens info page content padding on mobile', () => {
    expect(infoPageView).toContain('@media (max-width: 767px)')
    expect(infoPageView).toContain('.info-page__content')
  })

  it('switches list and map panels below desktop and keeps filter grid single column', () => {
    expect(searchMapView).toContain('@media (max-width: 1279px)')
    expect(searchMapView).toContain('.search-map__layout.search-map__layout--with-filters')
    expect(searchMapView).toContain('.search-map__mobile-switch')
    expect(searchMapView).toContain(".search-map__content--list .search-map__panels :deep(.map-panel)")
    expect(searchMapView).toContain('display: none')
  })

  it('replaces horizontal filter bar with vertical filters button on mobile', () => {
    expect(searchMapView).toContain('@media (max-width: 767px)')
    expect(searchMapView).toContain('.search-map__filters-open')
    expect(searchMapView).toContain('.search-map :deep(.filter-bar)')
  })

  it('stacks catalog layout into one column below desktop', () => {
    expect(saleCatalogView).toContain('@media (max-width: 1279px)')
    expect(saleCatalogView).toContain('grid-template-columns: 1fr')
    expect(figmaCatalog).toContain('@media (min-width: 1280px)')
    expect(figmaCatalog).toContain('.catalog-layout__sidebar')
    expect(figmaCatalog).toContain('position: sticky')
  })

  it('keeps listing list scrollable on mobile outside search-map viewport', () => {
    const source = readFileSync(
      resolve(__dirname, '../components/ListingList.vue'),
      'utf8',
    )
    expect(source).toContain('@media (max-width: 767px)')
    expect(source).toContain('max-height: min(52vh, 480px)')
    expect(source).toContain('overscroll-behavior: contain')
  })

  it('stacks listing detail actions on mobile with heart beside compare', () => {
    const source = readFileSync(
      resolve(__dirname, '../components/ListingDetailPanel.vue'),
      'utf8',
    )
    expect(source).toContain('@media (max-width: 767px)')
    expect(source).toContain('grid-template-columns: auto minmax(0, 1fr)')
    expect(source).toContain('.listing-detail-modal__actions > .listing-detail-modal__action-btn:nth-child(2)')
    expect(source).toContain('.listing-detail-modal__actions > .listing-detail-modal__action-btn:nth-child(3)')
    expect(source).toContain('grid-column: 1 / -1')
  })
})
