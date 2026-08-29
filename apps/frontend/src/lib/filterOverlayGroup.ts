import { computed, ref, type ComputedRef } from 'vue'

const activeOverlayId = ref<string | null>(null)

export function resetFilterOverlayGroup(): void {
  activeOverlayId.value = null
}

export function provideFilterOverlayGroup(): void {
  resetFilterOverlayGroup()
}

export function useFilterOverlay(overlayId: string): {
  isOpen: ComputedRef<boolean>
  open: () => void
  close: () => void
  toggle: () => void
} {
  const isOpen = computed(() => activeOverlayId.value === overlayId)

  function open() {
    activeOverlayId.value = overlayId
  }

  function close() {
    if (activeOverlayId.value === overlayId) {
      activeOverlayId.value = null
    }
  }

  function toggle() {
    if (isOpen.value) {
      close()
      return
    }
    open()
  }

  return { isOpen, open, close, toggle }
}

export function getActiveFilterOverlayId(): string | null {
  return activeOverlayId.value
}
