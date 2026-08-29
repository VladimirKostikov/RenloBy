<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  deleteTelegramSubscriber,
  fetchTelegramStatus,
  syncTelegramUpdates,
  updateTelegramSubscriber,
  type TelegramStatusDto,
  type TelegramSubscriberDto,
} from '@/api/admin'
import AdminConfirmDialog from '@/modules/admin/components/AdminConfirmDialog.vue'
import AdminPageHeader from '@/modules/admin/components/AdminPageHeader.vue'

const { t } = useI18n()

const loading = ref(true)
const syncing = ref(false)
const status = ref<TelegramStatusDto | null>(null)
const showConfirm = ref(false)
const pendingDelete = ref<TelegramSubscriberDto | null>(null)
const error = ref('')
const syncMessage = ref('')

async function load() {
  loading.value = true
  error.value = ''
  try {
    status.value = await fetchTelegramStatus()
  } catch {
    error.value = t('admin.telegramLoadError')
  } finally {
    loading.value = false
  }
}

async function sync() {
  syncing.value = true
  syncMessage.value = ''
  error.value = ''
  try {
    const result = await syncTelegramUpdates()
    status.value = result
    syncMessage.value = t('admin.telegramSyncResult', {
      processed: result.processed,
      connected: result.connected,
    })
  } catch {
    error.value = t('admin.telegramSyncError')
  } finally {
    syncing.value = false
  }
}

async function toggleActive(item: TelegramSubscriberDto) {
  await updateTelegramSubscriber(item.id, { isActive: !item.isActive })
  await load()
}

function askRemove(item: TelegramSubscriberDto) {
  pendingDelete.value = item
  showConfirm.value = true
}

async function confirmRemove() {
  if (pendingDelete.value) {
    await deleteTelegramSubscriber(pendingDelete.value.id)
  }
  showConfirm.value = false
  pendingDelete.value = null
  await load()
}

onMounted(() => {
  void load()
})
</script>

<template>
  <div>
    <AdminPageHeader :title="t('admin.telegram')">
      <template #actions>
        <button type="button" class="admin-btn-secondary" :disabled="loading || syncing" @click="load">
          {{ t('admin.telegramRefresh') }}
        </button>
        <button type="button" class="admin-btn-primary" :disabled="loading || syncing" @click="sync">
          {{ syncing ? t('admin.telegramSyncing') : t('admin.telegramSync') }}
        </button>
      </template>
    </AdminPageHeader>

    <div v-if="loading" class="telegram-admin__state">{{ t('admin.loading') }}</div>
    <div v-else-if="error" class="telegram-admin__state telegram-admin__state--error">{{ error }}</div>
    <div v-else-if="status" class="telegram-admin">
      <section class="telegram-admin__card">
        <h2>{{ t('admin.telegramSubscribers') }}</h2>
        <p v-if="syncMessage" class="telegram-admin__sync-ok">{{ syncMessage }}</p>
        <div v-if="status.subscribers.length === 0" class="telegram-admin__empty">
          {{ t('admin.telegramEmptyHint') }}
        </div>
        <table v-else class="admin-table">
          <thead>
            <tr>
              <th>{{ t('admin.fields.id') }}</th>
              <th>{{ t('admin.fields.telegramChatId') }}</th>
              <th>{{ t('admin.fields.telegramUsername') }}</th>
              <th>{{ t('admin.fields.telegramName') }}</th>
              <th>{{ t('admin.fields.telegramActive') }}</th>
              <th>{{ t('admin.actions') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in status.subscribers" :key="item.id">
              <td>{{ item.id }}</td>
              <td>{{ item.chatId }}</td>
              <td>{{ item.username || '-' }}</td>
              <td>{{ item.firstName || '-' }}</td>
              <td>{{ item.isActive ? t('admin.yes') : t('admin.no') }}</td>
              <td class="admin-table__actions">
                <button type="button" class="admin-table__btn" @click="toggleActive(item)">
                  {{ item.isActive ? t('admin.telegramDisable') : t('admin.telegramEnable') }}
                </button>
                <button type="button" class="admin-table__btn admin-table__btn--danger" @click="askRemove(item)">
                  {{ t('admin.delete') }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </section>
    </div>

    <AdminConfirmDialog
      :open="showConfirm"
      :message="t('admin.confirmDelete')"
      @confirm="confirmRemove"
      @cancel="showConfirm = false"
    />
  </div>
</template>

<style scoped>
.telegram-admin {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.telegram-admin__card {
  padding: 16px 18px;
  border: 1px solid #e8e8e8;
  border-radius: 12px;
  background: #fff;
}

.telegram-admin__card h2 {
  margin: 0 0 10px;
  font-size: 16px;
  font-weight: 700;
}

.telegram-admin__sync-ok {
  margin: 0 0 12px;
  font-size: 13px;
  font-weight: 600;
  color: #0f7a3f;
}

.telegram-admin__state {
  padding: 24px;
  color: #666;
}

.telegram-admin__state--error {
  color: #c62828;
}

.telegram-admin__empty {
  padding: 12px 0;
  color: #888;
}

.admin-table {
  width: 100%;
  border-collapse: collapse;
}

.admin-table th,
.admin-table td {
  padding: 10px 8px;
  border-bottom: 1px solid #eee;
  text-align: left;
  font-size: 13px;
}

.admin-table__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.admin-table__btn {
  height: 32px;
  padding: 0 10px;
  border: 1px solid #ddd;
  border-radius: 8px;
  background: #fff;
  cursor: pointer;
  font-size: 12px;
}

.admin-table__btn--danger {
  color: #c62828;
  border-color: #ef9a9a;
}
</style>
