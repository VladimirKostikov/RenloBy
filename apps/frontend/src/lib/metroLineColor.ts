const DEFAULT_METRO_LINE_COLOR = '#0072BC'

export const METRO_LINE_COLOR_OPTIONS = [
  '#0072BC',
  '#D62027',
  '#009A49',
] as const

function expandShortHex(value: string): string | null {
  if (!/^[0-9A-Fa-f]{3}$/.test(value)) {
    return null
  }

  return value
    .split('')
    .map((char) => `${char}${char}`)
    .join('')
    .toUpperCase()
}

export function normalizeMetroLineColor(color: string | undefined | null): string {
  if (!color) {
    return DEFAULT_METRO_LINE_COLOR
  }

  const trimmed = color.trim()
  const hexMatch = trimmed.match(/^#?([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/)
  if (!hexMatch) {
    return DEFAULT_METRO_LINE_COLOR
  }

  const normalized = hexMatch[1].length === 3
    ? expandShortHex(hexMatch[1])
    : hexMatch[1].toUpperCase()

  if (!normalized) {
    return DEFAULT_METRO_LINE_COLOR
  }

  return `#${normalized}`
}

export function isKnownMetroLineColor(color: string | undefined | null): boolean {
  const normalized = normalizeMetroLineColor(color)
  return (METRO_LINE_COLOR_OPTIONS as readonly string[]).includes(normalized)
}
