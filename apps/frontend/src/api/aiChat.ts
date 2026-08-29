import apiClient from './client'

export type AiChatRole = 'user' | 'assistant'
export type AiChatLocale = 'ru' | 'en'

export interface AiChatHistoryItem {
  role: AiChatRole
  content: string
}

export interface AiChatResponse {
  reply: string
}

export async function sendAiChatMessage(
  message: string,
  history: AiChatHistoryItem[] = [],
  locale: AiChatLocale = 'ru',
): Promise<AiChatResponse> {
  const { data } = await apiClient.post<AiChatResponse>('/api/ai-chat', {
    message,
    history,
    locale,
  })
  return data
}
