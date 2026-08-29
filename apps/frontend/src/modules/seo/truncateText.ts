export function truncateText(value: string, maxLength: number): string {
  const trimmed = value.trim().replace(/\s+/g, ' ')
  if (trimmed.length <= maxLength) {
    return trimmed
  }

  const slice = trimmed.slice(0, maxLength)
  const lastSpace = slice.lastIndexOf(' ')
  if (lastSpace > maxLength * 0.6) {
    return `${slice.slice(0, lastSpace).trim()}`
  }

  return slice.trim()
}
