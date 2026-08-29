<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute } from 'vue-router'
import { fetchInfoPage, fetchInfoPages } from '@/api/infoPages'
import InfoBreadcrumbs from '@/modules/info/components/InfoBreadcrumbs.vue'
import InfoFaqAccordion from '@/modules/info/components/InfoFaqAccordion.vue'
import InfoFeedback from '@/modules/info/components/InfoFeedback.vue'
import InfoImportantNote from '@/modules/info/components/InfoImportantNote.vue'
import InfoPageBody from '@/modules/info/components/InfoPageBody.vue'
import InfoShare from '@/modules/info/components/InfoShare.vue'
import InfoSidebar from '@/modules/info/components/InfoSidebar.vue'
import SeoPageHeading from '@/modules/seo/components/SeoPageHeading.vue'
import { useRoutePageSeo } from '@/modules/seo/useRoutePageSeo'
import { formatInfoUpdatedAt } from '@/modules/info/lib/infoPageNav'
import { readPrerenderPayload, clearPrerenderPayload } from '@/lib/readPrerenderPayload'
import type { PrerenderInfoPagePayload } from '@/modules/seo/buildPrerenderBody'
import type { InfoPageDto } from '@/types/info'

const route = useRoute()
const { t, locale } = useI18n()

const pages = ref<InfoPageDto[]>([])
const page = ref<InfoPageDto | null>(null)
const loading = ref(true)
const error = ref(false)

const slug = computed(() => String(route.params.slug ?? ''))

const breadcrumbLabel = computed(() => {
  if (!page.value) {
    return ''
  }

  const key = `info.breadcrumb.${page.value.slug}`
  const translated = t(key)
  return translated !== key ? translated : page.value.title
})

const updatedLabel = computed(() => {
  if (!page.value) {
    return ''
  }

  return t('info.updatedAt', {
    date: formatInfoUpdatedAt(page.value.updatedAt, locale.value),
  })
})

const infoSeoContext = computed(() => {
  if (!page.value) {
    return null
  }

  return {
    slug: page.value.slug,
    title: page.value.title,
    body: page.value.body,
    metaTitle: page.value.metaTitle,
    metaDescription: page.value.metaDescription,
  }
})

useRoutePageSeo({ infoPage: infoSeoContext })

async function loadPage(targetSlug: string) {
  error.value = false

  const prerender = readPrerenderPayload<PrerenderInfoPagePayload>()
  if (prerender?.kind === 'info-page' && prerender.page.slug === targetSlug) {
    page.value = {
      id: 0,
      slug: prerender.page.slug,
      title: prerender.page.title,
      body: prerender.page.body,
      category: 'buyers',
      importantNote: null,
      faqItems: [],
      sortOrder: 0,
      metaTitle: null,
      metaDescription: null,
      updatedAt: new Date().toISOString(),
    }
    loading.value = false
  } else {
    loading.value = true
  }

  try {
    if (!pages.value.length) {
      pages.value = await fetchInfoPages()
    }

    page.value = await fetchInfoPage(targetSlug)
    clearPrerenderPayload()
  } catch {
    if (!page.value) {
      error.value = true
    }
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  void loadPage(slug.value)
})

watch(slug, (nextSlug) => {
  if (!nextSlug) {
    return
  }

  void loadPage(nextSlug)
})
</script>

<template>
  <div class="info-page">
    <SeoPageHeading v-if="page" :title="page.title" />

    <main class="page-shell info-page__main">
      <InfoBreadcrumbs v-if="page" :current-label="breadcrumbLabel" />

      <div class="info-page__layout">
        <InfoSidebar
          v-if="pages.length"
          :pages="pages"
          :active-slug="slug"
        />

        <section class="info-page__content">
          <div v-if="loading" class="info-page__state">{{ t('info.loading') }}</div>
          <div v-else-if="error" class="info-page__state">{{ t('info.error') }}</div>

          <template v-else-if="page">
            <header class="info-page__header">
              <h1 class="info-page__title">{{ page.title }}</h1>
              <p class="info-page__updated">
                <img data-theme-ink src="/figma/calendar.svg" alt="" width="20" height="20" />
                <span>{{ updatedLabel }}</span>
              </p>
            </header>

            <InfoPageBody :body="page.body" />

            <InfoImportantNote
              v-if="page.importantNote"
              :text="page.importantNote"
            />

            <InfoFaqAccordion
              v-if="page.faqItems.length"
              :items="page.faqItems"
              :title="t('info.faqTitle')"
            />

            <footer class="info-page__footer">
              <InfoFeedback />
              <InfoShare :title="page.title" />
            </footer>
          </template>
        </section>
      </div>
    </main>
  </div>
</template>

<style scoped>
.info-page {
  display: flex;
  flex-direction: column;
  background: var(--figma-surface);
}

.info-page__main {
  display: flex;
  flex-direction: column;
  gap: 20px;
  padding-top: 20px;
  padding-bottom: 24px;
  background: var(--figma-page-bg);
}

.info-page__layout {
  display: flex;
  flex-direction: column;
  gap: 16px;
  min-height: 0;
}

.info-page__content {
  min-width: 0;
  width: 100%;
  padding: 24px;
  border-radius: var(--figma-radius-chip);
  background: var(--figma-surface);
  border: 1px solid var(--figma-border);
}

.info-page__state {
  padding: 40px 0;
  text-align: center;
  color: rgba(0, 0, 0, 0.72);
}

.info-page__header {
  margin-bottom: 40px;
}

.info-page__title {
  margin: 0 0 20px;
  font-size: 24px;
  font-weight: 600;
  line-height: 1.25;
}

.info-page__updated {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  margin: 0;
  font-size: 14px;
  color: rgba(0, 0, 0, 0.72);
}

.info-page__footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 16px;
  margin-top: 28px;
  padding-top: 20px;
  border-top: 1px solid var(--figma-border);
}

@media (min-width: 1280px) {
  .info-page__layout {
    flex-direction: row;
    align-items: flex-start;
    gap: 22px;
  }

  .info-page__layout > :first-child {
    width: 349px;
    flex-shrink: 0;
  }

  .info-page__content {
    flex: 1;
  }

  .info-page__title {
    font-size: 28px;
  }
}

@media (max-width: 767px) {
  .info-page__content {
    padding: 16px;
  }

  .info-page__title {
    font-size: 22px;
  }

  .info-page__header {
    margin-bottom: 24px;
  }

  .info-page__footer {
    flex-direction: column;
    align-items: stretch;
  }
}
</style>
