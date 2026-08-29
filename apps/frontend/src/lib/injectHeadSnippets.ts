const MARKER_ATTR = 'data-renlo-head-snippet'

export function clearInjectedHeadSnippets(doc: Document = document): void {
  doc.head.querySelectorAll(`[${MARKER_ATTR}]`).forEach((node) => {
    node.remove()
  })
}

export function injectHeadSnippetCode(code: string, doc: Document = document): void {
  const trimmed = code.trim()
  if (trimmed === '') {
    return
  }

  const template = doc.createElement('template')
  template.innerHTML = trimmed

  const elements: Element[] = []
  template.content.childNodes.forEach((node) => {
    if (node.nodeType === Node.ELEMENT_NODE) {
      elements.push(node as Element)
    }
  })

  if (elements.length === 0) {
    const script = doc.createElement('script')
    script.text = trimmed
    script.setAttribute(MARKER_ATTR, '1')
    doc.head.appendChild(script)
    return
  }

  for (const el of elements) {
    if (el.tagName.toLowerCase() === 'script') {
      const script = doc.createElement('script')
      for (const attr of Array.from(el.attributes)) {
        script.setAttribute(attr.name, attr.value)
      }
      script.text = el.textContent ?? ''
      script.setAttribute(MARKER_ATTR, '1')
      doc.head.appendChild(script)
      continue
    }

    const clone = el.cloneNode(true) as Element
    clone.setAttribute(MARKER_ATTR, '1')
    doc.head.appendChild(clone)
  }
}

export function injectHeadSnippetCodes(codes: string[], doc: Document = document): void {
  clearInjectedHeadSnippets(doc)
  for (const code of codes) {
    injectHeadSnippetCode(code, doc)
  }
}
