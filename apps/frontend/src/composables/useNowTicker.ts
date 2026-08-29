import { onMounted, onUnmounted, ref, type Ref } from 'vue'

const TICK_MS = 30_000

const nowMs = ref(Date.now())
let subscribers = 0
let timerId: ReturnType<typeof setInterval> | null = null

function startTicker(): void {
  if (timerId !== null) {
    return
  }

  nowMs.value = Date.now()
  timerId = setInterval(() => {
    nowMs.value = Date.now()
  }, TICK_MS)
}

function stopTicker(): void {
  if (timerId === null) {
    return
  }

  clearInterval(timerId)
  timerId = null
}

export function useNowTicker(): Ref<number> {
  nowMs.value = Date.now()

  onMounted(() => {
    subscribers += 1
    startTicker()
  })

  onUnmounted(() => {
    subscribers = Math.max(0, subscribers - 1)
    if (subscribers === 0) {
      stopTicker()
    }
  })

  return nowMs
}

export function __resetNowTickerForTests(): void {
  stopTicker()
  subscribers = 0
  nowMs.value = Date.now()
}
