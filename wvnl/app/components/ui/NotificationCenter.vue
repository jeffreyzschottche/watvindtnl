<template>
  <ClientOnly>
    <Teleport to="body">
      <div class="notification-center" role="status" aria-live="polite">
        <transition-group name="notification" tag="ul" class="notification-list">
          <li
            v-for="notification in notifications"
            :key="notification.id"
            class="notification"
            :class="`notification--${notification.type}`"
          >
            <span class="notification__badge" aria-hidden="true"></span>
            <p class="notification__message">{{ notification.message }}</p>
            <button
              type="button"
              class="notification__close"
              aria-label="Sluit melding"
              @click="remove(notification.id)"
            >
              ×
            </button>
          </li>
        </transition-group>
      </div>
    </Teleport>
  </ClientOnly>
</template>

<script setup lang="ts">
import { useNotifications } from "~/composables/useNotifications";

const { notifications, removeNotification } = useNotifications();

function remove(id: number) {
  removeNotification(id);
}
</script>

<style scoped>
.notification-center {
  position: fixed;
  inset: 0;
  z-index: 9999;
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  justify-content: flex-start;
  padding: 1.5rem;
  pointer-events: none;
}

.notification-list {
  display: grid;
  gap: 0.75rem;
}

.notification-enter-active,
.notification-leave-active {
  transition: all 0.2s ease;
}

.notification-enter-from,
.notification-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

.notification {
  position: relative;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  min-width: min(360px, calc(100vw - 2rem));
  padding: 0.9rem 1rem;
  border-radius: 0.9rem;
  background: #1f2937;
  color: #ffffff;
  box-shadow: 0 10px 35px rgba(15, 23, 42, 0.35);
  pointer-events: auto;
  list-style: none;
  overflow: hidden;
}

.notification__badge {
  width: 0.75rem;
  height: 0.75rem;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.85);
  box-shadow: 0 0 0 4px rgba(255, 255, 255, 0.25);
}

.notification--success {
  background: linear-gradient(135deg, rgba(34, 197, 94, 0.92), rgba(21, 128, 61, 0.92));
}

.notification--error {
  background: linear-gradient(135deg, rgba(239, 68, 68, 0.92), rgba(185, 28, 28, 0.92));
}

.notification--info {
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.92), rgba(37, 99, 235, 0.92));
}

.notification--success .notification__badge {
  background: #22c55e;
}

.notification--error .notification__badge {
  background: #ef4444;
}

.notification--info .notification__badge {
  background: #3b82f6;
}

.notification__message {
  flex: 1;
  margin: 0;
  font-weight: 500;
}

.notification__close {
  flex-shrink: 0;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 1.5rem;
  height: 1.5rem;
  border: none;
  border-radius: 999px;
  background: rgba(15, 23, 42, 0.2);
  color: inherit;
  font-size: 1.1rem;
  cursor: pointer;
}

.notification__close:hover {
  background: rgba(15, 23, 42, 0.35);
}

@media (prefers-reduced-motion: reduce) {
  .notification-enter-active,
  .notification-leave-active {
    transition-duration: 0.01ms;
  }
}
</style>
