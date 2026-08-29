import { createApp } from 'vue'
import { createHead } from '@unhead/vue/client'
import { createPinia } from 'pinia'
import '@fontsource/open-sans/400.css'
import '@fontsource/open-sans/600.css'
import '@fontsource/open-sans/700.css'
import '@/themes/base.css'
import '@/themes/light.css'
import '@/themes/dark.css'
import '@/themes/palettes.css'
import '@/themes/figma-home.css'
import '@/themes/figma-location.css'
import '@/themes/figma-catalog.css'
import '@/themes/figma-listing-detail.css'
import '@/themes/figma-search.css'
import '@/styles/responsive.css'
import '@/styles/cross-browser.css'
import '@/styles/filter-chip.css'
import '@/styles/theme-icons.css'
import App from './App.vue'
import router from './router'
import { i18n } from './modules/locale'
import { useThemeStore } from './stores/theme'

const app = createApp(App)
const head = createHead()

const pinia = createPinia()
app.use(pinia)
useThemeStore()
app.use(head)
app.use(router)
app.use(i18n)
app.mount('#app')
