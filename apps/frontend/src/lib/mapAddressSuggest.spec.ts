import { describe, expect, it } from 'vitest'
import { mapAddressSuggestItem } from '@/lib/mapAddressSuggest'

describe('mapAddressSuggestItem', () => {
  it('maps street suggestion from api', () => {
    const item = mapAddressSuggestItem({
      id: 'street-1',
      kind: 'street',
      label: 'пр. Независимости',
      subtitle: 'Минск',
      query: 'пр. Независимости',
      cityId: 1,
    })

    expect(item).toEqual({
      id: 'street-1',
      kind: 'street',
      label: 'пр. Независимости',
      subtitle: 'Минск',
      query: 'пр. Независимости',
      cityId: 1,
      districtId: undefined,
      metroStationId: undefined,
    })
  })
})
