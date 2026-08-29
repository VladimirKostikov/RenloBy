export const GEO_VERSION = '9b12e2600a750080'

export const REGION_CITY_SLUGS = {
  "brest": [
    "brest-city"
  ],
  "vitebsk": [
    "vitebsk-city"
  ],
  "gomel": [
    "gomel-city"
  ],
  "grodno": [
    "grodno-city"
  ],
  "mogilev": [
    "mogilev-city"
  ],
  "minsk-region": [
    "borisov",
    "soligorsk",
    "molodechno",
    "minsk"
  ],
  "minsk-city": [
    "minsk"
  ]
} as const

export const CITY_TO_REGION: Record<string, string> = {
  "minsk": "minsk-city",
  "borisov": "minsk-region",
  "soligorsk": "minsk-region",
  "molodechno": "minsk-region",
  "brest-city": "brest",
  "vitebsk-city": "vitebsk",
  "gomel-city": "gomel",
  "grodno-city": "grodno",
  "mogilev-city": "mogilev"
}

export const DISTRICT_GEO_URLS: Record<string, string> = {
  "minsk": "/geo/minsk-districts.geojson"
}

export const MAP_GEO_URLS = {
  regions: '/geo/belarus-regions.geojson',
  cities: '/geo/belarus-cities.geojson',
} as const
