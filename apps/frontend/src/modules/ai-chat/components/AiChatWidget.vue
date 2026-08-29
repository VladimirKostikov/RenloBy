<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute } from 'vue-router'
import { useAiChat } from '@/modules/ai-chat/composables/useAiChat'

const { t } = useI18n()
const route = useRoute()
const {
  isOpen,
  messages,
  draft,
  loading,
  error,
  hasMessages,
  open,
  close,
  toggle,
  send,
} = useAiChat()

const panelRef = ref<HTMLElement | null>(null)
const listRef = ref<HTMLElement | null>(null)
const inputRef = ref<HTMLTextAreaElement | null>(null)

const isAdminRoute = computed(() => route.path.startsWith('/admin'))
const visible = computed(() => !isAdminRoute.value)

const quickReplies = computed(() => [
  t('aiChat.quick.saleMinsk'),
  t('aiChat.quick.rentHelp'),
  t('aiChat.quick.howToList'),
  t('aiChat.quick.tariffs'),
])

async function scrollToBottom(behavior: ScrollBehavior = 'smooth') {
  await nextTick()
  const el = listRef.value
  if (!el) {
    return
  }
  const reduceMotion = typeof window !== 'undefined'
    && window.matchMedia('(prefers-reduced-motion: reduce)').matches
  el.scrollTo({
    top: el.scrollHeight,
    behavior: reduceMotion ? 'auto' : behavior,
  })
}

watch(messages, () => {
  void scrollToBottom('smooth')
}, { deep: true })

watch(loading, (value) => {
  if (value) {
    void scrollToBottom('smooth')
  }
})

watch(isOpen, async (openState) => {
  if (!openState) {
    return
  }
  await scrollToBottom('auto')
  await nextTick()
  inputRef.value?.focus()
})

function onKeydown(event: KeyboardEvent) {
  if (event.key === 'Escape' && isOpen.value) {
    close()
  }
}

async function onSubmit() {
  await send()
  await scrollToBottom()
}

async function onQuickReply(text: string) {
  if (loading.value) {
    return
  }
  await send(text)
  await scrollToBottom()
}

function onComposerKeydown(event: KeyboardEvent) {
  if (event.key === 'Enter' && !event.shiftKey) {
    event.preventDefault()
    void onSubmit()
  }
}

onMounted(() => {
  window.addEventListener('keydown', onKeydown)
})

onBeforeUnmount(() => {
  window.removeEventListener('keydown', onKeydown)
})

defineExpose({ open, close, toggle })
</script>

<template>
  <Teleport to="body">
    <div v-if="visible" class="ai-chat" :class="{ 'ai-chat--open': isOpen }">
      <Transition name="ai-chat-panel">
        <section
          v-if="isOpen"
          ref="panelRef"
          class="ai-chat__panel"
          role="dialog"
          aria-modal="false"
          :aria-label="t('aiChat.title')"
        >
          <header class="ai-chat__header">
            <div class="ai-chat__header-text">
              <p class="ai-chat__title">{{ t('aiChat.title') }}</p>
              <p class="ai-chat__subtitle">{{ t('aiChat.subtitle') }}</p>
            </div>
            <button
              type="button"
              class="ai-chat__icon-btn"
              :aria-label="t('aiChat.close')"
              @click="close"
            >
              <span aria-hidden="true">×</span>
            </button>
          </header>

          <div ref="listRef" class="ai-chat__messages" role="log" aria-live="polite">
            <Transition name="ai-chat-fade">
              <p v-if="!hasMessages" key="welcome" class="ai-chat__welcome">{{ t('aiChat.welcome') }}</p>
            </Transition>
            <TransitionGroup name="ai-chat-msg" tag="div" class="ai-chat__thread">
              <div
                v-for="item in messages"
                :key="item.id"
                class="ai-chat__bubble"
                :class="item.role === 'user' ? 'ai-chat__bubble--user' : 'ai-chat__bubble--assistant'"
              >
                {{ item.content }}
              </div>
            </TransitionGroup>
            <Transition name="ai-chat-fade">
              <p v-if="loading" key="typing" class="ai-chat__typing">{{ t('aiChat.typing') }}</p>
            </Transition>
            <Transition name="ai-chat-fade">
              <p v-if="error" key="error" class="ai-chat__error" role="alert">{{ error }}</p>
            </Transition>
          </div>

          <div class="ai-chat__footer">
            <div class="ai-chat__quick" role="group" :aria-label="t('aiChat.quickLabel')">
              <button
                v-for="reply in quickReplies"
                :key="reply"
                type="button"
                class="ai-chat__quick-btn"
                :disabled="loading"
                @click="onQuickReply(reply)"
              >
                {{ reply }}
              </button>
            </div>

            <form class="ai-chat__composer" @submit.prevent="onSubmit">
              <label class="visually-hidden" for="ai-chat-input">{{ t('aiChat.placeholder') }}</label>
              <textarea
                id="ai-chat-input"
                ref="inputRef"
                v-model="draft"
                class="ai-chat__input"
                rows="1"
                :placeholder="t('aiChat.placeholder')"
                :disabled="loading"
                maxlength="2000"
                @keydown="onComposerKeydown"
              />
              <button
                type="submit"
                class="ai-chat__send"
                :disabled="loading || !draft.trim()"
                :aria-label="t('aiChat.send')"
              >
                <svg class="ai-chat__send-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                  <path
                    fill="currentColor"
                    d="M3.4 20.6 20.95 12 3.4 3.4l.05 6.75L14.1 12 3.45 13.85l-.05 6.75Z"
                  />
                </svg>
              </button>
            </form>
          </div>
        </section>
      </Transition>

      <button
        v-show="!isOpen"
        type="button"
        class="ai-chat__fab"
        :aria-expanded="isOpen"
        :aria-label="t('aiChat.title')"
        @click="toggle"
      >
        <img src="/figma/ai-assistant.svg" alt="" width="22" height="22" />
      </button>
    </div>
  </Teleport>
