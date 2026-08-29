import type { UserDto } from '@/types'

export type SellerProfileMissingKey =
  | 'lastName'
  | 'firstName'
  | 'patronymic'
  | 'social'

function filled(value: string | null | undefined): boolean {
  return Boolean(value && value.trim() !== '')
}

export function getSellerProfileMissing(user: UserDto | null | undefined): SellerProfileMissingKey[] {
  if (!user) {
    return ['lastName', 'firstName', 'patronymic', 'social']
  }

  const missing: SellerProfileMissingKey[] = []
  if (!filled(user.lastName)) {
    missing.push('lastName')
  }
  if (!filled(user.firstName)) {
    missing.push('firstName')
  }
  if (!filled(user.patronymic)) {
    missing.push('patronymic')
  }
  if (
    !filled(user.instagram)
    && !filled(user.telegram)
    && !filled(user.whatsapp)
    && !filled(user.viber)
  ) {
    missing.push('social')
  }

  return missing
}

export function isSellerProfileComplete(user: UserDto | null | undefined): boolean {
  return getSellerProfileMissing(user).length === 0
}
