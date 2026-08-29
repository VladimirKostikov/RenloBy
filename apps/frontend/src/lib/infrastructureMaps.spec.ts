import { describe, expect, it } from 'vitest'
import {
  buildInfrastructureTooltipHtml,
  getInfrastructureMapsUrl,
  getYandexMapsOrgUrl,
  getYandexMapsPointUrl,
} from '@/lib/infrastructureMaps'

describe('infrastructureMaps', () => {
  it('builds yandex maps url from coordinates', () => {
    expect(getYandexMapsPointUrl(53.9, 27.56)).toBe(
      'https://yandex.ru/maps/?pt=27.56,53.9&z=17&l=map',
    )
  })

  it('builds yandex org url for yandex poi ids', () => {
    expect(getYandexMapsOrgUrl('12345')).toBe('https://yandex.ru/maps/org/12345')
    expect(
      getInfrastructureMapsUrl({
        id: 'yandex-12345',
        latitude: 53.9,
        longitude: 27.56,
      }),
    ).toBe('https://yandex.ru/maps/org/12345')
  })

  it('builds clickable address popup html with yandex link', () => {
    const html = buildInfrastructureTooltipHtml({
      id: '1',
      type: 'pharmacy',
      name: 'Аптека',
      address: 'ул. Ленина, 10',
      latitude: 53.9,
      longitude: 27.56,
    })

    expect(html).toContain('map-infra-tooltip__card')
    expect(html).toContain('map-infra-tooltip__address')
    expect(html).toContain('ул. Ленина, 10')
    expect(html).toContain('target="_blank"')
    expect(html).toContain('https://yandex.ru/maps/?pt=27.56,53.9&z=17&l=map')
  })
})
