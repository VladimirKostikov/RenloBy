<script setup lang="ts">
import { computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import {
  ACCOUNT_CABINETS,
  isAccountNavActive,
  resolveAccountCabinet,
  type AccountCabinetKey,
} from '@/modules/account/lib/accountNav'
import ThemeIcon from '@/components/ThemeIcon.vue'
import { useNotificationsStore } from '@/stores/notifications'

const route = useRoute()
const router = useRouter()
const { t } = useI18n()
const notifications = useNotificationsStore()

watch(
  () => route.path,
  () => {
    void notifications.loadUnreadCount()
  },
  { immediate: true },
)

const activeCabinet = computed(() => resolveAccountCabinet(route.path))

const cabinetOptions = computed(() =>
  ACCOUNT_CABINETS.map((cabinet) => ({
    ...cabinet,
    label: t(cabinet.titleKey),
  })),
)

const navItems = computed(() => {
  const cabinet = ACCOUNT_CABINETS.find((entry) => entry.key === activeCabinet.value)
  if (!cabinet) {
    return []
  }

  return cabinet.items.map((item) => ({
    ...item,
    label: t(item.labelKey),
    active: isAccountNavActive(item, route.path),
    showBadge: item.key === 'notifications' && notifications.hasUnread,
  }))
})

function switchCabinet(key: AccountCabinetKey) {
  if (activeCabinet.value === key) {
    return
  }

  const cabinet = ACCOUNT_CABINETS.find((entry) => entry.key === key)
  if (!cabinet) {
    return
  }

  void router.push(cabinet.defaultPath)
}
</script>

<template>
  <aside class="account-sidebar">
    <div class="account-sidebar__switcher" role="group" :aria-label="t('account.sections.switcher')">
      <button
        v-for="cabinet in cabinetOptions"
        :key="cabinet.key"
        type="button"
        class="account-sidebar__switch-btn"
        :class="{ 'account-sidebar__switch-btn--active': activeCabinet === cabinet.key }"
        :aria-pressed="activeCabinet === cabinet.key"
        @click="switchCabinet(cabinet.key)"
      >
        <ThemeIcon
          :src="cabinet.iconSrc"
          image-class="account-sidebar__switch-icon"
          :width="16"
          :height="16"
        />
        <span>{{ cabinet.label }}</span>
      </button>
    </div>

    <nav class="account-sidebar__nav">
      <RouterLink
        v-for="item in navItems"
        :key="item.key"
        :to="item.to"
        class="account-sidebar__link"
        :class="{ 'account-sidebar__link--active': item.active }"
      >
        <span class="account-sidebar__link-bar" aria-hidden="true" />
        <span class="account-sidebar__link-icon-wrap">
          <ThemeIcon
            :src="item.iconSrc"
            image-class="account-sidebar__link-icon"
            :width="20"
            :height="20"
          />
          <span v-if="item.showBadge" class="account-sidebar__notify-dot" aria-hidden="true" />
        </span>
        <span>{{ item.label }}</span>
      </RouterLink>
    </nav>
  </aside>
</template>

<style scoped>
.account-sidebar {
  display: flex;
  flex-direction: column;
  gap: 16px;
  height: 100%;
  min-height: 100%;
  box-sizing: border-box;
  padding: 20px;
  border-radius: var(--figma-radius-chip);
  background: var(--color-bg-elevated);
  border: 1px solid var(--figma-border);
}

.account-sidebar__switcher {
  display: flex;
  gap: 0;
  padding: 3px;
  border: 1px solid var(--figma-border);
  border-radius: var(--figma-radius-btn);
  background: var(--figma-surface);
}

.account-sidebar__switch-btn {
  flex: 1;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  min-height: 34px;
  padding: 0 10px;
  border: none;
  border-radius: calc(var(--figma-radius-btn) - 2px);
  background: transparent;
  color: var(--color-text-muted);
  font-size: 13px;
  font-weight: 600;
  line-height: 1.2;
  cursor: pointer;
  transition:
    background-color 0.2s ease,
    color 0.2s ease;
}

.account-sidebar__switch-btn:hover {
  color: var(--color-text);
}

.account-sidebar__switch-btn--active {
  background: var(--figma-accent);
  color: var(--figma-on-accent);
}

.account-sidebar__switch-btn--active:hover {
  background: var(--figma-accent-hover);
  color: var(--figma-on-accent);
}

.account-sidebar__switch-icon {
  width: 16px;
  height: 16px;
  flex-shrink: 0;
}

.account-sidebar__nav {
  display: flex;
  flex-direction: column;
  flex: 1 1 auto;
  gap: 8px;
}

.account-sidebar__link {
  position: relative;
  display: flex;
  align-items: center;
  min-height: 35px;
  padding: 8px 16px 8px 55px;
  color: var(--color-text);
  font-size: 14px;
  font-weight: 600;
  text-decoration: none;
  border-radius: 4px;
  transition:
    background-color 0.2s ease,
    color 0.2s ease;
}

.account-sidebar__link:hover {
  background: rgba(225, 69, 84, 0.06);
}

.account-sidebar__link--active {
  background: rgba(225, 69, 84, 0.08);
  color: var(--figma-accent);
}

.account-sidebar__link-bar {
  position: absolute;
  left: 0;
  top: 0;
  width: 6px;
  height: 100%;
  border-radius: 3px 0 0 3px;
  background: transparent;
  transition: background-color 0.2s ease;
}

.account-sidebar__link--active .account-sidebar__link-bar {
  background: var(--figma-accent);
}

.account-sidebar__link-icon-wrap {
  position: absolute;
  left: 18px;
  top: 50%;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 20px;
  height: 20px;
  transform: translateY(-50%);
}

.account-sidebar__link-icon {
  width: 20px;
  height: 20px;
  object-fit: contain;
  flex-shrink: 0;
  opacity: 0.72;
  transition: opacity 0.2s ease;
}

.account-sidebar__notify-dot {
  position: absolute;
  top: -2px;
  right: -3px;
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: var(--figma-accent);
  box-shadow: 0 0 0 2px var(--figma-surface);
}

.account-sidebar__link--active .account-sidebar__link-icon {
  opacity: 1;
}

@media (max-width: 1279px) {
  .account-sidebar {
    padding: 14px;
    gap: 12px;
  }

  .account-sidebar__nav {
    flex-direction: row;
    flex-wrap: nowrap;
    gap: 6px;
    overflow-x: auto;
    overflow-y: hidden;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
    padding-bottom: 2px;
  }

  .account-sidebar__nav::-webkit-scrollbar {
    display: none;
  }

  .account-sidebar__link {
    flex: 0 0 auto;
    min-height: var(--touch-target-min);
    padding: 8px 14px 8px 44px;
    white-space: nowrap;
  }

  .account-sidebar__link-bar {
    border-radius: 3px;
  }

  .account-sidebar__link-icon-wrap {
    left: 12px;
  }
}

@media (max-width: 767px) {
  .account-sidebar__switch-btn {
    min-height: var(--touch-target-min);
    font-size: 12px;
    padding: 0 8px;
  }

  .account-sidebar__switch-icon {
    display: none;
  }
}
</style>
