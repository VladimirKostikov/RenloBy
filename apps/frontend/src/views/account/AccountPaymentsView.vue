<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { fetchMyPayments, type PaymentTransactionDto } from '@/api/payments'
import { useRoutePageSeo } from '@/modules/seo/useRoutePageSeo'

const { t } = useI18n()
useRoutePageSeo({ noindex: true })

const items = ref<PaymentTransactionDto[]>([])
const loading = ref(false)
const error = ref('')

async function load() {
  loading.value = true
  error.value = ''
  try {
    items.value = await fetchMyPayments()
  } catch {
    error.value = t('account.payments.loadError')
    items.value = []
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  void load()
})
</script>

<template>
  <div class="payments">
    <h1 class="payments__title">{{ t('account.payments.title') }}</h1>
    <p class="payments__hint">{{ t('account.payments.hint') }}</p>

    <p v-if="error" class="payments__error">{{ error }}</p>
    <p v-if="loading" class="payments__state">{{ t('account.payments.loading') }}</p>

    <div v-else class="payments__list">
      <div v-if="items.length === 0" class="payments__state">{{ t('account.payments.empty') }}</div>
      <article v-for="item in items" :key="item.id" class="payments__card">
        <div class="payments__row">
          <strong>{{ item.amount }} {{ item.currency }}</strong>
          <span>{{ item.status }}</span>
        </div>
        <p class="payments__meta">{{ item.description || '-' }}</p>
        <p class="payments__meta">{{ item.createdAt }}</p>
      </article>
    </div>
  </div>
</template>

<style scoped>
.payments__title {
  margin: 0 0 8px;
  font-size: 24px;
}

.payments__hint {
  margin: 0 0 20px;
  color: var(--color-text-muted);
  font-size: 14px;
}

.payments__error {
  color: var(--accent);
}

.payments__state {
  color: var(--color-text-muted);
}

.payments__list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.payments__card {
  padding: 14px 16px;
  border: 1px solid var(--color-border);
  border-radius: 10px;
  background: var(--color-bg-elevated);
}

.payments__row {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 6px;
}

.payments__meta {
  margin: 0;
  font-size: 13px;
  color: var(--color-text-muted);
}

@media (max-width: 767px) {
  .payments__title {
    font-size: 22px;
  }

  .payments__row {
    flex-direction: column;
    align-items: flex-start;
    gap: 4px;
  }
}
</style>
