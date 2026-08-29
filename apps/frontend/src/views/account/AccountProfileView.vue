<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import { updateProfile, uploadProfilePhoto } from '@/api/account'
import ListingSellerCard from '@/components/ListingSellerCard.vue'
import SocialBrandIcon from '@/components/SocialBrandIcon.vue'
import { pickFirstAcceptedImage } from '@/lib/profilePhotoFile'
import { isSellerProfileComplete } from '@/lib/sellerProfileGate'
import { resolveApiError } from '@/lib/resolveApiError'
import { useAuthStore } from '@/stores/auth'
import type { ListingSellerDto } from '@/types'

const { t } = useI18n()
const auth = useAuthStore()
const router = useRouter()
const route = useRoute()

const lastName = ref('')
const firstName = ref('')
const patronymic = ref('')
const phone = ref('')
const instagram = ref('')
const telegram = ref('')
const whatsapp = ref('')
const viber = ref('')
const photo = ref<string | null>(null)
const photoInput = ref<HTMLInputElement | null>(null)
const uploadingPhoto = ref(false)
const dragActive = ref(false)
const saving = ref(false)
const saved = ref(false)
const error = ref(false)
const saveErrorMessage = ref('')
const previewOpen = ref(false)
let dragDepth = 0

const avatarLetter = computed(() => {
  const source = firstName.value.trim() || lastName.value.trim() || '?'
  return source.slice(0, 1)
})

const previewSeller = computed((): ListingSellerDto => {
  const name = [lastName.value, firstName.value, patronymic.value]
    .map((part) => part.trim())
    .filter(Boolean)
    .join(' ')

  return {
    id: auth.user?.id ?? 0,
    name: name || (auth.user?.email ?? t('account.profile.title')),
    photo: photo.value || null,
    phone: phone.value.trim() || null,
    instagram: instagram.value.trim() || null,
    telegram: telegram.value.trim() || null,
    whatsapp: whatsapp.value.trim() || null,
    viber: viber.value.trim() || null,
  }
})

onMounted(() => {
  syncFromAuth()
})

function syncFromAuth() {
  lastName.value = auth.user?.lastName ?? ''
  firstName.value = auth.user?.firstName ?? ''
  patronymic.value = auth.user?.patronymic ?? ''
  phone.value = auth.user?.phone ?? ''
  instagram.value = auth.user?.instagram ?? ''
  telegram.value = auth.user?.telegram ?? ''
  whatsapp.value = auth.user?.whatsapp ?? ''
  viber.value = auth.user?.viber ?? ''
  photo.value = auth.user?.photo ?? null
}

function openPreview() {
  previewOpen.value = true
}

function closePreview() {
  previewOpen.value = false
}

async function saveProfile() {
  saving.value = true
  saved.value = false
  error.value = false
  saveErrorMessage.value = ''
  try {
    auth.user = await updateProfile({
      lastName: lastName.value.trim(),
      firstName: firstName.value.trim(),
      patronymic: patronymic.value.trim(),
      phone: phone.value.trim(),
      photo: photo.value,
      instagram: instagram.value.trim(),
      telegram: telegram.value.trim(),
      whatsapp: whatsapp.value.trim(),
      viber: viber.value.trim(),
    })
    syncFromAuth()
    saved.value = true
    const next = typeof route.query.next === 'string' ? route.query.next : ''
    if (next === '/account/seller/create' && isSellerProfileComplete(auth.user)) {
      await router.push(next)
    }
  } catch (err) {
    error.value = true
    saved.value = false
    saveErrorMessage.value = resolveApiError(err, t, 'account.profile.saveError').message
  } finally {
    saving.value = false
  }
}

async function uploadPhotoFile(file: File | null) {
  if (!file || uploadingPhoto.value) {
    return
  }

  uploadingPhoto.value = true
  error.value = false
  saveErrorMessage.value = ''
  try {
    auth.user = await uploadProfilePhoto(file)
    syncFromAuth()
    saved.value = true
  } catch (err) {
    error.value = true
    saveErrorMessage.value = resolveApiError(err, t, 'account.profile.photoError').message
  } finally {
    uploadingPhoto.value = false
    if (photoInput.value) {
      photoInput.value.value = ''
    }
  }
}

