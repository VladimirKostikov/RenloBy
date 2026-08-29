export type InfrastructureType = 'shop' | 'pharmacy' | 'school' | 'park'

export interface InfrastructureBbox {
  south: number
  west: number
  north: number
  east: number
  zoom?: number
}

export interface InfrastructurePoi {
  id: string
  type: InfrastructureType
  name: string
  address: string
  latitude: number
  longitude: number
}

export const INFRASTRUCTURE_TYPES: InfrastructureType[] = ['shop', 'pharmacy', 'school', 'park']

export const MIN_INFRASTRUCTURE_ZOOM = 11
export const INFRA_TARGET_ZOOM = 14
export const MAX_INFRA_BBOX_SPAN = 1.2
