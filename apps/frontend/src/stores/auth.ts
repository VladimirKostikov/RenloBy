import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { isAxiosError } from 'axios'
import * as authApi from '@/api/auth'
import { resetUserCollections, syncUserCollections } from '@/modules/collections/syncUserCollections'
import type { LoginRequest, RegisterRequest, UserDto } from '@/types'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<UserDto | null>(null)
  const loading = ref(false)
  const initialized = ref(false)

  const isAuthenticated = computed(() => user.value !== null)
  const isAdmin = computed(() => user.value?.roles.includes('ROLE_ADMIN') ?? false)

  async function initialize() {
    if (initialized.value) {
      return
    }
    loading.value = true
    try {
      user.value = await authApi.fetchMe()
      initialized.value = true
      await syncUserCollections(true)
    } catch (err) {
      user.value = null
      if (isAxiosError(err) && err.response?.status === 401) {
        initialized.value = true
        await syncUserCollections(true)
      }
    } finally {
      loading.value = false
    }
  }

  async function login(payload: LoginRequest) {
    loading.value = true
    try {
      user.value = await authApi.login(payload)
      initialized.value = true
      await syncUserCollections(true)
    } finally {
      loading.value = false
    }
  }

  async function register(payload: RegisterRequest) {
    loading.value = true
    try {
      user.value = await authApi.register(payload)
      initialized.value = true
      await syncUserCollections(true)
    } finally {
      loading.value = false
    }
  }

  async function logout() {
    await authApi.logout()
    user.value = null
    resetUserCollections()
    await syncUserCollections(true)
  }

  return {
    user,
    loading,
    initialized,
    isAuthenticated,
    isAdmin,
    initialize,
    login,
    register,
    logout,
  }
})