async function onPhotoSelected(event: Event) {
  const input = event.target as HTMLInputElement
  const file = pickFirstAcceptedImage(input.files)
  if (!file) {
    if (input.files?.length) {
      error.value = true
      saveErrorMessage.value = t('account.profile.photoInvalid')
    }
    input.value = ''
    return
  }
  await uploadPhotoFile(file)
}

function openPhotoPicker() {
  if (uploadingPhoto.value) {
    return
  }
  photoInput.value?.click()
}

function onDragEnter(event: DragEvent) {
  event.preventDefault()
  dragDepth += 1
  dragActive.value = true
}

function onDragOver(event: DragEvent) {
  event.preventDefault()
  if (event.dataTransfer) {
    event.dataTransfer.dropEffect = 'copy'
  }
  dragActive.value = true
}

function onDragLeave(event: DragEvent) {
  event.preventDefault()
  dragDepth = Math.max(0, dragDepth - 1)
  if (dragDepth === 0) {
    dragActive.value = false
  }
}

async function onDrop(event: DragEvent) {
  event.preventDefault()
  dragDepth = 0
  dragActive.value = false
  if (uploadingPhoto.value) {
    return
  }

  const file = pickFirstAcceptedImage(event.dataTransfer?.files)
  if (!file) {
    error.value = true
    saveErrorMessage.value = t('account.profile.photoInvalid')
    return
  }
  await uploadPhotoFile(file)
}

function removePhoto() {
  photo.value = ''
}

async function logout() {
  await auth.logout()
  await router.push('/')
}
</script>

