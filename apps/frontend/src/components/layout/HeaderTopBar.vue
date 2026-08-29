<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink } from 'vue-router'
import SocialBrandIcon from '@/components/SocialBrandIcon.vue'
import { isSafeHttpUrl, isSafeTelHref } from '@/lib/safeLinks'
import { useUserLocation } from '@/modules/location/composables/useUserLocation'
import ThemeAppearanceMenu from '@/modules/theme/components/ThemeAppearanceMenu.vue'
import { useAuthStore } from '@/stores/auth'
import { useExchangeRateStore } from '@/stores/exchangeRate'
import { useSiteSettingsStore } from '@/stores/siteSettings'

const NBRB_RATES_URL = 'https://www.nbrb.by/statistics/rates/ratesdaily'

const { t } = useI18n()
const auth = useAuthStore()
const siteSettings = useSiteSettingsStore()
const exchangeRate = useExchangeRateStore()
const {
  detecting,
  cityOptions,
  selectedCityId,
  bootstrap,
  selectCity,
  selectNationwide,
  refreshLocation,
} = useUserLocation()

const socialLinks = computed(() => {
  const items: { key: 'telegram' | 'whatsapp' | 'vk'; href: string; label: string }[] = []
  if (isSafeHttpUrl(siteSettings.telegramUrl)) {
    items.push({
      key: 'telegram',
      href: siteSettings.telegramUrl,
      label: t('topBar.telegram'),
    })
  }
  if (isSafeHttpUrl(siteSettings.whatsappUrl)) {
    items.push({
      key: 'whatsapp',
      href: siteSettings.whatsappUrl,
      label: t('topBar.whatsapp'),
    })
  }
  if (isSafeHttpUrl(siteSettings.vkUrl)) {
    items.push({
      key: 'vk',
      href: siteSettings.vkUrl,
      label: t('topBar.vk'),
    })
  }
  return items
})

const phoneVisible = computed(() => isSafeTelHref(siteSettings.phoneHref))

onMounted(() => {
  void bootstrap()
  void siteSettings.load()
  void exchangeRate.load()
})

function onCityChange(event: Event) {
  const raw = (event.target as HTMLSelectElement).value
  if (raw === '') {
    void selectNationwide()
    return
  }

  const value = Number(raw)
  if (!Number.isFinite(value)) {
    return
  }

  void selectCity(value)
}
</script>

<template>
  <div class="header-top-bar">
    <div class="page-shell header-top-bar__inner">
      <div class="header-top-bar__location">
        <svg
          class="header-top-bar__pin"
          width="14"
          height="18"
          viewBox="0 0 16 20"
          fill="none"
          xmlns="http://www.w3.org/2000/svg"
          aria-hidden="true"
        >
          <path
            class="header-top-bar__pin-base"
            fill-rule="evenodd"
            clip-rule="evenodd"
            d="M7.56215 19.3143C7.95734 19.3143 15.0186 11.7697 15.0186 7.65163C15.0186 3.53355 11.6802 0.19519 7.56215 0.19519C3.44407 0.19519 0.105713 3.53355 0.105713 7.65163C0.105713 11.7697 7.16697 19.3143 7.56215 19.3143ZM7.56216 11.3811C9.65003 11.3811 11.3426 9.68851 11.3426 7.60064C11.3426 5.51277 9.65003 3.82022 7.56216 3.82022C5.4743 3.82022 3.78175 5.51277 3.78175 7.60064C3.78175 9.68851 5.4743 11.3811 7.56216 11.3811Z"
          />
          <path
            class="header-top-bar__pin-shade"
            fill-rule="evenodd"
            clip-rule="evenodd"
            d="M0.105713 7.65163C0.105713 11.7697 7.16697 19.3143 7.56215 19.3143V11.3811C5.47429 11.381 3.78175 9.6885 3.78175 7.60064C3.78175 5.51278 5.47429 3.82023 7.56215 3.82022V0.19519C3.44407 0.19519 0.105713 3.53355 0.105713 7.65163Z"
          />
        </svg>
        <span v-if="detecting" class="header-top-bar__status">
          {{ t('topBar.detecting') }}
        </span>
        <template v-else>
          <select
            class="header-top-bar__city"
            :value="selectedCityId"
            :aria-label="t('topBar.selectCity')"
            @change="onCityChange"
          >
            <option value="">
              {{ t('map.breadcrumb.belarus') }}
            </option>
            <option
              v-for="city in cityOptions"
              :key="city.value"
              :value="city.value"
            >
              {{ city.label }}
            </option>
          </select>
          <button
            type="button"
            class="header-top-bar__refresh"
            :title="t('topBar.refreshLocation')"
            :aria-label="t('topBar.refreshLocation')"
            @click="refreshLocation"
          >
            <svg
              class="header-top-bar__refresh-icon"
              width="12"
              height="12"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              aria-hidden="true"
            >
              <circle cx="12" cy="12" r="3" />
              <path d="M12 2v3M12 19v3M2 12h3M19 12h3" />
            </svg>
          </button>
        </template>
      </div>

      <div class="header-top-bar__controls">
        <a
          class="header-top-bar__rate"
          :href="NBRB_RATES_URL"
          target="_blank"
          rel="noopener noreferrer"
          :title="t('topBar.exchangeRateHint')"
        >
          <span class="header-top-bar__rate-label">{{ t('topBar.exchangeRate') }}</span>
          <span class="header-top-bar__rate-value">1 $ = {{ exchangeRate.rateLabel }} BYN</span>
        </a>

        <ThemeAppearanceMenu placement="bottom" />

        <div
          v-if="socialLinks.length || phoneVisible"
          class="header-top-bar__contacts"
        >
          <a
            v-for="item in socialLinks"
            :key="item.key"
            class="header-top-bar__social"
            :href="item.href"
            :title="item.label"
            :aria-label="item.label"
            target="_blank"
            rel="noopener noreferrer"
          >
            <SocialBrandIcon :name="item.key" :size="16" />
          </a>
          <a
            v-if="phoneVisible"
            class="header-top-bar__phone"
            :href="siteSettings.phoneHref"
            :title="t('topBar.phone')"
          >
            {{ siteSettings.phoneDisplay }}
          </a>
        </div>

        <RouterLink
          v-if="auth.isAdmin"
          to="/admin"
          class="header-top-bar__admin"
        >
          {{ t('nav.admin') }}
        </RouterLink>
      </div>
    </div>
  </div>
