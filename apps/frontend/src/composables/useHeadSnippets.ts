import { onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import { fetchHeadSnippets } from '@/api/headSnippets'
import { clearInjectedHeadSnippets, injectHeadSnippetCodes } from '@/lib/injectHeadSnippets'

export function useHeadSnippets() {
  const route = useRoute()

  async function apply() {
    if (route.path.startsWith('/admin')) {
      clearInjectedHeadSnippets()
      return
    }

    try {
      const snippets = await fetchHeadSnippets()
      injectHeadSnippetCodes(snippets.map((item) => item.code))
    } catch {
      clearInjectedHeadSnippets()
    }
  }

  onMounted(() => {
    void apply()
  })

  watch(
    () => route.path,
    () => {
      void apply()
    },
  )
}
