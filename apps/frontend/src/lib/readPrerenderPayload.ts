export function readPrerenderPayload<T>(): T | null {
  const element = document.getElementById('renlo-prerender')
  if (!element?.textContent) {
    return null
  }

  try {
    return JSON.parse(element.textContent) as T
  } catch {
    return null
  }
}

export function clearPrerenderPayload(): void {
  document.getElementById('renlo-prerender')?.remove()
}
