import { beforeEach, describe, expect, it, vi } from 'vitest'
import { uploadAdminUserPhoto } from '@/api/admin'

vi.mock('@/api/client', () => ({
  default: {
    post: vi.fn(async () => ({
      data: {
        id: 5,
        email: 'user@test.local',
        name: 'User',
        roles: ['ROLE_USER'],
        photo: '/uploads/avatars/2026/07/abc.jpg',
      },
    })),
    get: vi.fn(),
    patch: vi.fn(),
    delete: vi.fn(),
  },
}))

describe('uploadAdminUserPhoto', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('posts multipart file to admin users photo endpoint', async () => {
    const { default: apiClient } = await import('@/api/client')
    const file = new File([new Uint8Array([1, 2, 3])], 'avatar.png', { type: 'image/png' })

    const result = await uploadAdminUserPhoto(5, file)

    expect(apiClient.post).toHaveBeenCalledWith(
      '/admin/users/5/photo',
      expect.any(FormData),
      expect.objectContaining({
        headers: { 'Content-Type': undefined },
      }),
    )
    expect(result.photo).toBe('/uploads/avatars/2026/07/abc.jpg')
  })
})
