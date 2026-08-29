import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createI18n } from 'vue-i18n'
import HeadSnippetsAdminView from '@/views/admin/HeadSnippetsAdminView.vue'
import ru from '@/locales/ru.json'

const list = vi.fn()
const create = vi.fn()
const update = vi.fn()
const remove = vi.fn()

vi.mock('@/api/admin', () => ({
  adminHeadSnippets: {
    list: (...args: unknown[]) => list(...args),
    create: (...args: unknown[]) => create(...args),
    update: (...args: unknown[]) => update(...args),
    remove: (...args: unknown[]) => remove(...args),
    get: vi.fn(),
  },
}))

describe('HeadSnippetsAdminView', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    setActivePinia(createPinia())
    list.mockResolvedValue([])
  })

  it('does not show injection hint text', async () => {
    const i18n = createI18n({
      legacy: false,
      locale: 'ru',
      messages: { ru },
    })

    const wrapper = mount(HeadSnippetsAdminView, {
      global: {
        plugins: [i18n, createPinia()],
        stubs: {
          AdminPageHeader: { template: '<div><slot name="actions" /></div>' },
          AdminCrudTable: true,
          AdminModal: true,
          AdminCrudForm: true,
          AdminConfirmDialog: true,
        },
      },
    })
    await flushPromises()

    expect(wrapper.text()).not.toContain('На сайт попадают')
    expect(wrapper.find('.head-snippets-admin__hint').exists()).toBe(false)
  })

  it('defaults new snippet isTest to false so snippets stay public', () => {
    const source = readFileSync(resolve(__dirname, './HeadSnippetsAdminView.vue'), 'utf8')
    expect(source).toContain('isTest: false')
    expect(source).not.toContain('isTest: testMode.isTest')
  })
})
