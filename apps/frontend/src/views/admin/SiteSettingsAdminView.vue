<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { adminSiteSettings, type SiteSettingsDto } from '@/api/admin'
import AdminCrudForm from '@/modules/admin/components/AdminCrudForm.vue'
import AdminPageHeader from '@/modules/admin/components/AdminPageHeader.vue'
import { useAdminTestModeStore } from '@/stores/adminTestMode'

const { t } = useI18n()
const testMode = useAdminTestModeStore()

const loading = ref(true)
const saving = ref(false)
const error = ref('')
const saved = ref(false)
const current = ref<SiteSettingsDto | null>(null)
const formModel = ref<Record<string, unknown>>({})

const aboutFields = [
  { key: 'aboutText', label: t('admin.fields.aboutText'), type: 'textarea' as const },
  { key: 'ownerName', label: t('admin.fields.ownerName') },
  { key: 'address', label: t('admin.fields.address') },
  { key: 'email', label: t('admin.fields.email'), type: 'email' as const },
  { key: 'supportHours', label: t('admin.fields.supportHours') },
  { key: 'offersText', label: t('admin.fields.offersText'), type: 'textarea' as const },
  { key: 'offersEmail', label: t('admin.fields.offersEmail'), type: 'email' as const },
]

const mediaFields = [
  { key: 'phoneDisplay', label: t('admin.fields.phoneDisplay') },
  { key: 'phoneRaw', label: t('admin.fields.phoneRaw') },
  { key: 'telegramUrl', label: t('admin.fields.telegramUrl') },
  { key: 'whatsappUrl', label: t('admin.fields.whatsappUrl') },
  { key: 'vkUrl', label: t('admin.fields.vkUrl') },
  { key: 'isTest', label: t('admin.fields.isTest'), type: 'checkbox' as const },
]

function applyForm(item: SiteSettingsDto) {
  formModel.value = {
    aboutText: item.aboutText,
    ownerName: item.ownerName ?? '',
    address: item.address ?? '',
    phoneDisplay: item.phoneDisplay,
    phoneRaw: item.phoneRaw,
    email: item.email,
    supportHours: item.supportHours,
    offersText: item.offersText ?? '',
    offersEmail: item.offersEmail ?? '',
    telegramUrl: item.telegramUrl ?? '',
    whatsappUrl: item.whatsappUrl ?? '',
    vkUrl: item.vkUrl ?? '',
    isTest: item.isTest,
  }
}

async function load() {
  loading.value = true
  error.value = ''
  saved.value = false
  try {
    const list = await adminSiteSettings.list({ isTest: testMode.isTest })
    const items = Array.isArray(list) ? list : []
    const item = items[0] ?? null
    current.value = item
    if (item) {
      applyForm(item)
    }
  } catch {
    error.value = t('admin.siteSettingsLoadError')
    current.value = null
  } finally {
    loading.value = false
  }
}

async function save(payload: Record<string, unknown>) {
  if (!current.value) {
    return
  }
  saving.value = true
  error.value = ''
  saved.value = false
  try {
    const updated = await adminSiteSettings.update(current.value.id, {
      aboutText: String(payload.aboutText ?? formModel.value.aboutText ?? ''),
      ownerName: String(payload.ownerName ?? formModel.value.ownerName ?? ''),
      address: String(payload.address ?? formModel.value.address ?? ''),
      phoneDisplay: String(payload.phoneDisplay ?? formModel.value.phoneDisplay ?? ''),
      phoneRaw: String(payload.phoneRaw ?? formModel.value.phoneRaw ?? ''),
      email: String(payload.email ?? formModel.value.email ?? ''),
      supportHours: String(payload.supportHours ?? formModel.value.supportHours ?? ''),
      offersText: String(payload.offersText ?? formModel.value.offersText ?? ''),
      offersEmail: String(payload.offersEmail ?? formModel.value.offersEmail ?? ''),
      telegramUrl: String(payload.telegramUrl ?? formModel.value.telegramUrl ?? ''),
      whatsappUrl: String(payload.whatsappUrl ?? formModel.value.whatsappUrl ?? ''),
      vkUrl: String(payload.vkUrl ?? formModel.value.vkUrl ?? ''),
      isTest: Boolean(payload.isTest ?? formModel.value.isTest),
    })
    current.value = updated
    applyForm(updated)
    saved.value = true
  } catch {
    error.value = t('admin.siteSettingsSaveError')
  } finally {
    saving.value = false
  }
}

function saveAbout(payload: Record<string, unknown>) {
  formModel.value = { ...formModel.value, ...payload }
  void save({ ...formModel.value, ...payload })
}

function saveMedia(payload: Record<string, unknown>) {
  formModel.value = { ...formModel.value, ...payload }
  void save({ ...formModel.value, ...payload })
}

onMounted(() => {
  void load()
})

watch(
  () => testMode.isTest,
  () => {
    void load()
  },
)
</script>

<template>
  <div>
    <AdminPageHeader :title="t('admin.siteSettings')" />

    <p v-if="loading" class="site-settings-admin__state">{{ t('admin.loading') }}</p>
    <p v-else-if="error" class="site-settings-admin__state site-settings-admin__state--error">{{ error }}</p>
    <p v-else-if="!current" class="site-settings-admin__state">{{ t('admin.empty') }}</p>
    <div v-else class="site-settings-admin">
      <p v-if="saved" class="site-settings-admin__saved">{{ t('admin.siteSettingsSaved') }}</p>

      <section class="site-settings-admin__section">
        <h2 class="site-settings-admin__section-title">{{ t('admin.siteSettingsAbout') }}</h2>
        <AdminCrudForm
          :fields="aboutFields"
          :model-value="formModel"
          omit-test-field
          @save="saveAbout"
          @cancel="load"
        />
      </section>

      <section class="site-settings-admin__section">
        <h2 class="site-settings-admin__section-title">{{ t('admin.siteSettingsMedia') }}</h2>
        <AdminCrudForm
          :fields="mediaFields"
          :model-value="formModel"
          @save="saveMedia"
          @cancel="load"
        />
      </section>

      <p v-if="saving" class="site-settings-admin__state">{{ t('admin.saving') }}</p>
    </div>
  </div>
</template>

<style scoped>
.site-settings-admin {
  display: grid;
  gap: 16px;
  max-width: 720px;
}

.site-settings-admin__section {
  padding: 16px 18px;
  border: 1px solid #e8e8e8;
  border-radius: 12px;
  background: #fff;
}

.site-settings-admin__section-title {
  margin: 0 0 14px;
  font-size: 16px;
  font-weight: 700;
  color: #111;
}

.site-settings-admin__state {
  margin: 0;
  color: #666;
}

.site-settings-admin__state--error {
  color: #c62828;
}

.site-settings-admin__saved {
  margin: 0;
  font-size: 14px;
  font-weight: 600;
  color: #2e7d32;
}
</style>
