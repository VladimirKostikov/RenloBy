import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'
import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import AdminActionIconButton from '@/modules/admin/components/AdminActionIconButton.vue'

describe('AdminActionIconButton', () => {
  it('renders solid color variants with icons', () => {
    const view = mount(AdminActionIconButton, {
      props: { title: 'Просмотреть', variant: 'view' },
    })
    const edit = mount(AdminActionIconButton, {
      props: { title: 'Редактировать', variant: 'edit' },
    })
    const remove = mount(AdminActionIconButton, {
      props: { title: 'Удалить', variant: 'delete' },
    })

    expect(view.classes()).toContain('admin-action-btn--view')
    expect(edit.classes()).toContain('admin-action-btn--edit')
    expect(remove.classes()).toContain('admin-action-btn--delete')
    expect(view.find('svg').exists()).toBe(true)
    expect(edit.find('svg').exists()).toBe(true)
    expect(remove.find('svg').exists()).toBe(true)
  })

  it('uses muted tinted colors instead of solid primary fills', () => {
    const source = readFileSync(resolve(__dirname, './AdminActionIconButton.vue'), 'utf8')

    expect(source).toContain('background: #edf2f7')
    expect(source).toContain('background: #e6f4f1')
    expect(source).toContain('background: #fdecee')
    expect(source).not.toContain('background: #2563eb')
    expect(source).not.toContain('background: #f59e0b')
  })
})
