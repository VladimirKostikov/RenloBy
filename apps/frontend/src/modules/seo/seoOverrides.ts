import { ref } from 'vue'
import { fetchSeoMeta, type SeoMetaDto } from '@/api/seoMeta'

export type SeoOverrideMap = Record<
  string,
  { title: string; description: string; h1: string | null; keywords: string | null }
>

const cache = ref<Record<string, SeoOverrideMap>>({})
const inflight = new Map<string, Promise<SeoOverrideMap>>()
export const seoOverridesVersion = ref(0)

export function seoMetaListToMap(items: SeoMetaDto[]): SeoOverrideMap {
  const map: SeoOverrideMap = {}
  for (const item of items) {
    map[item.pageKey] = {
      title: item.title,
      description: item.description,
      h1: item.h1,
      keywords: item.keywords ?? null,
    }
  }
  return map
}

export async function loadSeoOverrides(locale: string): Promise<SeoOverrideMap> {
  if (cache.value[locale]) {
    return cache.value[locale]
  }

  const existing = inflight.get(locale)
  if (existing) {
    return existing
  }

  const promise = fetchSeoMeta(locale)
    .then((items) => {
      const map = seoMetaListToMap(items)
      cache.value = { ...cache.value, [locale]: map }
      seoOverridesVersion.value += 1
      return map
    })
    .catch(() => {
      const empty: SeoOverrideMap = {}
      cache.value = { ...cache.value, [locale]: empty }
      seoOverridesVersion.value += 1
      return empty
    })
    .finally(() => {
      inflight.delete(locale)
    })

  inflight.set(locale, promise)
  return promise
}

export function peekSeoOverrides(locale: string): SeoOverrideMap | undefined {
  return cache.value[locale]
}
