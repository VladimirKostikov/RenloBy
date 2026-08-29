import { describe, expect, it } from 'vitest'
import { isMediaFileWithinLimit, MAX_MEDIA_FILE_BYTES } from '@/lib/mediaUploadLimits'

describe('mediaUploadLimits', () => {
  it('allows files up to 15 MB', () => {
    const file = new File(['ok'], 'ok.jpg')
    Object.defineProperty(file, 'size', { value: MAX_MEDIA_FILE_BYTES })
    expect(isMediaFileWithinLimit(file)).toBe(true)
  })

  it('rejects files over 15 MB', () => {
    const file = new File(['big'], 'big.mp4')
    Object.defineProperty(file, 'size', { value: MAX_MEDIA_FILE_BYTES + 1 })
    expect(isMediaFileWithinLimit(file)).toBe(false)
  })
})
