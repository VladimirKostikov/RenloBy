import { isAxiosError } from 'axios'

const LEGACY_ERROR_CODES: Record<string, string> = {
  'Invalid credentials': 'auth.invalid_credentials',
  'Invalid user': 'auth.invalid_user',
  'Email already registered': 'auth.email_exists',
  'Validation failed': 'validation.failed',
  'Email is required': 'validation.email_required',
  'Invalid email': 'validation.email_invalid',
  'Password is required': 'validation.password_required',
  'Password must be at least 8 characters': 'validation.password_min',
  'Comparison limit reached': 'comparison.limit_reached',
}

const FIELD_LABEL_KEYS: Record<string, string> = {
  dealType: 'errors.validation.listing.deal_type',
  listingType: 'errors.validation.listing.type',
  price: 'errors.validation.listing.price',
  rooms: 'errors.validation.listing.rooms',
  area: 'errors.validation.listing.area',
  floor: 'errors.validation.listing.floor',
  totalFloors: 'errors.validation.listing.total_floors',
  address: 'errors.validation.listing.address',
  city: 'errors.validation.listing.city',
  district: 'errors.validation.listing.district',
  metro: 'errors.validation.listing.metro',
  metroLineColor: 'errors.validation.listing.metro_line_color',
  latitude: 'errors.validation.listing.coords',
  longitude: 'errors.validation.listing.coords',
  images: 'errors.validation.listing.images',
  lastName: 'errors.validation.profile_last_name',
  firstName: 'errors.validation.profile_first_name',
  patronymic: 'errors.validation.profile_patronymic',
  social: 'errors.validation.profile_social_required',
  file: 'errors.validation.photo_invalid',
}

export function normalizeErrorCode(code: string): string {
  return LEGACY_ERROR_CODES[code] ?? code
}

export function translateErrorCode(
  code: string,
  t: (key: string) => string,
  fallbackKey = 'errors.generic',
): string {
  const normalized = normalizeErrorCode(code)
  const key = `errors.${normalized}`
  const translated = t(key)
  if (translated !== key) {
    return translated
  }

  const fallback = t(fallbackKey)
  return fallback !== fallbackKey ? fallback : code
}

function translateFieldError(
  field: string,
  code: string,
  t: (key: string) => string,
): string {
  const normalized = normalizeErrorCode(code)
  const key = `errors.${normalized}`
  const fromCode = t(key)
  const codeTranslated = fromCode !== key

  if (codeTranslated && normalized !== 'validation.failed') {
    return fromCode
  }

  const labelKey = FIELD_LABEL_KEYS[field]
  if (labelKey) {
    const label = t(labelKey)
    if (label !== labelKey) {
      return label
    }
  }

  if (codeTranslated) {
    return fromCode
  }

  return t('errors.validation.failed')
}

export function translateFieldErrors(
  fields: Record<string, string>,
  t: (key: string) => string,
): Record<string, string> {
  const result: Record<string, string> = {}

  for (const [field, code] of Object.entries(fields)) {
    result[field] = translateFieldError(field, code, t)
  }

  return result
}

export interface ResolvedApiError {
  message: string
  fieldErrors: Record<string, string>
}

export function formatResolvedApiErrorMessage(resolved: ResolvedApiError): string {
  const details = [...new Set(Object.values(resolved.fieldErrors).filter(Boolean))]
  if (details.length === 0) {
    return resolved.message
  }

  const uniqueDetails = details.filter((item) => item !== resolved.message)
  if (uniqueDetails.length === 0) {
    return resolved.message
  }

  return `${resolved.message}. ${uniqueDetails.join('. ')}`
}

export function resolveApiError(
  err: unknown,
  t: (key: string) => string,
  fallbackKey: string,
): ResolvedApiError {
  if (!isAxiosError(err)) {
    return {
      message: t(fallbackKey),
      fieldErrors: {},
    }
  }

  if (!err.response) {
    return {
      message: t('errors.network'),
      fieldErrors: {},
    }
  }

  const data = err.response.data
  if (!data || typeof data !== 'object') {
    if (err.response.status >= 500) {
      return {
        message: t('errors.server'),
        fieldErrors: {},
      }
    }
    return {
      message: t(fallbackKey),
      fieldErrors: {},
    }
  }

  const fields = 'fields' in data && data.fields && typeof data.fields === 'object'
    ? data.fields as Record<string, string>
    : {}

  const fieldErrors = Object.keys(fields).length > 0
    ? translateFieldErrors(fields, t)
    : {}

  let message = t(fallbackKey)
  if ('error' in data && typeof data.error === 'string' && data.error.trim() !== '') {
    message = translateErrorCode(data.error, t, fallbackKey)
  } else if (err.response.status >= 500) {
    message = t('errors.server')
  }

  const resolved = { message, fieldErrors }
  return {
    message: formatResolvedApiErrorMessage(resolved),
    fieldErrors,
  }
}
