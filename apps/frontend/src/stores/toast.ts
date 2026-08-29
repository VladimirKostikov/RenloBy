import { defineStore } from 'pinia'
import { ref } from 'vue'

const DEFAULT_DURATION_MS = 2600

export const useToastStore = defineStore('toast', () => {
  const message = ref('')
  const visible = ref(false)
  let hideTimer: ReturnType<typeof setTimeout> | null = null

  function clearTimer() {
    if (hideTimer !== null) {
      clearTimeout(hideTimer)
      hideTimer = null
    }
  }

  function hide() {
    clearTimer()
    visible.value = false
  }

  function show(nextMessage: string, durationMs = DEFAULT_DURATION_MS) {
    const trimmed = nextMessage.trim()
    if (!trimmed) {
      return
    }

    clearTimer()
    message.value = trimmed
    visible.value = true

    hideTimer = setTimeout(() => {
      visible.value = false
      hideTimer = null
    }, durationMs)
  }

  return {
    message,
    visible,
    show,
    hide,
  }
})
