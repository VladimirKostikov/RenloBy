import type { InfrastructurePoi } from '@/types/infrastructure'

export function getYandexMapsPointUrl(latitude: number, longitude: number): string {
  return `https://yandex.ru/maps/?pt=${longitude},${latitude}&z=17&l=map`
}

export function getYandexMapsOrgUrl(orgId: string): string {
  return `https://yandex.ru/maps/org/${encodeURIComponent(orgId)}`
}

export function getInfrastructureMapsUrl(poi: Pick<InfrastructurePoi, 'id' | 'latitude' | 'longitude'>): string {
  const match = /^yandex-(\d+)$/.exec(poi.id)
  if (match?.[1]) {
    return getYandexMapsOrgUrl(match[1])
  }

  return getYandexMapsPointUrl(poi.latitude, poi.longitude)
}

function escapeHtml(value: string): string {
  return value
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
}

export function buildInfrastructureTooltipHtml(poi: InfrastructurePoi): string {
  const name = escapeHtml(poi.name)
  const address = escapeHtml(poi.address)
  const mapsUrl = getInfrastructureMapsUrl(poi)

  return `
    <div class="map-infra-tooltip__card">
      <div class="map-infra-tooltip__name">${name}</div>
      <a
        class="map-infra-tooltip__address"
        href="${mapsUrl}"
        target="_blank"
        rel="noopener noreferrer"
      >${address}</a>
    </div>
  `
}
