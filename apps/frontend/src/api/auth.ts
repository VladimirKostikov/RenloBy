import apiClient from './client'
import type { LoginRequest, RegisterRequest, UserDto } from '@/types'

export async function login(payload: LoginRequest): Promise<UserDto> {
  const { data } = await apiClient.post<UserDto>('/api/auth/login', payload)
  return data
}

export async function register(payload: RegisterRequest): Promise<UserDto> {
  const { data } = await apiClient.post<UserDto>('/api/auth/register', payload)
  return data
}

export async function logout(): Promise<void> {
  await apiClient.post('/api/auth/logout')
}

export async function fetchMe(): Promise<UserDto> {
  const { data } = await apiClient.get<UserDto>('/api/auth/me')
  return data
}
