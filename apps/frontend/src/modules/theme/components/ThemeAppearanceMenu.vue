<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  THEME_MODE_OPTIONS,
  THEME_PALETTE_OPTIONS,
  type PaletteId,
  type ThemeMode,
} from '@/modules/theme/lib/palettes'
import { useThemeStore } from '@/stores/theme'

const props = withDefaults(defineProps<{
  placement?: 'bottom' | 'top'
  variant?: 'default' | 'on-dark'
}>(), {
  placement: 'bottom',
  variant: 'default',
})

const { t } = useI18n()
const theme = useThemeStore()
const open = ref(false)
const rootRef = ref<HTMLElement | null>(null)

function toggle() {
  open.value = !open.value
}

function close() {
  open.value = false
}

function selectMode(mode: ThemeMode) {
  theme.setMode(mode)
}

function selectPalette(palette: PaletteId) {
  theme.setPalette(palette)
}

function onDocumentPointerDown(event: PointerEvent) {
  if (!open.value || !rootRef.value) {
    return
  }
  const target = event.target
  if (target instanceof Node && !rootRef.value.contains(target)) {
    close()
  }
}

function onDocumentKeydown(event: KeyboardEvent) {
  if (event.key === 'Escape' && open.value) {
    close()
  }
}

onMounted(() => {
  document.addEventListener('pointerdown', onDocumentPointerDown)
  document.addEventListener('keydown', onDocumentKeydown)
})

onBeforeUnmount(() => {
  document.removeEventListener('pointerdown', onDocumentPointerDown)
  document.removeEventListener('keydown', onDocumentKeydown)
})
</script>

<template>
  <div
    ref="rootRef"
    class="theme-menu"
    :class="[
      `theme-menu--${props.placement}`,
      `theme-menu--${props.variant}`,
      { 'theme-menu--open': open },
    ]"
  >
    <button
      type="button"
      class="theme-menu__trigger"
      :aria-label="t('theme.menu')"
      :aria-expanded="open"
      :aria-haspopup="true"
      @click="toggle"
    >
      <span class="theme-menu__rainbow" aria-hidden="true" />
    </button>

    <div
      v-if="open"
      class="theme-menu__panel"
      role="dialog"
      :aria-label="t('theme.menu')"
    >
      <p class="theme-menu__label">{{ t('account.settings.themeMode') }}</p>
      <div class="theme-menu__modes" role="group" :aria-label="t('account.settings.themeMode')">
        <button
          v-for="option in THEME_MODE_OPTIONS"
          :key="option.id"
          type="button"
          class="theme-menu__mode"
          :class="{ 'theme-menu__mode--active': theme.mode === option.id }"
          :aria-pressed="theme.mode === option.id"
          @click="selectMode(option.id)"
        >
          {{ t(option.labelKey) }}
        </button>
      </div>

      <p class="theme-menu__label">{{ t('theme.palette') }}</p>
      <div class="theme-menu__swatches" role="list">
        <button
          v-for="option in THEME_PALETTE_OPTIONS"
          :key="option.id"
          type="button"
          class="theme-menu__swatch"
          :class="{ 'theme-menu__swatch--active': theme.palette === option.id }"
          :style="{ background: option.swatch }"
          :aria-label="t(option.labelKey)"
          :aria-pressed="theme.palette === option.id"
          :title="t(option.labelKey)"
          @click="selectPalette(option.id)"
        />
      </div>
    </div>
  </div>
</template>

<style scoped>
.theme-menu {
  position: relative;
  display: inline-flex;
  flex-shrink: 0;
}

