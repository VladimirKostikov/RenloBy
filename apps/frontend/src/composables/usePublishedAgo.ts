import { computed, toValue, type MaybeRefOrGetter } from 'vue'
import { formatPublishedAgo } from '@/lib/formatPublishedAgo'
import { useNowTicker } from '@/composables/useNowTicker'

export function usePublishedAgo(publishedAt: MaybeRefOrGetter<string>) {
  const nowMs = useNowTicker()

  return computed(() => formatPublishedAgo(toValue(publishedAt), nowMs.value))
}
