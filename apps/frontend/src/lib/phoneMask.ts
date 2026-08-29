export type PhoneCountry = 'by' | 'ru'

const BY_PREFIX = '375'
const RU_PREFIX = '7'

export function phoneLocalDigitLimit(country: PhoneCountry): number {
  return country === 'by' ? 9 : 10
}

export function extractPhoneLocalDigits(country: PhoneCountry, raw: string): string {
  let digits = raw.replace(/\D/g, '')
  if (country === 'by') {
    if (digits.startsWith('80') && digits.length > 2) {
      digits = digits.slice(2)
    } else if (digits.startsWith(BY_PREFIX)) {
      digits = digits.slice(BY_PREFIX.length)
    } else if (digits.startsWith('0') && digits.length > 1) {
      digits = digits.slice(1)
    }
  } else if (digits.startsWith('8') && digits.length > 1) {
    digits = digits.slice(1)
  } else if (digits.startsWith(RU_PREFIX) && digits.length > 1) {
    digits = digits.slice(1)
  }

  return digits.slice(0, phoneLocalDigitLimit(country))
}

export function formatPhoneMask(country: PhoneCountry, raw: string): string {
  const local = extractPhoneLocalDigits(country, raw)
  if (!local) {
    return ''
  }
  if (country === 'by') {
    const op = local.slice(0, 2)
    const a = local.slice(2, 5)
    const b = local.slice(5, 7)
    const c = local.slice(7, 9)
    let result = '+375'
    if (op) {
      result += ` ${op}`
    }
    if (a) {
      result += ` ${a}`
    }
    if (b) {
      result += `-${b}`
    }
    if (c) {
      result += `-${c}`
    }
    return result
  }

  const a = local.slice(0, 3)
  const b = local.slice(3, 6)
  const c = local.slice(6, 8)
  const d = local.slice(8, 10)
  let result = '+7'
  if (a) {
    result += ` ${a}`
  }
  if (b) {
    result += ` ${b}`
  }
  if (c) {
    result += `-${c}`
  }
  if (d) {
    result += `-${d}`
  }
  return result
}

export function phoneE164(country: PhoneCountry, raw: string): string {
  const local = extractPhoneLocalDigits(country, raw)
  if (country === 'by') {
    return `+${BY_PREFIX}${local}`
  }
  return `+${RU_PREFIX}${local}`
}

export function isPhoneComplete(country: PhoneCountry, raw: string): boolean {
  return extractPhoneLocalDigits(country, raw).length === phoneLocalDigitLimit(country)
}

export function phonePlaceholder(country: PhoneCountry): string {
  return country === 'by' ? '+375 29 000-00-00' : '+7 900 000-00-00'
}
