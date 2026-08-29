<script setup lang="ts">
import { ref, watch } from 'vue'
import ListingDetailPanel from '@/components/ListingDetailPanel.vue'
import type { ListingDto, MetroStationDto } from '@/types'

const props = defineProps<{
  listing: ListingDto
  metroStation?: MetroStationDto
  districtName?: string
  loading?: boolean
}>()

const emit = defineEmits<{
  close: []
  showOnMap: []
}>()

const visible = ref(true)

watch(
  () => props.listing.id,
  () => {
    visible.value = true
  },
)

function requestClose() {
  if (!visible.value) {
    return
  }
  visible.value = false
}

function onAfterLeave() {
  emit('close')
}

function onShowOnMap() {
  emit('showOnMap')
  requestClose()
}
</script>

<template>
  <Teleport to="body">
    <Transition name="listing-detail" appear @after-leave="onAfterLeave">
      <div
        v-if="visible"
        class="listing-detail-overlay"
        @click.self="requestClose"
      >
        <ListingDetailPanel
          v-bind="$props"
          @close="requestClose"
          @show-on-map="onShowOnMap"
          @click.stop
        />
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.listing-detail-overlay {
  position: fixed;
  inset: 0;
  z-index: 3000;
  display: flex;
  align-items: flex-start;
  justify-content: center;
  padding: 16px;
  overflow-y: auto;
  background: rgba(0, 0, 0, 0.5);
}

.listing-detail-enter-active,
.listing-detail-leave-active {
  transition: opacity 0.24s ease;
}

.listing-detail-enter-active :deep(.listing-detail-modal),
.listing-detail-leave-active :deep(.listing-detail-modal) {
  transition:
    opacity 0.28s cubic-bezier(0.22, 1, 0.36, 1),
    transform 0.28s cubic-bezier(0.22, 1, 0.36, 1);
  will-change: opacity, transform;
}

.listing-detail-enter-from,
.listing-detail-leave-to {
  opacity: 0;
}

.listing-detail-enter-from :deep(.listing-detail-modal),
.listing-detail-leave-to :deep(.listing-detail-modal) {
  opacity: 0;
  transform: translate3d(0, 16px, 0) scale(0.98);
}

.listing-detail-enter-to :deep(.listing-detail-modal),
.listing-detail-leave-from :deep(.listing-detail-modal) {
  opacity: 1;
  transform: translate3d(0, 0, 0) scale(1);
}

@media (max-width: 767px) {
  .listing-detail-overlay {
    padding: 0;
  }

  .listing-detail-enter-from :deep(.listing-detail-modal),
  .listing-detail-leave-to :deep(.listing-detail-modal) {
    transform: translate3d(0, 24px, 0);
  }
}

@media (prefers-reduced-motion: reduce) {
  .listing-detail-enter-active,
  .listing-detail-leave-active,
  .listing-detail-enter-active :deep(.listing-detail-modal),
  .listing-detail-leave-active :deep(.listing-detail-modal) {
    transition-duration: 0.01ms;
  }
}
</style>
