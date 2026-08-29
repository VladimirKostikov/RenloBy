import { describe, expect, it } from 'vitest'
import {
  LISTING_DETAIL_OVERLAY_Z_INDEX,
  LISTING_NESTED_MODAL_Z_INDEX,
} from '@/lib/listingModalZIndex'

describe('listingModalZIndex', () => {
  it('keeps nested listing modals above the detail overlay', () => {
    expect(LISTING_NESTED_MODAL_Z_INDEX).toBeGreaterThan(LISTING_DETAIL_OVERLAY_Z_INDEX)
  })
})
