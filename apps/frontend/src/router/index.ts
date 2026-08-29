import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { appScrollBehavior } from '@/router/scrollBehavior'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  scrollBehavior: appScrollBehavior,
  routes: [
    {
      path: '/',
      name: 'home',
      component: () => import('@/views/HomeView.vue'),
    },
    {
      path: '/rent',
      name: 'rent-catalog',
      component: () => import('@/views/RentCatalogView.vue'),
    },
    {
      path: '/rent/listings/:id',
      name: 'rent-listing-detail',
      component: () => import('@/views/RentCatalogView.vue'),
      meta: { dealType: 'rent', catalogRouteName: 'rent-catalog' },
    },
    {
      path: '/sale',
      name: 'sale-catalog',
      component: () => import('@/views/SaleCatalogView.vue'),
    },
    {
      path: '/listings/:id',
      name: 'listing-detail',
      component: () => import('@/views/HomeView.vue'),
      meta: { catalogRouteName: 'home' },
    },
    {
      path: '/search',
      name: 'search-map',
      component: () => import('@/views/SearchMapView.vue'),
    },
    {
      path: '/search/listings/:id',
      name: 'search-listing-detail',
      component: () => import('@/views/SearchMapView.vue'),
    },
    {
      path: '/sale/listings/:id',
      name: 'sale-listing-detail',
      component: () => import('@/views/SaleCatalogView.vue'),
      meta: { dealType: 'sale', catalogRouteName: 'sale-catalog' },
    },
    {
      path: '/commercial',
      name: 'commercial-catalog',
      component: () => import('@/views/CommercialCatalogView.vue'),
    },
    {
      path: '/commercial/listings/:id',
      name: 'commercial-listing-detail',
      component: () => import('@/views/CommercialCatalogView.vue'),
      meta: { listingType: 'commercial', catalogRouteName: 'commercial-catalog' },
    },
    {
      path: '/info',
      redirect: { name: 'info-page', params: { slug: 'deal-safety' } },
    },
    {
      path: '/info/:slug',
      name: 'info-page',
      component: () => import('@/views/InfoPageView.vue'),
    },
    {
      path: '/articles',
      name: 'articles',
      component: () => import('@/views/ArticlesView.vue'),
    },
    {
      path: '/articles/:slug',
      name: 'article',
      component: () => import('@/views/ArticleView.vue'),
    },
    {
      path: '/region/:regionSlug/listings/:id',
      name: 'region-listing-detail',
      component: () => import('@/views/LocationPageView.vue'),
    },
    {
      path: '/region/:regionSlug',
      name: 'region-location',
      component: () => import('@/views/LocationPageView.vue'),
    },
    {
      path: '/city/:citySlug/listings/:id',
      name: 'city-listing-detail',
      component: () => import('@/views/LocationPageView.vue'),
    },
    {
      path: '/city/:citySlug/:districtSlug/listings/:id',
      name: 'district-listing-detail',
      component: () => import('@/views/LocationPageView.vue'),
    },
    {
      path: '/city/:citySlug/:districtSlug',
      name: 'district-location',
      component: () => import('@/views/LocationPageView.vue'),
    },
    {
      path: '/city/:citySlug',
      name: 'city-location',
      component: () => import('@/views/LocationPageView.vue'),
    },
    {
      path: '/login',
      name: 'login',
      component: () => import('@/views/LoginView.vue'),
    },
    {
      path: '/favorites',
      name: 'favorites',
      component: () => import('@/views/FavoritesView.vue'),
    },
    {
      path: '/compare',
      name: 'compare',
      component: () => import('@/views/ComparisonView.vue'),
    },
    {
      path: '/account',
      component: () => import('@/modules/account/layout/AccountLayout.vue'),
      meta: { requiresAuth: true },
      children: [
        {
          path: '',
          redirect: '/account/user/profile',
        },
        {
          path: 'user',
          redirect: '/account/user/profile',
        },
        {
          path: 'user/profile',
          name: 'account-user-profile',
          component: () => import('@/views/account/AccountProfileView.vue'),
        },
        {
          path: 'user/favorites',
          name: 'account-user-favorites',
          component: () => import('@/views/account/AccountFavoritesView.vue'),
        },
        {
          path: 'user/compare',
          name: 'account-user-compare',
          component: () => import('@/views/account/AccountCompareView.vue'),
        },
        {
          path: 'user/notifications',
          name: 'account-user-notifications',
          component: () => import('@/views/account/AccountNotificationsView.vue'),
        },
        {
          path: 'user/settings',
          name: 'account-user-settings',
          component: () => import('@/views/account/AccountSettingsView.vue'),
        },
        {
          path: 'seller',
          redirect: '/account/seller/listings',
        },
        {
          path: 'seller/create',
          name: 'account-seller-create',
          component: () => import('@/views/account/AccountSellerCreateListingView.vue'),
        },
        {
          path: 'seller/listings',
          name: 'account-seller-listings',
          component: () => import('@/views/account/AccountListingsView.vue'),
        },
        {
          path: 'seller/requests',
          name: 'account-seller-requests',
          component: () => import('@/views/account/AccountSellerRequestsView.vue'),
        },
        {
          path: 'seller/complaints',
          name: 'account-seller-complaints',
          component: () => import('@/views/account/AccountSellerComplaintsView.vue'),
        },
        {
          path: 'seller/analytics',
          name: 'account-seller-analytics',
          component: () => import('@/views/account/AccountSellerAnalyticsView.vue'),
        },
        {
          path: 'seller/promotion',
          name: 'account-seller-promotion',
          component: () => import('@/views/account/AccountSellerPromotionView.vue'),
        },
        {
          path: 'seller/payments',
          name: 'account-seller-payments',
          component: () => import('@/views/account/AccountPaymentsView.vue'),
        },
        {
          path: 'seller/telegram',
          name: 'account-seller-telegram',
          component: () => import('@/views/account/AccountSellerTelegramView.vue'),
        },
        {
          path: 'profile',
          redirect: '/account/user/profile',
        },
        {
          path: 'listings',
          redirect: '/account/seller/listings',
        },
        {
          path: 'favorites',
          redirect: '/account/user/favorites',
        },
        {
          path: 'compare',
          redirect: '/account/user/compare',
        },
      ],
    },
    {
      path: '/promotion/payment',
      name: 'promotion-payment',
      component: () => import('@/views/PromotionPaymentView.vue'),
    },
    {
      path: '/admin',
      component: () => import('@/modules/admin/layout/AdminLayout.vue'),
      meta: { requiresAdmin: true },
      children: [
        {
          path: '',
          name: 'admin-dashboard',
          component: () => import('@/views/admin/AdminDashboardView.vue'),
        },
        {
          path: 'listings',
          name: 'admin-listings',
          component: () => import('@/views/admin/ListingsAdminView.vue'),
        },
        {
          path: 'users',
          name: 'admin-users',
          component: () => import('@/views/admin/UsersAdminView.vue'),
        },
        {
          path: 'site-settings',
          name: 'admin-site-settings',
          component: () => import('@/views/admin/SiteSettingsAdminView.vue'),
        },
        {
          path: 'media-files',
          name: 'admin-media-files',
          component: () => import('@/views/admin/MediaFilesAdminView.vue'),
        },
        {
          path: 'tariffs',
          name: 'admin-tariffs',
          component: () => import('@/views/admin/TariffsAdminView.vue'),
        },
        {
          path: 'payment-transactions',
          name: 'admin-payment-transactions',
          component: () => import('@/views/admin/PaymentTransactionsAdminView.vue'),
        },
        {
          path: 'telegram',
          name: 'admin-telegram',
          component: () => import('@/views/admin/TelegramAdminView.vue'),
        },
        {
          path: 'seo',
          name: 'admin-seo',
          component: () => import('@/views/admin/SeoAdminView.vue'),
        },
        {
          path: 'head-snippets',
          name: 'admin-head-snippets',
          component: () => import('@/views/admin/HeadSnippetsAdminView.vue'),
        },
        {
          path: 'info-pages',
          name: 'admin-info-pages',
          component: () => import('@/views/admin/InfoPagesAdminView.vue'),
        },
        {
          path: 'articles',
          name: 'admin-articles',
          component: () => import('@/views/admin/ArticlesAdminView.vue'),
        },
        {
          path: 'listing-reports',
          name: 'admin-listing-reports',
          component: () => import('@/views/admin/ComplaintsAdminView.vue'),
        },
        {
          path: 'listing-requests',
          name: 'admin-listing-requests',
          component: () => import('@/views/admin/ListingRequestsAdminView.vue'),
        },
        {
          path: 'user-notifications',
          name: 'admin-user-notifications',
          component: () => import('@/views/admin/UserNotificationsAdminView.vue'),
        },
      ],
    },
  ],
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()
  if (!auth.initialized) {
    await auth.initialize()
  }
  if (to.meta.requiresAdmin && !auth.isAdmin) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }
  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }
})

export default router
