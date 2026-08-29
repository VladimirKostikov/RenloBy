import { createHash } from 'node:crypto'
import { readFileSync, writeFileSync, mkdirSync, statSync, rmSync } from 'node:fs'
import { resolve, dirname } from 'node:path'
import { fileURLToPath } from 'node:url'
import { spawnSync } from 'node:child_process'
import area from '@turf/area'
import bbox from '@turf/bbox'
import flatten from '@turf/flatten'
import intersect from '@turf/intersect'
import { feature, featureCollection } from '@turf/helpers'

const __dirname = dirname(fileURLToPath(import.meta.url))
const ROOT = __dirname
const OSM_DIR = resolve(ROOT, 'osm')
const OUT_DIR = resolve(ROOT, '../../public/geo')
const TMP_DIR = resolve(ROOT, '.tmp')
const MANIFEST = JSON.parse(readFileSync(resolve(ROOT, 'manifest.json'), 'utf8'))
const ADM1 = JSON.parse(readFileSync(resolve(ROOT, 'geoBoundaries-BLR-ADM1.geojson'), 'utf8'))

const OVERPASS_URLS = [
  'https://overpass-api.de/api/interpreter',
  'https://overpass.kumi.systems/api/interpreter',
]

const SIMPLIFY = {
  regions: '45%',
  cities: '50%',
  districts: '55%',
}

const DEFAULT_MAX_CITY_SPAN = 0.5

function osmToGeojson(osmPath) {
  const result = spawnSync('npx', ['--yes', 'osmtogeojson', osmPath], {
    cwd: ROOT,
    encoding: 'utf8',
  })

  if (result.status !== 0) {
    throw new Error(`osmtogeojson failed for ${osmPath}: ${result.stderr}`)
  }

  const start = result.stdout.indexOf('{')
  if (start < 0) {
    throw new Error(`No JSON from osmtogeojson for ${osmPath}`)
  }

  return JSON.parse(result.stdout.slice(start))
}

function loadOsmFeature(osmFile) {
  const collection = osmToGeojson(resolve(OSM_DIR, osmFile))
  const first = collection.features?.[0]
  if (!first?.geometry) {
    throw new Error(`Missing geometry in ${osmFile}`)
  }

  return feature(first.geometry, first.properties ?? {})
}

function geometrySpan(geometry) {
  const [west, south, east, north] = bbox(feature(geometry))
  return { lon: east - west, lat: north - south }
}

function ringArea(ring) {
  let value = 0
  for (let index = 0; index < ring.length - 1; index += 1) {
    value += ring[index][0] * ring[index + 1][1] - ring[index + 1][0] * ring[index][1]
  }
  return Math.abs(value) / 2
}

function primaryPolygon(geometry) {
  if (geometry.type === 'Polygon') {
    return geometry
  }

  if (geometry.type !== 'MultiPolygon') {
    return geometry
  }

  let bestPolygon = null
  let bestArea = -1

  for (const polygon of geometry.coordinates) {
    const polygonArea = ringArea(polygon[0])
    if (polygonArea > bestArea) {
      bestArea = polygonArea
      bestPolygon = polygon
    }
  }

  if (!bestPolygon) {
    throw new Error('MultiPolygon has no polygon rings')
  }

  return { type: 'Polygon', coordinates: bestPolygon }
}

function toMultiPolygonCoordinates(geometries) {
  const coordinates = []
  for (const geometry of geometries) {
    if (geometry.type === 'Polygon') {
      coordinates.push(geometry.coordinates)
      continue
    }
    if (geometry.type === 'MultiPolygon') {
      coordinates.push(...geometry.coordinates)
    }
  }
  return coordinates
}

function mergeGeometries(geometries) {
  if (geometries.length === 1) {
    return geometries[0]
  }

  return {
    type: 'MultiPolygon',
    coordinates: toMultiPolygonCoordinates(geometries),
  }
}

function clipToBoundary(sourceGeometry, boundaryGeometry) {
  const boundary = feature(boundaryGeometry)
  const parts = []

  for (const part of flatten(feature(sourceGeometry)).features) {
    const clipped = intersect(featureCollection([part, boundary]))
    if (!clipped) {
      continue
    }

    for (const piece of flatten(clipped).features) {
      if (piece.geometry && area(piece) > 1) {
        parts.push(piece.geometry)
      }
    }
  }

  if (parts.length === 0) {
    return null
  }

  return mergeGeometries(parts)
}

