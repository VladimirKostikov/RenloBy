export function getDomEvent(event: YandexMapEvent): MouseEvent | null {
  const domEvent = event.get('domEvent')
  return domEvent instanceof MouseEvent ? domEvent : null
}

export function getEventPointer(event: YandexMapEvent): { x: number; y: number } | null {
  const domEvent = getDomEvent(event)
  if (domEvent) {
    return { x: domEvent.clientX, y: domEvent.clientY }
  }

  const pagePixels = event.get('pagePixels')
  if (Array.isArray(pagePixels) && pagePixels.length >= 2) {
    return {
      x: Number(pagePixels[0]) - window.scrollX,
      y: Number(pagePixels[1]) - window.scrollY,
    }
  }

  return null
}

export function getEventContainerPoint(
  event: YandexMapEvent,
  container: HTMLElement,
): { x: number; y: number } | null {
  const pointer = getEventPointer(event)
  if (!pointer) {
    return null
  }

  const rect = container.getBoundingClientRect()
  return {
    x: pointer.x - rect.left,
    y: pointer.y - rect.top,
  }
}
