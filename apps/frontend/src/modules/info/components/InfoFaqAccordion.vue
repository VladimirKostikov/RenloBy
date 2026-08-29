<script setup lang="ts">
import { ref } from 'vue'
import type { InfoFaqItemDto } from '@/types/info'

defineProps<{
  items: InfoFaqItemDto[]
  title: string
}>()

const openIndex = ref(0)

function toggle(index: number) {
  openIndex.value = openIndex.value === index ? -1 : index
}
</script>

<template>
  <section v-if="items.length" class="info-faq">
    <h2 class="info-faq__title">{{ title }}</h2>

    <div class="info-faq__list">
      <div
        v-for="(item, index) in items"
        :key="`${index}-${item.question}`"
        class="info-faq__item"
        :class="{ 'info-faq__item--open': openIndex === index }"
      >
        <button
          type="button"
          class="info-faq__trigger"
          :aria-expanded="openIndex === index"
          @click="toggle(index)"
        >
          <span class="info-faq__question">{{ item.question }}</span>
          <img data-theme-ink
            src="/figma/accordion-chevron.svg"
            alt=""
            class="info-faq__chevron"
            width="22"
            height="13"
          />
        </button>

        <Transition name="info-faq-panel">
          <div v-if="openIndex === index" class="info-faq__answer-wrap">
            <p class="info-faq__answer">{{ item.answer }}</p>
          </div>
        </Transition>
      </div>
    </div>
  </section>
</template>

<style scoped>
.info-faq {
  padding-top: 28px;
  border-top: 1px solid var(--figma-border);
}

.info-faq__title {
  margin: 0 0 40px;
  font-size: 24px;
  font-weight: 600;
  line-height: 1.2;
}

.info-faq__list {
  display: flex;
  flex-direction: column;
  gap: 0;
}

.info-faq__item {
  border-bottom: 1px solid var(--figma-border);
}

.info-faq__item--open {
  background: rgba(0, 0, 0, 0.02);
}

.info-faq__trigger {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  width: 100%;
  padding: 22px 24px;
  border: none;
  background: transparent;
  text-align: left;
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.info-faq__trigger:hover {
  background: rgba(0, 0, 0, 0.03);
}

.info-faq__question {
  font-size: 16px;
  font-weight: 600;
  line-height: 1.35;
  color: var(--figma-ink);
}

.info-faq__chevron {
  flex-shrink: 0;
  transition: transform 0.2s ease;
}

.info-faq__item--open .info-faq__chevron {
  transform: rotate(180deg);
}

.info-faq__answer-wrap {
  padding: 0 24px 22px;
}

.info-faq__answer {
  margin: 0;
  max-width: 1043px;
  font-size: 14px;
  line-height: 1.5;
  color: rgba(0, 0, 0, 0.78);
}

.info-faq-panel-enter-active,
.info-faq-panel-leave-active {
  transition:
    opacity 0.2s ease,
    transform 0.2s ease;
}

.info-faq-panel-enter-from,
.info-faq-panel-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}

@media (prefers-reduced-motion: reduce) {
  .info-faq-panel-enter-active,
  .info-faq-panel-leave-active {
    transition-duration: 0.01ms;
  }
}
</style>