function runMapshaper(inputPath, outputPath, simplifyPct) {
  mkdirSync(dirname(outputPath), { recursive: true })
  const args = [
    '--yes',
    'mapshaper',
    inputPath,
    '-simplify',
    simplifyPct,
    'keep-shapes',
    '-snap',
    '-clean',
    '-o',
    outputPath,
  ]

  const result = spawnSync('npx', args, {
    cwd: ROOT,
    encoding: 'utf8',
  })

  if (result.status !== 0) {
    throw new Error(`mapshaper failed: ${result.stderr || result.stdout}`)
  }

  return JSON.parse(readFileSync(outputPath, 'utf8'))
}

function simplifyCollection(collection, simplifyPct, label) {
  const inputPath = resolve(TMP_DIR, `${label}-raw.geojson`)
  const outputPath = resolve(TMP_DIR, `${label}-out.geojson`)
  writeFileSync(inputPath, JSON.stringify(collection))
  return runMapshaper(inputPath, outputPath, simplifyPct)
}

function hashGeoFiles(filenames) {
  const hash = createHash('sha256')
  for (const name of filenames) {
    hash.update(readFileSync(resolve(OUT_DIR, name)))
  }
  return hash.digest('hex').slice(0, 16)
}

async function fetchOsmRelation(relationId) {
  const query = `[out:json][timeout:90];relation(${relationId});out body;>;out skel qt;`
  const body = new URLSearchParams({ data: query })

  let lastError = null
  for (const url of OVERPASS_URLS) {
    try {
      const response = await fetch(url, {
        method: 'POST',
        headers: { 'User-Agent': 'RenloGeoBuilder/2.0' },
        body,
      })
      if (!response.ok) {
        throw new Error(`HTTP ${response.status}`)
      }
      return await response.json()
    } catch (error) {
      lastError = error
    }
  }

  throw lastError ?? new Error(`Failed to fetch relation ${relationId}`)
}

async function ensureOsmFile(osmFile, relationId, fetchMissing) {
  const path = resolve(OSM_DIR, osmFile)
  try {
    if (statSync(path).size > 500) {
      return path
    }
  } catch {
  }

  if (!fetchMissing) {
    throw new Error(`Missing OSM cache ${path}. Run npm run fetch:geo`)
  }

  mkdirSync(OSM_DIR, { recursive: true })
  const payload = await fetchOsmRelation(relationId)
  writeFileSync(path, JSON.stringify(payload))
  return path
}

function writeGeojson(filename, data) {
  mkdirSync(OUT_DIR, { recursive: true })
  writeFileSync(resolve(OUT_DIR, filename), JSON.stringify(data))
}

function buildRegionsRaw(minskGeometry) {
  const features = MANIFEST.regions.map((region) => {
    let geometry

    if (region.slug === 'minsk-city') {
      geometry = minskGeometry
    } else {
      const match = ADM1.features.find((item) => item.properties.shapeName === region.geoboundariesName)
      if (!match) {
        throw new Error(`Region not found in ADM1: ${region.geoboundariesName}`)
      }
      geometry = match.geometry
    }

    const props = {
      slug: region.slug,
      name: region.name,
      level: 'region',
    }
    if (region.citySlug) {
      props.citySlug = region.citySlug
    }

    return { type: 'Feature', properties: props, geometry }
  })

  return { type: 'FeatureCollection', features }
}

async function buildCitiesRaw(fetchMissing) {
  const features = []

  for (const city of MANIFEST.cities) {
    await ensureOsmFile(city.osmFile, city.relationId, fetchMissing)
    const raw = loadOsmFeature(city.osmFile).geometry
    const geometry = primaryPolygon(raw)
    const span = geometrySpan(geometry)
    const maxSpan = city.maxSpan ?? DEFAULT_MAX_CITY_SPAN

    if (span.lon > maxSpan || span.lat > maxSpan) {
      throw new Error(`City ${city.slug} span too large: ${span.lon.toFixed(3)}, ${span.lat.toFixed(3)}`)
    }

    const props = {
      slug: city.slug,
      name: city.name,
      level: 'city',
      regionSlug: city.regionSlug,
      osmRelationId: city.relationId,
    }
    if (city.citySlug) {
      props.citySlug = city.citySlug
    }
    if (city.hasDistricts) {
      props.hasDistricts = true
    }

    features.push({ type: 'Feature', properties: props, geometry })
  }

  features.sort((left, right) => left.properties.name.localeCompare(right.properties.name, 'ru'))
  return { type: 'FeatureCollection', features }
}

