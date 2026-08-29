export function formatBelarusPhone(raw: string | null | undefined): string {
  if (!raw) {
    return ''
  }

  const trimmed = raw.trim()
  if (!trimmed) {
    return ''
  }

  let digits = trimmed.replace(/\D/g, '')
  if (!digits) {
    return trimmed
  }

  if (digits.startsWith('80') && digits.length >= 11) {
    digits = `375${digits.slice(2)}`
  } else if (digits.startsWith('0') && digits.length >= 10) {
    digits = `375${digits.slice(1)}`
  } else if (!digits.startsWith('375') && digits.length === 9) {
    digits = `375${digits}`
  }

  if (digits.startsWith('375') && digits.length >= 12) {
    const op = digits.slice(3, 5)
    const partA = digits.slice(5, 8)
    const partB = digits.slice(8, 10)
    const partC = digits.slice(10, 12)
    return `+375 ${op} ${partA}-${partB}-${partC}`
  }

  if (trimmed.startsWith('+')) {
    return trimmed
  }

  return `+${digits}`
}
