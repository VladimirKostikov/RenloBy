export const MAP_CARD_WIDTH = 263
export const MAP_CARD_HEIGHT = 380
export const MAP_CARD_TAIL_GAP = 14
export const MAP_CARD_TAIL_SIZE = 8
export const MAP_MARKER_OFFSET = 18

export type PopupPlacement = 'above' | 'below'

export interface MapPopupPosition {
  x: number
  y: number
  placement: PopupPlacement
  cardWidth: number
  cardMaxHeight: number
}

export interface MapPopupBounds {
  width: number
  height: number
}

export interface MapPopupInsets {
  top: number
  side: number
  bottom: number
}

export interface MapPopupRect {
  left: number
  right: number
  top: number
  bottom: number
}

const DEFAULT_INSETS: MapPopupInsets = {
  top: 48,
  side: 12,
  bottom: 16,
}

export function getEffectiveCardWidth(containerWidth: number, sideInset = DEFAULT_INSETS.side): number {
  return Math.min(MAP_CARD_WIDTH, Math.max(180, containerWidth - sideInset * 2))
}

export function getPopupRect(
  position: { x: number; y: number; placement: PopupPlacement },
  cardWidth: number,
  cardHeight: number,
): MapPopupRect {
  const halfW = cardWidth / 2

  if (position.placement === 'above') {
    return {
      left: position.x - halfW,
      right: position.x + halfW,
      top: position.y - cardHeight - MAP_CARD_TAIL_GAP - MAP_CARD_TAIL_SIZE,
      bottom: position.y + MAP_CARD_TAIL_SIZE,
    }
  }

  const top = position.y + MAP_MARKER_OFFSET - MAP_CARD_TAIL_SIZE
  return {
    left: position.x - halfW,
    right: position.x + halfW,
    top,
    bottom: top + cardHeight + MAP_CARD_TAIL_GAP + MAP_CARD_TAIL_SIZE * 2,
  }
}

export function isPopupInsideContainer(
  rect: MapPopupRect,
  container: MapPopupBounds,
  insets: MapPopupInsets = DEFAULT_INSETS,
): boolean {
  return (
    rect.left >= insets.side
    && rect.right <= container.width - insets.side
    && rect.top >= insets.top
    && rect.bottom <= container.height - insets.bottom
  )
}

function clampX(
  x: number,
  containerWidth: number,
  cardWidth: number,
  sideInset: number,
): number {
  const halfW = cardWidth / 2
  const minX = halfW + sideInset
  const maxX = containerWidth - halfW - sideInset
  if (maxX < minX) {
    return containerWidth / 2
  }
  return Math.min(maxX, Math.max(minX, x))
}

function fitPopupRect(
  position: { x: number; y: number; placement: PopupPlacement },
  container: MapPopupBounds,
  cardWidth: number,
  cardHeight: number,
  insets: MapPopupInsets,
): { x: number; y: number; placement: PopupPlacement } {
  let x = clampX(position.x, container.width, cardWidth, insets.side)
  let y = position.y
  let placement = position.placement

  if (placement === 'below') {
    y = position.y + MAP_MARKER_OFFSET
  }

  for (let attempt = 0; attempt < 6; attempt += 1) {
    const rect = getPopupRect({ x, y, placement }, cardWidth, cardHeight)

    let nextX = x
    let nextY = y

    if (rect.left < insets.side) {
      nextX += insets.side - rect.left
    }
    if (rect.right > container.width - insets.side) {
      nextX -= rect.right - (container.width - insets.side)
    }
    if (rect.top < insets.top) {
      nextY += insets.top - rect.top
    }
    if (rect.bottom > container.height - insets.bottom) {
      nextY -= rect.bottom - (container.height - insets.bottom)
    }

    x = clampX(nextX, container.width, cardWidth, insets.side)
    y = nextY

    const fitted = getPopupRect({ x, y, placement }, cardWidth, cardHeight)
    if (isPopupInsideContainer(fitted, container, insets)) {
      return { x, y, placement }
    }
  }

  return { x, y, placement }
}

export function computeMapPopupPosition(
  point: { x: number; y: number },
  container: MapPopupBounds,
  options?: {
    cardWidth?: number
    cardHeight?: number
    topInset?: number
    sideInset?: number
    bottomInset?: number
  },
): MapPopupPosition {
  const insets: MapPopupInsets = {
    top: options?.topInset ?? DEFAULT_INSETS.top,
    side: options?.sideInset ?? DEFAULT_INSETS.side,
    bottom: options?.bottomInset ?? DEFAULT_INSETS.bottom,
  }

  const cardWidth = options?.cardWidth ?? getEffectiveCardWidth(container.width, insets.side)
  const requestedHeight = options?.cardHeight ?? MAP_CARD_HEIGHT
  const availableHeight = container.height - insets.top - insets.bottom

  const maxCardHeight = Math.max(
    120,
    availableHeight - MAP_MARKER_OFFSET - MAP_CARD_TAIL_GAP - MAP_CARD_TAIL_SIZE * 2,
  )
  let effectiveCardHeight = Math.min(requestedHeight, maxCardHeight)

  const tryPlacement = (placement: PopupPlacement, height: number): MapPopupPosition | null => {
    const fitted = fitPopupRect(
      { x: point.x, y: point.y, placement },
      container,
      cardWidth,
      height,
      insets,
    )
    const rect = getPopupRect(fitted, cardWidth, height)
    if (!isPopupInsideContainer(rect, container, insets)) {
      return null
    }

    return {
      x: fitted.x,
      y: fitted.y,
      placement: fitted.placement,
      cardWidth,
      cardMaxHeight: height,
    }
  }

  const needAbove = effectiveCardHeight + MAP_CARD_TAIL_GAP + MAP_CARD_TAIL_SIZE * 2

  const spaceAbove = point.y - insets.top
  const spaceBelow = container.height - point.y - insets.bottom

  const preferAbove = spaceAbove >= needAbove || spaceAbove >= spaceBelow
  const primaryPlacement: PopupPlacement = preferAbove ? 'above' : 'below'
  const secondaryPlacement: PopupPlacement = primaryPlacement === 'above' ? 'below' : 'above'

  for (const placement of [primaryPlacement, secondaryPlacement]) {
    const result = tryPlacement(placement, effectiveCardHeight)
    if (result) {
      return result
    }
  }

  while (effectiveCardHeight >= 120) {
    for (const placement of [primaryPlacement, secondaryPlacement]) {
      const result = tryPlacement(placement, effectiveCardHeight)
      if (result) {
        return result
      }
    }
    effectiveCardHeight -= 16
  }

  const fallback = fitPopupRect(
    { x: point.x, y: point.y, placement: primaryPlacement },
    container,
    cardWidth,
    120,
    insets,
  )

  return {
    x: fallback.x,
    y: fallback.y,
    placement: fallback.placement,
    cardWidth,
    cardMaxHeight: 120,
  }
}

export function refinePopupPosition(
  position: MapPopupPosition,
  container: MapPopupBounds,
  measuredHeight: number,
  markerPoint: { x: number; y: number },
  options?: {
    topInset?: number
    sideInset?: number
    bottomInset?: number
  },
): MapPopupPosition {
  return computeMapPopupPosition(markerPoint, container, {
    cardWidth: position.cardWidth,
    cardHeight: measuredHeight,
    topInset: options?.topInset,
    sideInset: options?.sideInset,
    bottomInset: options?.bottomInset,
  })
}
