import { mount, flushPromises } from '@vue/test-utils'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { createI18n } from 'vue-i18n'
import { nextTick } from 'vue'
import ListingReportModal, { REPORT_COMMENT_MIN_LENGTH } from '@/components/ListingReportModal.vue'
import ru from '@/locales/ru.json'

vi.mock('@/api/listingReports', () => ({
  createListingReport: vi.fn(),
}))

import { createListingReport } from '@/api/listingReports'

function mountModal(props: Record<string, unknown> = {}) {
  const i18n = createI18n({
    legacy: false,
    locale: 'ru',
    messages: { ru },
  })

  return mount(ListingReportModal, {
    props: {
      open: true,
      listingId: 42,
      ...props,
    },
    global: {
      plugins: [i18n],
      stubs: {
        teleport: true,
      },
    },
  })
}

describe('ListingReportModal', () => {
  beforeEach(() => {
    vi.mocked(createListingReport).mockReset()
  })

  afterEach(() => {
    document.body.innerHTML = ''
  })

  it('requires at least 30 characters before submit', async () => {
    const wrapper = mountModal()

    expect(wrapper.find('.listing-report-modal__submit').attributes('disabled')).toBeDefined()

    await wrapper.find('.listing-report-modal__comment').setValue('короткий текст')
    await nextTick()

    expect(wrapper.find('.listing-report-modal__submit').attributes('disabled')).toBeDefined()
    expect(wrapper.text()).toContain(`14 / ${REPORT_COMMENT_MIN_LENGTH}`)

    const longComment = 'Это подробный комментарий к жалобе на объявление.'
    expect(longComment.length).toBeGreaterThanOrEqual(REPORT_COMMENT_MIN_LENGTH)

    await wrapper.find('.listing-report-modal__comment').setValue(longComment)
    await nextTick()

    expect(wrapper.find('.listing-report-modal__submit').attributes('disabled')).toBeUndefined()

    vi.mocked(createListingReport).mockResolvedValue({
      id: 1,
      listingId: 42,
      reason: 'spam',
      comment: longComment,
      status: 'new',
      createdAt: '2026-07-16T00:00:00Z',
      isTest: true,
    })

    await wrapper.find('.listing-report-modal__submit').trigger('click')
    await flushPromises()

    expect(createListingReport).toHaveBeenCalledWith(42, {
      reason: 'spam',
      comment: longComment,
    })
    expect(wrapper.text()).toContain('Жалоба отправлена')

    wrapper.unmount()
  })

  it('animates open and close via open prop', async () => {
    const wrapper = mountModal({ open: false })

    expect(wrapper.find('.listing-report-modal').exists()).toBe(false)

    await wrapper.setProps({ open: true })
    await nextTick()
    await flushPromises()

    expect(wrapper.find('.listing-report-modal').exists()).toBe(true)

    await wrapper.setProps({ open: false })
    await nextTick()
    await flushPromises()

    expect(wrapper.find('.listing-report-modal').exists()).toBe(false)

    wrapper.unmount()
  })
})
