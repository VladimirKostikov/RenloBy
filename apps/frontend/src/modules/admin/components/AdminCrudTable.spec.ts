import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import { createI18n } from 'vue-i18n'
import AdminCrudTable from '@/modules/admin/components/AdminCrudTable.vue'

const i18n = createI18n({
  legacy: false,
  locale: 'ru',
  messages: {
    ru: {
      admin: {
        yes: 'Да',
        no: 'Нет',
        loading: 'Загрузка...',
        empty: 'Нет данных',
        actions: 'Действия',
        view: 'Просмотреть',
        edit: 'Редактировать',
        delete: 'Удалить',
        fields: {
          id: 'ID',
          name: 'Название',
          isTest: 'Тест',
        },
      },
    },
  },
})

describe('AdminCrudTable', () => {
  it('renders square action buttons for view edit and delete', async () => {
    const wrapper = mount(AdminCrudTable, {
      props: {
        items: [{ id: 7, name: 'Test row', isTest: true }],
        columns: [
          { key: 'id', label: 'ID' },
          { key: 'name', label: 'Название' },
        ],
      },
      global: {
        plugins: [i18n],
      },
    })

    const buttons = wrapper.findAll('.admin-action-btn')
    expect(buttons).toHaveLength(3)
    expect(buttons[0]?.attributes('title')).toBe('Просмотреть')
    expect(buttons[1]?.attributes('title')).toBe('Редактировать')
    expect(buttons[2]?.attributes('title')).toBe('Удалить')

    await buttons[0]!.trigger('click')
    await buttons[1]!.trigger('click')
    await buttons[2]!.trigger('click')

    expect(wrapper.emitted('edit')).toHaveLength(2)
    expect(wrapper.emitted('remove')).toHaveLength(1)
  })

  it('keeps only delete button when edit is hidden', () => {
    const wrapper = mount(AdminCrudTable, {
      props: {
        hideEdit: true,
        items: [{ id: 7, name: 'Test row', isTest: true }],
        columns: [
          { key: 'id', label: 'ID' },
          { key: 'name', label: 'Название' },
        ],
      },
      global: {
        plugins: [i18n],
      },
    })

    expect(wrapper.findAll('.admin-action-btn')).toHaveLength(1)
    expect(wrapper.find('.admin-action-btn').attributes('title')).toBe('Удалить')
  })
})
