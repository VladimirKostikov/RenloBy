export function fitFontToWidth(
  element: HTMLElement,
  availableWidth: number,
  options?: { minRatio?: number; stepPx?: number },
): number {
  if (availableWidth <= 0) {
    return Number.parseFloat(window.getComputedStyle(element).fontSize) || 0
  }

  element.style.fontSize = ''
  const computedStyle = window.getComputedStyle(element)
  let size = Number.parseFloat(computedStyle.fontSize)
  if (!Number.isFinite(size) || size <= 0) {
    return 0
  }

  const minRatio = options?.minRatio ?? 0.55
  const stepPx = options?.stepPx ?? 0.5
  const minSize = Math.max(10, size * minRatio)

  while (element.scrollWidth > availableWidth && size > minSize) {
    size -= stepPx
    element.style.fontSize = `${size}px`
  }

  return size
}
