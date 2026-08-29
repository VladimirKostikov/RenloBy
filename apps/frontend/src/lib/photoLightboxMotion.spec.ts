import { describe, expect, it } from 'vitest'
import { buildPhotoFlipTransform, type PhotoOriginRect } from '@/lib/photoLightboxMotion'

describe('photoLightboxMotion', () => {
  it('builds flip transform from origin rect to centered target', () => {
    const origin: PhotoOriginRect = {
      top: 100,
      left: 50,
      width: 200,
      height: 140,
    }
    const target: PhotoOriginRect = {
      top: 80,
      left: 120,
      width: 800,
      height: 560,
    }

    const flip = buildPhotoFlipTransform(origin, target)

    expect(flip.scale).toBeCloseTo(0.25, 5)
    expect(flip.translateX).toBeCloseTo(50 + 100 - (120 + 400), 5)
    expect(flip.translateY).toBeCloseTo(100 + 70 - (80 + 280), 5)
  })
})
