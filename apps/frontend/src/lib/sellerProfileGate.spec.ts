import { describe, expect, it } from 'vitest'
import { getSellerProfileMissing, isSellerProfileComplete } from '@/lib/sellerProfileGate'
import type { UserDto } from '@/types'

function user(partial: Partial<UserDto> = {}): UserDto {
  return {
    id: 1,
    email: 'u@test.local',
    name: '',
    roles: ['ROLE_USER'],
    lastName: null,
    firstName: null,
    patronymic: null,
    instagram: null,
    telegram: null,
    whatsapp: null,
    viber: null,
    ...partial,
  }
}

describe('sellerProfileGate', () => {
  it('requires fio and at least one social', () => {
    expect(getSellerProfileMissing(user())).toEqual([
      'lastName',
      'firstName',
      'patronymic',
      'social',
    ])
    expect(isSellerProfileComplete(user())).toBe(false)
  })

  it('passes when fio and one social are set', () => {
    const complete = user({
      lastName: 'Иванов',
      firstName: 'Иван',
      patronymic: 'Иванович',
      telegram: '@ivan',
    })
    expect(getSellerProfileMissing(complete)).toEqual([])
    expect(isSellerProfileComplete(complete)).toBe(true)
  })
})