.theme-menu__trigger {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  padding: 0;
  border: 1px solid var(--figma-border, #e5e7eb);
  border-radius: 50%;
  background: var(--figma-surface);
  cursor: pointer;
  transition:
    border-color 0.2s ease,
    box-shadow 0.2s ease,
    transform 0.2s ease;
}

.theme-menu__trigger:hover {
  border-color: color-mix(in srgb, var(--figma-accent) 45%, var(--figma-border, #e5e7eb));
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--figma-accent) 12%, transparent);
}

.theme-menu__trigger:active {
  transform: scale(0.96);
}

.theme-menu__trigger:focus-visible {
  outline: 2px solid var(--figma-accent);
  outline-offset: 2px;
}

.theme-menu__rainbow {
  display: block;
  width: 16px;
  height: 16px;
  border-radius: 50%;
  background: conic-gradient(
    from 0deg,
    #e14554,
    #d97706,
    #ca8a04,
    #65a30d,
    #059669,
    #0891b2,
    #1d4ed8,
    #4f46e5,
    #a21caf,
    #be185d,
    #e14554
  );
  box-shadow: inset 0 0 0 1px rgba(0, 0, 0, 0.08);
}

.theme-menu__panel {
  position: absolute;
  z-index: 80;
  right: 0;
  width: min(280px, calc(100vw - 24px));
  padding: 14px;
  border: 1px solid var(--figma-border, #e5e7eb);
  border-radius: 12px;
  background: var(--color-bg-elevated, #fff);
  box-shadow: 0 12px 32px rgba(0, 0, 0, 0.14);
  color: var(--color-text, #000);
  animation: theme-menu-in 0.18s ease;
}

.theme-menu--bottom .theme-menu__panel {
  top: calc(100% + 8px);
}

.theme-menu--top .theme-menu__panel {
  bottom: calc(100% + 8px);
}

.theme-menu__label {
  margin: 0 0 8px;
  font-size: 12px;
  font-weight: 700;
  color: var(--color-text-muted, rgba(0, 0, 0, 0.72));
}

.theme-menu__label + .theme-menu__swatches,
.theme-menu__modes + .theme-menu__label {
  margin-top: 14px;
}

.theme-menu__modes {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 6px;
  padding: 3px;
  border: 1px solid var(--figma-border, #e5e7eb);
  border-radius: 10px;
  background: var(--color-bg-muted, #f5f5f5);
}

.theme-menu__mode {
  min-height: 34px;
  padding: 0 10px;
  border: none;
  border-radius: 8px;
  background: transparent;
  color: var(--color-text-muted, rgba(0, 0, 0, 0.72));
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition:
    background-color 0.2s ease,
    color 0.2s ease;
}

.theme-menu__mode--active {
  background: var(--figma-accent);
  color: var(--figma-on-accent);
}

.theme-menu__swatches {
  display: grid;
  grid-template-columns: repeat(8, minmax(0, 1fr));
  gap: 8px;
}

.theme-menu__swatch {
  width: 100%;
  aspect-ratio: 1;
  min-width: 0;
  min-height: 24px;
  padding: 0;
  border: 2px solid transparent;
  border-radius: 50%;
  box-shadow: inset 0 0 0 1px rgba(0, 0, 0, 0.1);
  cursor: pointer;
  transition:
    transform 0.15s ease,
    box-shadow 0.15s ease,
    border-color 0.15s ease;
}

.theme-menu__swatch:hover {
  transform: scale(1.08);
}

.theme-menu__swatch--active {
  border-color: var(--color-text, #000);
  box-shadow:
    inset 0 0 0 1px rgba(255, 255, 255, 0.35),
    0 0 0 2px color-mix(in srgb, var(--figma-accent) 35%, transparent);
}

.theme-menu--on-dark .theme-menu__trigger {
  border-color: rgba(255, 255, 255, 0.22);
  background: rgba(255, 255, 255, 0.08);
}

.theme-menu--on-dark .theme-menu__trigger:hover {
  border-color: rgba(255, 255, 255, 0.42);
  box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.08);
}

@keyframes theme-menu-in {
  from {
    opacity: 0;
    transform: translateY(4px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.theme-menu--top .theme-menu__panel {
  animation-name: theme-menu-in-up;
}

@keyframes theme-menu-in-up {
  from {
    opacity: 0;
    transform: translateY(-4px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@media (prefers-reduced-motion: reduce) {
  .theme-menu__panel {
    animation: none;
  }

  .theme-menu__swatch,
  .theme-menu__trigger {
    transition: none;
  }
}
</style>
