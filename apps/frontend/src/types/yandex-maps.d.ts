declare global {
  interface YandexMapBounds {
    getSouthWest: () => number[]
    getNorthEast: () => number[]
  }

  interface YandexMapEvent {
    get: (key: string) => unknown
    stopPropagation: () => void
  }

  interface YandexGeoObject {
    events: {
      add: (types: string | string[], callback: (event: YandexMapEvent) => void) => void
      remove: (types: string | string[], callback: (event: YandexMapEvent) => void) => void
    }
    options: {
      set: (key: string, value: unknown) => void
    }
    geometry?: {
      getBounds: () => YandexMapBounds | null
      getCoordinates?: () => number[]
      setCoordinates?: (coords: number[]) => void
    }
    properties?: {
      get: (key: string) => unknown
      set: (key: string, value: unknown) => void
    }
    add?: (objects: YandexGeoObject[]) => void
    getGeoObjects?: () => YandexGeoObject[]
    getBounds?: () => YandexMapBounds | null
  }

  interface YandexGeoObjectCollection {
    add: (object: YandexGeoObject) => void
    removeAll: () => void
    events: {
      add: (types: string | string[], callback: (event: YandexMapEvent) => void) => void
    }
  }

  interface YandexObjectManager extends YandexGeoObject {
    objects: {
      events: {
        add: (types: string | string[], callback: (event: YandexMapEvent) => void) => void
      }
    }
    clusters: {
      events: {
        add: (types: string | string[], callback: (event: YandexMapEvent) => void) => void
      }
    }
    add: (data: object) => void
    removeAll: () => void
  }

  interface YandexMapInstance {
    destroy: () => void
    setCenter: (center: number[], zoom: number, options?: Record<string, unknown>) => void
    setZoom: (zoom: number, options?: Record<string, unknown>) => void
    getCenter: () => number[]
    getZoom: () => number
    getBounds: () => YandexMapBounds
    setBounds: (bounds: number[][], options?: Record<string, unknown>) => void
    geoObjects: {
      add: (object: YandexGeoObject | YandexGeoObjectCollection, index?: number) => void
      remove: (object: YandexGeoObject | YandexGeoObjectCollection) => void
    }
    events: {
      add: (types: string | string[], callback: (event: YandexMapEvent) => void) => void
      remove: (types: string | string[], callback: (event: YandexMapEvent) => void) => void
    }
    container: {
      fitToViewport: () => void
      getElement: () => HTMLElement
      getSize: () => number[]
    }
    controls: {
      add: (control: unknown) => void
    }
    converter: {
      coordinatesToGlobalPixels: (coords: number[], zoom: number) => number[]
      globalToPage: (pixels: number[]) => number[]
    }
    behaviors: {
      disable: (behaviors: string[]) => void
    }
    options: {
      get: (key: string) => unknown
      set: (key: string, value: unknown) => void
    }
  }

  interface YandexMapsApi {
    ready: (callback: () => void, context?: unknown) => void
    Map: new (
      container: HTMLElement,
      state: { center: number[]; zoom: number; controls?: string[] },
      options?: Record<string, unknown>,
    ) => YandexMapInstance
    Polygon: new (
      coordinates: number[][][],
      properties?: Record<string, unknown>,
      options?: Record<string, unknown>,
    ) => YandexGeoObject
    GeoObjectCollection: new () => YandexGeoObjectCollection
    Clusterer: new (options?: Record<string, unknown>) => YandexGeoObject
    ObjectManager: new (options?: Record<string, unknown>) => YandexObjectManager
    Placemark: new (
      coordinates: number[],
      properties?: Record<string, unknown>,
      options?: Record<string, unknown>,
    ) => YandexGeoObject
    control: {
      ZoomControl: new (options?: Record<string, unknown>) => unknown
    }
    util: {
      bounds: {
        fromPoints: (points: number[][]) => number[][]
        getCenterAndZoom: (
          bounds: number[][],
          size: number[],
          projection?: unknown,
          options?: Record<string, unknown>,
        ) => { center: number[]; zoom: number }
      }
    }
    projection?: {
      wgs84Mercator: {
        toGlobalPixels: (coords: number[], zoom: number) => number[]
      }
    }
  }

  interface Window {
    ymaps?: YandexMapsApi
  }

  const ymaps: YandexMapsApi
}

export {}
