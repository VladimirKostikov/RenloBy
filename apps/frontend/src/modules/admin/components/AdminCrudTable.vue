<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import AdminActionIconButton from '@/modules/admin/components/AdminActionIconButton.vue'

interface TableRow {
  id: number
  [key: string]: unknown
}

const props = defineProps<{
  items: TableRow[]
  columns: { key: string; label: string }[]
  loading?: boolean
  hideEdit?: boolean
}>()

const emit = defineEmits<{
  view: [item: TableRow]
  edit: [item: TableRow]
  remove: [item: TableRow]
}>()

const { t } = useI18n()

function formatValue(value: unknown): string {
  if (value === null || value === undefined || value === '') {
    return '-'
  }
  if (typeof value === 'boolean') {
    return value ? t('admin.yes') : t('admin.no')
  }
  if (typeof value === 'object') {
    return JSON.stringify(value)
  }
  return String(value)
}

const resolvedColumns = computed(() => {
  if (props.columns.some((col) => col.key === 'isTest')) {
    return props.columns
  }
  return [...props.columns, { key: 'isTest', label: t('admin.fields.isTest') }]
})
</script>

<template>
  <div class="admin-table-wrap">
    <div v-if="loading" class="admin-table__state">{{ t('admin.loading') }}</div>
    <table v-else class="admin-table">
      <thead>
        <tr>
          <th v-for="col in resolvedColumns" :key="col.key">{{ col.label }}</th>
          <th class="admin-table__actions-col">{{ t('admin.actions') }}</th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="items.length === 0">
          <td :colspan="resolvedColumns.length + 1" class="admin-table__empty">{{ t('admin.empty') }}</td>
        </tr>
        <tr v-for="item in items" :key="item.id" class="admin-table__row">
          <td v-for="col in resolvedColumns" :key="col.key">
            <slot :name="`cell-${col.key}`" :item="item" :value="item[col.key]">
              {{ formatValue(item[col.key]) }}
            </slot>
          </td>
          <td class="admin-table__actions">
            <AdminActionIconButton
              v-if="!hideEdit"
              variant="view"
              :title="t('admin.view')"
              @click="emit('edit', item)"
            />
            <AdminActionIconButton
              v-if="!hideEdit"
              variant="edit"
              class="admin-table__btn"
              :title="t('admin.edit')"
              @click="emit('edit', item)"
            />
            <AdminActionIconButton
              variant="delete"
              :title="t('admin.delete')"
              @click="emit('remove', item)"
            />
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<style scoped>
.admin-table-wrap {
  overflow-x: auto;
  border: 1px solid var(--admin-border, #e8eaef);
  border-radius: var(--admin-radius, 10px);
  background: #fff;
  box-shadow: 0 1px 2px rgba(26, 29, 38, 0.04);
}

.admin-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 14px;
}

.admin-table th,
.admin-table td {
  padding: 12px 14px;
  text-align: left;
  border-bottom: 1px solid var(--admin-border, #e8eaef);
  vertical-align: middle;
}

.admin-table th {
  background: #f8f9fb;
  font-weight: 700;
  font-size: 12px;
  letter-spacing: 0.02em;
  text-transform: uppercase;
  color: var(--admin-text-muted, #6b7280);
}

.admin-table__row {
  transition: background-color 160ms ease;
}

.admin-table__row:hover {
  background: var(--admin-row-hover, #f8f9fb);
}

.admin-table__row:last-child td {
  border-bottom: none;
}

.admin-table__actions-col {
  width: 1%;
  white-space: nowrap;
}

.admin-table__actions {
  display: flex;
  align-items: center;
  gap: 8px;
  white-space: nowrap;
}

.admin-table__empty,
.admin-table__state {
  padding: 40px 24px;
  text-align: center;
  color: var(--admin-text-muted, #6b7280);
}
</style>
