export type PhotoOriginRect = {
  top: number
  left: number
  width: number
  height: number
}

export type PhotoFlipTransform = {
  translateX: number
  translateY: number
  scale: number
}

export function readElementOriginRect(element: Element | null | undefined): PhotoOriginRect | null {
  if (!element) {
    return null
  }

  const rect = element.getBoundingClientRect()
  if (rect.width < 1 || rect.height < 1) {
    return null
  }

  return {
    top: rect.top,
    left: rect.left,
    width: rect.width,
    height: rect.height,
  }
}

export function buildPhotoFlipTransform(
  origin: PhotoOriginRect,
  target: PhotoOriginRect,
): PhotoFlipTransform {
  const scale = Math.min(origin.width / target.width, origin.height / target.height)
  const originCenterX = origin.left + origin.width / 2
  const originCenterY = origin.top + origin.height / 2
  const targetCenterX = target.left + target.width / 2
  const targetCenterY = target.top + target.height / 2

  return {
    translateX: originCenterX - targetCenterX,
    translateY: originCenterY - targetCenterY,
    scale,
  }
}

export function prefersReducedMotion(): boolean {
  if (typeof window === 'undefined' || typeof window.matchMedia !== 'function') {
    return false
  }

  return window.matchMedia('(prefers-reduced-motion: reduce)').matches
}
