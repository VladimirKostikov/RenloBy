import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { fetchSiteSettings, type SiteSettingsDto } from '@/api/siteSettings'

const FALLBACK = {
  aboutText: 'Агрегатор покупки, продажи и аренды квартир в Беларуси.',
  phoneDisplay: '+375 29 000-00-00',
  phoneRaw: '+375290000000',
  email: 'support@renlo.by',
  supportHours: 'Ежедневно 9:00-18:00',
  ownerName: 'Renlo',
  address: 'Минск, Беларусь',
  offersText: 'По вопросам рекламы и сотрудничества пишите на почту для предложений.',
  offersEmail: '',
  telegramUrl: 'https://t.me/renlo_bot',
  whatsappUrl: 'https://wa.me/375290000000',
  vkUrl: 'https://vk.com/renlo',
} satisfies Omit<SiteSettingsDto, 'id' | 'isTest'>

export const useSiteSettingsStore = defineStore('siteSettings', () => {
  const settings = ref<SiteSettingsDto | null>(null)
  const loaded = ref(false)
  const loading = ref(false)

  const aboutText = computed(() => settings.value?.aboutText || FALLBACK.aboutText)
  const phoneDisplay = computed(() => settings.value?.phoneDisplay || FALLBACK.phoneDisplay)
  const phoneRaw = computed(() => settings.value?.phoneRaw || FALLBACK.phoneRaw)
  const email = computed(() => settings.value?.email || FALLBACK.email)
  const supportHours = computed(() => settings.value?.supportHours || FALLBACK.supportHours)
  const ownerName = computed(() => settings.value?.ownerName || FALLBACK.ownerName)
  const address = computed(() => settings.value?.address || FALLBACK.address)
  const offersText = computed(() => settings.value?.offersText || FALLBACK.offersText)
  const offersEmail = computed(() => settings.value?.offersEmail || FALLBACK.offersEmail)
  const telegramUrl = computed(() => settings.value?.telegramUrl || FALLBACK.telegramUrl)
  const whatsappUrl = computed(() => settings.value?.whatsappUrl || FALLBACK.whatsappUrl)
  const vkUrl = computed(() => settings.value?.vkUrl || FALLBACK.vkUrl)
  const phoneHref = computed(() => `tel:${phoneRaw.value}`)
  const emailHref = computed(() => `mailto:${email.value}`)
  const offersEmailHref = computed(() =>
    offersEmail.value ? `mailto:${offersEmail.value}` : '',
  )

  async function load(force = false) {
    if (loading.value || (loaded.value && !force)) {
      return
    }
    loading.value = true
    try {
      settings.value = await fetchSiteSettings()
      loaded.value = true
    } catch {
      settings.value = null
      loaded.value = true
    } finally {
      loading.value = false
    }
  }

  return {
    settings,
    loaded,
    loading,
    aboutText,
    phoneDisplay,
    phoneRaw,
    email,
    supportHours,
    ownerName,
    address,
    offersText,
    offersEmail,
    telegramUrl,
    whatsappUrl,
    vkUrl,
    phoneHref,
    emailHref,
    offersEmailHref,
    load,
  }
})
