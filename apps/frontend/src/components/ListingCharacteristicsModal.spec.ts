import { readFileSync } from 'fs'
import { dirname, resolve } from 'path'
import { fileURLToPath } from 'url'
import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import { createI18n } from 'vue-i18n'
import ListingCharacteristicsModal from '@/components/ListingCharacteristicsModal.vue'
import ru from '@/locales/ru.json'

const componentsDir = resolve(dirname(fileURLToPath(import.meta.url)))

describe('ListingCharacteristicsModal', () => {
  it('uses theme tokens for characteristic text colors', () => {
    const panelCss = readFileSync(resolve(componentsDir, 'ListingDetailPanel.vue'), 'utf8')
    const modalCss = readFileSync(resolve(componentsDir, 'ListingCharacteristicsModal.vue'), 'utf8')

    expect(panelCss).toContain('.listing-detail-modal__characteristics-row dt')
    expect(panelCss).toMatch(
      /\.listing-detail-modal__characteristics-row dt\s*\{[^}]*color:\s*var\(--figma-text-muted\)/s,
    )
    expect(panelCss).not.toMatch(
      /\.listing-detail-modal__characteristics-row dt\s*\{[^}]*color:\s*rgba\(0,\s*0,\s*0/s,
    )
    expect(modalCss).toMatch(/\.listing-chars-modal__row dt\s*\{[^}]*color:\s*var\(--figma-text-muted\)/s)
    expect(modalCss).toMatch(/\.listing-chars-modal__head h2\s*\{[^}]*color:\s*var\(--figma-ink\)/s)
  })

  it('renders all characteristic rows', () => {
    const i18n = createI18n({ legacy: false, locale: 'ru', messages: { ru } })
    const wrapper = mount(ListingCharacteristicsModal, {
      props: {
        rows: [
          { label: 'listingDetail.rooms', value: '3' },
          { label: 'listingDetail.floor', value: '9/16' },
          { label: 'listingDetail.houseType', value: 'Кирпичный' },
        ],
      },
      global: { plugins: [i18n] },
    })

    expect(wrapper.text()).toContain('Характеристики')
    expect(wrapper.text()).toContain('Комнаты')
    expect(wrapper.text()).toContain('Кирпичный')
    expect(wrapper.findAll('.listing-chars-modal__row')).toHaveLength(3)
  })

  it('emits close from backdrop and button', async () => {
    const i18n = createI18n({ legacy: false, locale: 'ru', messages: { ru } })
    const wrapper = mount(ListingCharacteristicsModal, {
      props: { rows: [] },
      global: { plugins: [i18n] },
    })

    await wrapper.find('.listing-chars-modal__close').trigger('click')
    expect(wrapper.emitted('close')?.length).toBe(1)

    await wrapper.find('.listing-chars-modal').trigger('click')
    expect(wrapper.emitted('close')?.length).toBe(2)
  })
})
