import apiClient from './client'

export interface PaymentTransactionDto {
  id: number
  userId: number
  amount: string
  currency: string
  status: string
  provider: string
  providerPaymentId: string | null
  description: string | null
  confirmationUrl: string | null
  metadata: Record<string, unknown>
  isTest: boolean
  createdAt: string
  updatedAt: string
}

export interface CreatePaymentPayload {
  amount: string
  currency: string
  description: string
  returnUrl: string
  metadata?: Record<string, unknown>
  isTest?: boolean
}

export async function fetchMyPayments(): Promise<PaymentTransactionDto[]> {
  const { data } = await apiClient.get<PaymentTransactionDto[]>('/api/me/payments')
  return Array.isArray(data) ? data : []
}

export async function createMyPayment(payload: CreatePaymentPayload): Promise<PaymentTransactionDto> {
  const { data } = await apiClient.post<PaymentTransactionDto>('/api/me/payments', payload)
  return data
}
