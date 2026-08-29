export interface InfoFaqItemDto {
  question: string
  answer: string
}

export type InfoPageCategory =
  | 'buyers'
  | 'sellers'
  | 'renters'
  | 'deal_safety'
  | 'faq'
  | 'support'
  | 'offer'
  | 'privacy'
  | 'personal_data'

export interface InfoPageDto {
  id: number
  slug: string
  title: string
  body: string
  category: InfoPageCategory
  importantNote: string | null
  faqItems: InfoFaqItemDto[]
  sortOrder: number
  metaTitle: string | null
  metaDescription: string | null
  updatedAt: string
}
