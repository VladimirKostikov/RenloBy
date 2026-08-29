import { toValue, type MaybeRefOrGetter } from 'vue'
import { useHead, type ReactiveHead } from '@unhead/vue'
import { pageMetaToHead } from './pageMetaToHead'
import type { PageMeta } from './types'

export function usePageSeo(meta: MaybeRefOrGetter<PageMeta | null | undefined>) {
  useHead((): ReactiveHead => pageMetaToHead(toValue(meta)) as ReactiveHead)
}
