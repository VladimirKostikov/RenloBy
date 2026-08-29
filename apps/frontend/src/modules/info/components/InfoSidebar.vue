<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import InfoNavIcon from '@/modules/info/components/InfoNavIcon.vue'
import type { InfoPageCategory, InfoPageDto } from '@/types/info'
import { useSiteSettingsStore } from '@/stores/siteSettings'

const props = defineProps<{
  pages: InfoPageDto[]
  activeSlug: string
}>()

const { t } = useI18n()
const siteSettings = useSiteSettingsStore()

onMounted(() => {
  void siteSettings.load()
})

const navItems = computed(() =>
  props.pages.map((page) => ({
    slug: page.slug,
    category: page.category as InfoPageCategory,
    label: t(`info.nav.${page.category}`),
    active: page.slug === props.activeSlug,
  })),
)

function isSafeTel(url: string): boolean {
  return /^tel:\+?[0-9()[\]\-\s]+$/.test(url)
}
</script>

<template>
  <aside class="info-sidebar">
    <h2 class="info-sidebar__title">
      <InfoNavIcon name="info" class="info-sidebar__title-icon" />
      <span>{{ t('info.sidebarTitle') }}</span>
    </h2>

    <nav class="info-sidebar__nav">
      <a
        v-for="item in navItems"
        :key="item.slug"
        :href="`/info/${item.slug}`"
        class="info-sidebar__link"
        :class="{ 'info-sidebar__link--active': item.active }"
      >
        <span class="info-sidebar__link-bar" aria-hidden="true" />
        <InfoNavIcon :name="item.category" class="info-sidebar__link-icon" />
        <span>{{ item.label }}</span>
      </a>
    </nav>

    <div class="info-sidebar__support">
      <img data-theme-ink src="/figma/support.svg" alt="" class="info-sidebar__support-icon" width="37" height="37" />
      <div class="info-sidebar__support-text">
        <p class="info-sidebar__support-title">{{ t('info.supportTitle') }}</p>
        <a
          v-if="isSafeTel(siteSettings.phoneHref)"
          class="info-sidebar__support-phone"
          :href="siteSettings.phoneHref"
        >
          {{ siteSettings.phoneDisplay }}
        </a>
        <p class="info-sidebar__support-hours">{{ siteSettings.supportHours }}</p>
      </div>
    </div>
  </aside>
</template>

<style scoped>
.info-sidebar {
  display: flex;
  flex-direction: column;
  gap: 20px;
  padding: 24px 20px 20px;
  border-radius: var(--figma-radius-chip);
  background: var(--figma-surface);
  border: 1px solid var(--figma-border);
}

.info-sidebar__title {
  display: flex;
  align-items: center;
  gap: 12px;
  margin: 0 0 8px;
  font-size: 18px;
  font-weight: 600;
  line-height: 1.2;
  color: var(--figma-ink);
}

.info-sidebar__title-icon {
  color: var(--figma-accent);
}

.info-sidebar__nav {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.info-sidebar__link {
  position: relative;
  display: flex;
  align-items: center;
  gap: 12px;
  min-height: 35px;
  padding: 8px 16px 8px 18px;
  color: var(--figma-ink);
  font-size: 14px;
  font-weight: 600;
  text-decoration: none;
  border-radius: 4px;
  transition:
    background-color 0.2s ease,
    color 0.2s ease;
}

.info-sidebar__link:hover {
  background: rgba(225, 69, 84, 0.06);
}

.info-sidebar__link--active {
  background: rgba(225, 69, 84, 0.08);
  color: var(--figma-accent);
}

.info-sidebar__link-icon {
  color: inherit;
}

.info-sidebar__link-bar {
  position: absolute;
  left: 0;
  top: 0;
  width: 6px;
  height: 100%;
  border-radius: 3px 0 0 3px;
  background: transparent;
  transition: background-color 0.2s ease;
}

.info-sidebar__link--active .info-sidebar__link-bar {
  background: var(--figma-accent);
}

.info-sidebar__support {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  margin-top: 8px;
  padding: 16px;
  border-radius: var(--figma-radius-chip);
  border: 1px solid var(--figma-border);
  background: var(--figma-surface);
}

.info-sidebar__support-icon {
  flex-shrink: 0;
}

.info-sidebar__support-text {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.info-sidebar__support-title {
  margin: 0;
  font-size: 14px;
  font-weight: 600;
}

.info-sidebar__support-phone {
  color: var(--figma-ink);
  font-size: 12px;
  font-weight: 600;
  text-decoration: none;
}

.info-sidebar__support-phone:hover {
  color: var(--figma-accent);
}

.info-sidebar__support-hours {
  margin: 0;
  font-size: 12px;
  color: rgba(0, 0, 0, 0.72);
}
</style>
