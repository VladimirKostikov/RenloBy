import { describe, expect, it } from 'vitest'
import {
  resolveDistrictSlug,
  shouldToggleCloseOnReselect,
  sliceListingImages,
} from '@/lib/mapListingSelection'

describe('mapListingSelection', () => {
  it('resolves district slug by id', () => {
    const districts = [
      { id: 1, slug: 'central' },
      { id: 2, slug: 'sovetsky' },
    ]

    expect(resolveDistrictSlug(districts, 2)).toBe('sovetsky')
    expect(resolveDistrictSlug(districts, 99)).toBeNull()
  })

  it('toggles close when the same listing is selected again', () => {
    expect(shouldToggleCloseOnReselect(5, 5)).toBe(true)
    expect(shouldToggleCloseOnReselect(5, 6)).toBe(false)
    expect(shouldToggleCloseOnReselect(null, 1)).toBe(false)
  })

  it('limits slider images to four slides', () => {
    const images = ['a.jpg', 'b.jpg', 'c.jpg', 'd.jpg', 'e.jpg']
    expect(sliceListingImages(images)).toEqual(['a.jpg', 'b.jpg', 'c.jpg', 'd.jpg'])
    expect(sliceListingImages(images, 2)).toEqual(['a.jpg', 'b.jpg'])
  })
})
