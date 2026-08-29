import { flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createMemoryHistory, createRouter } from 'vue-router'
import AccountProfileView from '@/views/account/AccountProfileView.vue'
import SocialBrandIcon from '@/components/SocialBrandIcon.vue'
import { i18n } from '@/modules/locale'
import { useAuthStore } from '@/stores/auth'

vi.mock('@/api/account', () => ({
  updateProfile: vi.fn(async (payload) => ({
    id: 1,
    email: 'user@test.local',
    name: [payload.lastName, payload.firstName, payload.patronymic].filter(Boolean).join(' '),
    roles: ['ROLE_USER'],
    lastName: payload.lastName || null,
    firstName: payload.firstName || null,
    patronymic: payload.patronymic || null,
    phone: payload.phone ?? null,
    photo: payload.photo ?? null,
    instagram: payload.instagram ?? null,
    telegram: payload.telegram ?? null,
    whatsapp: payload.whatsapp ?? null,
    viber: payload.viber ?? null,
  })),
  uploadProfilePhoto: vi.fn(async () => ({
    id: 1,
    email: 'user@test.local',
    name: '',
    roles: ['ROLE_USER'],
    lastName: null,
    firstName: null,
    patronymic: null,
    phone: '+375290000000',
    photo: '/uploads/avatars/test.png',
    instagram: '@user',
    telegram: '',
    whatsapp: '',
    viber: '',
  })),
}))

describe('AccountProfileView', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    setActivePinia(createPinia())
    const auth = useAuthStore()
    auth.user = {
      id: 1,
      email: 'user@test.local',
      name: '',
      roles: ['ROLE_USER'],
      lastName: null,
      firstName: null,
      patronymic: null,
      phone: '+375290000000',
      photo: null,
      instagram: '@user',
      telegram: '',
      whatsapp: '',
      viber: '',
    }
  })

  async function mountView() {
    const router = createRouter({
      history: createMemoryHistory(),
      routes: [
        { path: '/account/user/profile', component: AccountProfileView },
        { path: '/', component: { template: '<div />' } },
      ],
    })
    await router.push('/account/user/profile')
    await router.isReady()

    return mount(AccountProfileView, {
      attachTo: document.body,
      global: {
        plugins: [i18n, router],
      },
    })
  }

  it('renders empty FIO fields in three columns and saves them', async () => {
    const { updateProfile } = await import('@/api/account')
    const wrapper = await mountView()
    await flushPromises()

    expect(wrapper.text()).toContain('Профиль')
    expect(wrapper.text()).toContain('Фамилия')
    expect(wrapper.text()).toContain('Имя')
    expect(wrapper.text()).toContain('Отчество')
    expect(wrapper.find('.account-profile__fio').exists()).toBe(true)
    expect(wrapper.findAll('.account-profile__social-icon')).toHaveLength(4)
    expect(wrapper.findAllComponents(SocialBrandIcon)).toHaveLength(4)

    const fioInputs = wrapper.findAll('.account-profile__fio input')
    expect(fioInputs).toHaveLength(3)
    expect((fioInputs[0].element as HTMLInputElement).value).toBe('')
    expect((fioInputs[1].element as HTMLInputElement).value).toBe('')
    expect((fioInputs[2].element as HTMLInputElement).value).toBe('')

    await fioInputs[0].setValue('Иванов')
    await fioInputs[1].setValue('Иван')
    await fioInputs[2].setValue('Иванович')

    const phoneInput = wrapper.findAll('input').find((input) => input.attributes('type') === 'tel')
    await phoneInput!.setValue('+375291112233')
    await wrapper.find('form').trigger('submit.prevent')
    await flushPromises()

    expect(updateProfile).toHaveBeenCalledWith(
      expect.objectContaining({
        lastName: 'Иванов',
        firstName: 'Иван',
        patronymic: 'Иванович',
        phone: '+375291112233',
        instagram: '@user',
      }),
    )
    expect(wrapper.text()).toContain('Изменения сохранены')
  })

  it('uploads photo via dropzone drop and shows click hint', async () => {
    const { uploadProfilePhoto } = await import('@/api/account')
    const wrapper = await mountView()
    await flushPromises()

    expect(wrapper.text()).toContain('Перетащите файл сюда или нажмите, чтобы выбрать')
    const dropzone = wrapper.get('.account-profile__dropzone')
    expect(dropzone.classes()).not.toContain('account-profile__dropzone--busy')

    const file = new File([new Uint8Array([1, 2, 3])], 'avatar.png', { type: 'image/png' })
    await dropzone.trigger('drop', {
      dataTransfer: { files: [file] },
    })
    await flushPromises()

    expect(uploadProfilePhoto).toHaveBeenCalledWith(file)
    expect(wrapper.get('.account-profile__avatar-img').attributes('src')).toBe('/uploads/avatars/test.png')
  })

  it('rejects non-image drops without calling upload', async () => {
    const { uploadProfilePhoto } = await import('@/api/account')
    const wrapper = await mountView()
    await flushPromises()

    const dropzone = wrapper.get('.account-profile__dropzone')
    await dropzone.trigger('drop', {
      dataTransfer: {
        files: [new File(['x'], 'note.txt', { type: 'text/plain' })],
      },
    })
    await flushPromises()

    expect(uploadProfilePhoto).not.toHaveBeenCalled()
    expect(wrapper.text()).toContain('Выберите изображение JPG, PNG, WEBP или GIF')
  })

  it('opens seller card preview modal from form data', async () => {
    const wrapper = await mountView()
    await flushPromises()

    const fioInputs = wrapper.findAll('.account-profile__fio input')
    await fioInputs[0].setValue('Иванов')
    await fioInputs[1].setValue('Иван')
    await fioInputs[2].setValue('Иванович')

    expect(wrapper.text()).toContain('Посмотреть как выглядит мой профиль')
    await wrapper.get('[data-testid="profile-preview"]').trigger('click')
    await flushPromises()

    const modal = document.querySelector('[data-testid="profile-preview-modal"]')
    expect(modal).not.toBeNull()
    expect(modal!.textContent).toContain('Как выглядит профиль')
    expect(modal!.textContent).toContain('Иванов Иван Иванович')
    expect(modal!.querySelector('.listing-seller')).not.toBeNull()
    expect(modal!.querySelector('.listing-seller--static')).not.toBeNull()
    expect(modal!.querySelector('a[href="https://instagram.com/user"]')).not.toBeNull()
    wrapper.unmount()
  })
})
