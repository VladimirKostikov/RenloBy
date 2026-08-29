import { buildInfrastructureTooltipHtml } from '@/lib/infrastructureMaps'
import type { InfrastructurePoi, InfrastructureType } from '@/types/infrastructure'

const PRESETS: Record<InfrastructureType, string> = {
  shop: 'islands#greenDotIcon',
  pharmacy: 'islands#pinkDotIcon',
  school: 'islands#blueDotIcon',
  park: 'islands#darkGreenDotIcon',
}

export class YandexInfrastructureLayer {
  private map: YandexMapInstance
  private manager: YandexObjectManager | null = null

  constructor(map: YandexMapInstance) {
    this.map = map
  }

  clear(): void {
    if (this.manager) {
      this.map.geoObjects.remove(this.manager)
      this.manager = null
    }
  }

  sync(pois: InfrastructurePoi[]): void {
    this.clear()

    if (pois.length === 0) {
      return
    }

    this.manager = new ymaps.ObjectManager({
      clusterize: false,
    })
    this.manager.options.set('interactivityModel', 'default#silent')

    this.manager.add({
      type: 'FeatureCollection',
      features: pois.map((poi) => ({
        type: 'Feature',
        id: poi.id,
        geometry: {
          type: 'Point',
          coordinates: [poi.latitude, poi.longitude],
        },
        properties: {
          balloonContent: buildInfrastructureTooltipHtml(poi),
          hintContent: poi.name,
        },
        options: {
          preset: PRESETS[poi.type],
        },
      })),
    })

    this.map.geoObjects.add(this.manager, 0)
  }

  destroy(): void {
    this.clear()
  }
}