</template>

<style scoped>
.header-top-bar {
  background: var(--figma-page-bg);
  border-bottom: 1px solid var(--figma-border);
}

.header-top-bar__inner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  min-height: 34px;
  padding-top: 4px;
  padding-bottom: 4px;
}

.header-top-bar__location {
  display: flex;
  align-items: center;
  gap: 8px;
  min-width: 0;
  flex: 1 1 auto;
}

.header-top-bar__pin {
  flex-shrink: 0;
  display: block;
}

.header-top-bar__pin-base {
  fill: var(--figma-accent);
}

.header-top-bar__pin-shade {
  fill: color-mix(in srgb, var(--figma-accent) 72%, var(--figma-mix-base));
}

.header-top-bar__status {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  font-size: 12px;
  font-weight: 600;
  color: var(--figma-text-muted);
}

.header-top-bar__city {
  appearance: none;
  -webkit-appearance: none;
  max-width: 240px;
  min-width: 140px;
  height: 28px;
  padding: 0 28px 0 12px;
  border: 1px solid var(--figma-border);
  border-radius: 50px;
  background-color: var(--figma-surface);
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6' fill='none'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%23currentColor' stroke-width='1.4' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 10px center;
  background-size: 10px 6px;
  font-family: inherit;
  font-size: 12px;
  font-weight: 600;
  line-height: 26px;
  color: var(--figma-ink);
  cursor: pointer;
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.header-top-bar__city:hover {
  border-color: var(--figma-accent);
  box-shadow: 0 0 0 3px rgba(225, 69, 84, 0.08);
}

.header-top-bar__city:focus-visible {
  outline: 2px solid var(--figma-accent);
  outline-offset: 2px;
}

.header-top-bar__city option {
  font-weight: 600;
}

.header-top-bar__refresh {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  border: 1px solid var(--figma-border);
  border-radius: 50%;
  background: var(--figma-surface);
  cursor: pointer;
  transition: border-color 0.2s ease, color 0.2s ease, background-color 0.2s ease;
}

.header-top-bar__refresh:hover {
  border-color: var(--figma-accent);
  color: var(--figma-accent);
  background: rgba(225, 69, 84, 0.06);
}

.header-top-bar__refresh-icon {
  display: block;
}

.header-top-bar__controls {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
}

.header-top-bar__rate {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  margin: 0;
  min-height: 28px;
  padding: 0 10px;
  border: 1px solid var(--figma-border);
  border-radius: 50px;
  background: var(--figma-surface);
  text-decoration: none;
  white-space: nowrap;
  transition: border-color 0.2s ease, color 0.2s ease;
}

.header-top-bar__rate:hover {
  border-color: var(--figma-accent);
}

.header-top-bar__rate-label {
  font-size: 11px;
  font-weight: 600;
  color: var(--color-text-muted);
}

.header-top-bar__rate-value {
  font-size: 12px;
  font-weight: 700;
  color: var(--figma-ink-secondary);
}

.header-top-bar__contacts {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  min-width: 0;
}

.header-top-bar__social {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  border-radius: 50%;
  color: var(--color-text-muted);
  text-decoration: none;
  transition: color 0.2s ease, background-color 0.2s ease;
}

.header-top-bar__social:hover {
  color: var(--figma-accent);
  background: rgba(225, 69, 84, 0.08);
}

.header-top-bar__phone {
  display: inline-flex;
  align-items: center;
  max-width: 160px;
  height: 28px;
  padding: 0 4px;
  overflow: hidden;
  color: var(--figma-ink-secondary);
  font-size: 12px;
  font-weight: 700;
  text-decoration: none;
  text-overflow: ellipsis;
  white-space: nowrap;
  transition: color 0.2s ease;
}

.header-top-bar__phone:hover {
  color: var(--figma-accent);
}

.header-top-bar__admin {
  display: inline-flex;
  align-items: center;
  height: 28px;
  padding: 0 4px;
  color: var(--color-text-muted);
  font-size: 12px;
  font-weight: 600;
  text-decoration: none;
  white-space: nowrap;
  transition: color 0.2s ease;
}

.header-top-bar__admin:hover {
  color: var(--figma-accent);
}

@media (max-width: 767px) {
  .header-top-bar__inner {
    gap: 8px;
    min-height: 28px;
    padding-top: 2px;
    padding-bottom: 2px;
  }

  .header-top-bar__city {
    max-width: none;
    flex: 1 1 auto;
    min-width: 0;
    height: 26px;
    line-height: 24px;
  }

  .header-top-bar__rate,
  .header-top-bar__contacts,
  .header-top-bar__phone,
  .header-top-bar__admin {
    display: none;
  }

  .header-top-bar__controls {
    gap: 4px;
  }
}
</style>
