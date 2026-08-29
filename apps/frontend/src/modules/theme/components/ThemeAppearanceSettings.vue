<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import {
  THEME_MODE_OPTIONS,
  THEME_PALETTE_OPTIONS,
  type PaletteId,
  type ThemeMode,
} from '@/modules/theme/lib/palettes'
import { useThemeStore } from '@/stores/theme'

const { t } = useI18n()
const theme = useThemeStore()

function selectMode(mode: ThemeMode) {
  theme.setMode(mode)
}

function selectPalette(palette: PaletteId) {
  theme.setPalette(palette)
}
</script>

<template>
  <div class="theme-appearance">
    <section class="theme-appearance__section">
      <h2 class="theme-appearance__heading">{{ t('account.settings.appearance') }}</h2>
      <p class="theme-appearance__hint">{{ t('account.settings.appearanceHint') }}</p>

      <p class="theme-appearance__label">{{ t('account.settings.themeMode') }}</p>
      <div class="theme-appearance__mode-tiles" role="group" :aria-label="t('account.settings.themeMode')">
        <button
          v-for="option in THEME_MODE_OPTIONS"
          :key="option.id"
          type="button"
          class="theme-appearance__mode-btn"
          :class="{ 'theme-appearance__mode-btn--active': theme.mode === option.id }"
          :data-mode="option.id"
          :aria-pressed="theme.mode === option.id"
          @click="selectMode(option.id)"
        >
          <span class="theme-appearance__mode-preview" aria-hidden="true">
            <span class="theme-appearance__mode-preview-bar" />
            <span class="theme-appearance__mode-preview-block" />
            <span class="theme-appearance__mode-preview-lines">
              <i />
              <i />
              <i />
            </span>
          </span>
          <span class="theme-appearance__mode-name">{{ t(option.labelKey) }}</span>
        </button>
      </div>

      <p class="theme-appearance__label">{{ t('account.settings.palette') }}</p>
      <div class="theme-appearance__palettes" role="list">
        <button
          v-for="option in THEME_PALETTE_OPTIONS"
          :key="option.id"
          type="button"
          class="theme-appearance__palette"
          :class="{ 'theme-appearance__palette--active': theme.palette === option.id }"
          :aria-pressed="theme.palette === option.id"
          :aria-label="t(option.labelKey)"
          :title="t(option.labelKey)"
          @click="selectPalette(option.id)"
        >
          <span
            class="theme-appearance__swatch"
            :style="{ background: option.swatch }"
            aria-hidden="true"
          />
        </button>
      </div>
    </section>
  </div>
</template>

<style scoped>
.theme-appearance__section {
  display: flex;
  flex-direction: column;
  gap: 12px;
  width: 100%;
  min-width: 0;
}

.theme-appearance__heading {
  margin: 0;
  font-size: 18px;
  font-weight: 700;
  color: var(--color-text);
}

.theme-appearance__hint {
  margin: 0;
  font-size: 14px;
  color: var(--color-text-muted);
}

.theme-appearance__label {
  margin: 8px 0 0;
  font-size: 13px;
  font-weight: 600;
  color: var(--color-text-muted);
}

.theme-appearance__mode-tiles {
  display: grid;
  grid-template-columns: 1fr;
  gap: 12px;
  width: 100%;
}

.theme-appearance__mode-btn {
  display: flex;
  flex-direction: column;
  gap: 10px;
  min-width: 0;
  padding: 12px;
  border: 1px solid var(--figma-border);
  border-radius: 14px;
  background: var(--color-bg-elevated, #fff);
  text-align: left;
  cursor: pointer;
  transition:
    border-color 0.2s ease,
    box-shadow 0.2s ease;
}

.theme-appearance__mode-btn:hover {
  border-color: color-mix(in srgb, var(--figma-accent) 40%, transparent);
}

.theme-appearance__mode-btn--active {
  border-color: var(--figma-accent);
  box-shadow: 0 0 0 1px var(--figma-accent);
}

.theme-appearance__mode-preview {
  display: flex;
  flex-direction: column;
  gap: 8px;
  width: 100%;
  min-height: 88px;
  padding: 12px;
  border-radius: 10px;
  overflow: hidden;
}

.theme-appearance__mode-btn[data-mode='light'] .theme-appearance__mode-preview {
  background: #f3f4f6;
  border: 1px solid #e5e7eb;
}

.theme-appearance__mode-btn[data-mode='dark'] .theme-appearance__mode-preview {
  background: #111827;
  border: 1px solid #1f2937;
}

.theme-appearance__mode-preview-bar {
  display: block;
  width: 42%;
  height: 8px;
  border-radius: 999px;
}

.theme-appearance__mode-btn[data-mode='light'] .theme-appearance__mode-preview-bar {
  background: #111827;
}

.theme-appearance__mode-btn[data-mode='dark'] .theme-appearance__mode-preview-bar {
  background: #f9fafb;
}

.theme-appearance__mode-preview-block {
  display: block;
  width: 100%;
  height: 28px;
  border-radius: 8px;
  background: var(--figma-accent);
  opacity: 0.9;
}

.theme-appearance__mode-preview-lines {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.theme-appearance__mode-preview-lines i {
  display: block;
  height: 5px;
  border-radius: 999px;
}

.theme-appearance__mode-preview-lines i:nth-child(1) {
  width: 88%;
}

.theme-appearance__mode-preview-lines i:nth-child(2) {
  width: 68%;
}

.theme-appearance__mode-preview-lines i:nth-child(3) {
  width: 52%;
}

.theme-appearance__mode-btn[data-mode='light'] .theme-appearance__mode-preview-lines i {
  background: #d1d5db;
}

.theme-appearance__mode-btn[data-mode='dark'] .theme-appearance__mode-preview-lines i {
  background: #374151;
}

.theme-appearance__mode-name {
  font-size: 14px;
  font-weight: 700;
  color: var(--color-text);
}

.theme-appearance__palettes {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 10px;
  width: 100%;
}

.theme-appearance__palette {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  min-width: 36px;
  height: 36px;
  min-height: 36px;
  padding: 0;
  border: 2px solid transparent;
  border-radius: 50%;
  background: transparent;
  cursor: pointer;
  transition:
    border-color 0.2s ease,
    transform 0.2s ease,
    box-shadow 0.2s ease;
}

.theme-appearance__palette:hover {
  transform: scale(1.08);
}

.theme-appearance__palette--active {
  border-color: var(--color-text, #111);
  box-shadow: 0 0 0 2px var(--color-bg-elevated, #fff);
}

.theme-appearance__swatch {
  display: block;
  width: 28px;
  height: 28px;
  border-radius: 50%;
  flex-shrink: 0;
  border: 1px solid rgba(0, 0, 0, 0.1);
  box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.18);
}

.theme-appearance__palette:focus-visible {
  outline: 2px solid var(--figma-accent);
  outline-offset: 2px;
}

@media (prefers-reduced-motion: reduce) {
  .theme-appearance__palette {
    transition: none;
  }

  .theme-appearance__palette:hover {
    transform: none;
  }
}

@media (min-width: 640px) {
  .theme-appearance__mode-tiles {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}
</style>
