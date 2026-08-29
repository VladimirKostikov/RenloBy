import { parseInfoBody } from '@/modules/info/lib/parseInfoBody'

const BLOCK_TAGS = new Set(['P', 'H2', 'H3', 'UL', 'OL', 'LI', 'BR', 'STRONG', 'EM', 'B', 'I', 'A'])

export function infoBodyToHtml(body: string): string {
  if (!body.trim()) {
    return '<p><br></p>'
  }

  if (/^\s*</.test(body)) {
    return sanitizeHtml(body)
  }

  const blocks = parseInfoBody(body)
  if (blocks.length === 0) {
    return '<p><br></p>'
  }

  return blocks
    .map((block) => {
      if (block.type === 'heading') {
        return `<h2>${escapeHtml(block.text)}</h2>`
      }
      if (block.type === 'list') {
        const items = block.items.map((item) => `<li>${escapeHtml(item)}</li>`).join('')
        return `<ul>${items}</ul>`
      }
      return `<p>${escapeHtml(block.text).replace(/\n/g, '<br>')}</p>`
    })
    .join('')
}

export function htmlToInfoBody(html: string): string {
  const sanitized = sanitizeHtml(html)
  const root = document.createElement('div')
  root.innerHTML = sanitized

  const parts: string[] = []

  for (const node of Array.from(root.childNodes)) {
    if (node.nodeType === Node.TEXT_NODE) {
      const text = node.textContent?.trim()
      if (text) {
        parts.push(text)
      }
      continue
    }

    if (!(node instanceof HTMLElement)) {
      continue
    }

    const tag = node.tagName
    if (tag === 'H2' || tag === 'H3') {
      parts.push(`## ${node.textContent?.trim() ?? ''}`)
      continue
    }

    if (tag === 'UL' || tag === 'OL') {
      const items = Array.from(node.children)
        .filter((child) => child instanceof HTMLElement && child.tagName === 'LI')
        .map((li) => `- ${li.textContent?.trim() ?? ''}`)
        .filter((line) => line !== '- ')
      if (items.length > 0) {
        parts.push(items.join('\n'))
      }
      continue
    }

    if (tag === 'P' || tag === 'DIV') {
      const text = serializeInline(node).trim()
      if (text) {
        parts.push(text)
      }
    }
  }

  return parts.join('\n\n')
}

export function sanitizeHtml(html: string): string {
  const template = document.createElement('template')
  template.innerHTML = html

  const walk = (node: Node) => {
    const children = Array.from(node.childNodes)
    for (const child of children) {
      if (child.nodeType === Node.COMMENT_NODE) {
        child.parentNode?.removeChild(child)
        continue
      }

      if (child.nodeType === Node.ELEMENT_NODE && child instanceof HTMLElement) {
        if (!BLOCK_TAGS.has(child.tagName)) {
          const parent = child.parentNode
          while (child.firstChild) {
            parent?.insertBefore(child.firstChild, child)
          }
          parent?.removeChild(child)
          continue
        }

        for (const attr of Array.from(child.attributes)) {
          if (child.tagName === 'A' && attr.name === 'href') {
            const href = attr.value.trim()
            if (!/^(https?:|\/|#|mailto:)/i.test(href)) {
              child.removeAttribute(attr.name)
            }
            continue
          }
          child.removeAttribute(attr.name)
        }

        if (child.tagName === 'B') {
          renameTag(child, 'strong')
        } else if (child.tagName === 'I') {
          renameTag(child, 'em')
        }

        walk(child)
      }
    }
  }

  walk(template.content)
  return template.innerHTML
}

function renameTag(el: HTMLElement, tagName: string) {
  const replacement = document.createElement(tagName)
  while (el.firstChild) {
    replacement.appendChild(el.firstChild)
  }
  el.replaceWith(replacement)
}

function serializeInline(el: HTMLElement): string {
  let result = ''
  for (const node of Array.from(el.childNodes)) {
    if (node.nodeType === Node.TEXT_NODE) {
      result += node.textContent ?? ''
      continue
    }
    if (node instanceof HTMLElement) {
      if (node.tagName === 'BR') {
        result += '\n'
        continue
      }
      if (node.tagName === 'STRONG' || node.tagName === 'B') {
        result += node.textContent ?? ''
        continue
      }
      if (node.tagName === 'EM' || node.tagName === 'I') {
        result += node.textContent ?? ''
        continue
      }
      result += node.textContent ?? ''
    }
  }
  return result
}

function escapeHtml(value: string): string {
  return value
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
}
