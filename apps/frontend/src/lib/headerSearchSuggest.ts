export type HeaderSuggestKind = 'street' | 'district' | 'metro' | 'city'

export type HeaderSuggestItem = {
  id: string
  kind: HeaderSuggestKind
  label: string
  subtitle?: string
  cityId?: number
  districtId?: number
  metroStationId?: number
  query: string
}
