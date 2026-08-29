import { computed, onMounted, toValue, watch, type MaybeRefOrGetter } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute } from 'vue-router'
import { buildRoutePageMeta } from './index'
import { loadSeoOverrides, peekSeoOverrides, seoOverridesVersion } from './seoOverrides'
import { usePageSeo } from './usePageSeo'
import type { ArticleSeoContext, InfoPageSeoContext, ListingSeoContext, LocationSeoContext } from './types'

interface RouteSeoOptions {
  listing?: MaybeRefOrGetter<ListingSeoContext | null | undefined>
  infoPage?: MaybeRefOrGetter<InfoPageSeoContext | null | undefined>
  article?: MaybeRefOrGetter<ArticleSeoContext | null | undefined>
  location?: MaybeRefOrGetter<LocationSeoContext | null | undefined>
  noindex?: MaybeRefOrGetter<boolean | undefined>
}

function currentLocale(locale: string): 'ru' | 'en' {
  return locale === 'en' ? 'en' : 'ru'
}

export function useRoutePageSeo(options: RouteSeoOptions = {}) {
  const route = useRoute()
  const { locale } = useI18n()

  async function refreshOverrides() {
    await loadSeoOverrides(currentLocale(locale.value))
  }

  onMounted(() => {
    void refreshOverrides()
  })

  watch(locale, () => {
    void refreshOverrides()
  })

  const meta = computed(() => {
    void seoOverridesVersion.value
    const loc = currentLocale(locale.value)
    return buildRoutePageMeta(route.path, loc, {
      listing: toValue(options.listing) ?? undefined,
      infoPage: toValue(options.infoPage) ?? undefined,
      article: toValue(options.article) ?? undefined,
      location: toValue(options.location) ?? undefined,
      noindex: toValue(options.noindex),
      seoOverrides: peekSeoOverrides(loc),
    })
  })

  usePageSeo(meta)

  return { meta }
}

export function buildListingSeoContext(
  listing: ListingSeoContext['listing'],
  cityName: string,
  districtName: string,
): ListingSeoContext {
  return {
    listing: {
      id: listing.id,
      dealType: listing.dealType,
      listingType: listing.listingType,
      price: listing.price,
      rooms: listing.rooms,
      area: listing.area,
      address: listing.address,
      images: listing.images,
      metaTitle: listing.metaTitle ?? null,
      metaDescription: listing.metaDescription ?? null,
      metaKeywords: listing.metaKeywords ?? null,
    },
    cityName,
    districtName,
  }
}
