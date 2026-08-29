import { describe, expect, it } from 'vitest'
import { isSafeMediaUrl } from '@/lib/isSafeMediaUrl'

describe('isSafeMediaUrl', () => {
  it('allows https and local uploads paths', () => {
    expect(isSafeMediaUrl('https://images.unsplash.com/photo.jpg')).toBe(true)
    expect(isSafeMediaUrl('/uploads/articles/2026/07/file.jpg')).toBe(true)
  })

  it('rejects unsafe values', () => {
    expect(isSafeMediaUrl('javascript:alert(1)')).toBe(false)
    expect(isSafeMediaUrl('/uploads/../etc/passwd')).toBe(false)
    expect(isSafeMediaUrl('')).toBe(false)
  })
})
