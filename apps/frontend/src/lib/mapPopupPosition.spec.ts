import { describe, expect, it } from 'vitest'
import {
  computeMapPopupPosition,
  getPopupRect,
  isPopupInsideContainer,
} from '@/lib/mapPopupPosition'

describe('mapPopupPosition', () => {
  const container = { width: 600, height: 618 }

  it('keeps popup rect inside horizontal bounds', () => {
    const left = computeMapPopupPosition({ x: 0, y: 300 }, container)
    const right = computeMapPopupPosition({ x: 600, y: 300 }, container)

    expect(isPopupInsideContainer(
      getPopupRect(left, left.cardWidth, left.cardMaxHeight),
      container,
    )).toBe(true)
    expect(isPopupInsideContainer(
      getPopupRect(right, right.cardWidth, right.cardMaxHeight),
      container,
    )).toBe(true)
  })

  it('places card above marker when there is enough space', () => {
    const position = computeMapPopupPosition({ x: 300, y: 450 }, container)

    expect(position.placement).toBe('above')
    expect(position.y).toBe(450)
    expect(isPopupInsideContainer(
      getPopupRect(position, position.cardWidth, position.cardMaxHeight),
      container,
    )).toBe(true)
  })

  it('flips card below marker when top space is too small', () => {
    const position = computeMapPopupPosition({ x: 300, y: 60 }, container)

    expect(position.placement).toBe('below')
    expect(isPopupInsideContainer(
      getPopupRect(position, position.cardWidth, position.cardMaxHeight),
      container,
    )).toBe(true)
  })

  it('fits popup inside short map height', () => {
    const position = computeMapPopupPosition({ x: 300, y: 80 }, { width: 600, height: 220 })

    expect(isPopupInsideContainer(
      getPopupRect(position, position.cardWidth, position.cardMaxHeight),
      { width: 600, height: 220 },
    )).toBe(true)
  })

  it('limits card width on narrow map panel', () => {
    const position = computeMapPopupPosition({ x: 140, y: 200 }, { width: 280, height: 280 })

    expect(position.cardWidth).toBeLessThan(263)
    expect(isPopupInsideContainer(
      getPopupRect(position, position.cardWidth, position.cardMaxHeight),
      { width: 280, height: 280 },
    )).toBe(true)
  })

  it('clamps popup for cramped vertical space', () => {
    const position = computeMapPopupPosition({ x: 300, y: 300 }, { width: 600, height: 500 })

    expect(position.cardMaxHeight).toBeLessThan(500)
    expect(isPopupInsideContainer(
      getPopupRect(position, position.cardWidth, position.cardMaxHeight),
      { width: 600, height: 500 },
    )).toBe(true)
  })
})
