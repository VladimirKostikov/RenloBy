<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import AdminNavIcon from '@/modules/admin/components/AdminNavIcon.vue'
import AdminPageHeader from '@/modules/admin/components/AdminPageHeader.vue'
import { adminNavItems } from '@/modules/admin/nav'

const { t } = useI18n()

const sections = adminNavItems.filter((item) => item.to !== '/admin')
</script>

<template>
  <div class="dashboard">
    <AdminPageHeader :title="t('admin.dashboard')" />

    <div class="dashboard__grid">
      <RouterLink
        v-for="section in sections"
        :key="section.to"
        :to="section.to"
        class="dashboard__card"
      >
        <span class="dashboard__icon">
          <AdminNavIcon :name="section.icon" />
        </span>
        <span class="dashboard__label">{{ t(section.labelKey) }}</span>
      </RouterLink>
    </div>
  </div>
</template>

<style scoped>
.dashboard__grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 16px;
}

.dashboard__card {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 18px 20px;
  border: 1px solid var(--admin-border, #e8eaef);
  border-radius: var(--admin-radius, 10px);
  background: #fff;
  color: var(--admin-text, #1a1d26);
  font-weight: 600;
  text-decoration: none;
  box-shadow: 0 1px 2px rgba(26, 29, 38, 0.04);
  transition:
    transform 200ms ease,
    border-color 200ms ease,
    box-shadow 200ms ease,
    background-color 200ms ease;
}

.dashboard__card:hover {
  transform: translateY(-2px);
  border-color: #f0b4ba;
  background: #fff;
  box-shadow: 0 8px 24px rgba(26, 29, 38, 0.08);
}

.dashboard__card:active {
  transform: translateY(0);
}

.dashboard__icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: var(--admin-accent-muted, rgba(225, 69, 84, 0.08));
  color: var(--admin-accent, #e14554);
  transition: background-color 200ms ease, transform 200ms ease;
}

.dashboard__card:hover .dashboard__icon {
  background: var(--admin-accent, #e14554);
  color: #fff;
  transform: scale(1.05);
}

.dashboard__label {
  font-size: 15px;
  line-height: 1.3;
}

@media (prefers-reduced-motion: reduce) {
  .dashboard__card,
  .dashboard__icon {
    transition-duration: 0.01ms;
  }

  .dashboard__card:hover {
    transform: none;
  }

  .dashboard__card:hover .dashboard__icon {
    transform: none;
  }
}
</style>
