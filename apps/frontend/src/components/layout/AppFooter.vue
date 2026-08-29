<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { useAuthModal } from '@/modules/auth/composables/useAuthModal'
import { FOOTER_LEGAL_LINKS, FOOTER_SECTIONS } from '@/lib/footerLinks'
import { goToCreateListing } from '@/lib/goToCreateListing'
import AppLogomark from '@/components/layout/AppLogomark.vue'
import ThemeAppearanceMenu from '@/modules/theme/components/ThemeAppearanceMenu.vue'
import { useAuthStore } from '@/stores/auth'
import { useSiteSettingsStore } from '@/stores/siteSettings'

const { t } = useI18n()
const router = useRouter()
const auth = useAuthStore()
const authModal = useAuthModal()
const siteSettings = useSiteSettingsStore()
const year = new Date().getFullYear()

const copyright = computed(() => t('footer.copyright', { year }))

onMounted(() => {
  void siteSettings.load()
})

async function handleFooterLink(link: (typeof FOOTER_SECTIONS)[number]['links'][number]) {
  if (link.action === 'login') {
    authModal.openLogin()
    return
  }

  if (link.action === 'register' && link.key === 'post-listing') {
    await goToCreateListing({
      isAuthenticated: auth.isAuthenticated,
      router,
      openRegister: (options) => authModal.openRegister(options),
    })
    return
  }

  if (link.action === 'register') {
    authModal.openRegister()
  }
}

function isSafeMailto(url: string): boolean {
  return url.startsWith('mailto:') && !url.includes('\n') && !url.includes('\r')
}

function isSafeTel(url: string): boolean {
  return /^tel:\+?[0-9()[\]\-\s]+$/.test(url)
}
</script>

<template>
  <footer class="app-footer">
    <div class="page-shell app-footer__inner">
      <div class="app-footer__top">
        <div class="app-footer__brand">
          <a href="/" class="app-footer__logo">
            <AppLogomark :width="40" :height="40" image-class="app-footer__logomark" />
            <span class="app-footer__brand-text">
              <span class="app-footer__name">{{ t('app.name') }}</span>
              <span class="app-footer__tagline">{{ t('app.tagline') }}</span>
            </span>
          </a>
          <p class="app-footer__about">{{ siteSettings.aboutText }}</p>
        </div>

        <div class="app-footer__columns">
          <section
            v-for="section in FOOTER_SECTIONS"
            :key="section.key"
            class="app-footer__section"
          >
            <h2 class="app-footer__section-title">{{ t(section.titleKey) }}</h2>
            <ul class="app-footer__list">
              <li v-for="link in section.links" :key="link.key">
                <a
                  v-if="link.external && link.to"
                  :href="link.to"
                  class="app-footer__link"
                >
                  {{ t(link.labelKey) }}
                </a>
                <button
                  v-else-if="link.action"
                  type="button"
                  class="app-footer__link app-footer__link--button"
                  @click="handleFooterLink(link)"
                >
                  {{ t(link.labelKey) }}
                </button>
                <a
                  v-else-if="link.to"
                  :href="link.to"
                  class="app-footer__link"
                >
                  {{ t(link.labelKey) }}
                </a>
              </li>
            </ul>
          </section>

          <section class="app-footer__section">
            <h2 class="app-footer__section-title">{{ t('footer.sections.contacts') }}</h2>
            <ul class="app-footer__list">
              <li v-if="isSafeTel(siteSettings.phoneHref)">
                <a :href="siteSettings.phoneHref" class="app-footer__link app-footer__link--accent">
                  {{ siteSettings.phoneDisplay }}
                </a>
              </li>
              <li v-if="isSafeMailto(siteSettings.emailHref)">
                <a :href="siteSettings.emailHref" class="app-footer__link app-footer__link--accent">
                  {{ siteSettings.email }}
                </a>
              </li>
              <li v-if="siteSettings.ownerName">
                <span class="app-footer__meta">{{ siteSettings.ownerName }}</span>
              </li>
              <li>
                <span class="app-footer__meta">{{ siteSettings.supportHours }}</span>
              </li>
            </ul>
          </section>
        </div>
      </div>

      <div class="app-footer__bottom">
        <p class="app-footer__copyright">{{ copyright }}</p>
        <div class="app-footer__bottom-actions">
          <ThemeAppearanceMenu placement="top" variant="on-dark" />
          <a
            v-for="link in FOOTER_LEGAL_LINKS"
            :key="link.key"
            :href="link.to"
            class="app-footer__bottom-link"
          >
            {{ t(link.labelKey) }}
          </a>
        </div>
      </div>
    </div>
  </footer>
