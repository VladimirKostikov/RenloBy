<script lang="ts">
export const REPORT_COMMENT_MIN_LENGTH = 30
</script>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { createListingReport } from '@/api/listingReports'
import { LISTING_NESTED_MODAL_Z_INDEX } from '@/lib/listingModalZIndex'

defineOptions({ name: 'ListingReportModal' })

const props = withDefaults(
  defineProps<{
    open?: boolean
    listingId: number
  }>(),
  {
    open: true,
  },
)

const emit = defineEmits<{
  close: []
}>()

const { t } = useI18n()
const reason = ref('spam')
const comment = ref('')
const sent = ref(false)
const submitting = ref(false)
const error = ref(false)
const validationError = ref(false)

const reasons = [
  { value: 'spam', labelKey: 'listingDetail.reportSpam' },
  { value: 'wrong', labelKey: 'listingDetail.reportWrong' },
  { value: 'fraud', labelKey: 'listingDetail.reportFraud' },
  { value: 'other', labelKey: 'listingDetail.reportOther' },
]

const trimmedComment = computed(() => comment.value.trim())
const commentLength = computed(() => trimmedComment.value.length)
const commentValid = computed(() => commentLength.value >= REPORT_COMMENT_MIN_LENGTH)
const canSubmit = computed(() => commentValid.value && !submitting.value)

async function submit() {
  if (submitting.value) {
    return
  }
  if (!commentValid.value) {
    validationError.value = true
    return
  }

  submitting.value = true
  error.value = false
  validationError.value = false
  try {
    await createListingReport(props.listingId, {
      reason: reason.value,
      comment: trimmedComment.value,
    })
    sent.value = true
  } catch {
    error.value = true
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <Teleport to="body">
    <Transition name="listing-report-modal">
      <div
        v-if="open"
        class="listing-report-modal"
        role="dialog"
        aria-modal="true"
        :style="{ zIndex: LISTING_NESTED_MODAL_Z_INDEX }"
        @click.self="emit('close')"
      >
        <div class="listing-report-modal__card">
          <div class="listing-report-modal__head">
            <h2>{{ t('listingDetail.reportTitle') }}</h2>
            <button
              type="button"
              class="listing-report-modal__close"
              :aria-label="t('listingDetail.close')"
              @click="emit('close')"
            >
              ×
            </button>
          </div>

          <template v-if="!sent">
            <div class="listing-report-modal__reasons">
              <label v-for="item in reasons" :key="item.value" class="listing-report-modal__reason">
                <input v-model="reason" type="radio" :value="item.value" />
                <span>{{ t(item.labelKey) }}</span>
              </label>
            </div>
            <label class="listing-report-modal__comment-label" for="listing-report-comment">
              {{ t('listingDetail.reportComment') }}
            </label>
            <textarea
              id="listing-report-comment"
              v-model="comment"
              class="listing-report-modal__comment"
              rows="4"
              :minlength="REPORT_COMMENT_MIN_LENGTH"
              :placeholder="t('listingDetail.reportCommentPlaceholder')"
              @input="validationError = false"
            />
            <p
              class="listing-report-modal__counter"
              :class="{ 'listing-report-modal__counter--invalid': !commentValid }"
            >
              {{ t('listingDetail.reportCommentCounter', { n: commentLength, min: REPORT_COMMENT_MIN_LENGTH }) }}
            </p>
            <p v-if="validationError" class="listing-report-modal__error">
              {{ t('listingDetail.reportCommentTooShort', { min: REPORT_COMMENT_MIN_LENGTH }) }}
            </p>
            <p v-else-if="error" class="listing-report-modal__error">{{ t('listingDetail.reportError') }}</p>
            <button
              type="button"
              class="listing-report-modal__submit"
              :disabled="!canSubmit"
              @click="submit"
            >
              {{ submitting ? t('listingDetail.reportSending') : t('listingDetail.reportSubmit') }}
            </button>
          </template>
          <p v-else class="listing-report-modal__done">{{ t('listingDetail.reportThanks') }}</p>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.listing-report-modal {
  position: fixed;
  inset: 0;
  z-index: 2400;
  display: grid;
  place-items: center;
  padding: 16px;
  background: rgba(0, 0, 0, 0.45);
}

.listing-report-modal__card {
  width: min(420px, 100%);
  padding: 20px;
  border-radius: 16px;
  background: var(--figma-surface);
}

.listing-report-modal__head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 14px;
}

.listing-report-modal__head h2 {
  margin: 0;
  font-size: 18px;
  font-weight: 700;
}

.listing-report-modal__close {
  width: 32px;
  height: 32px;
  border: none;
  border-radius: 50%;
  background: rgba(146, 146, 146, 0.12);
  font-size: 20px;
  cursor: pointer;
}

.listing-report-modal__reasons {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-bottom: 12px;
}

.listing-report-modal__reason {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  cursor: pointer;
}

.listing-report-modal__comment-label {
  display: block;
  margin-bottom: 6px;
  font-size: 13px;
  font-weight: 600;
}

.listing-report-modal__comment {
  width: 100%;
  margin-bottom: 6px;
  padding: 10px 12px;
  border: 1px solid var(--figma-border);
  border-radius: 12px;
  resize: vertical;
  font: inherit;
}

.listing-report-modal__counter {
  margin: 0 0 12px;
  font-size: 12px;
  color: var(--figma-text-muted);
}

.listing-report-modal__counter--invalid {
  color: #c62828;
}

.listing-report-modal__submit {
  width: 100%;
  height: 44px;
  border: none;
  border-radius: 12px;
  background: var(--figma-accent);
  color: var(--figma-on-accent);
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
}

.listing-report-modal__submit:disabled {
  opacity: 0.7;
  cursor: default;
}

.listing-report-modal__error {
  margin: 0 0 10px;
  font-size: 13px;
  color: #c62828;
}

.listing-report-modal__done {
  margin: 8px 0 0;
  font-size: 14px;
  font-weight: 600;
}
</style>

<style>
.listing-report-modal-enter-active,
.listing-report-modal-leave-active {
  transition: opacity 0.22s ease;
}

.listing-report-modal-enter-active .listing-report-modal__card,
.listing-report-modal-leave-active .listing-report-modal__card {
  transition:
    opacity 0.24s cubic-bezier(0.22, 1, 0.36, 1),
    transform 0.24s cubic-bezier(0.22, 1, 0.36, 1);
}

.listing-report-modal-enter-from,
.listing-report-modal-leave-to {
  opacity: 0;
}

.listing-report-modal-enter-from .listing-report-modal__card,
.listing-report-modal-leave-to .listing-report-modal__card {
  opacity: 0;
  transform: translateY(14px) scale(0.98);
}

@media (prefers-reduced-motion: reduce) {
  .listing-report-modal-enter-active,
  .listing-report-modal-leave-active,
  .listing-report-modal-enter-active .listing-report-modal__card,
  .listing-report-modal-leave-active .listing-report-modal__card {
    transition-duration: 0.01ms;
  }

  .listing-report-modal-enter-from .listing-report-modal__card,
  .listing-report-modal-leave-to .listing-report-modal__card {
    transform: none;
  }
}
</style>