<template>
  <div class="account-profile">
    <header class="account-profile__header">
      <div>
        <h1 class="account-profile__title">{{ t('account.profile.title') }}</h1>
        <p class="account-profile__subtitle">{{ t('account.profile.subtitle') }}</p>
      </div>
      <div class="account-profile__header-actions">
        <button type="button" class="account-profile__logout" @click="logout">
          {{ t('nav.logout') }}
        </button>
      </div>
    </header>

    <form class="account-profile__form" @submit.prevent="saveProfile">
      <div class="account-profile__layout">
        <section class="account-profile__panel account-profile__panel--photo">
          <h2 class="account-profile__panel-title">{{ t('account.profile.uploadPhoto') }}</h2>
          <div class="account-profile__photo-block">
            <button
              type="button"
              class="account-profile__dropzone"
              :class="{
                'account-profile__dropzone--active': dragActive,
                'account-profile__dropzone--busy': uploadingPhoto,
              }"
              :disabled="uploadingPhoto"
              :aria-label="t('account.profile.uploadPhoto')"
              @click="openPhotoPicker"
              @dragenter="onDragEnter"
              @dragover="onDragOver"
              @dragleave="onDragLeave"
              @drop="onDrop"
            >
              <div class="account-profile__avatar" aria-hidden="true">
                <img v-if="photo" :src="photo" alt="" class="account-profile__avatar-img" />
                <span v-else class="account-profile__avatar-fallback">{{ avatarLetter }}</span>
              </div>
              <div class="account-profile__dropzone-copy">
                <span class="account-profile__dropzone-title">
                  {{ uploadingPhoto ? t('listing.loading') : t('account.profile.uploadPhoto') }}
                </span>
                <span class="account-profile__dropzone-hint">
                  {{ t('account.profile.photoDropHint') }}
                </span>
              </div>
            </button>

            <button
              v-if="photo"
              type="button"
              class="account-profile__photo-btn account-profile__photo-btn--ghost"
              :disabled="uploadingPhoto"
              @click="removePhoto"
            >
              {{ t('account.profile.removePhoto') }}
            </button>

            <input
              ref="photoInput"
              type="file"
              class="account-profile__file"
              accept="image/jpeg,image/png,image/webp,image/gif"
              @change="onPhotoSelected"
            />
          </div>
        </section>

        <section class="account-profile__panel account-profile__panel--main">
          <h2 class="account-profile__panel-title">{{ t('account.profile.name') }}</h2>
          <div class="account-profile__fio">
            <label class="account-profile__field">
              <span class="account-profile__label">{{ t('account.profile.lastName') }}</span>
              <input v-model="lastName" type="text" class="account-profile__input" maxlength="80" autocomplete="family-name" />
            </label>
            <label class="account-profile__field">
              <span class="account-profile__label">{{ t('account.profile.firstName') }}</span>
              <input v-model="firstName" type="text" class="account-profile__input" maxlength="80" autocomplete="given-name" />
            </label>
            <label class="account-profile__field">
              <span class="account-profile__label">{{ t('account.profile.patronymic') }}</span>
              <input v-model="patronymic" type="text" class="account-profile__input" maxlength="80" autocomplete="additional-name" />
            </label>
          </div>

          <div class="account-profile__contacts">
            <label class="account-profile__field">
              <span class="account-profile__label">{{ t('account.profile.email') }}</span>
              <input :value="auth.user?.email" type="email" class="account-profile__input" disabled />
            </label>

            <label class="account-profile__field">
              <span class="account-profile__label">{{ t('account.profile.phone') }}</span>
              <input
                v-model="phone"
                type="tel"
                class="account-profile__input"
                maxlength="32"
                :placeholder="t('account.profile.phonePlaceholder')"
              />
            </label>
          </div>
        </section>
      </div>

      <fieldset class="account-profile__panel account-profile__socials">
        <legend class="account-profile__legend">{{ t('account.profile.socials') }}</legend>
        <div class="account-profile__socials-grid">
          <label class="account-profile__field">
            <span class="account-profile__label account-profile__label--social">
              <span class="account-profile__social-icon account-profile__social-icon--instagram" aria-hidden="true">
                <SocialBrandIcon name="instagram" :size="14" />
              </span>
              {{ t('account.profile.instagram') }}
            </span>
            <input v-model="instagram" type="text" class="account-profile__input" maxlength="120" placeholder="@username" />
          </label>
          <label class="account-profile__field">
            <span class="account-profile__label account-profile__label--social">
              <span class="account-profile__social-icon account-profile__social-icon--telegram" aria-hidden="true">
                <SocialBrandIcon name="telegram" :size="14" />
              </span>
              {{ t('account.profile.telegram') }}
            </span>
            <input v-model="telegram" type="text" class="account-profile__input" maxlength="120" placeholder="@username" />
          </label>
          <label class="account-profile__field">
            <span class="account-profile__label account-profile__label--social">
              <span class="account-profile__social-icon account-profile__social-icon--whatsapp" aria-hidden="true">
                <SocialBrandIcon name="whatsapp" :size="14" />
              </span>
              {{ t('account.profile.whatsapp') }}
            </span>
            <input
              v-model="whatsapp"
              type="text"
              class="account-profile__input"
              maxlength="120"
              :placeholder="t('account.profile.phonePlaceholder')"
            />
          </label>
          <label class="account-profile__field">
            <span class="account-profile__label account-profile__label--social">
              <span class="account-profile__social-icon account-profile__social-icon--viber" aria-hidden="true">
                <SocialBrandIcon name="viber" :size="14" />
              </span>
              {{ t('account.profile.viber') }}
            </span>
            <input
              v-model="viber"
              type="text"
              class="account-profile__input"
              maxlength="120"
              :placeholder="t('account.profile.phonePlaceholder')"
            />
          </label>
        </div>
      </fieldset>

      <p v-if="saved" class="account-profile__message account-profile__message--success" role="status">
        {{ t('account.profile.saved') }}
      </p>
      <p v-if="error" class="account-profile__message account-profile__message--error" role="alert">
        {{ saveErrorMessage || t('account.profile.saveError') }}
      </p>

      <div class="account-profile__actions">
        <button type="button" class="account-profile__preview-btn" data-testid="profile-preview" @click="openPreview">
          {{ t('account.profile.previewCard') }}
        </button>
        <button type="submit" class="account-profile__submit" :disabled="saving || uploadingPhoto">
          {{ saving ? t('listing.loading') : t('account.profile.save') }}
        </button>
      </div>
    </form>

    <Teleport to="body">
      <div
        v-if="previewOpen"
        class="account-profile__preview-overlay"
        data-testid="profile-preview-modal"
        @click.self="closePreview"
      >
        <div
          class="account-profile__preview-dialog"
          role="dialog"
          aria-modal="true"
          :aria-label="t('account.profile.previewCardTitle')"
        >
          <div class="account-profile__preview-head">
            <h2 class="account-profile__preview-title">{{ t('account.profile.previewCardTitle') }}</h2>
            <button
              type="button"
              class="account-profile__preview-close"
              :aria-label="t('account.profile.previewClose')"
              @click="closePreview"
            >
              ×
            </button>
          </div>
          <p class="account-profile__preview-hint">{{ t('account.profile.previewCardHint') }}</p>
          <ListingSellerCard :seller="previewSeller" :from-owner="true" :interactive="false" />
        </div>
      </div>
    </Teleport>
  </div>
