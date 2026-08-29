<script setup lang="ts">
import { onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthModal } from '@/modules/auth/composables/useAuthModal'
import { useRoutePageSeo } from '@/modules/seo/useRoutePageSeo'

const route = useRoute()
const router = useRouter()
const authModal = useAuthModal()

useRoutePageSeo({ noindex: true })

onMounted(() => {
  const redirect = typeof route.query.redirect === 'string' ? route.query.redirect : null
  const mode = route.query.mode === 'register' ? 'register' : 'login'

  authModal.open({
    mode,
    redirect,
  })

  void router.replace(redirect ?? '/')
})
</script>

<template>
  <div />
</template>
