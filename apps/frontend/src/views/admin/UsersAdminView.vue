<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { adminUsers, exportUserEmailsCsv, uploadAdminUserPhoto } from '@/api/admin'
import AdminConfirmDialog from '@/modules/admin/components/AdminConfirmDialog.vue'
import AdminCrudForm from '@/modules/admin/components/AdminCrudForm.vue'
import AdminCrudTable from '@/modules/admin/components/AdminCrudTable.vue'
import AdminModal from '@/modules/admin/components/AdminModal.vue'
import AdminPageHeader from '@/modules/admin/components/AdminPageHeader.vue'
import { useAdminCrud } from '@/modules/admin/composables/useAdminCrud'
import { toTableRows, type AdminTableRow } from '@/modules/admin/types'
import type { UserDto } from '@/types'

const { t } = useI18n()
const { items, loading, create, update, remove, load: reload } = useAdminCrud<UserDto>(adminUsers)

const showForm = ref(false)
const showConfirm = ref(false)
const editing = ref<UserDto | null>(null)
const pendingDelete = ref<AdminTableRow | null>(null)
const formModel = ref<Record<string, unknown>>({})
const exporting = ref(false)
const uploadingPhoto = ref(false)
const photoInput = ref<HTMLInputElement | null>(null)

const columns = [
  { key: 'id', label: t('admin.fields.id') },
  { key: 'email', label: t('admin.fields.email') },
  { key: 'name', label: t('admin.fields.fullName') },
  { key: 'phone', label: t('admin.fields.phone') },
  { key: 'roles', label: t('admin.fields.roles') },
]

const fields = [
  { key: 'email', label: t('admin.fields.email'), type: 'email' as const },
  { key: 'password', label: t('admin.fields.password'), type: 'password' as const },
  { key: 'lastName', label: t('admin.fields.lastName') },
  { key: 'firstName', label: t('admin.fields.firstName') },
  { key: 'patronymic', label: t('admin.fields.patronymic') },
  { key: 'phone', label: t('admin.fields.phone') },
  { key: 'photo', label: t('admin.fields.photo') },
  { key: 'instagram', label: t('admin.fields.instagram') },
  { key: 'telegram', label: t('admin.fields.telegram') },
  { key: 'whatsapp', label: t('admin.fields.whatsapp') },
  { key: 'viber', label: t('admin.fields.viber') },
  { key: 'roles', label: t('admin.fields.roles'), type: 'textarea' as const },
]

function openCreate() {
  editing.value = null
  formModel.value = {
    email: '',
    password: '',
    lastName: '',
    firstName: '',
    patronymic: '',
    phone: '',
    photo: '',
    instagram: '',
    telegram: '',
    whatsapp: '',
    viber: '',
    roles: '[]',
  }
  showForm.value = true
}

function openEdit(item: AdminTableRow) {
  const user = item as unknown as UserDto
  editing.value = user
  formModel.value = {
    email: user.email,
    lastName: user.lastName ?? '',
    firstName: user.firstName ?? '',
    patronymic: user.patronymic ?? '',
    phone: user.phone ?? '',
    photo: user.photo ?? '',
    instagram: user.instagram ?? '',
    telegram: user.telegram ?? '',
    whatsapp: user.whatsapp ?? '',
    viber: user.viber ?? '',
    roles: JSON.stringify(user.roles),
    password: '',
  }
  showForm.value = true
}

async function save(payload: Record<string, unknown>) {
  const data: Record<string, unknown> = {
    email: payload.email,
    lastName: payload.lastName,
    firstName: payload.firstName,
    patronymic: payload.patronymic,
    phone: payload.phone,
    photo: payload.photo,
    instagram: payload.instagram,
    telegram: payload.telegram,
    whatsapp: payload.whatsapp,
    viber: payload.viber,
    roles: JSON.parse(String(payload.roles || '[]')),
    isTest: payload.isTest,
  }
  if (payload.password) {
    data.password = payload.password
  }
  if (editing.value) {
    await update(editing.value.id, data)
  } else {
    await create(data)
  }
  showForm.value = false
}

function askRemove(item: AdminTableRow) {
  pendingDelete.value = item
  showConfirm.value = true
}

async function confirmRemove() {
  if (pendingDelete.value) {
    await remove(pendingDelete.value.id)
  }
  showConfirm.value = false
  pendingDelete.value = null
}

async function exportEmails() {
  exporting.value = true
  try {
    const blob = await exportUserEmailsCsv()
    const url = URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = 'renlo-users-emails.csv'
    link.click()
    URL.revokeObjectURL(url)
  } finally {
    exporting.value = false
  }
}

async function onPhotoSelected(event: Event) {
  if (!editing.value) {
    return
  }
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  if (!file) {
    return
  }

  uploadingPhoto.value = true
  try {
    const updated = await uploadAdminUserPhoto(editing.value.id, file)
    editing.value = updated
    formModel.value = {
      ...formModel.value,
      photo: updated.photo ?? '',
    }
    await reload()
  } finally {
    uploadingPhoto.value = false
    input.value = ''
  }
}
</script>

<template>
  <div>
    <AdminPageHeader :title="t('admin.users')">
      <template #actions>
        <button type="button" class="admin-btn-primary" :disabled="exporting" @click="exportEmails">
          {{ exporting ? t('admin.exportingEmails') : t('admin.exportEmails') }}
        </button>
        <button type="button" class="admin-btn-primary" @click="openCreate">{{ t('admin.create') }}</button>
      </template>
    </AdminPageHeader>

    <AdminCrudTable
      :items="toTableRows(items)"
      :columns="columns"
      :loading="loading"
      @edit="openEdit"
      @remove="askRemove"
    />

    <AdminModal
      :open="showForm"
      :title="editing ? t('admin.edit') : t('admin.create')"
      @close="showForm = false"
    >
      <div v-if="editing" class="users-admin__photo">
        <img v-if="formModel.photo" :src="String(formModel.photo)" alt="" class="users-admin__photo-preview" />
        <button
          type="button"
          class="admin-btn-primary"
          :disabled="uploadingPhoto"
          @click="photoInput?.click()"
        >
          {{ uploadingPhoto ? t('admin.uploading') : t('admin.uploadPhoto') }}
        </button>
        <input
          ref="photoInput"
          type="file"
          class="users-admin__file"
          accept="image/jpeg,image/png,image/webp,image/gif"
          @change="onPhotoSelected"
        />
      </div>
      <AdminCrudForm
        :fields="fields"
        :model-value="formModel"
        @save="save"
        @cancel="showForm = false"
      />
    </AdminModal>

    <AdminConfirmDialog
      :open="showConfirm"
      :message="t('admin.confirmDelete')"
      @confirm="confirmRemove"
      @cancel="showConfirm = false"
    />
  </div>
</template>

<style scoped>
.users-admin__photo {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
}

.users-admin__photo-preview {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  object-fit: cover;
  border: 1px solid var(--color-border, #ddd);
}

.users-admin__file {
  display: none;
}
</style>
