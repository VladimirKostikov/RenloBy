let loadPromise: Promise<void> | null = null

export function loadYandexMapsApi(apiKey: string): Promise<void> {
  if (typeof window === 'undefined') {
    return Promise.resolve()
  }

  if (window.ymaps) {
    return new Promise((resolve) => {
      window.ymaps!.ready(resolve)
    })
  }

  if (!loadPromise) {
    loadPromise = new Promise((resolve, reject) => {
      const script = document.createElement('script')
      script.src = `https://api-maps.yandex.ru/2.1/?apikey=${encodeURIComponent(apiKey)}&lang=ru_RU`
      script.async = true
      script.onload = () => {
        if (!window.ymaps) {
          reject(new Error('yandex_maps_unavailable'))
          return
        }

        window.ymaps.ready(() => resolve())
      }
      script.onerror = () => reject(new Error('yandex_maps_load_failed'))
      document.head.appendChild(script)
    })
  }

  return loadPromise
}
