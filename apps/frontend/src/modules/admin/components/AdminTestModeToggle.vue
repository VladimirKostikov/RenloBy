<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import AdminModal from './AdminModal.vue'
import { useAdminTestModeStore } from '@/stores/adminTestMode'

const { t } = useI18n()
const testMode = useAdminTestModeStore()

const confirmMessage = () => {
  if (testMode.pendingEnabled) {
    return t('admin.testMode.confirmEnable')
  }
  return t('admin.testMode.confirmDisable')
}
</script>

<template>
  <div class="admin-test-toggle">
    <span class="admin-test-toggle__label">{{ t('admin.testMode.label') }}</span>
    <button
      type="button"
      class="admin-test-toggle__switch"
      :class="{ 'admin-test-toggle__switch--on': testMode.enabled }"
      :aria-pressed="testMode.enabled"
      :aria-label="t('admin.testMode.label')"
      @click="testMode.requestToggle(!testMode.enabled)"
    >
      <span class="admin-test-toggle__knob" />
    </button>
    <span class="admin-test-toggle__state">
      {{ testMode.enabled ? t('admin.testMode.on') : t('admin.testMode.off') }}
    </span>
  </div>

  <AdminModal
    :open="testMode.confirmOpen"
    :title="t('admin.testMode.confirmTitle')"
    @close="testMode.cancelToggle()"
  >
    <p class="admin-test-toggle__confirm-text">{{ confirmMessage() }}</p>
    <div class="admin-test-toggle__confirm-actions">
      <button type="button" class="admin-test-toggle__cancel" @click="testMode.cancelToggle()">
        {{ t('admin.cancel') }}
      </button>
      <button type="button" class="admin-test-toggle__confirm" @click="testMode.confirmToggle()">
        {{ t('admin.confirm') }}
      </button>
    </div>
  </AdminModal>
</template>

<style scoped>
.admin-test-toggle {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 12px;
  padding: 10px 12px;
  border: 1px solid var(--admin-border, #e8eaef);
  border-radius: 10px;
  background: #f8f9fb;
}

.admin-test-toggle__label {
  flex: 1;
  font-size: 13px;
  font-weight: 600;
  color: var(--admin-text, #1a1d26);
}

.admin-test-toggle__switch {
  position: relative;
  width: 44px;
  height: 26px;
  border: none;
  border-radius: 999px;
  background: #cfd4dc;
  cursor: pointer;
  transition: background-color 180ms ease;
}

.admin-test-toggle__switch--on {
  background: var(--admin-accent, #e14554);
}

.admin-test-toggle__knob {
  position: absolute;
  top: 3px;
  left: 3px;
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background: #fff;
  box-shadow: 0 1px 3px rgba(26, 29, 38, 0.2);
  transition: transform 180ms ease;
}

.admin-test-toggle__switch--on .admin-test-toggle__knob {
  transform: translateX(18px);
}

.admin-test-toggle__state {
  min-width: 28px;
  font-size: 12px;
  font-weight: 700;
  color: var(--admin-text-muted, #6b7280);
}

.admin-test-toggle__confirm-text {
  margin: 0 0 20px;
  font-size: 14px;
  line-height: 1.5;
  color: var(--admin-text-muted, #6b7280);
}

.admin-test-toggle__confirm-actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
}

.admin-test-toggle__cancel,
.admin-test-toggle__confirm {
  min-height: 40px;
  padding: 0 16px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 180ms ease, transform 180ms ease;
}

.admin-test-toggle__cancel {
  border: 1px solid var(--admin-border, #e8eaef);
  background: #fff;
  color: var(--admin-text, #1a1d26);
}

.admin-test-toggle__confirm {
  border: none;
  background: var(--admin-accent, #e14554);
  color: #fff;
}

.admin-test-toggle__cancel:hover {
  background: var(--admin-row-hover, #f8f9fb);
}

.admin-test-toggle__confirm:hover {
  background: var(--admin-accent-hover, #c93a48);
}
</style>
