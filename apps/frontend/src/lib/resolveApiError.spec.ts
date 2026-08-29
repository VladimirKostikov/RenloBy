import { AxiosError } from 'axios'
import { describe, expect, it } from 'vitest'
import {
  formatResolvedApiErrorMessage,
  resolveApiError,
  translateErrorCode,
} from '@/lib/resolveApiError'
import { i18n } from '@/modules/locale'

const t = i18n.global.t

describe('resolveApiError', () => {
  it('translates auth error codes', () => {
    expect(translateErrorCode('auth.invalid_credentials', t, 'errors.generic')).toBe('Неверный email или пароль')
  })

  it('maps legacy english auth errors', () => {
    expect(translateErrorCode('Invalid credentials', t, 'errors.generic')).toBe('Неверный email или пароль')
  })

  it('returns fallback for unknown codes', () => {
    expect(translateErrorCode('unknown.code', t, 'errors.generic')).toBe('Что-то пошло не так')
  })

  it('resolves axios auth error payload', () => {
    const err = new AxiosError('Unauthorized')
    err.response = {
      data: { error: 'auth.invalid_credentials' },
      status: 401,
      statusText: 'Unauthorized',
      headers: {},
      config: {} as never,
    }

    const result = resolveApiError(err, t, 'errors.generic')
    expect(result.message).toBe('Неверный email или пароль')
  })

  it('includes field details in validation message', () => {
    const err = new AxiosError('Unprocessable Entity')
    err.response = {
      data: {
        error: 'validation.failed',
        fields: {
          metroLineColor: 'validation.failed',
          city: 'validation.listing.city',
        },
      },
      status: 422,
      statusText: 'Unprocessable Entity',
      headers: {},
      config: {} as never,
    }

    const result = resolveApiError(err, t, 'account.wizard.submitError')
    expect(result.message).toContain('Проверьте поля формы')
    expect(result.message).toContain('Выберите корректный цвет линии метро')
    expect(result.message).toContain('Укажите город')
    expect(result.fieldErrors.metroLineColor).toBe('Выберите корректный цвет линии метро')
  })

  it('returns network message when response is missing', () => {
    const err = new AxiosError('Network Error')
    const result = resolveApiError(err, t, 'account.wizard.submitError')
    expect(result.message).toBe('Нет связи с сервером. Проверьте интернет и попробуйте снова')
  })

  it('formats resolved message without duplicating details', () => {
    expect(formatResolvedApiErrorMessage({
      message: 'Проверьте поля формы',
      fieldErrors: { city: 'Проверьте поля формы' },
    })).toBe('Проверьте поля формы')
  })
})
