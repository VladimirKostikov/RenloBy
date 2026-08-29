import type { AddressSuggestDto } from '@/api/listings'
import type { HeaderSuggestItem } from '@/lib/headerSearchSuggest'

export function mapAddressSuggestItem(dto: AddressSuggestDto): HeaderSuggestItem {
  return {
    id: dto.id,
    kind: dto.kind,
    label: dto.label,
    subtitle: dto.subtitle ?? undefined,
    query: dto.query,
    cityId: dto.cityId ?? undefined,
    districtId: dto.districtId ?? undefined,
    metroStationId: dto.metroStationId ?? undefined,
  }
}