</template>

<style scoped>
.app-footer {
  margin-top: 50px;
  background: #333;
  color: rgba(255, 255, 255, 0.88);
}

.app-footer__inner {
  padding-top: 40px;
  padding-bottom: 32px;
  padding-left: max(var(--figma-page-padding-x), env(safe-area-inset-left, 0px));
  padding-right: max(var(--figma-page-padding-x), env(safe-area-inset-right, 0px));
}

.app-footer__top {
  display: grid;
  gap: 40px;
}

.app-footer__brand {
  display: flex;
  flex-direction: column;
  gap: 14px;
  max-width: 320px;
}

.app-footer__logo {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  text-decoration: none;
  color: inherit;
  transition: opacity 0.2s ease;
}

.app-footer__logo:hover {
  opacity: 0.86;
}

.app-footer__logomark {
  width: 40px;
  height: 40px;
  object-fit: contain;
}

.app-footer__brand-text {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.app-footer__name {
  font-size: 18px;
  font-weight: 700;
  line-height: 1.1;
  color: #fff;
}

.app-footer__tagline {
  font-size: 11px;
  line-height: 1.2;
  color: rgba(255, 255, 255, 0.72);
}

.app-footer__about {
  margin: 0;
  font-size: 13px;
  line-height: 1.5;
  color: rgba(255, 255, 255, 0.72);
}

.app-footer__columns {
  display: grid;
  gap: 28px 32px;
  grid-template-columns: repeat(2, minmax(0, 1fr));
}

.app-footer__section-title {
  margin: 0 0 14px;
  font-size: 13px;
  font-weight: 700;
  line-height: 1.35;
  color: #fff;
}

.app-footer__list {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin: 0;
  padding: 0;
  list-style: none;
}

.app-footer__link {
  display: inline-flex;
  align-items: center;
  min-height: 28px;
  font-family: inherit;
  font-size: 13px;
  font-weight: 500;
  line-height: 1.35;
  letter-spacing: 0;
  color: rgba(255, 255, 255, 0.78);
  text-decoration: none;
  white-space: normal;
  transition: color 0.2s ease;
}

.app-footer__link:hover {
  color: #fff;
}

.app-footer__link--accent {
  color: #fff;
  font-weight: 500;
}

.app-footer__link--button {
  border: none;
  background: transparent;
  padding: 0;
  cursor: pointer;
  text-align: left;
}

.app-footer__meta {
  font-size: 13px;
  font-weight: 500;
  line-height: 1.35;
  color: rgba(255, 255, 255, 0.62);
}

.app-footer__bottom {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
  margin-top: 40px;
  padding-top: 24px;
  border-top: 1px solid rgba(255, 255, 255, 0.12);
}

.app-footer__copyright {
  margin: 0;
  font-size: 12px;
  color: rgba(255, 255, 255, 0.55);
}

.app-footer__bottom-actions {
  display: inline-flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 10px 14px;
  justify-content: flex-end;
}

.app-footer__bottom-link {
  font-size: 12px;
  font-weight: 600;
  color: rgba(255, 255, 255, 0.72);
  text-decoration: none;
  transition: color 0.2s ease;
}

.app-footer__bottom-link:hover {
  color: #fff;
}

@media (max-width: 767px) {
  .app-footer {
    margin-top: 32px;
  }

  .app-footer__bottom {
    flex-direction: column;
    align-items: flex-start;
  }

  .app-footer__bottom-actions {
    justify-content: flex-start;
  }
}

@media (min-width: 768px) {
  .app-footer__inner {
    padding-top: 64px;
    padding-bottom: 48px;
  }

  .app-footer__columns {
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 28px 40px;
  }
}

@media (min-width: 1100px) {
  .app-footer__columns {
    grid-template-columns: repeat(5, minmax(0, 1fr));
  }
}

@media (min-width: 1280px) {
  .app-footer__top {
    grid-template-columns: 300px minmax(0, 1fr);
    align-items: start;
    gap: 64px;
  }

  .app-footer__columns {
    gap: 28px 48px;
  }
}
</style>