</template>

<style scoped>
.ai-chat {
  position: fixed;
  right: max(20px, env(safe-area-inset-right, 0px));
  bottom: max(20px, env(safe-area-inset-bottom, 0px));
  z-index: 4200;
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 12px;
  pointer-events: none;
}

.ai-chat__panel,
.ai-chat__fab {
  pointer-events: auto;
}

.ai-chat__panel {
  display: flex;
  flex-direction: column;
  width: min(380px, calc(100vw - 40px));
  height: min(520px, calc(100dvh - 100px));
  max-height: calc(100vh - 100px);
  border: 1px solid var(--figma-border, #e5e7eb);
  border-radius: 16px;
  background: var(--color-bg, #fff);
  box-shadow: 0 12px 40px rgba(15, 23, 42, 0.18);
  overflow: hidden;
  transform-origin: bottom right;
}

.ai-chat__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  padding: 14px 14px 12px;
  background: var(--figma-accent, var(--accent, #e14554));
  color: var(--figma-on-accent);
}

.ai-chat__header-text {
  min-width: 0;
}

.ai-chat__title {
  margin: 0;
  font-size: 15px;
  font-weight: 700;
  line-height: 1.3;
}

.ai-chat__subtitle {
  margin: 4px 0 0;
  font-size: 12px;
  line-height: 1.4;
  opacity: 0.92;
}

.ai-chat__icon-btn {
  flex-shrink: 0;
  width: 44px;
  height: 44px;
  margin: -8px -8px 0 0;
  border: none;
  border-radius: 12px;
  background: transparent;
  color: inherit;
  font-size: 28px;
  line-height: 1;
  transition: background-color 0.18s ease, transform 0.15s ease;
}

.ai-chat__icon-btn:hover {
  background: rgba(255, 255, 255, 0.16);
}

.ai-chat__icon-btn:active {
  transform: scale(0.96);
}

.ai-chat__messages {
  flex: 1 1 auto;
  min-height: 0;
  overflow-y: auto;
  padding: 14px;
  display: flex;
  flex-direction: column;
  gap: 10px;
  background: var(--color-bg, #fff);
}

.ai-chat__thread {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.ai-chat__welcome {
  margin: 0;
  padding: 10px 12px;
  border-radius: 12px;
  background: var(--accent-muted, rgba(225, 69, 84, 0.12));
  color: var(--color-text, #111827);
  font-size: 13px;
  line-height: 1.45;
}

.ai-chat__bubble {
  max-width: 88%;
  padding: 10px 12px;
  border-radius: 14px;
  font-size: 13px;
  line-height: 1.45;
  white-space: pre-wrap;
  word-break: break-word;
}

.ai-chat__bubble--user {
  align-self: flex-end;
  background: var(--figma-accent, var(--accent, #e14554));
  color: var(--figma-on-accent);
  border-bottom-right-radius: 4px;
}

.ai-chat__bubble--assistant {
  align-self: flex-start;
  background: var(--color-surface, #f3f4f6);
  color: var(--color-text, #111827);
  border-bottom-left-radius: 4px;
}

.ai-chat__typing,
.ai-chat__error {
  margin: 0;
  font-size: 12px;
  line-height: 1.4;
}

.ai-chat__typing {
  color: var(--color-text-muted, #6b7280);
}

.ai-chat__error {
  color: var(--figma-accent, var(--accent, #e14554));
}

.ai-chat__footer {
  display: flex;
  flex-direction: column;
  gap: 10px;
  padding: 12px;
  border-top: 1px solid var(--figma-border, #e5e7eb);
  background: var(--color-bg, #fff);
}

.ai-chat__quick {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.ai-chat__quick-btn {
  min-height: 36px;
  padding: 6px 12px;
  border: 1px solid var(--figma-border, #e5e7eb);
  border-radius: 999px;
  background: var(--color-surface, #f9fafb);
  color: var(--color-text, #374151);
  font-size: 12px;
  line-height: 1.3;
  text-align: left;
  transition: background-color 0.18s ease, border-color 0.18s ease, transform 0.15s ease;
}

.ai-chat__quick-btn:hover:not(:disabled) {
  background: var(--color-surface-hover, #f3f4f6);
  border-color: var(--figma-border-strong, #d1d5db);
}

.ai-chat__quick-btn:active:not(:disabled) {
  transform: scale(0.98);
}

.ai-chat__quick-btn:disabled {
  opacity: 0.55;
  cursor: not-allowed;
}

.ai-chat__composer {
  display: flex;
  align-items: flex-end;
  gap: 8px;
}

.ai-chat__input {
  flex: 1 1 auto;
  min-width: 0;
  min-height: 44px;
  max-height: 96px;
  resize: none;
  padding: 10px 12px;
  border: 1px solid var(--figma-border, #e5e7eb);
  border-radius: 12px;
  background: var(--color-bg, #fff);
  color: var(--color-text, #111827);
  outline: none;
  transition: border-color 0.18s ease;
}

.ai-chat__input:focus {
  border-color: var(--figma-accent, var(--accent, #e14554));
}

.ai-chat__send {
  flex-shrink: 0;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 44px;
  height: 44px;
  padding: 0;
  border: none;
  border-radius: 12px;
  background: var(--figma-accent, var(--accent, #e14554));
  color: var(--figma-on-accent);
  transition: background-color 0.18s ease, transform 0.15s ease, opacity 0.18s ease;
}

.ai-chat__send-icon {
  width: 20px;
  height: 20px;
  display: block;
}

.ai-chat__send:hover:not(:disabled) {
  background: var(--figma-accent-hover, var(--accent-hover, #c93a48));
}

.ai-chat__send:active:not(:disabled) {
  transform: scale(0.98);
}

.ai-chat__send:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.ai-chat__fab {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  min-height: 48px;
  padding: 0 16px 0 14px;
  border: none;
  border-radius: 999px;
  background: var(--figma-accent, var(--accent, #e14554));
  color: var(--figma-on-accent);
  box-shadow: 0 8px 24px rgba(225, 69, 84, 0.35);
  font-size: 13px;
  font-weight: 700;
  transition: background-color 0.18s ease, transform 0.18s ease, box-shadow 0.18s ease;
}

.ai-chat__fab:hover {
  background: var(--figma-accent-hover, var(--accent-hover, #c93a48));
  box-shadow: 0 10px 28px rgba(225, 69, 84, 0.42);
}

.ai-chat__fab:active {
  transform: scale(0.98);
}

.ai-chat__fab img {
  width: 20px;
  height: 20px;
  flex-shrink: 0;
}

.ai-chat__fab-label {
  white-space: nowrap;
}

.visually-hidden {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}

.ai-chat-panel-enter-active,
.ai-chat-panel-leave-active {
  transition: opacity 0.22s ease, transform 0.22s ease;
}

.ai-chat-panel-enter-from,
.ai-chat-panel-leave-to {
  opacity: 0;
  transform: translateY(10px) scale(0.96);
}

.ai-chat-msg-enter-active {
  transition: opacity 0.24s ease-out, transform 0.24s ease-out;
}

.ai-chat-msg-leave-active {
  transition: opacity 0.16s ease-in, transform 0.16s ease-in;
  position: absolute;
}

.ai-chat-msg-enter-from {
  opacity: 0;
  transform: translateY(8px);
}

.ai-chat-msg-leave-to {
  opacity: 0;
  transform: translateY(4px);
}

.ai-chat-msg-move {
  transition: transform 0.22s ease-out;
}

.ai-chat-fade-enter-active,
.ai-chat-fade-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}

.ai-chat-fade-enter-from,
.ai-chat-fade-leave-to {
  opacity: 0;
  transform: translateY(6px);
}

@media (max-width: 480px) {
  .ai-chat {
    right: max(12px, env(safe-area-inset-right, 0px));
    bottom: max(12px, env(safe-area-inset-bottom, 0px));
  }

  .ai-chat__panel {
    width: min(100vw - 24px, 380px);
    height: min(70dvh, 520px);
  }

  .ai-chat__fab-label {
    font-size: 12px;
  }
}

@media (prefers-reduced-motion: reduce) {
  .ai-chat__fab,
  .ai-chat__send,
  .ai-chat__quick-btn,
  .ai-chat__icon-btn,
  .ai-chat-panel-enter-active,
  .ai-chat-panel-leave-active,
  .ai-chat-msg-enter-active,
  .ai-chat-msg-leave-active,
  .ai-chat-msg-move,
  .ai-chat-fade-enter-active,
  .ai-chat-fade-leave-active {
    transition-duration: 0.01ms;
  }
}
</style>