async function buildDistrictsRaw(districtSet, minskBoundary, fetchMissing) {
  const features = []

  for (const district of districtSet.districts) {
    await ensureOsmFile(district.osmFile, district.relationId, fetchMissing)
    const raw = loadOsmFeature(district.osmFile).geometry
    const geometry = clipToBoundary(raw, minskBoundary)
    if (!geometry) {
      throw new Error(`District ${district.slug} is empty after clip to Minsk`)
    }

    features.push({
      type: 'Feature',
      properties: {
        slug: district.slug,
        name: district.name,
        level: 'district',
        citySlug: districtSet.citySlug,
        districtSlug: district.districtSlug,
        osmRelationId: district.relationId,
      },
      geometry,
    })
  }

  const minskArea = area(feature(minskBoundary))
  const coverage = features.reduce((sum, item) => sum + area(feature(item.geometry)), 0)
  if (coverage < minskArea * 0.88) {
    throw new Error(`District coverage too low: ${((coverage / minskArea) * 100).toFixed(1)}%`)
  }

  features.sort((left, right) => left.properties.name.localeCompare(right.properties.name, 'ru'))
  return { type: 'FeatureCollection', features }
}

function buildMapManifestTs(geoVersion) {
  const regionCitySlugs = {}
  for (const region of MANIFEST.regions) {
    const slugs = MANIFEST.cities
      .filter((city) => city.regionSlug === region.slug)
      .map((city) => city.slug)

    if (region.slug === 'minsk-region') {
      slugs.push('minsk')
    }

    regionCitySlugs[region.slug] = slugs
  }

  regionCitySlugs['minsk-city'] = ['minsk']

  const cityToRegion = {}
  for (const city of MANIFEST.cities) {
    cityToRegion[city.slug] = city.hasDistricts ? 'minsk-city' : city.regionSlug
  }

  const districtUrls = {}
  for (const set of MANIFEST.districtSets) {
    districtUrls[set.citySlug] = `/geo/${set.outputFile}`
  }

  const content = `export const GEO_VERSION = '${geoVersion}'

export const REGION_CITY_SLUGS = ${JSON.stringify(regionCitySlugs, null, 2)} as const

export const CITY_TO_REGION: Record<string, string> = ${JSON.stringify(cityToRegion, null, 2)}

export const DISTRICT_GEO_URLS: Record<string, string> = ${JSON.stringify(districtUrls, null, 2)}

export const MAP_GEO_URLS = {
  regions: '/geo/belarus-regions.geojson',
  cities: '/geo/belarus-cities.geojson',
} as const
`

  writeFileSync(resolve(ROOT, '../../src/lib/mapManifest.ts'), content)
}

async function main() {
  const fetchMissing = process.argv.includes('--fetch-missing')

  rmSync(TMP_DIR, { recursive: true, force: true })
  mkdirSync(TMP_DIR, { recursive: true })

  await ensureOsmFile('minsk.json', 59195, fetchMissing)
  const minskRaw = loadOsmFeature('minsk.json').geometry
  const minskBoundary = minskRaw

  const regions = simplifyCollection(buildRegionsRaw(minskRaw), SIMPLIFY.regions, 'regions')
  const cities = simplifyCollection(await buildCitiesRaw(fetchMissing), SIMPLIFY.cities, 'cities')

  const minskCity = cities.features.find((feature) => feature.properties.slug === 'minsk')
  const minskCityRegion = regions.features.find((feature) => feature.properties.slug === 'minsk-city')
  if (minskCity && minskCityRegion) {
    minskCityRegion.geometry = minskCity.geometry
  }

  writeGeojson('belarus-regions.geojson', regions)
  writeGeojson('belarus-cities.geojson', cities)

  for (const districtSet of MANIFEST.districtSets) {
    const rawDistricts = await buildDistrictsRaw(districtSet, minskBoundary, fetchMissing)
    const districts = simplifyCollection(rawDistricts, SIMPLIFY.districts, `districts-${districtSet.citySlug}`)
    writeGeojson(districtSet.outputFile, districts)
    console.log(`${districtSet.outputFile}: ${districts.features.length} districts, coverage ok`)
  }

  rmSync(TMP_DIR, { recursive: true, force: true })

  const outputNames = ['belarus-regions.geojson', 'belarus-cities.geojson', 'minsk-districts.geojson']
  const geoVersion = hashGeoFiles(outputNames)
  buildMapManifestTs(geoVersion)

  console.log(`regions: ${regions.features.length}`)
  console.log(`cities: ${cities.features.length}`)
  console.log(`geo version: ${geoVersion}`)
  for (const name of outputNames) {
    const bytes = readFileSync(resolve(OUT_DIR, name)).length
    console.log(`${name}: ${Math.round(bytes / 1024)} KB`)
  }
}

main().catch((error) => {
  console.error(error)
  process.exit(1)
})
