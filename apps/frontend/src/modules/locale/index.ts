import { createI18n } from 'vue-i18n'
import ru from '@/locales/ru.json'
import en from '@/locales/en.json'

const LOCALE_KEY = 'renlo-locale'

function readStoredLocale(): string {
  const stored = localStorage.getItem(LOCALE_KEY)
  if (stored === 'ru' || stored === 'en') {
    return stored
  }
  return 'ru'
}

export const i18n = createI18n({
  legacy: false,
  locale: readStoredLocale(),
  fallbackLocale: 'ru',
  messages: { ru, en },
})

export function setLocale(locale: 'ru' | 'en') {
  i18n.global.locale.value = locale
  localStorage.setItem(LOCALE_KEY, locale)
  document.documentElement.lang = locale
}

document.documentElement.lang = readStoredLocale()
