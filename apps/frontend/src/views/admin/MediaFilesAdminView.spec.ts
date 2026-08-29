import { mount, flushPromises } from '@vue/test-utils'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import MediaFilesAdminView from '@/views/admin/MediaFilesAdminView.vue'
import { i18n } from '@/modules/locale'

const listMock = vi.fn()
const removeMock = vi.fn()

vi.mock('@/api/admin', () => ({
  adminMediaFiles: {
    list: (...args: unknown[]) => listMock(...args),
    get: vi.fn(),
    create: vi.fn(),
    update: vi.fn(),
    remove: (...args: unknown[]) => removeMock(...args),
  },
}))

describe('MediaFilesAdminView', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    listMock.mockReset()
    removeMock.mockReset()
    listMock.mockResolvedValue([
      {
        id: 1,
        url: '/uploads/avatars/2026/07/a.jpg',
        type: 'image',
        mimeType: 'image/jpeg',
        size: 2048,
        context: 'avatar',
        uploadedById: 2,
        uploadedByEmail: 'user@renlo.local',
        originalName: 'a.jpg',
        isTest: false,
        createdAt: '2026-07-16T10:00:00+00:00',
      },
    ])
  })

  it('renders uploaded media rows with preview', async () => {
    const wrapper = mount(MediaFilesAdminView, {
      global: {
        plugins: [i18n],
      },
    })

    await flushPromises()

    expect(listMock).toHaveBeenCalled()
    expect(wrapper.text()).toContain('user@renlo.local')
    expect(wrapper.find('.media-files-admin__thumb').attributes('src')).toBe(
      '/uploads/avatars/2026/07/a.jpg',
    )
  })
})
