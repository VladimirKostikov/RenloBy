import { beforeEach, describe, expect, it, vi } from 'vitest'
import { MediaFileTooLargeError, uploadAdminMedia } from '@/api/adminMedia'
import { MAX_MEDIA_FILE_BYTES } from '@/lib/mediaUploadLimits'

vi.mock('@/api/client', () => ({
  default: {
    post: vi.fn(),
  },
}))

describe('uploadAdminMedia', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('rejects files over 15 MB before request', async () => {
    const file = new File([new Uint8Array(MAX_MEDIA_FILE_BYTES + 1)], 'big.mp4', { type: 'video/mp4' })

    await expect(uploadAdminMedia(file)).rejects.toBeInstanceOf(MediaFileTooLargeError)
  })
})
