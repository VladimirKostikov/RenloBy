export const STUDIO_ROOMS = 0

export const ROOM_FILTER_VALUES = [STUDIO_ROOMS, 1, 2, 3, 4, 5] as const

export const WIZARD_ROOM_OPTIONS = [STUDIO_ROOMS, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10] as const

export function isStudioRooms(rooms: number | null | undefined): boolean {
  return rooms === STUDIO_ROOMS
}

export function isRoomsFilterActive(rooms: number | null | undefined): rooms is number {
  return typeof rooms === 'number'
}

export function formatListingRooms(
  rooms: number,
  translate: (key: string, params?: Record<string, unknown>) => string,
): string {
  if (isStudioRooms(rooms)) {
    return translate('listing.studio')
  }
  return translate('listing.rooms', { n: rooms })
}

export function formatListingRoomsShort(
  rooms: number,
  translate: (key: string, params?: Record<string, unknown>) => string,
): string {
  if (isStudioRooms(rooms)) {
    return translate('listing.studio')
  }
  return translate('listing.roomsShort', { n: rooms })
}

export function roomOptionLabel(
  rooms: number,
  translate: (key: string, params?: Record<string, unknown>) => string,
): string {
  if (isStudioRooms(rooms)) {
    return translate('listing.studio')
  }
  return String(rooms)
}

export function buildRoomFilterOptions(
  anyLabel: string,
  translate: (key: string, params?: Record<string, unknown>) => string,
): Array<{ value: string | number; label: string }> {
  return [
    { value: '', label: anyLabel },
    ...ROOM_FILTER_VALUES.map((rooms) => ({
      value: rooms,
      label: roomOptionLabel(rooms, translate),
    })),
  ]
}

export function roomsSeoLabel(rooms: number, locale: 'ru' | 'en'): string {
  if (isStudioRooms(rooms)) {
    return locale === 'en' ? 'Studio' : 'Студия'
  }
  return locale === 'en' ? `${rooms}-room` : `${rooms}-комн.`
}
