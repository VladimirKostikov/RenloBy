export type InfoBodyBlock =
  | { type: 'paragraph'; text: string }
  | { type: 'heading'; text: string }
  | { type: 'list'; items: string[] }

export function parseInfoBody(body: string): InfoBodyBlock[] {
  const blocks: InfoBodyBlock[] = []

  for (const section of body.split('\n\n')) {
    const trimmed = section.trim()
    if (!trimmed) {
      continue
    }

    if (trimmed.startsWith('## ')) {
      blocks.push({ type: 'heading', text: trimmed.slice(3).trim() })
      continue
    }

    const lines = trimmed.split('\n').map((line) => line.trim())
    const isList = lines.length > 0 && lines.every((line) => line.startsWith('- '))

    if (isList) {
      blocks.push({
        type: 'list',
        items: lines.map((line) => line.slice(2).trim()),
      })
      continue
    }

    blocks.push({ type: 'paragraph', text: trimmed })
  }

  return blocks
}