</template>

<style scoped>
.account-profile {
  display: flex;
  flex-direction: column;
  gap: 20px;
  width: 100%;
  min-width: 0;
}

.account-profile__header {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px 16px;
}

.account-profile__title {
  margin: 0 0 8px;
  font-size: 28px;
  font-weight: 700;
  line-height: 1.2;
}

.account-profile__subtitle {
  margin: 0;
  font-size: 15px;
  color: rgba(0, 0, 0, 0.72);
  line-height: 1.4;
}

.account-profile__header-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.account-profile__form {
  display: flex;
  flex-direction: column;
  gap: 16px;
  width: 100%;
  max-width: 860px;
  min-width: 0;
}

.account-profile__layout {
  display: grid;
  grid-template-columns: 1fr;
  gap: 16px;
  width: 100%;
  min-width: 0;
}

.account-profile__panel {
  display: flex;
  flex-direction: column;
  gap: 16px;
  min-width: 0;
  padding: 18px;
  border: 1px solid var(--figma-border);
  border-radius: 14px;
  background: #fff;
}

.account-profile__panel-title {
  margin: 0;
  font-size: 15px;
  font-weight: 700;
  line-height: 1.3;
}

.account-profile__fio,
.account-profile__contacts,
.account-profile__socials-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 14px 16px;
  width: 100%;
  min-width: 0;
}

.account-profile__photo-block {
  display: flex;
  flex-direction: column;
  gap: 14px;
  width: 100%;
  min-width: 0;
}

.account-profile__dropzone {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 14px;
  width: 100%;
  min-height: 220px;
  padding: 24px 20px;
  border: 1px dashed var(--figma-border);
  border-radius: 12px;
  background: color-mix(in srgb, var(--figma-page-bg, #f5f5f5) 70%, #fff);
  color: inherit;
  text-align: center;
  cursor: pointer;
  transition:
    border-color 0.2s ease,
    background-color 0.2s ease,
    box-shadow 0.2s ease;
}

.account-profile__dropzone:hover,
.account-profile__dropzone:focus-visible {
  outline: none;
  border-color: color-mix(in srgb, var(--figma-accent) 55%, var(--figma-border));
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--figma-accent) 12%, transparent);
}

.account-profile__dropzone--active {
  border-color: var(--figma-accent);
  border-style: solid;
  background: color-mix(in srgb, var(--figma-accent) 8%, #fff);
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--figma-accent) 14%, transparent);
}

.account-profile__dropzone--busy,
.account-profile__dropzone:disabled {
  opacity: 0.75;
  cursor: default;
}

