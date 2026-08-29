import { describe, expect, it } from 'vitest'
import { isAcceptedProfileImage, pickFirstAcceptedImage } from '@/lib/profilePhotoFile'

describe('profilePhotoFile', () => {
  it('accepts common image mime types', () => {
    expect(isAcceptedProfileImage(new File(['x'], 'a.jpg', { type: 'image/jpeg' }))).toBe(true)
    expect(isAcceptedProfileImage(new File(['x'], 'a.png', { type: 'image/png' }))).toBe(true)
    expect(isAcceptedProfileImage(new File(['x'], 'a.webp', { type: 'image/webp' }))).toBe(true)
    expect(isAcceptedProfileImage(new File(['x'], 'a.gif', { type: 'image/gif' }))).toBe(true)
  })

  it('rejects non-image files', () => {
    expect(isAcceptedProfileImage(new File(['x'], 'a.pdf', { type: 'application/pdf' }))).toBe(false)
    expect(isAcceptedProfileImage(new File(['x'], 'a.txt', { type: 'text/plain' }))).toBe(false)
  })

  it('picks first accepted image from a list', () => {
    const files = [
      new File(['x'], 'note.txt', { type: 'text/plain' }),
      new File(['x'], 'avatar.png', { type: 'image/png' }),
      new File(['x'], 'extra.jpg', { type: 'image/jpeg' }),
    ]
    const picked = pickFirstAcceptedImage(files)
    expect(picked?.name).toBe('avatar.png')
  })
})
