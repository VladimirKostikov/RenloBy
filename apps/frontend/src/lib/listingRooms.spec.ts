import { describe, expect, it } from 'vitest'
import {
  buildRoomFilterOptions,
  formatListingRooms,
  formatListingRoomsShort,
  isRoomsFilterActive,
  isStudioRooms,
  roomOptionLabel,
  roomsSeoLabel,
  STUDIO_ROOMS,
} from '@/lib/listingRooms'

const translate = (key: string, params?: Record<string, unknown>) => {
  if (key === 'listing.studio') return 'Студия'
  if (key === 'listing.rooms') return `${params?.n} комн.`
  if (key === 'listing.roomsShort') return `${params?.n}-комн.`
  return key
}

describe('listingRooms', () => {
  it('treats zero rooms as studio', () => {
    expect(isStudioRooms(STUDIO_ROOMS)).toBe(true)
    expect(isStudioRooms(1)).toBe(false)
    expect(formatListingRooms(0, translate)).toBe('Студия')
    expect(formatListingRoomsShort(0, translate)).toBe('Студия')
    expect(formatListingRoomsShort(2, translate)).toBe('2-комн.')
    expect(roomOptionLabel(0, translate)).toBe('Студия')
    expect(roomOptionLabel(3, translate)).toBe('3')
    expect(roomsSeoLabel(0, 'ru')).toBe('Студия')
    expect(roomsSeoLabel(0, 'en')).toBe('Studio')
    expect(roomsSeoLabel(2, 'ru')).toBe('2-комн.')
  })

  it('includes studio in room filter options', () => {
    const options = buildRoomFilterOptions('Любая', translate)

    expect(options[0]).toEqual({ value: '', label: 'Любая' })
    expect(options[1]).toEqual({ value: 0, label: 'Студия' })
    expect(options.map((option) => option.value)).toEqual(['', 0, 1, 2, 3, 4, 5])
  })

  it('treats studio rooms as an active filter', () => {
    expect(isRoomsFilterActive(0)).toBe(true)
    expect(isRoomsFilterActive(2)).toBe(true)
    expect(isRoomsFilterActive(undefined)).toBe(false)
    expect(isRoomsFilterActive(null)).toBe(false)
  })
})
