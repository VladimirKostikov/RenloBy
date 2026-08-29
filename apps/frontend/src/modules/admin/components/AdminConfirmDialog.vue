<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import AdminModal from './AdminModal.vue'

defineProps<{
  open: boolean
  message: string
}>()

const emit = defineEmits<{
  confirm: []
  cancel: []
}>()

const { t } = useI18n()
</script>

<template>
  <AdminModal :open="open" :title="t('admin.confirmTitle')" @close="emit('cancel')">
    <p class="admin-confirm__text">{{ message }}</p>
    <div class="admin-confirm__actions">
      <button type="button" class="admin-confirm__cancel" @click="emit('cancel')">
        {{ t('admin.cancel') }}
      </button>
      <button type="button" class="admin-confirm__delete" @click="emit('confirm')">
        {{ t('admin.delete') }}
      </button>
    </div>
  </AdminModal>
</template>

<style scoped>
.admin-confirm__text {
  margin: 0 0 20px;
  font-size: 14px;
  line-height: 1.5;
  color: var(--admin-text-muted, #6b7280);
}

.admin-confirm__actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
}

.admin-confirm__cancel,
.admin-confirm__delete {
  min-height: 40px;
  padding: 0 16px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 180ms ease, border-color 180ms ease, transform 180ms ease;
}

.admin-confirm__cancel {
  border: 1px solid var(--admin-border, #e8eaef);
  background: #fff;
  color: var(--admin-text, #1a1d26);
}

.admin-confirm__cancel:hover {
  background: var(--admin-row-hover, #f8f9fb);
}

.admin-confirm__delete {
  border: none;
  background: var(--admin-accent, #e14554);
  color: #fff;
}

.admin-confirm__delete:hover {
  background: var(--admin-accent-hover, #c93a48);
}

.admin-confirm__cancel:active,
.admin-confirm__delete:active {
  transform: scale(0.98);
}
</style>
