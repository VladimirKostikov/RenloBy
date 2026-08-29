import { mount } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createRouter, createMemoryHistory } from 'vue-router'
import AiChatWidget from '@/modules/ai-chat/components/AiChatWidget.vue'
import { useAiChat } from '@/modules/ai-chat/composables/useAiChat'
import { i18n } from '@/modules/locale'
import * as aiChatApi from '@/api/aiChat'

vi.mock('@/api/aiChat', () => ({
  sendAiChatMessage: vi.fn(),
}))

async function mountWidget(path = '/') {
  const router = createRouter({
    history: createMemoryHistory(),
    routes: [
      { path: '/', name: 'home', component: { template: '<div />' } },
      { path: '/admin', name: 'admin', component: { template: '<div />' } },
    ],
  })
  await router.push(path)
  await router.isReady()

  const container = document.createElement('div')
  document.body.appendChild(container)

  return mount(AiChatWidget, {
    attachTo: container,
    global: {
      plugins: [i18n, router],
    },
  })
}

describe('AiChatWidget', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    const chat = useAiChat()
    chat.close()
    chat.draft.value = ''
    chat.messages.value = []
    chat.error.value = ''
    chat.loading.value = false
    document.body.innerHTML = ''
  })

  it('shows floating button and opens panel', async () => {
    await mountWidget()

    const fab = document.body.querySelector('.ai-chat__fab') as HTMLButtonElement
    expect(fab).not.toBeNull()
    expect(fab.getAttribute('aria-label')).toBe('ИИ Консультант')
    expect(fab.querySelector('img')).not.toBeNull()
    expect(document.body.querySelector('.ai-chat__panel')).toBeNull()

    fab.click()
    await Promise.resolve()
    await Promise.resolve()

    expect(document.body.querySelector('.ai-chat__panel')).not.toBeNull()
    expect(document.body.textContent).toContain('Задайте вопрос о покупке')
    expect(window.getComputedStyle(fab).display).toBe('none')
  })

  it('sends a quick reply message', async () => {
    vi.mocked(aiChatApi.sendAiChatMessage).mockResolvedValue({
      reply: 'Откройте фильтры по Минску.',
    })

    await mountWidget()
    useAiChat().open()
    await Promise.resolve()

    const quickBtn = Array.from(document.body.querySelectorAll('.ai-chat__quick-btn'))
      .find((el) => el.textContent?.includes('Ищу квартиру в Минске')) as HTMLButtonElement
    expect(quickBtn).not.toBeNull()
    quickBtn.click()

    await vi.waitFor(() => {
      expect(document.body.textContent).toContain('Откройте фильтры по Минску.')
    })

    expect(aiChatApi.sendAiChatMessage).toHaveBeenCalledWith('Ищу квартиру в Минске', [], 'ru')
  })

  it('sends message and shows assistant reply', async () => {
    vi.mocked(aiChatApi.sendAiChatMessage).mockResolvedValue({
      reply: 'Помогу с фильтрами по Минску.',
    })

    await mountWidget()
    useAiChat().open()
    await Promise.resolve()

    const input = document.body.querySelector('#ai-chat-input') as HTMLTextAreaElement
    const form = document.body.querySelector('.ai-chat__composer') as HTMLFormElement
    input.value = 'Ищу квартиру в Минске'
    input.dispatchEvent(new Event('input'))
    form.dispatchEvent(new Event('submit'))
    await vi.waitFor(() => {
      expect(document.body.textContent).toContain('Помогу с фильтрами по Минску.')
    })

    expect(aiChatApi.sendAiChatMessage).toHaveBeenCalledWith('Ищу квартиру в Минске', [], 'ru')
  })

  it('uses accent colors for header and send button', async () => {
    await mountWidget()
    useAiChat().open()
    await Promise.resolve()

    const header = document.body.querySelector('.ai-chat__header') as HTMLElement
    const send = document.body.querySelector('.ai-chat__send') as HTMLElement
    expect(header).not.toBeNull()
    expect(send).not.toBeNull()
    expect(header.className).toContain('ai-chat__header')
    expect(send.className).toContain('ai-chat__send')
  })

  it('renders quick replies as wrapped chips in the footer', async () => {
    await mountWidget()
    useAiChat().open()
    await Promise.resolve()

    const footer = document.body.querySelector('.ai-chat__footer') as HTMLElement
    const quick = document.body.querySelector('.ai-chat__quick') as HTMLElement
    const buttons = document.body.querySelectorAll('.ai-chat__quick-btn')

    expect(footer).not.toBeNull()
    expect(quick).not.toBeNull()
    expect(buttons.length).toBe(4)
    expect(footer.contains(quick)).toBe(true)
  })

  it('hides widget on admin routes', async () => {
    await mountWidget('/admin')
    expect(document.body.querySelector('.ai-chat')).toBeNull()
  })
})
