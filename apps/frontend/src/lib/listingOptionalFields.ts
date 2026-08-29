export function formatFloorShort(
  floor: number | null | undefined,
  totalFloors: number | null | undefined,
  notSpecified = '-',
): string {
  const hasFloor = floor !== null && floor !== undefined
  const hasTotal = totalFloors !== null && totalFloors !== undefined

  if (!hasFloor && !hasTotal) {
    return notSpecified
  }

  if (hasFloor && hasTotal) {
    return `${floor}/${totalFloors}`
  }

  if (hasFloor) {
    return String(floor)
  }

  return String(totalFloors)
}

export function displayOptionalValue(
  value: string | number | null | undefined,
  notSpecified = '-',
): string {
  if (value === null || value === undefined) {
    return notSpecified
  }

  if (typeof value === 'string') {
    const trimmed = value.trim()
    return trimmed !== '' ? trimmed : notSpecified
  }

  return String(value)
}
