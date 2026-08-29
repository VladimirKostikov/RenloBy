import { describe, expect, it } from 'vitest'
import { getEventContainerPoint, getEventPointer } from '@/modules/map/lib/yandexMapEvents'

describe('yandexMapEvents', () => {
  it('reads pointer from dom event', () => {
    const event = {
      get: (key: string) => {
        if (key === 'domEvent') {
          return new MouseEvent('click', { clientX: 120, clientY: 240 })
        }
        return undefined
      },
      stopPropagation: () => undefined,
    } as YandexMapEvent

    expect(getEventPointer(event)).toEqual({ x: 120, y: 240 })
  })

  it('converts pointer to container coordinates', () => {
    const container = document.createElement('div')
    container.getBoundingClientRect = () => ({
      x: 20,
      y: 30,
      left: 20,
      top: 30,
      right: 420,
      bottom: 330,
      width: 400,
      height: 300,
      toJSON: () => ({}),
    })

    const event = {
      get: (key: string) => {
        if (key === 'domEvent') {
          return new MouseEvent('click', { clientX: 120, clientY: 240 })
        }
        return undefined
      },
      stopPropagation: () => undefined,
    } as YandexMapEvent

    expect(getEventContainerPoint(event, container)).toEqual({ x: 100, y: 210 })
  })
})