.account-profile__avatar {
  width: 104px;
  height: 104px;
  border-radius: 50%;
  overflow: hidden;
  border: 1px solid var(--figma-border);
  background: color-mix(in srgb, var(--figma-accent) 10%, #fff);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.account-profile__avatar-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.account-profile__avatar-fallback {
  font-size: 32px;
  font-weight: 700;
  color: var(--figma-accent);
  text-transform: uppercase;
}

.account-profile__dropzone-copy {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  width: 100%;
  max-width: 220px;
  min-width: 0;
}

.account-profile__dropzone-title {
  font-size: 15px;
  font-weight: 700;
  color: var(--color-text, #000);
}

.account-profile__dropzone-hint {
  font-size: 13px;
  line-height: 1.45;
  color: var(--color-text-muted, rgba(0, 0, 0, 0.65));
}

.account-profile__photo-btn {
  align-self: stretch;
  min-height: 44px;
  min-width: 44px;
  padding: 0 14px;
  border: none;
  border-radius: 8px;
  background: var(--figma-accent);
  color: #fff;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
}

.account-profile__photo-btn--ghost {
  background: #fff;
  border: 1px solid var(--figma-border);
  color: var(--color-text, #000);
}

.account-profile__photo-btn:disabled {
  opacity: 0.7;
  cursor: default;
}

.account-profile__file {
  display: none;
}

.account-profile__socials {
  margin: 0;
  min-inline-size: 0;
}

.account-profile__legend {
  padding: 0 4px;
  font-size: 15px;
  font-weight: 700;
}

.account-profile__field {
  display: flex;
  flex-direction: column;
  gap: 8px;
  min-width: 0;
}

.account-profile__label {
  font-size: 13px;
  font-weight: 600;
  color: rgba(0, 0, 0, 0.78);
}

.account-profile__label--social {
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.account-profile__social-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  border-radius: 7px;
  color: #fff;
  flex-shrink: 0;
}

.account-profile__social-icon--instagram {
  background: linear-gradient(135deg, #f58529 0%, #dd2a7b 50%, #8134af 100%);
}

.account-profile__social-icon--telegram {
  background: #2aabee;
}

.account-profile__social-icon--whatsapp {
  background: #25d366;
}

.account-profile__social-icon--viber {
  background: #7360f2;
}

.account-profile__input {
  box-sizing: border-box;
  width: 100%;
  height: 48px;
  padding: 0 14px;
  border: 1px solid var(--figma-border);
  border-radius: var(--figma-radius-chip);
  background: var(--color-bg-elevated, #fff);
  color: var(--color-text, #000);
  font-family: inherit;
  font-size: 14px;
  transition:
    border-color 0.2s ease,
    box-shadow 0.2s ease;
}

.account-profile__input:focus {
  outline: none;
  border-color: var(--figma-accent);
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--figma-accent) 16%, transparent);
}

.account-profile__input:disabled {
  background: color-mix(in srgb, var(--color-text, #000) 4%, transparent);
  color: var(--color-text-muted, rgba(0, 0, 0, 0.55));
}

.account-profile__message {
  margin: 0;
  font-size: 14px;
}

.account-profile__message--success {
  color: #04832a;
}

.account-profile__message--error {
  color: var(--figma-accent);
}

.account-profile__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  margin-top: 4px;
}

.account-profile__submit,
.account-profile__logout,
.account-profile__preview-btn {
  min-height: 44px;
  padding: 0 20px;
  border-radius: var(--radius-md, 8px);
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
}

.account-profile__preview-btn {
  border: 1px solid var(--figma-border);
  background: #fff;
  color: var(--color-text, #000);
}

.account-profile__submit {
  border: none;
  background: var(--figma-accent);
  color: #fff;
}

.account-profile__submit:disabled {
  opacity: 0.7;
  cursor: default;
}

.account-profile__logout {
  border: 1px solid var(--figma-border);
  background: #fff;
  color: #000;
}

.account-profile__preview-overlay {
  position: fixed;
  inset: 0;
  z-index: 3000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
  overflow-y: auto;
  background: rgba(0, 0, 0, 0.5);
}

.account-profile__preview-dialog {
  width: min(100%, 420px);
  padding: 18px;
  border-radius: 16px;
  background: #fff;
  box-shadow: 0 16px 40px rgba(15, 23, 42, 0.18);
}

.account-profile__preview-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 8px;
}

.account-profile__preview-title {
  margin: 0;
  font-size: 18px;
  font-weight: 700;
  line-height: 1.3;
}

.account-profile__preview-close {
  flex-shrink: 0;
  width: 36px;
  height: 36px;
  border: none;
  border-radius: 8px;
  background: #f3f4f6;
  color: #111;
  font-size: 22px;
  line-height: 1;
  cursor: pointer;
}

.account-profile__preview-hint {
  margin: 0 0 12px;
  color: var(--color-text-muted, #6b7280);
  font-size: 13px;
  line-height: 1.4;
}

@media (min-width: 720px) {
  .account-profile__fio {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }

  .account-profile__contacts {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .account-profile__socials-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (min-width: 1024px) {
  .account-profile__layout {
    grid-template-columns: 300px minmax(0, 1fr);
    align-items: stretch;
    gap: 20px;
  }

  .account-profile__panel--photo,
  .account-profile__panel--main {
    height: 100%;
  }

  .account-profile__panel--photo {
    padding: 20px;
  }

  .account-profile__panel--photo .account-profile__photo-block {
    flex: 1;
    min-height: 0;
  }

  .account-profile__panel--photo .account-profile__dropzone {
    flex: 1;
    min-height: 220px;
  }

  .account-profile__socials-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .account-profile__actions {
    justify-content: flex-end;
  }
}

@media (max-width: 767px) {
  .account-profile__fio {
    grid-template-columns: 1fr;
  }
  .account-profile__grid {
    grid-template-columns: 1fr;
  }
  .account-profile__socials {
    grid-template-columns: 1fr;
  }
  .account-profile__actions {
    flex-direction: column;
    align-items: stretch;
  }
  .account-profile__actions .account-profile__btn {
    width: 100%;
  }
}

</style>
