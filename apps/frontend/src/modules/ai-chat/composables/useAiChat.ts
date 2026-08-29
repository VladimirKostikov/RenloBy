import { computed, nextTick, ref } from 'vue'
import { sendAiChatMessage, type AiChatHistoryItem, type AiChatRole } from '@/api/aiChat'
import { resolveApiError } from '@/lib/resolveApiError'
import { i18n } from '@/modules/locale'

export interface AiChatUiMessage {
  id: number
  role: AiChatRole
  content: string
}

const isOpen = ref(false)
const messages = ref<AiChatUiMessage[]>([])
const draft = ref('')
const loading = ref(false)
const error = ref('')
let nextId = 1

export function useAiChat() {
  const hasMessages = computed(() => messages.value.length > 0)

  function open() {
    isOpen.value = true
    error.value = ''
  }

  function close() {
    isOpen.value = false
  }

  function toggle() {
    if (isOpen.value) {
      close()
      return
    }
    open()
  }

  function clearError() {
    error.value = ''
  }

  async function send(text?: string) {
    const content = (text ?? draft.value).trim()
    if (!content || loading.value) {
      return
    }

    error.value = ''
    draft.value = ''
    messages.value.push({
      id: nextId++,
      role: 'user',
      content,
    })
    loading.value = true

    const history: AiChatHistoryItem[] = messages.value
      .slice(0, -1)
      .slice(-20)
      .map((item) => ({
        role: item.role,
        content: item.content,
      }))

    try {
      const locale = i18n.global.locale.value === 'en' ? 'en' : 'ru'
      const response = await sendAiChatMessage(content, history, locale)
      messages.value.push({
        id: nextId++,
        role: 'assistant',
        content: response.reply,
      })
      await nextTick()
    } catch (err) {
      const resolved = resolveApiError(err, i18n.global.t, 'errors.ai_chat.unavailable')
      error.value = resolved.message
    } finally {
      loading.value = false
    }
  }

  return {
    isOpen,
    messages,
    draft,
    loading,
    error,
    hasMessages,
    open,
    close,
    toggle,
    clearError,
    send,
  }
}
